<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DirectoryPersonResource;
use App\Models\DirectoryPerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    /** GET /api/directory?search=&area= */
    public function index(Request $request): JsonResponse
    {
        $query = DirectoryPerson::query();

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

        return $this->items(DirectoryPersonResource::collection($query->orderBy('name')->get()));
    }

    /** POST /api/directory · admin */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePerson($request, true);
        $person = DirectoryPerson::create($data);

        return (new DirectoryPersonResource($person))->response()->setStatusCode(201);
    }

    /** PUT /api/directory/{directoryPerson} · admin */
    public function update(Request $request, DirectoryPerson $directoryPerson): DirectoryPersonResource
    {
        $directoryPerson->update($this->validatePerson($request, false));

        return new DirectoryPersonResource($directoryPerson);
    }

    /** DELETE /api/directory/{directoryPerson} · admin */
    public function destroy(DirectoryPerson $directoryPerson): JsonResponse
    {
        $directoryPerson->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @return array<string,mixed>
     */
    private function validatePerson(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'name' => [$req, 'string', 'max:255'],
            'role' => [$req, 'string', 'max:255'],
            'area' => [$req, 'string', 'max:255'],
            'image' => ['sometimes', 'nullable', 'string'],
            'initials' => [$req, 'string', 'max:4'],
            'color' => [$req, 'string', 'max:255'],
            'email' => [$req, 'email', 'max:255'],
            'phone' => [$req, 'string', 'max:255'],
        ]);
    }
}
