<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreForumPostRequest;
use App\Http\Requests\VoteRequest;
use App\Http\Resources\ForumPostResource;
use App\Models\ForumPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ForumController extends Controller
{
    /** GET /api/forum/posts */
    public function index(): JsonResponse
    {
        return $this->items(ForumPostResource::collection(ForumPost::latest('id')->get()));
    }

    /** POST /api/forum/posts · user */
    public function store(StoreForumPostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $post = ForumPost::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'tags' => $data['tags'] ?? [],
            'tag' => $data['tag'] ?? ($data['tags'][0] ?? 'General'),
            'votes' => 0,
            'replies' => 0,
            'author' => $user->name,
            'author_id' => $user->id,
            'date' => 'Ahora mismo',
        ]);

        return (new ForumPostResource($post))->response()->setStatusCode(201);
    }

    /**
     * POST /api/forum/posts/{forumPost}/vote — devuelve el total autoritativo.
     * Un usuario vota up/down una vez; re-votar igual es idempotente, cambiar dirección ajusta ±2.
     */
    public function vote(VoteRequest $request, ForumPost $forumPost): JsonResponse
    {
        $user = $request->user();
        $new = $request->input('direction');

        $votes = DB::transaction(function () use ($forumPost, $user, $new) {
            $post = ForumPost::whereKey($forumPost->id)->lockForUpdate()->first();

            $existing = $post->postVotes()->where('user_id', $user->id)->first();
            $prevValue = $existing ? ($existing->direction === 'up' ? 1 : -1) : 0;
            $newValue = $new === 'up' ? 1 : -1;

            $delta = $newValue - $prevValue;
            if ($delta !== 0) {
                $post->increment('votes', $delta);
            }

            $post->postVotes()->updateOrCreate(
                ['user_id' => $user->id],
                ['direction' => $new],
            );

            return $post->fresh()->votes;
        });

        return response()->json(['votes' => $votes]);
    }

    /** PUT /api/forum/posts/{forumPost} · 👤 autor o admin */
    public function update(StoreForumPostRequest $request, ForumPost $forumPost): ForumPostResource
    {
        $this->authorize('update', $forumPost);

        $data = $request->validated();
        if (isset($data['tags']) && ! isset($data['tag'])) {
            $data['tag'] = $data['tags'][0] ?? $forumPost->tag;
        }
        $forumPost->update($data);

        return new ForumPostResource($forumPost);
    }

    /** DELETE /api/forum/posts/{forumPost} · 👤 autor o admin */
    public function destroy(ForumPost $forumPost): JsonResponse
    {
        $this->authorize('delete', $forumPost);
        $forumPost->delete();

        return response()->json(['success' => true]);
    }
}
