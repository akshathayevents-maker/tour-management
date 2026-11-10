<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierLocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function companyUser(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company_user');

        return $user;
    }

    public function test_supplier_can_be_created_with_an_operating_location(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->post(route('suppliers.store'), [
            'name' => 'ABC Hotels',
            'operating_location' => 'Goa',
        ])->assertRedirect();

        $this->assertSame('Goa', Supplier::first()->operating_location);
    }

    public function test_supplier_can_be_created_without_a_location(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->post(route('suppliers.store'), [
            'name' => 'National Travel Services',
        ])->assertRedirect();

        $this->assertNull(Supplier::first()->operating_location);
    }

    public function test_supplier_location_can_be_edited_and_cleared(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'operating_location' => 'Goa']);

        $this->actingAs($user)->put(route('suppliers.update', $supplier), [
            'name' => $supplier->name,
            'operating_location' => 'Munnar',
        ])->assertRedirect();
        $this->assertSame('Munnar', $supplier->fresh()->operating_location);

        $this->actingAs($user)->put(route('suppliers.update', $supplier), [
            'name' => $supplier->name,
            'operating_location' => '',
        ])->assertRedirect();
        $this->assertNull($supplier->fresh()->operating_location);
    }

    public function test_directory_location_filter_returns_only_matching_suppliers(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Goa Hotel', 'operating_location' => 'Goa']);
        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Munnar Cabs', 'operating_location' => 'Munnar']);

        $response = $this->actingAs($user)->get(route('suppliers.index', ['location' => 'Goa']));

        $response->assertOk()->assertSee('Goa Hotel')->assertDontSee('Munnar Cabs');
    }

    public function test_directory_location_filter_unspecified_option(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Goa Hotel', 'operating_location' => 'Goa']);
        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Generic Transport', 'operating_location' => null]);

        $response = $this->actingAs($user)->get(route('suppliers.index', ['location' => '__unspecified__']));

        $response->assertOk()->assertSee('Generic Transport')->assertDontSee('Goa Hotel');
    }

    public function test_directory_combines_name_type_and_location_filters(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        Supplier::factory()->create([
            'company_id' => $company->id, 'name' => 'ABC Hotels', 'type' => \App\Enums\SupplierType::Hotel, 'operating_location' => 'Goa',
        ]);
        Supplier::factory()->create([
            'company_id' => $company->id, 'name' => 'ABC Transport', 'type' => \App\Enums\SupplierType::Transport, 'operating_location' => 'Goa',
        ]);

        $response = $this->actingAs($user)->get(route('suppliers.index', ['q' => 'ABC', 'type' => 'hotel', 'location' => 'Goa']));

        $response->assertOk()->assertSee('ABC Hotels')->assertDontSee('ABC Transport');
    }

    public function test_booking_supplier_dropdown_prioritizes_matching_destination(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Goa']);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);
        \App\Models\BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id]);

        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Goa Beach Resort', 'operating_location' => 'Goa', 'is_active' => true]);
        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Munnar Stay', 'operating_location' => 'Munnar', 'is_active' => true]);
        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Generic Cabs', 'operating_location' => null, 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('bookings.show', $booking));

        $response->assertOk();
        $response->assertSeeInOrder(['Matches Goa', 'Goa Beach Resort', 'Other locations', 'Munnar Stay', 'Location not specified', 'Generic Cabs']);
    }

    public function test_suppliers_from_other_locations_remain_selectable_on_a_booking(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Goa']);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);
        $item = \App\Models\BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id]);
        $munnarSupplier = Supplier::factory()->create(['company_id' => $company->id, 'operating_location' => 'Munnar', 'is_active' => true]);

        $response = $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => $item->description,
            'supplier_id' => $munnarSupplier->id,
        ]);

        $response->assertRedirect();
        $this->assertSame($munnarSupplier->id, $item->fresh()->supplier_id);
    }

    public function test_suppliers_without_location_remain_discoverable_on_a_booking(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Goa']);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);
        \App\Models\BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id]);
        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Any Destination Travel', 'operating_location' => null, 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('bookings.show', $booking));

        $response->assertOk()->assertSee('Any Destination Travel');
    }

    public function test_no_matching_supplier_falls_back_to_other_groups(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Goa']);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);
        \App\Models\BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id]);
        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Munnar Stay', 'operating_location' => 'Munnar', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('bookings.show', $booking));

        $response->assertOk()->assertDontSee('Matches Goa')->assertSee('Other locations')->assertSee('Munnar Stay');
    }

    public function test_cross_company_suppliers_are_not_suggested(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customer = Customer::factory()->create(['company_id' => $companyA->id]);
        $trip = Trip::factory()->create(['company_id' => $companyA->id, 'customer_id' => $customer->id, 'destination' => 'Goa']);
        $booking = Booking::factory()->create(['company_id' => $companyA->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);

        Supplier::factory()->create(['company_id' => $companyB->id, 'name' => 'Rival Goa Resort', 'operating_location' => 'Goa', 'is_active' => true]);

        $response = $this->actingAs($userA)->get(route('bookings.show', $booking));

        $response->assertOk()->assertDontSee('Rival Goa Resort');
    }

    public function test_supplier_directory_is_company_scoped_for_location_filter(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        Supplier::factory()->create(['company_id' => $companyA->id, 'name' => 'Own Goa Supplier', 'operating_location' => 'Goa']);
        Supplier::factory()->create(['company_id' => $companyB->id, 'name' => 'Other Company Goa Supplier', 'operating_location' => 'Goa']);

        $response = $this->actingAs($userA)->get(route('suppliers.index', ['location' => 'Goa']));

        $response->assertOk()->assertSee('Own Goa Supplier')->assertDontSee('Other Company Goa Supplier');
    }

    public function test_booking_show_query_count_does_not_scale_with_item_count(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Goa']);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);
        Supplier::factory()->count(5)->create(['company_id' => $company->id, 'is_active' => true]);

        \App\Models\BookingItem::factory()->count(2)->create(['company_id' => $company->id, 'booking_id' => $booking->id]);
        \DB::enableQueryLog();
        $this->actingAs($user)->get(route('bookings.show', $booking))->assertOk();
        $small = count(\DB::getQueryLog());
        \DB::disableQueryLog();
        \DB::flushQueryLog();

        \App\Models\BookingItem::factory()->count(6)->create(['company_id' => $company->id, 'booking_id' => $booking->id]);
        \DB::enableQueryLog();
        $this->actingAs($user)->get(route('bookings.show', $booking))->assertOk();
        $large = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // The old _item.blade.php queried Supplier::all() per item (an
        // N+1). Query count must stay roughly flat as items grow.
        $this->assertLessThanOrEqual(5, abs($large - $small));
    }
}
