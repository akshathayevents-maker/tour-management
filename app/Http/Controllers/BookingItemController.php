<?php

namespace App\Http\Controllers;

use App\Enums\BookingItemStatus;
use App\Http\Requests\BookingItem\StoreBookingItemRequest;
use App\Http\Requests\BookingItem\UpdateBookingItemRequest;
use App\Http\Requests\BookingItem\UpdateBookingItemStatusRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Http\RedirectResponse;

class BookingItemController extends Controller
{
    public function store(StoreBookingItemRequest $request, Booking $booking): RedirectResponse
    {
        if ($booking->isOperationallyLocked()) {
            abort(422, 'This booking is '.$booking->status->label().' — services can no longer be added.');
        }

        $booking->items()->create($request->validated());

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Service added.');
    }

    public function update(UpdateBookingItemRequest $request, BookingItem $bookingItem): RedirectResponse
    {
        if ($bookingItem->booking->isOperationallyLocked()) {
            abort(422, 'This booking is '.$bookingItem->booking->status->label().' — services can no longer be edited.');
        }

        $originalSupplierId = $bookingItem->supplier_id;

        $bookingItem->update($request->validated());

        if ($request->integer('supplier_id') !== $originalSupplierId) {
            activity()->performedOn($bookingItem->booking)->causedBy($request->user())
                ->log("Supplier changed on \"{$bookingItem->description}\".");
        }

        return redirect()
            ->route('bookings.show', $bookingItem->booking)
            ->with('status', 'Service updated.');
    }

    public function updateStatus(UpdateBookingItemStatusRequest $request, BookingItem $bookingItem): RedirectResponse
    {
        if ($bookingItem->booking->isOperationallyLocked()) {
            abort(422, 'This booking is '.$bookingItem->booking->status->label().' — service status can no longer be changed.');
        }

        $status = BookingItemStatus::from($request->string('status')->value());

        $attributes = ['status' => $status];

        if ($status === BookingItemStatus::Confirmed) {
            $supplier = $bookingItem->supplier;
            $attributes['supplier_name_snapshot'] = $supplier?->name;
            $attributes['supplier_phone_snapshot'] = $supplier?->phone;
        }

        if ($status === BookingItemStatus::Cancelled) {
            $attributes['cancelled_at'] = now();
            $attributes['cancellation_reason'] = $request->string('cancellation_reason')->value() ?: null;
        }

        $bookingItem->update($attributes);

        activity()->performedOn($bookingItem->booking)->causedBy($request->user())
            ->log("\"{$bookingItem->description}\" status changed to {$status->label()}.");

        return redirect()
            ->route('bookings.show', $bookingItem->booking)
            ->with('status', "Marked {$status->label()}.");
    }
}
