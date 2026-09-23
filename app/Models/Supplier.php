<?php

namespace App\Models;

use App\Enums\SupplierType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'type', 'phone', 'whatsapp', 'email', 'payment_terms', 'notes', 'is_active'])]
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
}
