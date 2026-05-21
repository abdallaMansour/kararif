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

}
