<?php

namespace Tests\Unit;

use App\Services\Questions\QuestionBulkExcelService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class QuestionBulkExcelServiceTest extends TestCase
{
    public function test_import_template_contains_questions_sheet_with_headers(): void
    {
        $service = new QuestionBulkExcelService();
        $path = $service->saveSpreadsheetToTempFile(
            $service->buildImportTemplateSpreadsheet(),
            'test_template'
        );

        $loaded = IOFactory::load($path);
        $sheet = $loaded->getSheetByName(QuestionBulkExcelService::QUESTIONS_SHEET_NAME);
        $this->assertNotNull($sheet);
        $this->assertSame('type_id', strtolower((string) $sheet->getCell('B1')->getValue()));
        $this->assertSame('question_kind', strtolower((string) $sheet->getCell('I1')->getValue()));

        @unlink($path);
    }

    public function test_lookups_workbook_has_combined_sheet_with_all_sections(): void
    {
        $service = new QuestionBulkExcelService();
        $path = $service->saveSpreadsheetToTempFile(
            $service->buildLookupsReferenceSpreadsheet(),
            'test_lookups_all'
        );

        $loaded = IOFactory::load($path);
        $sheet = $loaded->getSheetByName(QuestionBulkExcelService::LOOKUPS_ALL_SHEET_NAME);
        $this->assertNotNull($sheet);
        $this->assertSame('section', strtolower((string) $sheet->getCell('A1')->getValue()));

        $sections = [];
        $highestRow = (int) $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $section = trim((string) $sheet->getCell('A' . $row)->getValue());
            if ($section !== '') {
                $sections[$section] = true;
            }
        }

        $this->assertArrayHasKey('Types', $sections);
        $this->assertArrayHasKey('Categories', $sections);
        $this->assertArrayHasKey('Subcategories', $sections);
        $this->assertArrayHasKey('Question kinds', $sections);
        $this->assertNotNull($loaded->getSheetByName('Lookups_Types'));

        @unlink($path);
    }

}
