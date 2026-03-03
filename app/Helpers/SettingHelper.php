<?php

namespace App\Helpers;

class SettingHelper
{
    /**
     * Normalize terms_conditions / privacy_policy value to steps format.
     * Input can be legacy { title, content } or new { last_updated, steps }.
     *
     * @param  array|null  $value  Decoded JSON from setting
     * @return array{last_updated: ?string, steps: array<int, array{title: ?string, content: ?string}>}
     */
    public static function normalizeStepsFormat(?array $value): array
    {
        if (empty($value)) {
            return ['last_updated' => null, 'steps' => []];
        }

        if (isset($value['steps']) && is_array($value['steps'])) {
            $steps = array_values(array_map(function ($step) {
                return [
                    'title' => $step['title'] ?? null,
                    'content' => $step['content'] ?? null,
                ];
            }, $value['steps']));

            return [
                'last_updated' => $value['last_updated'] ?? null,
                'steps' => $steps,
            ];
        }

        // Legacy single title + content
        return [
            'last_updated' => null,
            'steps' => [
                [
                    'title' => $value['title'] ?? null,
                    'content' => $value['content'] ?? null,
                ],
            ],
        ];
    }
}
