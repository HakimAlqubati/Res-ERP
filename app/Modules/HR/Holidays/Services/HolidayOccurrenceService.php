<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Services;

use App\Models\HolidayOccurrence;
use App\Models\HolidayTemplate;
use App\Modules\HR\Holidays\Builders\HolidayOccurrencePayloadBuilder;
use App\Modules\HR\Holidays\Builders\HolidayTemplateQueryBuilder;
use App\Modules\HR\Holidays\Contracts\HolidayOccurrenceServiceInterface;
use App\Modules\HR\Holidays\DTOs\ConfirmHolidayOccurrenceDTO;
use App\Modules\HR\Holidays\DTOs\HolidayDateRangeDTO;
use Illuminate\Support\Facades\DB;

final class HolidayOccurrenceService implements HolidayOccurrenceServiceInterface
{
    public function __construct(
        private readonly HolidayDateResolverService $dateResolver,
    ) {}

    public function previewForTemplateAndYear(HolidayTemplate $template, int $gregorianYear): HolidayDateRangeDTO
    {
        return $this->dateResolver->resolvePrimaryForYear($template, $gregorianYear);
    }

    public function generateForYear(int $gregorianYear, ?int $actorId = null): int
    {
        return DB::transaction(function () use ($gregorianYear, $actorId): int {
            $templates = HolidayTemplateQueryBuilder::make()
                ->onlyActive()
                ->ordered()
                ->build()
                ->get();

            if ($templates->isEmpty()) {
                return 0;
            }

            $existingKeys = HolidayOccurrence::query()
                ->where('year', $gregorianYear)
                ->whereNotNull('holiday_template_id')
                ->get(['holiday_template_id', 'hijri_year'])
                ->mapWithKeys(fn (HolidayOccurrence $item): array => [
                    $this->occurrenceKey((int) $item->holiday_template_id, $item->hijri_year) => true,
                ])
                ->all();

            $createdCount = 0;

            foreach ($templates as $template) {
                $dateRanges = $this->dateResolver->resolveForYear($template, $gregorianYear);

                foreach ($dateRanges as $dateRange) {
                    $key = $this->occurrenceKey((int) $template->id, $dateRange->hijriYear);

                    if (isset($existingKeys[$key])) {
                        continue;
                    }

                    $payload = HolidayOccurrencePayloadBuilder::make()
                        ->fromTemplate($template)
                        ->withDateRange($dateRange)
                        ->byActor($actorId)
                        ->buildGeneratedPayload();

                    HolidayOccurrence::query()->create($payload);

                    $existingKeys[$key] = true;
                    $createdCount++;
                }
            }

            return $createdCount;
        });
    }

    public function create(array $data, ?int $actorId = null): HolidayOccurrence
    {
        return DB::transaction(function () use ($data, $actorId): HolidayOccurrence {
            $payload = HolidayOccurrencePayloadBuilder::make()
                ->byActor($actorId)
                ->buildPersistencePayload($data);

            return HolidayOccurrence::query()->create($payload);
        });
    }

    public function update(HolidayOccurrence $occurrence, array $data, ?int $actorId = null): HolidayOccurrence
    {
        return DB::transaction(function () use ($occurrence, $data, $actorId): HolidayOccurrence {
            $payload = HolidayOccurrencePayloadBuilder::make()
                ->byActor($actorId)
                ->buildPersistencePayload($data, $occurrence);

            $occurrence->update($payload);

            return $occurrence->refresh();
        });
    }

    public function confirm(HolidayOccurrence $occurrence, ConfirmHolidayOccurrenceDTO $dto): HolidayOccurrence
    {
        return DB::transaction(function () use ($occurrence, $dto): HolidayOccurrence {
            $payload = HolidayOccurrencePayloadBuilder::make()
                ->byActor($dto->actorId)
                ->buildConfirmationPayload($dto);

            $occurrence->update($payload);

            return $occurrence->refresh();
        });
    }

    public function shiftDays(HolidayOccurrence $occurrence, int $daysOffset, ?int $actorId = null): HolidayOccurrence
    {
        return DB::transaction(function () use ($occurrence, $daysOffset, $actorId): HolidayOccurrence {
            $shifted = HolidayOccurrencePayloadBuilder::shiftRange(
                $occurrence->from_date->toDateString(),
                (int) $occurrence->count_days,
                $daysOffset
            );

            $occurrence->update([
                'from_date'  => $shifted['from_date'],
                'to_date'    => $shifted['to_date'],
                'count_days' => $shifted['count_days'],
                'year'       => $shifted['year'],
                'updated_by' => $actorId ?? auth()->id(),
            ]);

            return $occurrence->refresh();
        });
    }

    private function occurrenceKey(int $templateId, ?int $hijriYear): string
    {
        return "{$templateId}:" . ($hijriYear ?? 'fixed');
    }
}
