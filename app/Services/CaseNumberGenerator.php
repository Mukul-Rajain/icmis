<?php

namespace App\Services;

use App\Models\CourtCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates unique case numbers in the format: CASE/YYYY/NNNNN
 * Uses a transaction to prevent race conditions.
 */
class CaseNumberGenerator
{
    public function next(): string
    {
        return DB::transaction(function () {
            $year = Carbon::now()->year;
            $prefix = "CASE/{$year}/";

            $lastCase = CourtCase::withTrashed()
                ->where('case_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($lastCase) {
                $lastNumber = (int) substr($lastCase->case_number, strlen($prefix));
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        });
    }
}
