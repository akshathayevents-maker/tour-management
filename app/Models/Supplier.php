<?php

namespace App\Models;

use App\Enums\SupplierType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'type', 'operating_location', 'phone', 'whatsapp', 'email', 'payment_terms', 'notes', 'is_active'])]
class Supplier extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => SupplierType::class,
            'is_active' => 'boolean',
        ];
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class)->latest('id');
    }

    /**
     * Case-insensitive match against a trip/destination string — used to
     * prioritize suppliers in selection UIs, never to restrict them.
     */
    public function matchesDestination(?string $destination): bool
    {
        return $destination !== null
            && $this->operating_location !== null
            && mb_strtolower(trim($this->operating_location)) === mb_strtolower(trim($destination));
    }
}
