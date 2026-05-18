<?php

namespace Tests\Unit;

use App\Models\CustomQuestion;
use App\Services\GameService;
use PHPUnit\Framework\TestCase;

class GameServiceAnswerNormalizationTest extends TestCase
{
    public function test_normalize_accepts_only_one_to_four_and_shapes(): void
    {
        $service = new GameService(
            $this->createMock(\App\Services\FirebaseGameSyncService::class),
            $this->createMock(\App\Services\CustomContentUsageService::class),
        );

        $this->assertSame(1, $service->normalizeAnswerOptionIndex(1));
        $this->assertSame(2, $service->normalizeAnswerOptionIndex(2));
        $this->assertSame(3, $service->normalizeAnswerOptionIndex(3));
        $this->assertSame(4, $service->normalizeAnswerOptionIndex(4));

        $this->assertSame(1, $service->normalizeAnswerOptionIndex('triangle'));
        $this->assertSame(2, $service->normalizeAnswerOptionIndex('circle'));
        $this->assertSame(3, $service->normalizeAnswerOptionIndex('x'));
        $this->assertSame(4, $service->normalizeAnswerOptionIndex('square'));

        $this->assertNull($service->normalizeAnswerOptionIndex(0));
        $this->assertNull($service->normalizeAnswerOptionIndex('o1'));
        $this->assertNull($service->normalizeAnswerOptionIndex(25));
        $this->assertNull($service->normalizeAnswerOptionIndex(null));
    }

    public function test_read_correct_flag_handles_int_and_string(): void
    {
        $service = new GameService(
            $this->createMock(\App\Services\FirebaseGameSyncService::class),
            $this->createMock(\App\Services\CustomContentUsageService::class),
        );

        $question = new CustomQuestion([
            'is_correct_1' => 1,
            'is_correct_2' => 0,
            'is_correct_3' => '0',
            'is_correct_4' => false,
        ]);

        $this->assertTrue($service->readQuestionCorrectFlag($question, 1));
        $this->assertFalse($service->readQuestionCorrectFlag($question, 2));
        $this->assertFalse($service->readQuestionCorrectFlag($question, 3));
        $this->assertSame(1, $service->resolveCorrectAnswerOptionIndex($question));
    }
}
