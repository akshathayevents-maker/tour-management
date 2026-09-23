<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

/**
 * Lead -> Customer (+ Enquiry) conversion.
 *
 * The lead row is never deleted or overwritten: its source and history
 * stay intact for reporting. Converting only sets lead.customer_id and
 * converted_at, tying the two records together (Lead #L-1001 -> Customer
 * #C-1001) without duplicating the lead's contact info if it's already
 * pointed at a customer.
 */
class LeadConversionService
{
    public function convert(Lead $lead): Customer
    {
        if ($lead->isConverted()) {
            return $lead->customer;
        }

        return DB::transaction(function () use ($lead) {
            $customer = Customer::create([
                'name' => $lead->name,
                'phone' => $lead->phone,
            ]);

            $lead->forceFill([
                'customer_id' => $customer->id,
                'converted_at' => now(),
                'status' => $lead->status->isOpen() ? LeadStatus::Won : $lead->status,
            ])->save();

            // A destination is the only thing an enquiry truly needs; if the
            // lead already captured one, carry it straight over so the user
            // doesn't retype it.
            if (filled($lead->destination)) {
                Enquiry::create([
                    'customer_id' => $customer->id,
                    'lead_id' => $lead->id,
                    'destination' => $lead->destination,
                    'start_date' => $lead->travel_month,
                    'adults' => $lead->travellers_count,
                    'budget' => $lead->budget,
                    'notes' => $lead->notes,
                ]);
            }

            return $customer;
        });
    }
}
