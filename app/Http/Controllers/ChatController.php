<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Chat;


class ChatController extends Controller
{
    public function upload(Request $request)
    {
        $user = $request->user();
        $max_file_size = 5 * 1024; // default max file size in KB

        # file Size restrictions
        if ($user)
        {
            $subscription = $user->getCurrentSubscription();
            if ($subscription)
            {
                $max_file_size = $subscription->pdf_size * 1024; // in KB
            }
        }

        $request->validate([
            "file" => "required|mimes:pdf|max:$max_file_size"
        ]);

        $file = $request->file("file");
        $fileName = sha1(time()) . "." . $file->extension();
        # may not be required
        # $file->move(public_path("uploads/chat-files"), $fileName);

        # dd($file->path());
        # Create a chat room
        $chatManager = getChatManager();
        $raw_response = $chatManager->createChatRoom($file->path(), true);

        if ($raw_response)
        {
            # register chat room in the db

            if ($user)
            {
                # update subscription quota
                $subscription = $user->getCurrentSubscription();
                $subscription->pdfs -= 1;
                $subscription->save();

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

        $user = $request->user();

        if ($user)
        {
            # update subscription quota
            $subscription = $user->getCurrentSubscription();
            if ($subscription->questions > 0)
                $subscription->questions -= 1;
            $subscription->save();
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

    public function stop(Request $request, string $uuid)
    {
        $chatManager = getChatManager();
        $chatRoom = $chatManager->getChatRoomByUUID($uuid);

        if ($chatRoom)
        {
            // TODO: Update user quota (questions + 1)

            $chatRoom->stopAgent();

            return response()->json([
                "errors" => false,
                "message" => "Stopped successfully"
            ], 204); // returns no content
        }

        return response()->json([
            "errors" => true,
            "message" => "Chat room not found."
        ], 400);
    }

    public function registerOpenAIKey(Request $request)
    {
        $request->validate([
            "openai_key" => "string|required"
        ]);

        $openai_key = $request->json("openai_key");

        // inform Rapid website.
        $chatManager = getChatManager();
        if ($chatManager->registerOpenAIKey($openai_key))
        {
            return response()->json([
                "errors" => false,
                "message" => "Updated successfully"
            ], 200);
        }

        return response()->json([
            "errors" => true,
            "message" => "Something went wrong, please check your Rapid API Key & Host again!"
        ], 400);
    }
}
