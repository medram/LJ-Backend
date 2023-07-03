<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class ChatController extends Controller
{
    public function send(Request $request, string $uuid)
    {
        $request->validate([
            "prompt" => "string|required"
        ]);

        $prompt = $request->json("prompt");
        $chatManager = getChatManager();
        $chatRoom = $chatManager->getChatRoomByUUID($uuid);

        if ($chatRoom)
        {
            return response()->json([
                "errors" => false,
                "response" => $chatRoom->send($prompt)
            ]);
        }

        return response()->json([
            "errors" => true,
            "message" => "Chat room not found"
        ], 404);
    }

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
            "message" => "Chat room not found."
        ], 404);
    }

    public function clearHistory(Request $request, string $uuid)
    {
        $chatManager = getChatManager();
        $chatRoom = $chatManager->getChatRoomByUUID($uuid);

        if ($chatRoom && $chatRoom->clearHistory())
        {
            return response()->json([
                "errors" => false,
                "message" => "Cleared successfully"
            ], 204); // returns no content
        }

        return response()->json([
            "errors" => true,
            "message" => "Chat room not found."
        ], 404);
    }
}
