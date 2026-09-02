<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModuleRequest;
use App\Http\Resources\ModuleResource;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Catálogos de módulos RH / SST / SIG / SINTYC / Inicio. La `section` llega desde
 * la ruta vía ->defaults('section', ...). `slug` (no `id`) identifica al módulo en
 * la URL porque es lo que el frontend conoce (ModuleResource expone `id` = slug);
 * es único solo dentro de su sección, así que la búsqueda siempre va acompañada
 * de la sección de la ruta.
 */
class ModuleController extends Controller
{
    /** GET /api/{section}/modules — solo visibles, para el lector. */
    public function index(Request $request): JsonResponse
    {
        $modules = Module::section($this->section($request))->visible()->ordered()->get();

        return $this->items(ModuleResource::collection($modules));
    }

    /** GET /api/{section}/modules/all · admin — incluye los ocultos. */
    public function all(Request $request): JsonResponse
    {
        $modules = Module::section($this->section($request))->ordered()->get();

        return $this->items(ModuleResource::collection($modules));
    }

    /** POST /api/{section}/modules · admin */
    public function store(StoreModuleRequest $request): JsonResponse
    {
        $module = Module::create($request->validated() + ['section' => $this->section($request)]);

        return (new ModuleResource($module))->response()->setStatusCode(201);
    }

    /** PUT /api/{section}/modules/{slug} · admin */
    public function update(StoreModuleRequest $request, string $slug): ModuleResource
    {
        $module = $this->findBySlug($request, $slug);
        $module->update($request->validated());

        return new ModuleResource($module);
    }

    /** DELETE /api/{section}/modules/{slug} · admin */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        $this->findBySlug($request, $slug)->delete();

        return response()->json(['success' => true]);
    }

    /** PATCH /api/{section}/modules/reorder · admin — { "ids": ["slug-a","slug-b"] } en el nuevo orden. */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['present', 'array'],
            'ids.*' => [
                'string',
                Rule::exists('modules', 'slug')->where('section', $this->section($request)),
            ],
        ]);

        foreach ($data['ids'] as $position => $slug) {
            Module::section($this->section($request))->where('slug', $slug)->update(['position' => $position]);
        }

        return response()->json(['success' => true]);
    }

    private function findBySlug(Request $request, string $slug): Module
    {
        return Module::section($this->section($request))->where('slug', $slug)->firstOrFail();
    }

    private function section(Request $request): string
    {
        return $request->route('section') ?? 'rh';
    }
}
