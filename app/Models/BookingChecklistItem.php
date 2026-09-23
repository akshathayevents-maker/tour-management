<?php

namespace App\Models;

use App\Enums\ChecklistPhase;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['label', 'phase', 'sort_order', 'done_at', 'done_by'])]
class BookingChecklistItem extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'phase' => ChecklistPhase::class,
            'done_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    public function isDone(): bool
    {
        return $this->done_at !== null;
    }
}
