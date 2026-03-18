<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\StoreVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApiSurveyController extends Controller
{
    public function getActiveQuestions(Request $request)
    {
        $questions = Question::active()->get(['id', 'question_text', 'is_count']);

        return response()->json([
            'success' => true,
            'data' => $questions
        ]);
    }

    public function submitAnswers(Request $request, $visitId)
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.answer_image' => 'nullable|image|max:5120', // 5MB
            'answers.*.count' => 'nullable|integer|min:0',
            'answers.*.remark' => 'nullable|string',
        ]);

        $employee = $request->user()->employee;

        // Verify visit belongs to employee
        $visit = StoreVisit::where('id', $visitId)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        try {
            $storeName = Str::slug($visit->store->name);
            $submittedAnswers = [];

            foreach ($request->answers as $answer) {
                $data = [
                    'visit_id' => $visitId,
                    'question_id' => $answer['question_id'],
                    'answer_text' => $answer['answer_text'] ?? null,
                    'count' => isset($answer['count']) ? (int) $answer['count'] : null,
                    'remark' => $answer['remark'] ?? null,
                ];

                // Handle image upload
                if (isset($answer['answer_image']) && $answer['answer_image']) {
                    $imageFolder = "survey-images/{$storeName}";
                    $file = $answer['answer_image'];
                    $fileName = "visit_{$visitId}_q_{$answer['question_id']}_" . time() . ".{$file->getClientOriginalExtension()}";
                    $imagePath = $file->storeAs($imageFolder, $fileName, 'public');
                    $data['answer_image'] = $imagePath;
                }

                $questionAnswer = QuestionAnswer::create($data);
                $submittedAnswers[] = $questionAnswer;
            }

            return response()->json([
                'success' => true,
                'message' => 'Survey answers submitted successfully',
                'data' => $submittedAnswers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit answers: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMySurveyHistory(Request $request)
    {
        $employee = $request->user()->employee;

        $query = QuestionAnswer::whereHas('visit', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })->with(['question', 'visit.store']);

        // Filter by status
        if ($request->has('admin_status') && $request->admin_status !== 'all') {
            $query->where('admin_status', $request->admin_status);
        }

        // Filter by store
        if ($request->has('store_id')) {
            $query->whereHas('visit', function ($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $answers = $query->orderBy('created_at', 'desc')->get();

        // ✅ Group by store + visit
        $grouped = $answers->groupBy(function ($item) {
            return $item->visit->store_id . '_' . $item->visit->id;
        })->values()->map(function ($group) {
            $first = $group->first();

            return [
                'store_id' => $first->visit->store->id,
                'store_name' => $first->visit->store->name,
                'visit_id' => $first->visit->id,
                'visit_date' => $first->visit->visit_date->toDateString(),

                // Optional: summary status (if any rejected, mark rejected)
                'admin_status_summary' => $group->contains(fn($a) => $a->admin_status === 'rejected')
                    ? 'rejected'
                    : ($group->contains(fn($a) => $a->admin_status === 'approved') ? 'approved' : 'pending'),

                'answers' => $group->map(function ($answer) {
                    return [
                        'id' => $answer->id,
                        'question' => $answer->question->question_text,
                        'answer_text' => $answer->answer_text,
                        'answer_image' => $answer->answer_image ? asset('storage/' . $answer->answer_image) : null,
                        'count' => $answer->count,
                        'remark' => $answer->remark,
                        'admin_status' => $answer->admin_status,
                        'admin_remark' => $answer->admin_remark,
                        'created_at' => $answer->created_at->toDateTimeString(),
                    ];
                })->values()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }


    public function getStoreSurveyHistory(Request $request, $storeId)
    {
        $employee = $request->user()->employee;

        $answers = QuestionAnswer::whereHas('visit', function ($q) use ($employee, $storeId) {
            $q->where('employee_id', $employee->id)
                ->where('store_id', $storeId);
        })->with(['question', 'visit'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $answers
        ]);
    }
}