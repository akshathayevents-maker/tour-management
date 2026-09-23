<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\ItineraryDayController;
use App\Http\Controllers\ItineraryItemController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\QuotationLineItemController;
use App\Http\Controllers\QuotationVersionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Platform-level: super admin only (also hard-enforced by CompanyPolicy).
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('companies', CompanyController::class)->except(['show', 'destroy']);
        Route::patch('companies/{company}/toggle-active', [CompanyController::class, 'toggleActive'])
            ->name('companies.toggle-active');
    });

    // Staff management: super admin (all companies) + company admin (own company).
    Route::middleware('role:super_admin|company_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
            ->name('users.toggle-active');
    });

    // Business data: company staff only. Super admin has no company and
    // has no reason to touch operational records.
    Route::middleware('role:company_admin|company_user')->group(function () {
        Route::resource('customers', CustomerController::class)->except(['destroy']);
        Route::resource('leads', LeadController::class)->except(['destroy']);
        Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
        Route::resource('enquiries', EnquiryController::class)->except(['destroy']);

        Route::resource('trips', TripController::class)->except(['destroy']);
        Route::post('trips/{trip}/itinerary', [ItineraryController::class, 'store'])->name('trips.itinerary.store');
        Route::patch('itineraries/{itinerary}', [ItineraryController::class, 'update'])->name('itineraries.update');

        Route::post('itineraries/{itinerary}/days', [ItineraryDayController::class, 'store'])->name('itinerary-days.store');
        Route::patch('itinerary-days/{itineraryDay}', [ItineraryDayController::class, 'update'])->name('itinerary-days.update');
        Route::delete('itinerary-days/{itineraryDay}', [ItineraryDayController::class, 'destroy'])->name('itinerary-days.destroy');

        Route::post('itinerary-days/{itineraryDay}/items', [ItineraryItemController::class, 'store'])->name('itinerary-items.store');
        Route::patch('itinerary-items/{itineraryItem}', [ItineraryItemController::class, 'update'])->name('itinerary-items.update');
        Route::delete('itinerary-items/{itineraryItem}', [ItineraryItemController::class, 'destroy'])->name('itinerary-items.destroy');
        Route::post('itinerary-items/{itineraryItem}/move', [ItineraryItemController::class, 'move'])->name('itinerary-items.move');

        Route::resource('suppliers', SupplierController::class)->except(['destroy']);

        Route::post('trips/{trip}/quotation', [QuotationController::class, 'store'])->name('trips.quotation.store');

        Route::get('quotation-versions/{quotationVersion}', [QuotationVersionController::class, 'show'])->name('quotation-versions.show');
        Route::patch('quotation-versions/{quotationVersion}', [QuotationVersionController::class, 'update'])->name('quotation-versions.update');
        Route::post('quotation-versions/{quotationVersion}/send', [QuotationVersionController::class, 'send'])->name('quotation-versions.send');
        Route::post('quotation-versions/{quotationVersion}/accept', [QuotationVersionController::class, 'accept'])->name('quotation-versions.accept');
        Route::post('quotation-versions/{quotationVersion}/reject', [QuotationVersionController::class, 'reject'])->name('quotation-versions.reject');
        Route::post('quotation-versions/{quotationVersion}/new-version', [QuotationVersionController::class, 'newVersion'])->name('quotation-versions.new-version');

        Route::post('quotation-versions/{quotationVersion}/line-items', [QuotationLineItemController::class, 'store'])->name('quotation-line-items.store');
        Route::patch('quotation-line-items/{quotationLineItem}', [QuotationLineItemController::class, 'update'])->name('quotation-line-items.update');
        Route::delete('quotation-line-items/{quotationLineItem}', [QuotationLineItemController::class, 'destroy'])->name('quotation-line-items.destroy');

        Route::get('follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
        Route::post('follow-ups', [FollowUpController::class, 'store'])->name('follow-ups.store');
        Route::patch('follow-ups/{followUp}/complete', [FollowUpController::class, 'complete'])->name('follow-ups.complete');
        Route::patch('follow-ups/{followUp}/reschedule', [FollowUpController::class, 'reschedule'])->name('follow-ups.reschedule');
        Route::patch('follow-ups/{followUp}/cancel', [FollowUpController::class, 'cancel'])->name('follow-ups.cancel');
    });
});
