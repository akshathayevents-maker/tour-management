<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Lead;
use App\Services\LeadConversionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LeadConversionTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_conversion_rolls_back_completely_if_any_step_fails(): void
    {
        $company = Company::factory()->create();
        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'destination' => 'Goa',
        ]);

        // Force the enquiry half of the conversion to blow up mid-transaction.
        Enquiry::creating(function () {
            throw new RuntimeException('forced failure');
        });

        try {
            app(LeadConversionService::class)->convert($lead);
            $this->fail('Expected exception was not thrown.');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(0, Customer::count());
        $this->assertFalse($lead->fresh()->isConverted());
        $this->assertNull($lead->fresh()->converted_at);
    }
}
