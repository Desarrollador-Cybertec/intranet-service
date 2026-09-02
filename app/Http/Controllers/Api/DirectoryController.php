<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportDirectoryRequest;
use App\Http\Requests\ReorderDirectoryRequest;
use App\Http\Requests\StoreDirectoryPersonRequest;
use App\Http\Resources\DirectoryPersonAdminResource;
use App\Http\Resources\DirectoryPersonResource;
use App\Models\DirectoryPerson;
use App\Services\DirectoryImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `directory_people` es la fuente autoritativa del Directorio (sin unión con
 * `users`): existe con o sin cuenta. DirectoryService la mantiene sincronizada
 * cuando un usuario con cuenta actualiza su perfil.
 */
class DirectoryController extends Controller
{
    public function __construct(private readonly DirectoryImportService $importer) {}

    /** GET /api/directory?search=&area= — forma pública, solo personas activas. */
    public function index(Request $request): JsonResponse
    {
        $query = DirectoryPerson::query()->active();
        $this->applySearch($query, $request);

        return $this->items(DirectoryPersonResource::collection($query->ordered()->get()));
    }

    /** GET /api/directory/entries?search=&area=&status= — gestión. */
    public function entries(Request $request): JsonResponse
    {
        $query = DirectoryPerson::query();
        $this->applySearch($query, $request);

        if ($status = $request->query('status')) {
            $query->where('active', $status === 'active');
        }

        return $this->items(DirectoryPersonAdminResource::collection($query->ordered()->get()));
    }

    /** POST /api/directory/entries */
    public function store(StoreDirectoryPersonRequest $request): JsonResponse
    {
        $person = DirectoryPerson::create($request->mapped() + ['active' => true, 'position' => 0]);

        return response()->json(new DirectoryPersonAdminResource($person), 201);
    }

    /** PATCH /api/directory/entries/{directoryPerson} */
    public function update(StoreDirectoryPersonRequest $request, DirectoryPerson $directoryPerson): DirectoryPersonAdminResource
    {
        $directoryPerson->update($request->mapped());

        return new DirectoryPersonAdminResource($directoryPerson);
    }

    /** DELETE /api/directory/entries/{directoryPerson} */
    public function destroy(DirectoryPerson $directoryPerson): JsonResponse
    {
        $directoryPerson->delete();

        return response()->json(['success' => true]);
    }

    /** POST /api/directory/entries/import (multipart) */
    public function import(ImportDirectoryRequest $request): JsonResponse
    {
        $rows = $this->importer->readCsv($request->file('file')->getRealPath());

        if ($rows === null) {
            return response()->json(['message' => 'No se pudo leer el archivo.'], 422);
        }

        return response()->json($this->importer->import($rows));
    }

    /** PATCH /api/directory/entries/reorder — { "ids": [3, 1, 2] } en el nuevo orden. */
    public function reorder(ReorderDirectoryRequest $request): JsonResponse
    {
        foreach ($request->validated('ids') as $position => $id) {
            DirectoryPerson::whereKey($id)->update(['position' => $position]);
        }

        return response()->json(['success' => true]);
    }

    private function applySearch($query, Request $request): void
    {
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            });
        }

        if ($area = $request->query('area')) {
            $query->where('area', $area);
        }
    }
}
