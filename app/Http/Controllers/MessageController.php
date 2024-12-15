<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
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

        // Check if a conversation already exists between the two users
        $conversation = Conversation::where(function ($query) {
            $query->where('user1_id', Auth::id())
                ->where('user2_id', request('receiver_id'));
        })->orWhere(function ($query) {
            $query->where('user1_id', request('receiver_id'))
                ->where('user2_id', Auth::id());
        })->first();

        // If no conversation exists, create a new one
        if (!$conversation) {
            $conversation = Conversation::create([
                'user1_id' => Auth::id(),
                'user2_id' => $request->receiver_id,
            ]);
        }

        // Create the message
        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'conversation_id' => $conversation->id,
            'message' => $request->message,
            'file_path' => $filePath,
        ]);

        // Broadcast the message event
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Message sent successfully!',
            'data' => $message,
        ]);
    }


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

    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'members' => 'required|array', // List of user IDs
            'members.*' => 'integer', // Ensure each member is an integer
        ]);

        // Check for invalid members (IDs not in the users table)
        $invalidMembers = collect($request->members)->filter(function ($id) {
            return !\App\Models\User::where('id', $id)->exists();
        });

        if ($invalidMembers->isNotEmpty()) {
            return response()->json([
                'message' => 'Some members are not registered.',
                'invalid_members' => $invalidMembers->values(), // Return the list of invalid IDs
            ], 400);
        }

        // Create the group
        $group = Group::create([
            'name' => $request->name,
            'creator_id' => Auth::id(),
        ]);

        // Add members to the group, including the creator
        $members = collect($request->members)->push(Auth::id())->unique();
        foreach ($members as $member) {
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $member,
            ]);
        }

        // Return success response with group details and members
        return response()->json([
            'message' => "Group #{$group->id} created successfully!",
            'data' => [
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'creator_id' => $group->creator_id,
                    'created_at' => $group->created_at,
                    'updated_at' => $group->updated_at,
                ],
                'members' => $members->values(), // List of all member IDs
            ],
        ]);
    }



    public function sendGroupMessages(Request $request, $group_id)
    {
        // Validate the request
        $request->validate([
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:2048',
        ]);

        if (!$request->filled('message') && !$request->hasFile('file')) {
            return response()->json([
                'message' => 'Either a message or a file is required.',
            ], 400);
        }

        // Find the group
        $group = Group::findOrFail($group_id);

        // Check if the authenticated user is a member of the group
        $isMember = GroupMember::where('group_id', $group->id)
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Add file to the group
        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('group_messages', 'public');
        }

        // Create the message
        $message = GroupMessage::create([
            'group_id' => $group->id,
            'sender_id' => Auth::id(),
            'message' => $request->message,
            'file_path' => $filePath,
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => [
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                ],
                'message' => $message,
            ]
        ]);
    }




    public function getGroupMessages($group_id)
    {
        // Find the group
        $group = Group::findOrFail($group_id);
        // check the authenticated user is a member of the group
        if (!$group->members->contains('user_id', Auth::id())) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }
        // Retrieve messages with pagination
        $messages = $group->messages()->with('sender:id,name')->orderBy('created_at', 'asc')->paginate(20);

        // return successful response
        return response()->json([
            'message' => 'Message sent successfully',
            'data' => [
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                ],
                'message' => $messages,
            ]
        ]);
    }
}
