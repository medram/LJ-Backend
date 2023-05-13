<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Page;
use App\Rules\StripTagsRule;


class PagesController extends Controller
{
    public function list(Request $request)
    {
        $pages = Page::all();

        return response()->json([
            'errors' => false,
            'pages' => $pages
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'title'     => ["required", "string", new StripTagsRule],
            'slug'      => ["required", "unique:pages", new StripTagsRule],
            'content'   => ["string", "nullable"],
            'status'    => "boolean"
        ]);

        $page = Page::create($request->all());

        if ($page)
        {
            return response()->json([
                'errors' => false,
                'message' => "Create successfully.",
                'page' => $page
            ]);
        }
    }


    public function edit(Request $request, $id)
    {
        $page = Page::where('id', $id)->get()->first();

        if ($page)
        {
            $request->validate([
                'title'     => ["required", "string", new StripTagsRule],
                'slug'      => ["required", "unique:pages,id,{$id}", new StripTagsRule],
                'content'   => ["string", "nullable"],
                'status'    => "boolean"
            ]);

            $page->update($request->all());

            return response()->json([
                'errors' => false,
                'message' => "Updated successfully.",
                'page' => $page
            ]);
        }

        return response()->json([
            'errors' => true,
            'message' => "Page not found."
        ]);
    }

    public function details(Request $request, $id)
    {
        $page = Page::find($id);

        if ($page)
        {
            return response()->json([
                'errors' => false,
                'page' => $page
            ]);
        }

        return response()->json([
            'errors' => true,
            'message' => "Page not found."
        ]);
    }

    public function delete(Request $request)
    {
        $id = $request->json('id');

        if ($id)
        {
            Page::destroy($id);

            return response()->json([
                'errors' => false,
                'message' => "Deleted successfully."
            ]);
        }

        return response()->json([
            'errors' => true,
            'message' => "Something went wrong!"
        ]);
    }
}
