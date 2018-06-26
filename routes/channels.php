<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('everywhere', function ($user) {
   return $user;
});

Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    if ($user->canEnterRoom($roomId)) {
        return $user;
    }
});