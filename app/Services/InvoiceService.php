<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Owns invoice creation: numbering and the BookingItem -> line-item
 * snapshot. An invoice is a record of what the booking looked like at
 * the moment it was issued — never recomputed from live BookingItems
 * afterwards, even if those items later change.
 */
class InvoiceService
{
    public function createForBooking(Booking $booking, ?float $taxRate = null): Invoice
    {
        $items = $booking->items()->whereNotNull('sell_price')->get();

        if ($items->isEmpty()) {
            throw new InvalidArgumentException('This booking has no priced services to invoice.');
        }

        return DB::transaction(function () use ($booking, $items, $taxRate) {
            $subtotal = (float) $items->sum('sell_price');
            $taxAmount = $taxRate !== null ? round($subtotal * $taxRate / 100, 2) : null;
            $total = $subtotal + ($taxAmount ?? 0);

            $invoice = $booking->invoices()->create([
                'invoice_number' => $this->nextInvoiceNumber($booking->company_id),
                'issued_at' => now()->toDateString(),
                'status' => InvoiceStatus::Issued,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'customer_name_snapshot' => $booking->customer->name,
            ]);

            foreach ($items as $index => $item) {
                $invoice->lineItems()->create([
                    'description' => $item->description,
                    'amount' => $item->sell_price,
                    'sort_order' => $index,
                ]);
            }

            return $invoice;
        });
    }

    private function nextInvoiceNumber(int $companyId): string
    {
        // Postgres rejects FOR UPDATE combined with an aggregate (count()),
        // so lock the actual rows and count them in PHP instead.
        $count = Invoice::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->get()
            ->count();

        return 'INV-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
