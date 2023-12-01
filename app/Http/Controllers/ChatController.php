<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Chat;


class ChatController extends Controller
{
    // Upload Document for chat room
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
            "file" => "required|mimes:pdf,txt,doc,docx,xlsx,xls,epub,csv,json|max:$max_file_size"
        ]);

        $file = $request->file("file");
        $fileName = sha1(time()) . "." . $file->extension();
        # may not be required

        # Create a chat room
        $chatManager = getChatManager();
        try {
            $raw_response = $chatManager->createChatRoom($file, true);
        } catch (\Exception $e){
            return response()->json([
                "errors" => true,
                "message" => $e->getMessage()
            ], 400);
        }

        if ($raw_response)
        {
            # register chat room in the db

            if ($user)
            {
                # update subscription quota
                if (!isDemo())
                {
                    $subscription = $user->getCurrentSubscription();
                    if ($subscription->pdfs > 0)
                    {
                        $subscription->pdfs -= 1;
                        $subscription->save();
                    }
                }

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

    // send prompt of the chat room to backend/RapidAPI
    public function send(Request $request, string $uuid)
    {
        $request->validate([
            "prompt" => "string|required"
        ]);

        $prompt = $request->json("prompt");
        $chat = Chat::where("uuid", $uuid)->get()->first();
        $chatManager = getChatManager();
        $chatRoom = $chatManager->getChatRoomByUUID($uuid);

        // register the prompt & the reply

        if ($chat)
        {
            try {
                $reply = $chatRoom->send($prompt);
            } catch (\Exception $e){
                return response()->json([
                    "errors" => true,
                    "message" => $e->getMessage()
                ], 400);
            }

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
            if (!isDemo())
            {
                # update subscription quota
                $subscription = $user->getCurrentSubscription();
                if ($subscription->questions > 0)
                {
                    $subscription->questions -= 1;
                    $subscription->save();
                }
            }
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

    // Get Chat history/info
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

    // Clear chat history
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

    // Delete chat room
    public function delete(Request $request, string $uuid)
    {
        $chat = Chat::where("uuid", $uuid)->get()->first();

        // Delete from remote
        $chatManager = getChatManager();
        $chatRoom = $chatManager->getChatRoomByUUID($uuid);

        if ($chatRoom)
            $chatRoom->destroy();

        // Delete from DB
        if ($chat)
            $chat->delete();

        return response()->json([
            "errors" => false,
            "message" => "Deleted successfully"
        ], 204); // returns no content


        return response()->json([
            "errors" => true,
            "message" => "Chat room not found."
        ], 404);
    }

    // Get all user's chat rooms
    public function list(Request $request)
    {
        $user = $request->user();

        $chats = Chat::where("user_id", $user->id)->orderBy('created_at', 'desc')->get();

        return response()->json([
            "errors" => false,
            "chats" => ($chats ? $chats : [])
        ]);
    }

    // Stop chat room agent
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

    // inform RapidAPI with User's OpenAPI key
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
