<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\FollowUp;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return view('dashboard');
        }

        $overdueFollowUps = FollowUp::query()->overdue()->with('followupable')->orderBy('due_at')->limit(5)->get();
        $todayFollowUps = FollowUp::query()->dueToday()->with('followupable')->orderBy('due_at')->limit(5)->get();

        $newLeadsCount = Lead::query()->where('created_at', '>=', now()->subDays(7))->count();
        $newEnquiriesCount = Enquiry::query()->where('created_at', '>=', now()->subDays(7))->count();

        $recentLeads = Lead::query()->latest()->limit(5)->get();
        $recentEnquiries = Enquiry::query()->with('customer')->latest()->limit(5)->get();

        return view('dashboard', compact(
            'overdueFollowUps', 'todayFollowUps',
            'newLeadsCount', 'newEnquiriesCount',
            'recentLeads', 'recentEnquiries',
        ));
    }
}
