<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Chat;


class ChatController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            "file" => "required|mimes:pdf"
        ]);

        $file = $request->file("file");
        $fileName = sha1(time()) . "." . $file->extension();
        # may not be required
        # $file->move(public_path("uploads/chat-files"), $fileName);

        # TODO:
        # file Size restrictions

        # dd($file->path());
        # Create a chat room
        $chatManager = getChatManager();
        $raw_response = $chatManager->createChatRoom($file->path(), true);

        if ($raw_response)
        {
            # register chat room in the db
            $user = $request->user();

            if ($user)
            {
                Chat::create([
                    "user_id"   => $user->id,
                    "title"     => $file->getClientOriginalName(),
                    "uuid"      => $raw_response->uuid,
                    "chat_history" => "",
                ]);
            }

            return response()->json([
                "errors" => false,
                "message" => "Created successfully",
                "chat_room" => $raw_response
            ], 201);
        }

        return response()->json([
            "errors" => true,
            "message" => "Something went wrong, please try again or later"
        ], 400);
    }

    public function send(Request $request, string $uuid)
    {
        $request->validate([
            "prompt" => "string|required"
        ]);

        $prompt = $request->json("prompt");
        $chat = Chat::where("uuid", $uuid)->get()->first();
        $chatManager = getChatManager();
        $chatRoom = $chatManager->getChatRoomByUUID($uuid);

        // TODO: register the prompt & the reply

        if ($chat)
        {
            $reply = $chatRoom->send($prompt);

            $history = json_decode($chat->chat_history, true);
            $history[] = [
                "type"      => "human",
                "content"   => $prompt
            ];
            $history[] = [
                "type"      => "ai",
                "content"   => $reply
            ];

            $chat->chat_history = json_encode($history);
            $chat->save();
        }

        if ($chatRoom)
        {
            return response()->json([
                "errors" => false,
                "response" => $reply
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
            $chat = Chat::where("uuid", $uuid)->get()->first();

            if ($chat)
            {
                $chat->chat_history = "";
                $chat->save();
            }

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

    public function delete(Request $request, string $uuid)
    {
        $chatManager = getChatManager();
        $chatRoom = $chatManager->getChatRoomByUUID($uuid);

        if ($chatRoom && $chatRoom->destroy())
        {
            $chat = Chat::where("uuid", $uuid)->get()->first();

            if ($chat)
            {
                $chat->delete();
            }

            return response()->json([
                "errors" => false,
                "message" => "Deleted successfully"
            ], 204); // returns no content
        }

        return response()->json([
            "errors" => true,
            "message" => "Chat room not found."
        ], 404);
    }

    public function list(Request $request)
    {
        $user = $request->user();

        $chats = Chat::where("user_id", $user->id)->orderBy('created_at', 'desc')->get();

        return response()->json([
            "errors" => false,
            "chats" => ($chats ? $chats : [])
        ]);
    }
}
