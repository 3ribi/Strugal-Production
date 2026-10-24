<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaquageReportLine extends Model
{
    protected $fillable = [
        'source_cart',
        'order_number',
        'of_number',
        'reference',
        'bars_count',
        'cart_number',
        'rejected',
        'thickness_min',
        'thickness_max',
        'color',
        'supplier',
        'category',
        'brilliance_average',
        'aspect',
    ];

    protected function casts(): array
    {
        return [
            'bars_count' => 'integer',
            'rejected' => 'integer',
            'thickness_min' => 'decimal:2',
            'thickness_max' => 'decimal:2',
            'brilliance_average' => 'decimal:2',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(LaquageReport::class, 'laquage_report_id');
    }
}
