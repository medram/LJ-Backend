<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class ChatController extends Controller
{
    public function details(Request $request, string $uuid)
    {
        $chatManager = getChatManager();
        $chatRoom = $chatManager->getChatRoomByUUID($uuid);

        if ($chatRoom)
        {
            return response()->json([
                "errors" => false,
                "chat" => $chatRoom
            ]);
        }

        return response()->json([
            "errors" => true,
            "chat" => "Chat room not found."
        ], 404);
    }
}
