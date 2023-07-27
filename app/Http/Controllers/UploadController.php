<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class UploadController extends Controller
{
    // Handle images upload.
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:png,jpg,jpeg'
        ]);

        $image = $request->file('file');
        $imageName = sha1(time()) . '.' . $image->extension();
        $image->move(public_path('uploads/images'), $imageName);

        return response()->json([
            'errors' => false,
            'filename' => $imageName,
            'url' => url("uploads/images/".$imageName)
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
