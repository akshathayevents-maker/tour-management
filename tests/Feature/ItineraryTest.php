<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Itinerary;
use App\Models\ItineraryDay;
use App\Models\ItineraryItem;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryTest extends TestCase
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

    public function test_company_can_create_itinerary(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('trips.itinerary.store', $trip))
            ->assertRedirect(route('trips.show', $trip));

        $this->assertSame(1, Itinerary::count());
        $this->assertSame($company->id, Itinerary::first()->company_id);
    }

    private function itineraryForCompany(Company $company): Itinerary
    {
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        return Itinerary::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);
    }

    private function itineraryDayForCompany(Company $company): ItineraryDay
    {
        $itinerary = $this->itineraryForCompany($company);

        return ItineraryDay::factory()->create(['company_id' => $company->id, 'itinerary_id' => $itinerary->id]);
    }

    public function test_days_belong_to_correct_itinerary(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $itinerary = $this->itineraryForCompany($company);

        $this->actingAs($user)->post(route('itinerary-days.store', $itinerary), [
            'day_number' => 1,
        ])->assertRedirect();

        $day = ItineraryDay::first();
        $this->assertTrue($day->itinerary->is($itinerary));
    }

    public function test_items_belong_to_correct_day(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $day = $this->itineraryDayForCompany($company);

        $this->actingAs($user)->post(route('itinerary-items.store', $day), [
            'title' => 'Airport pickup',
        ])->assertRedirect();

        $item = ItineraryItem::first();
        $this->assertTrue($item->itineraryDay->is($day));
    }

    public function test_ordering_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $day = $this->itineraryDayForCompany($company);
        $first = ItineraryItem::factory()->create(['company_id' => $company->id, 'itinerary_day_id' => $day->id, 'sort_order' => 0, 'title' => 'First']);
        $second = ItineraryItem::factory()->create(['company_id' => $company->id, 'itinerary_day_id' => $day->id, 'sort_order' => 1, 'title' => 'Second']);

        $this->actingAs($user)->post(route('itinerary-items.move', $first), ['direction' => 'down'])
            ->assertRedirect();

        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertSame(0, $second->fresh()->sort_order);
    }

    public function test_cross_company_itinerary_cannot_be_accessed(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $itineraryB = $this->itineraryForCompany($companyB);

        $this->actingAs($userA)->post(route('itinerary-days.store', $itineraryB), [
            'day_number' => 1,
        ])->assertNotFound();
    }

    public function test_cross_company_day_cannot_be_accessed(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $dayB = $this->itineraryDayForCompany($companyB);

        $this->actingAs($userA)->post(route('itinerary-items.store', $dayB), [
            'title' => 'Should not work',
        ])->assertNotFound();
    }

    public function test_nested_idor_trip_id_valid_but_itinerary_belongs_to_other_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        Trip::factory()->create(['company_id' => $companyA->id]);
        $itineraryB = $this->itineraryForCompany($companyB);

        // Even though Company A has a valid, owned trip, posting to update
        // an itinerary that belongs to Company B must never succeed.
        $this->actingAs($userA)->patch(route('itineraries.update', $itineraryB), [
            'notes' => 'hacked',
        ])->assertNotFound();

        $this->assertNotSame('hacked', $itineraryB->fresh()->notes);
    }

    public function test_item_cannot_be_moved_in_another_companys_itinerary(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $dayB = $this->itineraryDayForCompany($companyB);
        $itemB = ItineraryItem::factory()->create(['company_id' => $companyB->id, 'itinerary_day_id' => $dayB->id, 'sort_order' => 0]);

        $this->actingAs($userA)->post(route('itinerary-items.move', $itemB), ['direction' => 'down'])
            ->assertNotFound();
    }

    public function test_item_cannot_be_edited_in_another_companys_itinerary(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $dayB = $this->itineraryDayForCompany($companyB);
        $itemB = ItineraryItem::factory()->create(['company_id' => $companyB->id, 'itinerary_day_id' => $dayB->id, 'title' => 'Original']);

        $this->actingAs($userA)->patch(route('itinerary-items.update', $itemB), ['title' => 'Hacked'])
            ->assertNotFound();
        $this->actingAs($userA)->delete(route('itinerary-items.destroy', $itemB))
            ->assertNotFound();

        $this->assertSame('Original', $itemB->fresh()->title);
    }

    public function test_day_numbers_cannot_duplicate_within_an_itinerary(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $itinerary = $this->itineraryForCompany($company);
        ItineraryDay::factory()->create(['company_id' => $company->id, 'itinerary_id' => $itinerary->id, 'day_number' => 1]);

        $this->actingAs($user)->post(route('itinerary-days.store', $itinerary), [
            'day_number' => 1,
        ])->assertSessionHasErrors('day_number');

        $this->assertSame(1, ItineraryDay::count());
    }

    public function test_same_day_number_is_allowed_across_different_itineraries(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $itineraryOne = $this->itineraryForCompany($company);
        $itineraryTwo = $this->itineraryForCompany($company);
        ItineraryDay::factory()->create(['company_id' => $company->id, 'itinerary_id' => $itineraryOne->id, 'day_number' => 1]);

        $this->actingAs($user)->post(route('itinerary-days.store', $itineraryTwo), [
            'day_number' => 1,
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(2, ItineraryDay::count());
    }
}
