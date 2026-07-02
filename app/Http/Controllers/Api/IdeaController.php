<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIdeaRequest;
use App\Http\Resources\IdeaResource;
use App\Models\Idea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdeaController extends Controller
{
    /** GET /api/ideas */
    public function index(): JsonResponse
    {
        return $this->items(IdeaResource::collection(Idea::latest('id')->get()));
    }

    /** POST /api/ideas · user — author_id real siempre persistido (auditoría). */
    public function store(StoreIdeaRequest $request): JsonResponse
    {
        $user = $request->user();
        $anonymous = (bool) $request->boolean('anonymous');

        $idea = Idea::create([
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'anonymous' => $anonymous,
            'votes' => 0,
            'date' => 'Ahora mismo',
            'author_id' => $user->id,
            'author' => $user->name, // real; se enmascara en la respuesta si anónimo
            'status' => 'pendiente',
        ]);

        return response()->json(['success' => true, 'id' => $idea->id], 201);
    }

    /** POST /api/ideas/{idea}/vote · user — un voto por usuario. */
    public function vote(Request $request, Idea $idea): JsonResponse
    {
        $user = $request->user();

        $votes = DB::transaction(function () use ($idea, $user) {
            $row = Idea::whereKey($idea->id)->lockForUpdate()->first();

            $already = $row->ideaVotes()->where('user_id', $user->id)->exists();
            if (! $already) {
                $row->ideaVotes()->create(['user_id' => $user->id]);
                $row->increment('votes');
            }

            return $row->fresh()->votes;
        });

        return response()->json(['votes' => $votes]);
    }

    /** PATCH /api/ideas/{idea} · admin — moderación / cambio de estado. */
    public function update(Request $request, Idea $idea): IdeaResource
    {
        $data = $request->validate([
            'status' => ['sometimes', 'string', 'max:50'],
        ]);
        $idea->update($data);

        return new IdeaResource($idea);
    }

    /** DELETE /api/ideas/{idea} · admin */
    public function destroy(Idea $idea): JsonResponse
    {
        $idea->delete();

        return response()->json(['success' => true]);
    }
}
