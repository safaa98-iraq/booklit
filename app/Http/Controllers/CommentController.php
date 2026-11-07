<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Entry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Entry $entry): RedirectResponse
    {
        $this->ensureOwner($request, $entry);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $comment = new Comment($data);
        $comment->user()->associate($request->user());
        $entry->comments()->save($comment);

        return redirect()->to(route('entries.show', $entry).'#comments')->with('status', 'أُضيف التعليق.');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        $this->ensureOwner($request, $comment);
        $entry = $comment->entry;
        $comment->delete();

        return redirect()->to(route('entries.show', $entry).'#comments')->with('status', 'حُذف التعليق.');
    }
}
