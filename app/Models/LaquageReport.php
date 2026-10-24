<?php

namespace App\Models;

use App\Support\LaquageFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $report_date
 */
class LaquageReport extends Model
{
    /**
     * Name shown for the author when the user account no longer exists.
     */
    public const DELETED_AUTHOR = 'Utilisateur supprimé';

    /**
     * The fillable columns follow the report header definition in LaquageFields.
     *
     * @return list<string>
     */
    public function getFillable(): array
    {
        return ['user_id', ...LaquageFields::headerKeys()];
    }

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
        ];
    }

    /**
     * The author. Null once the user has been deleted: the report is kept.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<LaquageReportLine, $this>
     */
    public function rows(): HasMany
    {
        return $this->hasMany(LaquageReportLine::class, 'laquage_report_id');
    }

    public function authorName(): string
    {
        // The relation is null once the author's account has been deleted.
        $author = $this->getRelationValue('user');

        return $author instanceof User ? $author->name : self::DELETED_AUTHOR;
    }

    /**
     * Reports belong to the department: everyone who can access the Laquage
     * department (its users and the administrator) sees and edits every report.
     *
     * @param  Builder<LaquageReport>  $query
     * @return Builder<LaquageReport>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->canAccessDepartment('laquage') ? $query : $query->whereRaw('1 = 0');
    }

    /**
     * Anyone in the department may edit a report, but only its author or an
     * administrator may delete it (a report whose author was deleted: administrator only).
     */
    public function canBeDeletedBy(User $user): bool
    {
        return $user->isAdmin()
            || ($this->user_id !== null && (int) $this->user_id === (int) $user->id);
    }
}
