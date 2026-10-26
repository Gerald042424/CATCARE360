<?php

if (! function_exists('calculateScore')) {
    function calculateScore(array|object $application, array $answers = []): int
    {
        $application = (object) $application;
        $score = 0;

        $housing = ['House' => 20, 'Condo' => 15, 'Apartment' => 10, 'Rented' => 5, 'Owned' => 20];
        $score += $housing[$application->housing_type ?? ''] ?? 0;

        $occupation = strtolower((string) ($application->occupation ?? ''));
        if (str_contains($occupation, 'employed')) {
            $score += 20;
        } elseif (str_contains($occupation, 'self')) {
            $score += 15;
        } elseif (str_contains($occupation, 'retired')) {
            $score += 10;
        } elseif (str_contains($occupation, 'student')) {
            $score += 5;
        }

        $yesAnswers = array_filter($answers, static fn ($answer) => strtolower((string) $answer) === 'yes');
        $score += min(count($yesAnswers) * 8, 60);

        return min($score, 100);
    }
}
