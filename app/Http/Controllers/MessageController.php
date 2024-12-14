<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Events\MessageSent;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Send a message to another user.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:2048',
        ]);

        // Prevent sending messages to oneself
        if (Auth::id() === (int) $request->receiver_id) {
            return response()->json(['message' => 'You cannot send a message to yourself'], 403);
        }

        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('messages', 'public');
        }

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'file_path' => $filePath,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Message sent successfully!',
            'data' => $message,
        ]);
    }




    /**
     * Retrieve messages between the authenticated user and another user.
     */
    // public function getMessages(Request $request, $receiver_id)
    // {
    //     $messages = Message::where(function ($query) use ($receiver_id) {
    //             $query->where('sender_id', Auth::id())
    //                   ->where('receiver_id', $receiver_id);
    //         })
    //         ->orWhere(function ($query) use ($receiver_id) {
    //             $query->where('sender_id', $receiver_id)
    //                   ->where('receiver_id', Auth::id());
    //         })
    //         ->orderBy('created_at', 'asc')
    //         ->get();

    //     return response()->json($messages);
    // }

    public function getMessages(Request $request, $receiver_id)
    {
        // Ensure the authenticated user is part of the conversation
        $isSender = Message::where('sender_id', Auth::id())->where('receiver_id', $receiver_id)->exists();
        $isReceiver = Message::where('receiver_id', Auth::id())->where('sender_id', $receiver_id)->exists();

        if (!$isSender && !$isReceiver) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        // Retrieve messages with pagination
        $messages = Message::where(function ($query) use ($receiver_id) {
            $query->where('sender_id', Auth::id())
                ->where('receiver_id', $receiver_id);
        })
            ->orWhere(function ($query) use ($receiver_id) {
                $query->where('sender_id', $receiver_id)
                    ->where('receiver_id', Auth::id());
            })
            ->orderBy('created_at', 'asc')
            ->paginate(20); // 20 messages per page

        return response()->json($messages);
    }


    public function getConversations()
    {
        $userId = Auth::id();

        $conversations = Conversation::where('user1_id', $userId)
            ->orWhere('user2_id', $userId)
            ->with(['user1', 'user2', 'messages' => function ($query) {
                $query->latest()->first(); // Fetch the latest message for each conversation
            }])
            ->orderBy('updated_at', 'desc') // Sort by most recent activity
            ->get();

        return response()->json($conversations);
    }
}
