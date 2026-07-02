<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModuleRequest;
use App\Http\Resources\ModuleResource;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catálogos de módulos RH / SST / SIG. La `section` llega desde la ruta
 * vía ->defaults('section', ...).
 */
class ModuleController extends Controller
{
    /** GET /api/{rh|sst|sig}/modules */
    public function index(Request $request): JsonResponse
    {
        $modules = Module::section($this->section($request))
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return $this->items(ModuleResource::collection($modules));
    }

    /** POST /api/{section}/modules · admin */
    public function store(StoreModuleRequest $request): JsonResponse
    {
        $module = Module::create($request->validated() + ['section' => $this->section($request)]);

        return (new ModuleResource($module))->response()->setStatusCode(201);
    }

    /** PUT /api/{section}/modules/{module} · admin */
    public function update(StoreModuleRequest $request, Module $module): ModuleResource
    {
        $module->update($request->validated());

        return new ModuleResource($module);
    }

    /** DELETE /api/{section}/modules/{module} · admin */
    public function destroy(Module $module): JsonResponse
    {
        $module->delete();

        return response()->json(['success' => true]);
    }

    private function section(Request $request): string
    {
        return $request->route('section') ?? 'rh';
    }
}
