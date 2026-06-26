<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['source_id', 'date', 'visits'])]
class Metric extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /** @return BelongsTo<Source, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /** Одна точка записи визитов — используется везде (сайт, команды). */
    public static function incrementVisit(int $sourceId, int $count = 1, ?string $date = null): void
    {
        $date ??= today()->toDateString();
        $now = now();

        for ($i = 0; $i < $count; $i++) {
            static::query()->upsert(
                [[
                    'source_id' => $sourceId,
                    'date' => $date,
                    'visits' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['source_id', 'date'],
                [
                    'visits' => DB::raw('visits + 1'),
                    'updated_at' => $now,
                ],
            );
        }
    }

    /** @param Builder<Metric> $query */
    public function scopeForPeriod(Builder $query, int $days): Builder
    {
        if ($days > 0) {
            $query->where('date', '>=', now()->subDays($days - 1)->toDateString());
        }

        return $query;
    }

    /** @param Builder<Metric> $query */
    public function scopeForSource(Builder $query, ?int $sourceId): Builder
    {
        if ($sourceId) {
            $query->where('source_id', $sourceId);
        }

        return $query;
    }

    /** @param Builder<Metric> $query */
    public function scopeFiltered(Builder $query, int $period, ?int $sourceId): Builder
    {
        return $query->forPeriod($period)->forSource($sourceId);
    }
}
