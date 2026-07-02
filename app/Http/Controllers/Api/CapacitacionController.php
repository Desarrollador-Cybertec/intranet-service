<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CapacitacionController extends Controller
{
    /** GET /api/capacitaciones → { items, progress } (relativo al usuario). */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $completedIds = $user->enrollments()
            ->where('completed', true)
            ->pluck('course_id')
            ->all();

        $courses = Course::orderBy('id')->get()->map(function (Course $course) use ($completedIds) {
            $course->completed = in_array($course->id, $completedIds, true);

            return $course;
        });

        $total = $courses->count();
        $done = collect($completedIds)->intersect($courses->pluck('id'))->count();

        return response()->json([
            'items' => CourseResource::collection($courses),
            'progress' => [
                'total' => $total,
                'done' => $done,
                'percent' => $total ? (int) round($done / $total * 100) : 0,
            ],
        ]);
    }

    /** POST /api/capacitaciones/{course}/inscripcion · user → { success, id }. */
    public function inscripcion(Request $request, Course $course): JsonResponse
    {
        $request->user()->enrollments()->updateOrCreate(
            ['course_id' => $course->id],
            [], // inscrito; completed se marca por otro flujo
        );

        return response()->json(['success' => true, 'id' => $course->id]);
    }

    /** POST /api/capacitaciones · admin */
    public function store(Request $request): JsonResponse
    {
        $course = Course::create($this->validateCourse($request, true));

        return (new CourseResource($course))->response()->setStatusCode(201);
    }

    /** PUT /api/capacitaciones/{course} · admin */
    public function update(Request $request, Course $course): CourseResource
    {
        $course->update($this->validateCourse($request, false));

        return new CourseResource($course);
    }

    /** DELETE /api/capacitaciones/{course} · admin */
    public function destroy(Course $course): JsonResponse
    {
        $course->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @return array<string,mixed>
     */
    private function validateCourse(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        $data = $request->validate([
            'label' => [$req, 'string', 'max:255'],
            'icon' => [$req, 'string', 'max:255'],
            'tag' => [$req, 'in:Obligatorio,Desarrollo,Técnico'],
            'tagColor' => [$req, 'string', 'max:255'],
            'tagBg' => [$req, 'string', 'max:255'],
            'desc' => [$req, 'string'],
            'duration' => [$req, 'string', 'max:255'],
            'modality' => [$req, 'in:Virtual,Presencial,Mixto'],
        ]);

        // camelCase → snake_case
        foreach (['tagColor' => 'tag_color', 'tagBg' => 'tag_bg'] as $from => $to) {
            if (array_key_exists($from, $data)) {
                $data[$to] = $data[$from];
                unset($data[$from]);
            }
        }

        return $data;
    }
}
