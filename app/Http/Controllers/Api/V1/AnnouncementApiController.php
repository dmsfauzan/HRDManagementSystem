<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AnnouncementApiController extends Controller
{
    public function index(): JsonResponse
    {
        $announcements = Announcement::published()
            ->orderByDesc('published_at')
            ->paginate(15);

        return ApiResponse::paginated($announcements, fn (Announcement $a) => [
            'id' => $a->id,
            'title' => $a->title,
            'body' => $a->body,
            'published_at' => $a->published_at?->toDateTimeString(),
        ]);
    }
}
