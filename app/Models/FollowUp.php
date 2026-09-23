<?php

namespace App\Models;

use App\Enums\FollowUpStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['followupable_type', 'followupable_id', 'due_at', 'reason', 'notes', 'status', 'completed_at'])]
class FollowUp extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => FollowUpStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FollowUp $followUp) {
            if (blank($followUp->created_by) && auth()->check()) {
                $followUp->created_by = auth()->id();
            }
        });
    }

    public function followupable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Overdue is derived from due_at + status, never stored, so it can
     * never drift out of sync with "now".
     */
    public function isOverdue(): bool
    {
        return $this->status === FollowUpStatus::Pending && $this->due_at->isPast();
    }

    /**
     * Human label + link for whatever this follow-up is attached to,
     * so one shared blade partial can render a Lead, Customer or
     * Enquiry follow-up the same way.
     */
    public function subjectLabel(): string
    {
        return match (true) {
            $this->followupable instanceof Customer => $this->followupable->name,
            $this->followupable instanceof Lead => "Lead: {$this->followupable->name}",
            $this->followupable instanceof Enquiry => "Enquiry: {$this->followupable->destination}",
            default => 'Unknown',
        };
    }

    public function subjectUrl(): string
    {
        return match (true) {
            $this->followupable instanceof Customer => route('customers.show', $this->followupable),
            $this->followupable instanceof Lead => route('leads.show', $this->followupable),
            $this->followupable instanceof Enquiry => route('enquiries.show', $this->followupable),
            default => '#',
        };
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', FollowUpStatus::Pending);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->pending()->where('due_at', '<', now());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->pending()->whereDate('due_at', now()->toDateString());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->pending()->whereDate('due_at', '>', now()->toDateString());
    }
}
