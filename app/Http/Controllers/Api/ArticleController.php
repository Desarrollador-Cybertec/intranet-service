<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        $article = DB::transaction(function () use ($request, $data) {
            $article = Article::create($data);
            $this->attachImages($request, $article);

            return $article;
        });

        return (new ArticleResource($article))->response()->setStatusCode(201);
    }

    /**
     * PUT /api/{...}/{article} · admin.
     * El `type` de la ruta debe coincidir con el del artículo: sin esto, alguien con
     * permiso de editar sobre UN tipo (p. ej. enterate.editar) podría editar un
     * artículo de OTRO tipo (p. ej. un evento) resuelto por id vía otra URL.
     */
    public function update(StoreArticleRequest $request, Article $article): ArticleResource|JsonResponse
    {
        if ($article->type !== $this->type($request)) {
            return response()->json(['message' => 'Recurso no encontrado.'], 404);
        }

        DB::transaction(function () use ($request, $article) {
            $article->update($request->mapped());
            $this->attachImages($request, $article);
        });

        return new ArticleResource($article);
    }

    /** Sube las imágenes nuevas (campo `images[]`) y las anexa a `imgs` del artículo. */
    private function attachImages(Request $request, Article $article): void
    {
        /** @var UploadedFile[] $files */
        $files = $request->file('images', []);
        if (empty($files)) {
            return;
        }

        $urls = array_map(
            fn (UploadedFile $file) => Storage::disk('public')->url($file->store("articles/{$article->type}", 'public')),
            $files,
        );

        $article->update(['imgs' => [...$article->imgs, ...$urls]]);
    }

    /** DELETE /api/{...}/{article} · admin */
    public function destroy(Request $request, Article $article): JsonResponse
    {
        if ($article->type !== $this->type($request)) {
            return response()->json(['message' => 'Recurso no encontrado.'], 404);
        }

        $article->delete();

        return response()->json(['success' => true]);
    }

    private function type(Request $request): string
    {
        return $request->route('type') ?? 'noticias';
    }
}
