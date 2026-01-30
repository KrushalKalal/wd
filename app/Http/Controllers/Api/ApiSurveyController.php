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
        $questions = Question::active()->get(['id', 'question_text']);

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
}