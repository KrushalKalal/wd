<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QuestionMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Question::query();

        // Search
        if ($request->has('search') && $request->search) {
            $query->where('question_text', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $questions = $query->orderBy('question_text')->paginate($perPage);

        return Inertia::render('QuestionMaster/Index', [
            'records' => $questions,
            'filters' => [
                'search' => $request->search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('QuestionMaster/Form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'question_text' => 'required|string',
        ]);

        try {
            Question::create([
                'question_text' => $request->question_text,
                'is_active' => true,
            ]);

            return redirect()->route('question-master.index')
                ->with('success', 'Question added successfully');
        } catch (\Throwable $e) {
            Log::error('Question creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add question')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $question = Question::findOrFail($id);

        return Inertia::render('QuestionMaster/Form', [
            'question' => $question,   // ← note singular "question" to match form prop
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'question_text' => 'required|string',
        ]);

        try {
            $question = Question::findOrFail($id);
            $question->update([
                'question_text' => $request->question_text,
            ]);

            return redirect()->route('question-master.index')
                ->with('success', 'Question updated successfully');
        } catch (\Throwable $e) {
            Log::error('Question update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update question')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $question = Question::findOrFail($id);
            $question->delete();

            return redirect()->back()->with('success', 'Question deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Question deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete question. It may have associated answers.');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $question = Question::findOrFail($id);
            $question->is_active = !$question->is_active;
            $question->save();

            return redirect()->back()->with('success', 'Question status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Question toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update question status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header
            $sheet->setCellValue('A1', 'Question Text');

            // Styling
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setAutoSize(true);

            $writer = new Xlsx($spreadsheet);
            $filename = 'question_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Question template download failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to download template');
        }
    }

    public function uploadExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
                $request->file('excel_file')->getRealPath()
            );

            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $imported = 0;
            $errors = [];

            foreach (array_slice($rows, 1) as $index => $row) {
                $questionText = trim($row['A'] ?? '');

                if (!$questionText) {
                    $errors[] = "Row " . ($index + 2) . ": Question text is required";
                    continue;
                }

                // Check if already exists (case-insensitive)
                $exists = Question::whereRaw('LOWER(question_text) = ?', [strtolower($questionText)])->first();
                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": Question '{$questionText}' already exists";
                    continue;
                }

                Question::create([
                    'question_text' => $questionText,
                    'is_active' => true,
                ]);

                $imported++;
            }

            $message = "{$imported} questions imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Question upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}