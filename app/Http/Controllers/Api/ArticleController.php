<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feed unificado: sirve noticias, comunicados, reconocimientos y eventos.
 * El `type` llega desde la ruta vía ->defaults('type', ...).
 */
class ArticleController extends Controller
{
    /** GET /api/{news|comunicados|reconocimientos|events} */
    public function index(Request $request): JsonResponse
    {
        $items = Article::type($this->type($request))->latest('id')->get();

        return $this->items(ArticleResource::collection($items));
    }

    /** GET /api/news/{article} */
    public function show(Request $request, Article $article): ArticleResource|JsonResponse
    {
        if ($article->type !== $this->type($request)) {
            return response()->json(['message' => 'Recurso no encontrado.'], 404);
        }

        return new ArticleResource($article);
    }

    /** POST /api/{...} · admin */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        $data = $request->mapped();
        $data['type'] = $data['type'] ?? $this->type($request);
        $data['imgs'] = $data['imgs'] ?? [];

        $article = Article::create($data);

        return (new ArticleResource($article))->response()->setStatusCode(201);
    }

    /** PUT /api/{...}/{article} · admin */
    public function update(StoreArticleRequest $request, Article $article): ArticleResource
    {
        $article->update($request->mapped());

        return new ArticleResource($article);
    }

    /** DELETE /api/{...}/{article} · admin */
    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return response()->json(['success' => true]);
    }

    private function type(Request $request): string
    {
        return $request->route('type') ?? 'noticias';
    }
}
