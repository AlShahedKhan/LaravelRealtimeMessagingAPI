<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Broadcast::channel('chat.{receiver_id}', function ($user, $receiver_id)
// {
//     return (int) $user->id === (int) $receiver_id || (int) $user->id === (int) request()->user()->id;
// });


Broadcast::channel('chat.{receiver_id}', function ($user, $receiver_id) {
    // Ensure the authenticated user is the receiver of this channel
    return (int) $user->id === (int) $receiver_id;
});

