<?php

namespace App\Models;

use App\Support\LaquageFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaquageReportLine extends Model
{
    /**
     * The fillable columns follow the production-line definition in LaquageFields.
     *
     * @return list<string>
     */
    public function getFillable(): array
    {
        return LaquageFields::rowKeys();
    }

    protected function casts(): array
    {
        return LaquageFields::rowCasts();
    }

    /**
     * @return BelongsTo<LaquageReport, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(LaquageReport::class, 'laquage_report_id');
    }
}
