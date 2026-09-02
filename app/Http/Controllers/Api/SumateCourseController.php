<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panel admin de la precondición automática "capacitaciones" (SumateService::capacitacionesFor):
 * no tiene sección propia, vive dentro de la gestión de Súmate porque es lo único de lo
 * que depende esa precondición además de la antigüedad.
 */
class SumateCourseController extends Controller
{
    /** GET /api/sumate/cursos */
    public function index(): JsonResponse
    {
        $courses = Course::orderBy('id')->get()->map(fn (Course $c) => [
            'id' => $c->id,
            'label' => $c->label,
            'tag' => $c->tag,
            'obligatorio' => $c->tag === 'Obligatorio',
        ]);

        return $this->items($courses);
    }

    /** GET /api/sumate/cursos/{course}/completados */
    public function completions(Course $course): JsonResponse
    {
        $completedUserIds = $course->enrollments()->where('completed', true)->pluck('user_id')->all();

        $users = User::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'completed' => in_array($u->id, $completedUserIds, true),
            ]);

        return $this->items($users);
    }

    /** POST /api/sumate/cursos/{course}/completados — { "userId": 7, "completed": true } */
    public function setCompletion(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'userId' => ['required', 'integer', 'exists:users,id'],
            'completed' => ['required', 'boolean'],
        ]);

        $course->enrollments()->updateOrCreate(
            ['user_id' => $data['userId']],
            ['completed' => $data['completed']],
        );

        return response()->json(['success' => true]);
    }
}
