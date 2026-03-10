<?php

namespace App\Services;

use Illuminate\Support\Arr;

class QuoteService
{
    /**
     * Get all quotes normalized.
     *
     * @return array<int, array{date: int|null, content: string}>
     */
    public function getNormalizedQuotes(): array
    {
        $raw = config('workbench.quotes', []);

        return array_values(array_filter(array_map(function ($item) {
            if (is_array($item)) {
                $day     = (int) Arr::get($item, 'date', 0);
                $content = trim((string) Arr::get($item, 'content', ''));
                if ($content !== '') {
                    return ['date' => $day ?: null, 'content' => $content];
                }
                return null;
            }

            if (is_string($item)) {
                $content = trim($item);
                return $content === '' ? null : ['date' => null, 'content' => $content];
            }

            return null;
        }, $raw)));
    }

    /**
     * Get quotes available for today.
     *
     * @return array<int, string>
     */
    public function getTodayQuotes(): array
    {
        $normalized = $this->getNormalizedQuotes();
        $today = (int) now()->day;

        $todayQuotes = array_values(array_map(
            fn ($row) => $row['content'],
            array_filter($normalized, fn ($row) => isset($row['date']) && (int) $row['date'] === $today)
        ));

        // Fallback to all quotes if none for today
        if (empty($todayQuotes)) {
            $todayQuotes = array_values(array_map(fn ($row) => $row['content'], $normalized));
        }

        return array_values(array_filter(array_map('trim', $todayQuotes)));
    }

    /**
     * Get a single quote for today.
     *
     * @return string|null
     */
    public function getDailyQuote(): ?string
    {
        $quotes = $this->getTodayQuotes();

        if (empty($quotes)) {
            return null;
        }

        // Use the day of the month to pick a consistent index if there are multiple quotes for the day.
        // Or if we want it to be exactly the same as what the user sees, we might need to know the index.
        // However, the Livewire component uses a random start index.
        // If the user wants the "same message exactly", and it changes every 24h, 
        // usually it means if there's only one quote per day in config, it's easy.
        // If there are multiple, picking one by (day % count) is more "stable" than random for an API.
        
        $index = (now()->dayOfYear) % count($quotes);
        return $quotes[$index];
    }
}
