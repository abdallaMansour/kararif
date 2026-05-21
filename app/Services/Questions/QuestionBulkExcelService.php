<?php

namespace App\Services\Questions;

use App\Models\Category;
use App\Models\Question;
use App\Models\Subcategory;
use App\Models\Type;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QuestionBulkExcelService
{
    public const QUESTIONS_SHEET_NAME = 'Questions';

    public const LOOKUPS_ALL_SHEET_NAME = 'Lookups_All';

    /** @var list<string> */
    public const IMPORT_HEADERS = [
        'type_display',
        'type_id',
        'category_display',
        'category_id',
        'subcategory_display',
        'subcategory_id',
        'name',
        'question_kind_display',
        'question_kind',
        'word',
        'answer_1',
        'is_correct_1',
        'answer_2',
        'is_correct_2',
        'answer_3',
        'is_correct_3',
        'answer_4',
        'is_correct_4',
        'status',
    ];

    /** @var list<array{value: string, label_en: string, label_ar: string}> */
    private const QUESTION_KINDS = [
        ['value' => Question::KIND_NORMAL, 'label_en' => 'Normal', 'label_ar' => 'عادي'],
        ['value' => Question::KIND_WORDS, 'label_en' => 'Words', 'label_ar' => 'كلمات'],
        ['value' => Question::KIND_VOICE, 'label_en' => 'Voice', 'label_ar' => 'صوت'],
        ['value' => Question::KIND_VIDEO, 'label_en' => 'Video', 'label_ar' => 'فيديو'],
        ['value' => Question::KIND_IMAGE, 'label_en' => 'Image', 'label_ar' => 'صورة'],
    ];

    public function buildImportTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Khararif Questions Import Template')
            ->setSubject('Questions bulk import');

        $instructions = $spreadsheet->getActiveSheet();
        $instructions->setTitle('Instructions');
        $instructions->setCellValue('A1', 'Khararif — Questions bulk import template');
        $instructions->setCellValue('A3', '1. Download the lookups file (GET .../bulk-import/lookups) and use it to pick type_id, category_id, subcategory_id, and question_kind.');
        $instructions->setCellValue('A4', '2. Fill rows on the "Questions" sheet. Required: type_id, category_id, subcategory_id, name, question_kind, four answers, exactly one is_correct_* = TRUE.');
        $instructions->setCellValue('A5', '3. For question_kind = words, fill the "word" column (letters separated by spaces, e.g. ك ر ة).');
        $instructions->setCellValue('A6', '4. Media (image / voice / video) and the 5 stage videos are NOT imported via Excel — add them in the dashboard after import.');
        $instructions->setCellValue('A7', '5. Upload the filled file via POST .../bulk-import (multipart field: file).');

        $questions = $spreadsheet->createSheet();
        $questions->setTitle(self::QUESTIONS_SHEET_NAME);
        $this->writeHeaderRow($questions, self::IMPORT_HEADERS, 1);

        $example = [
            '',
            1,
            '',
            1,
            '',
            1,
            'Example question text',
            'Normal (عادي) [normal]',
            Question::KIND_NORMAL,
            '',
            'Answer A',
            false,
            'Answer B',
            true,
            'Answer C',
            false,
            'Answer D',
            false,
            1,
        ];
        $this->writeDataRow($questions, $example, 2);

        $spreadsheet->setActiveSheetIndexByName(self::QUESTIONS_SHEET_NAME);

        return $spreadsheet;
    }

    public function buildLookupsReferenceSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Khararif Questions Import Lookups')
            ->setSubject('Types, categories, subcategories, question kinds');

        $all = $spreadsheet->getActiveSheet();
        $all->setTitle(self::LOOKUPS_ALL_SHEET_NAME);
        $this->populateCombinedLookupsSheet($all);

        $this->addTypesLookupSheet($spreadsheet);
        $this->addCategoriesLookupSheet($spreadsheet);
        $this->addSubcategoriesLookupSheet($spreadsheet);
        $this->addQuestionKindsLookupSheet($spreadsheet);

        $spreadsheet->setActiveSheetIndexByName(self::LOOKUPS_ALL_SHEET_NAME);

        return $spreadsheet;
    }

    private function populateCombinedLookupsSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $headers = ['section', 'id', 'name', 'type_id', 'category_id', 'value', 'label_ar', 'display'];
        $this->writeHeaderRow($sheet, $headers, 1);

        $row = 2;
        foreach (Type::orderBy('id')->get(['id', 'name']) as $type) {
            $display = $type->name . ' [' . $type->id . ']';
            $this->writeDataRow($sheet, ['Types', $type->id, $type->name, '', '', '', '', $display], $row++);
        }

        foreach (Category::orderBy('id')->get(['id', 'name', 'type_id']) as $category) {
            $display = $category->name . ' [' . $category->id . ']';
            $this->writeDataRow($sheet, [
                'Categories',
                $category->id,
                $category->name,
                $category->type_id,
                '',
                '',
                '',
                $display,
            ], $row++);
        }

        foreach (Subcategory::orderBy('id')->get(['id', 'name', 'category_id']) as $subcategory) {
            $display = $subcategory->name . ' [' . $subcategory->id . ']';
            $this->writeDataRow($sheet, [
                'Subcategories',
                $subcategory->id,
                $subcategory->name,
                '',
                $subcategory->category_id,
                '',
                '',
                $display,
            ], $row++);
        }

        foreach (self::QUESTION_KINDS as $kind) {
            $display = $kind['label_en'] . ' (' . $kind['label_ar'] . ') [' . $kind['value'] . ']';
            $this->writeDataRow($sheet, [
                'Question kinds',
                '',
                $kind['label_en'],
                '',
                '',
                $kind['value'],
                $kind['label_ar'],
                $display,
            ], $row++);
        }

        $sheet->freezePane('A2');
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(40);
    }

    private function addTypesLookupSheet(Spreadsheet $spreadsheet): void
    {
        $types = $spreadsheet->createSheet();
        $types->setTitle('Lookups_Types');
        $this->writeHeaderRow($types, ['id', 'name', 'display'], 1);
        $row = 2;
        foreach (Type::orderBy('id')->get(['id', 'name']) as $type) {
            $display = $type->name . ' [' . $type->id . ']';
            $this->writeDataRow($types, [$type->id, $type->name, $display], $row++);
        }
    }

    private function addCategoriesLookupSheet(Spreadsheet $spreadsheet): void
    {
        $categories = $spreadsheet->createSheet();
        $categories->setTitle('Lookups_Categories');
        $this->writeHeaderRow($categories, ['id', 'name', 'type_id', 'display'], 1);
        $row = 2;
        foreach (Category::orderBy('id')->get(['id', 'name', 'type_id']) as $category) {
            $display = $category->name . ' [' . $category->id . ']';
            $this->writeDataRow($categories, [$category->id, $category->name, $category->type_id, $display], $row++);
        }
    }

    private function addSubcategoriesLookupSheet(Spreadsheet $spreadsheet): void
    {
        $subcategories = $spreadsheet->createSheet();
        $subcategories->setTitle('Lookups_Subcategories');
        $this->writeHeaderRow($subcategories, ['id', 'name', 'category_id', 'display'], 1);
        $row = 2;
        foreach (Subcategory::orderBy('id')->get(['id', 'name', 'category_id']) as $subcategory) {
            $display = $subcategory->name . ' [' . $subcategory->id . ']';
            $this->writeDataRow($subcategories, [$subcategory->id, $subcategory->name, $subcategory->category_id, $display], $row++);
        }
    }

    private function addQuestionKindsLookupSheet(Spreadsheet $spreadsheet): void
    {
        $kinds = $spreadsheet->createSheet();
        $kinds->setTitle('Lookups_QuestionKinds');
        $this->writeHeaderRow($kinds, ['value', 'label_en', 'label_ar', 'display'], 1);
        $row = 2;
        foreach (self::QUESTION_KINDS as $kind) {
            $display = $kind['label_en'] . ' (' . $kind['label_ar'] . ') [' . $kind['value'] . ']';
            $this->writeDataRow($kinds, [$kind['value'], $kind['label_en'], $kind['label_ar'], $display], $row++);
        }
    }

    /**
     * @return array{created: int, failed: int, errors: list<array{row: int, message: string}>}
     */
    public function importFromFile(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName(self::QUESTIONS_SHEET_NAME);
        if ($sheet === null) {
            throw new \InvalidArgumentException('Sheet "' . self::QUESTIONS_SHEET_NAME . '" not found in the uploaded file.');
        }

        $headerMap = $this->mapHeadersFromSheet($sheet);
        $missing = array_diff(
            ['type_id', 'category_id', 'subcategory_id', 'name', 'question_kind', 'answer_1', 'answer_2', 'answer_3', 'answer_4'],
            array_keys($headerMap)
        );
        if ($missing !== []) {
            throw new \InvalidArgumentException('Missing required columns: ' . implode(', ', $missing));
        }

        $created = 0;
        $failed = 0;
        $errors = [];
        $highestRow = (int) $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $this->readRow($sheet, $headerMap, $row);
            if ($this->isEmptyImportRow($rowData)) {
                continue;
            }

            $validationError = $this->validateRow($rowData);
            if ($validationError !== null) {
                $failed++;
                $errors[] = ['row' => $row, 'message' => $validationError];
                continue;
            }

            try {
                $attributes = $this->buildQuestionAttributes($rowData);
                DB::transaction(function () use ($attributes) {
                    Question::create($attributes);
                });
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['row' => $row, 'message' => $e->getMessage()];
            }
        }

        if ($created === 0 && $failed === 0) {
            throw new \InvalidArgumentException('No question rows found to import. Fill at least one data row on the Questions sheet.');
        }

        return [
            'created' => $created,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    public function saveSpreadsheetToTempFile(Spreadsheet $spreadsheet, string $basename): string
    {
        $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $basename . '_' . uniqid('', true) . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /**
     * @param list<string> $headers
     */
    private function writeHeaderRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $headers, int $row): void
    {
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . $row, $header);
        }
    }

    /**
     * @param list<mixed> $values
     */
    private function writeDataRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $values, int $row): void
    {
        foreach ($values as $index => $value) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . $row, $value);
        }
    }

    /**
     * @return array<string, int> header name (lowercase) => column index (1-based)
     */
    private function mapHeadersFromSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
    {
        $map = [];
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($col = 1; $col <= $highestColumn; $col++) {
            $header = strtolower(trim((string) $this->getCellValue($sheet, $col, 1)));
            if ($header !== '') {
                $map[$header] = $col;
            }
        }

        return $map;
    }

    /**
     * @param array<string, int> $headerMap
     * @return array<string, mixed>
     */
    private function readRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $headerMap, int $row): array
    {
        $data = [];
        foreach ($headerMap as $header => $col) {
            $data[$header] = $this->getCellValue($sheet, $col, $row);
        }

        return $data;
    }

    private function getCellValue(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $col, int $row): mixed
    {
        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row);

        try {
            return $cell->getCalculatedValue();
        } catch (\Throwable) {
            return $cell->getValue();
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function isEmptyImportRow(array $row): bool
    {
        $name = trim((string) ($row['name'] ?? ''));

        return $name === '';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function validateRow(array $row): ?string
    {
        $typeId = $this->parsePositiveInt($row['type_id'] ?? null);
        $categoryId = $this->parsePositiveInt($row['category_id'] ?? null);
        $subcategoryId = $this->parsePositiveInt($row['subcategory_id'] ?? null);
        $name = trim((string) ($row['name'] ?? ''));
        $kind = strtolower(trim((string) ($row['question_kind'] ?? '')));

        if ($typeId === null) {
            return 'type_id is required and must be a positive integer.';
        }
        if ($categoryId === null) {
            return 'category_id is required and must be a positive integer.';
        }
        if ($subcategoryId === null) {
            return 'subcategory_id is required and must be a positive integer.';
        }
        if ($name === '') {
            return 'name is required.';
        }
        if (strlen($name) > 255) {
            return 'name must be at most 255 characters.';
        }

        $allowedKinds = array_column(self::QUESTION_KINDS, 'value');
        if (!in_array($kind, $allowedKinds, true)) {
            return 'question_kind must be one of: ' . implode(', ', $allowedKinds);
        }

        if (!Type::whereKey($typeId)->exists()) {
            return "type_id {$typeId} does not exist.";
        }
        $category = Category::find($categoryId);
        if (!$category) {
            return "category_id {$categoryId} does not exist.";
        }
        if ((int) $category->type_id !== $typeId) {
            return "category_id {$categoryId} does not belong to type_id {$typeId}.";
        }
        $subcategory = Subcategory::find($subcategoryId);
        if (!$subcategory) {
            return "subcategory_id {$subcategoryId} does not exist.";
        }
        if ((int) $subcategory->category_id !== $categoryId) {
            return "subcategory_id {$subcategoryId} does not belong to category_id {$categoryId}.";
        }

        $answers = [];
        for ($i = 1; $i <= 4; $i++) {
            $answer = trim((string) ($row['answer_' . $i] ?? ''));
            if ($answer === '') {
                return "answer_{$i} is required.";
            }
            $answers[] = $answer;
        }

        $correctFlags = [];
        for ($i = 1; $i <= 4; $i++) {
            $correctFlags[] = $this->parseBoolean($row['is_correct_' . $i] ?? false);
        }
        if (array_sum(array_map(fn ($v) => $v ? 1 : 0, $correctFlags)) !== 1) {
            return 'Exactly one answer must be marked as correct (is_correct_1 … is_correct_4).';
        }

        if ($kind === Question::KIND_WORDS) {
            $raw = trim((string) ($row['word'] ?? ''));
            $letters = $raw === '' ? [] : preg_split('/\s+/', $raw);
            if ($letters === [] || $letters === false) {
                return 'word is required for question_kind = words (letters separated by spaces).';
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function buildQuestionAttributes(array $row): array
    {
        $kind = strtolower(trim((string) $row['question_kind']));
        $data = [
            'type_id' => $this->parsePositiveInt($row['type_id']),
            'category_id' => $this->parsePositiveInt($row['category_id']),
            'subcategory_id' => $this->parsePositiveInt($row['subcategory_id']),
            'name' => trim((string) $row['name']),
            'question_kind' => $kind,
            'answer_1' => trim((string) $row['answer_1']),
            'is_correct_1' => $this->parseBoolean($row['is_correct_1'] ?? false),
            'answer_2' => trim((string) $row['answer_2']),
            'is_correct_2' => $this->parseBoolean($row['is_correct_2'] ?? false),
            'answer_3' => trim((string) $row['answer_3']),
            'is_correct_3' => $this->parseBoolean($row['is_correct_3'] ?? false),
            'answer_4' => trim((string) $row['answer_4']),
            'is_correct_4' => $this->parseBoolean($row['is_correct_4'] ?? false),
            'status' => $this->parseBoolean($row['status'] ?? true),
        ];

        if ($kind === Question::KIND_WORDS) {
            $raw = trim((string) ($row['word'] ?? ''));
            $data['word_data'] = $raw === '' ? [] : preg_split('/\s+/', $raw);
        }

        return $data;
    }

    private function parsePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $int = (int) $value;

            return $int > 0 ? $int : null;
        }

        return null;
    }

    private function parseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'n', ''], true)) {
            return false;
        }

        return (bool) $value;
    }
}
