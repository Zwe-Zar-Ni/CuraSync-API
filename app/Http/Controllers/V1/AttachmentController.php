<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Attachment;

class AttachmentController extends BaseController
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048', // Max file size of 2MB
            'type' => 'required|string|in:profiles,certificates,other',
        ]);

        $path = $request->file('file')->store($request->input('type'), 'public');
        if ($path) {
            $mimeType = $request->file('file')->getMimeType();
            $attachment = Attachment::create([
                'mime_type' => $mimeType,
                'url' => $path,
            ]);
            return $this->success($attachment, 'File uploaded successfully.');
        } else {
            return $this->error([], 'File upload failed.', 500);
        }
    }
}
