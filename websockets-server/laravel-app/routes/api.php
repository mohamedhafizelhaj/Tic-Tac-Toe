<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth', function(Request $request) {
    if (!preg_match('/presence-TicTacToe/', $request->channel_name))
            abort(403);

        $playerId = $request->playerId;
        
        $userData = json_encode([
            'user_id' => $playerId,
            'user_info' => [
                'name'  => "player $playerId"
            ]
        ]);

        $stringToSign = "$request->socket_id:$request->channel_name:$userData";
        $signedString = hash_hmac('sha256', $stringToSign, env('PUSHER_APP_SECRET'));

        $auth = env('PUSHER_APP_KEY') . ":" . $signedString;

        return response()->json([
            'auth' => $auth,
            'channel_data' => $userData
        ]);
});