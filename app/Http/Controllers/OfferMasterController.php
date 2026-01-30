<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\CategoryOne;
use App\Models\CategoryTwo;
use App\Models\CategoryThree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class OfferMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Offer::with(['categoryOne', 'categoryTwo', 'categoryThree'])
            ->where('is_active', true);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('offer_title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by offer type
        if ($request->has('offer_type') && $request->offer_type) {
            $query->where('offer_type', $request->offer_type);
        }

        // Filter by category one
        if ($request->has('category_one_id') && $request->category_one_id) {
            $query->where('category_one_id', $request->category_one_id);
        }

        // Filter by category two
        if ($request->has('category_two_id') && $request->category_two_id) {
            $query->where('category_two_id', $request->category_two_id);
        }

        // Filter by category three
        if ($request->has('category_three_id') && $request->category_three_id) {
            $query->where('category_three_id', $request->category_three_id);
        }

        $perPage = $request->get('per_page', 10);
        $offers = $query->orderBy('start_date', 'desc')->paginate($perPage);

        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('OfferMaster/Index', [
            'records' => $offers,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
            'filters' => [
                'search' => $request->search,
                'offer_type' => $request->offer_type,
                'category_one_id' => $request->category_one_id,
                'category_two_id' => $request->category_two_id,
                'category_three_id' => $request->category_three_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('OfferMaster/Form', [
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'offer_type' => 'required|in:category,Group,sales_volume',
            'category_one_id' => 'nullable|exists:category_one,id',
            'category_two_id' => 'nullable|exists:category_two,id',
            'category_three_id' => 'nullable|exists:category_three,id',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'offer_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = true;

            Offer::create($data);

            return redirect()->route('offer-master.index')
                ->with('success', 'Offer added successfully');
        } catch (\Throwable $e) {
            Log::error('Offer creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add offer')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $offer = Offer::with(['categoryOne', 'categoryTwo', 'categoryThree'])->findOrFail($id);
        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('OfferMaster/Form', [
            'offer' => $offer,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'offer_type' => 'required|in:category,Group,sales_volume',
            'category_one_id' => 'nullable|exists:category_one,id',
            'category_two_id' => 'nullable|exists:category_two,id',
            'category_three_id' => 'nullable|exists:category_three,id',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'offer_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $offer = Offer::findOrFail($id);
            $offer->update($request->all());

            return redirect()->route('offer-master.index')
                ->with('success', 'Offer updated successfully');
        } catch (\Throwable $e) {
            Log::error('Offer update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update offer')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $offer = Offer::findOrFail($id);
            $offer->delete();

            return redirect()->back()->with('success', 'Offer deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Offer deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete offer');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $offer = Offer::findOrFail($id);
            $offer->is_active = !$offer->is_active;
            $offer->save();

            return redirect()->back()->with('success', 'Offer status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Offer toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update offer status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $categoryOnes = CategoryOne::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $categoryTwos = CategoryTwo::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $categoryThrees = CategoryThree::where('is_active', true)->orderBy('name')->pluck('name')->toArray();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Offers');

            // Headers
            $headers = [
                'Offer Type (category/Group/sales_volume)',
                'Category One',
                'Category Two',
                'Category Three',
                'Min Quantity',
                'Max Quantity',
                'Offer Title',
                'Description',
                'Start Date (YYYY-MM-DD)',
                'End Date (YYYY-MM-DD)'
            ];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $col++;
            }

            // Styling
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0']
                ]
            ];
            $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

            // Dropdowns
            $offerTypeList = '"category,Group,sales_volume"';
            $cat1List = '"' . implode(',', $categoryOnes) . '"';
            $cat2List = '"' . implode(',', $categoryTwos) . '"';
            $cat3List = '"' . implode(',', $categoryThrees) . '"';

            for ($row = 2; $row <= 1000; $row++) {
                // Offer Type dropdown
                $validation = $sheet->getCell('A' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($offerTypeList);

                // Category dropdowns
                $validation = $sheet->getCell('B' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat1List);

                $validation = $sheet->getCell('C' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat2List);

                $validation = $sheet->getCell('D' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat3List);
            }

            foreach (range('A', 'J') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'offer_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Offer template download failed: ' . $e->getMessage());
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
                $offerType = trim($row['A'] ?? '');
                $cat1Name = trim($row['B'] ?? '');
                $cat2Name = trim($row['C'] ?? '');
                $cat3Name = trim($row['D'] ?? '');
                $minQty = trim($row['E'] ?? '');
                $maxQty = trim($row['F'] ?? '');
                $offerTitle = trim($row['G'] ?? '');
                $description = trim($row['H'] ?? '');
                $startDate = trim($row['I'] ?? '');
                $endDate = trim($row['J'] ?? '');

                if (!$offerType || !$offerTitle || !$startDate || !$endDate) {
                    $errors[] = "Row " . ($index + 2) . ": Offer type, title, and dates are required";
                    continue;
                }

                $cat1Id = null;
                $cat2Id = null;
                $cat3Id = null;

                if ($cat1Name) {
                    $cat1 = CategoryOne::whereRaw('LOWER(name) = ?', [strtolower($cat1Name)])->first();
                    $cat1Id = $cat1?->id;
                }

                if ($cat2Name) {
                    $cat2 = CategoryTwo::whereRaw('LOWER(name) = ?', [strtolower($cat2Name)])->first();
                    $cat2Id = $cat2?->id;
                }

                if ($cat3Name) {
                    $cat3 = CategoryThree::whereRaw('LOWER(name) = ?', [strtolower($cat3Name)])->first();
                    $cat3Id = $cat3?->id;
                }

                Offer::create([
                    'is_active' => true,
                    'offer_type' => $offerType,
                    'category_one_id' => $cat1Id,
                    'category_two_id' => $cat2Id,
                    'category_three_id' => $cat3Id,
                    'min_quantity' => $minQty ?: null,
                    'max_quantity' => $maxQty ?: null,
                    'offer_title' => $offerTitle,
                    'description' => $description,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);

                $imported++;
            }

            $message = "{$imported} offers imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Offer upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}