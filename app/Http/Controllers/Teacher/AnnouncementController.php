<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $items = Announcement::visibleTo($request->user()->employee)->orderByDesc('published_at')->paginate(10)
            ->through(fn ($a) => ['id' => $a->id, 'title' => $a->title, 'excerpt' => str($a->content)->limit(180)->toString(), 'published_at' => $a->published_at->toIso8601String()]);

        return Inertia::render('guru/Announcements', ['items' => $items]);
    }

    public function show(Request $request, int $id)
    {
        $a = Announcement::visibleTo($request->user()->employee)->findOrFail($id);

        return Inertia::render('guru/AnnouncementShow', [
            'item' => ['id' => $a->id, 'title' => $a->title, 'content' => $a->content, 'published_at' => $a->published_at->toIso8601String()],
        ]);
    }
}
