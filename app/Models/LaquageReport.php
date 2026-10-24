<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaquageReport extends Model
{
    protected $fillable = [
        'user_id',
        'report_date',
        'line',
        'work_period',
        'operators',
        'micrometer_check',
        'brillometer',
        'phase',
        'furnace_temperature',
        // Legacy row columns remain fillable so existing records/code are not broken.
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
            'report_date' => 'date',
            'bars_count' => 'integer',
            'cart_number' => 'integer',
            'rejected' => 'integer',
            'thickness_min' => 'decimal:2',
            'thickness_max' => 'decimal:2',
            'brilliance_average' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(LaquageReportLine::class, 'laquage_report_id');
    }

    /**
     * Administrators see every report; other users only see their own.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where('user_id', $user->id);
    }
}
