<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItineraryTemplate\StoreItineraryTemplateRequest;
use App\Http\Requests\ItineraryTemplate\UpdateItineraryTemplateRequest;
use App\Models\ItineraryTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ItineraryTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ItineraryTemplate::class);

        $templates = ItineraryTemplate::query()
            ->withCount('days')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q');
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%");
                });
            })
            ->orderBy('destination')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('itinerary_templates.index', compact('templates'));
    }

    public function create(): View
    {
        $this->authorize('create', ItineraryTemplate::class);

        return view('itinerary_templates.create');
    }

    public function store(StoreItineraryTemplateRequest $request): RedirectResponse
    {
        $template = DB::transaction(function () use ($request) {
            $template = ItineraryTemplate::create($request->safe()->only(['destination', 'name', 'description', 'is_active']));

            foreach ($request->validated('days') as $dayData) {
                $day = $template->days()->create([
                    'company_id' => $template->company_id,
                    'day_number' => $dayData['day_number'],
                    'notes' => $dayData['notes'] ?? null,
                ]);

                foreach ($dayData['items'] ?? [] as $index => $itemData) {
                    $day->items()->create([
                        'company_id' => $template->company_id,
                        'title' => $itemData['title'],
                        'time' => $itemData['time'] ?? null,
                        'notes' => $itemData['notes'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }

            return $template;
        });

        activity()->performedOn($template)->causedBy($request->user())
            ->log("Itinerary template \"{$template->name}\" created.");

        return redirect()
            ->route('itinerary-templates.index')
            ->with('status', "Template \"{$template->name}\" created.");
    }

    public function edit(ItineraryTemplate $itineraryTemplate): View
    {
        $this->authorize('update', $itineraryTemplate);

        $itineraryTemplate->load('days.items');

        return view('itinerary_templates.edit', ['template' => $itineraryTemplate]);
    }

    public function update(UpdateItineraryTemplateRequest $request, ItineraryTemplate $itineraryTemplate): RedirectResponse
    {
        DB::transaction(function () use ($request, $itineraryTemplate) {
            $itineraryTemplate->update($request->safe()->only(['destination', 'name', 'description', 'is_active']));

            // Editing a template never touches itinerary days already
            // generated from it (source_itinerary_template_day_id is
            // traceability-only) — replacing the template's own day/item
            // records here is safe precisely because of that separation.
            $itineraryTemplate->days()->delete();

            foreach ($request->validated('days') as $dayData) {
                $day = $itineraryTemplate->days()->create([
                    'company_id' => $itineraryTemplate->company_id,
                    'day_number' => $dayData['day_number'],
                    'notes' => $dayData['notes'] ?? null,
                ]);

                foreach ($dayData['items'] ?? [] as $index => $itemData) {
                    $day->items()->create([
                        'company_id' => $itineraryTemplate->company_id,
                        'title' => $itemData['title'],
                        'time' => $itemData['time'] ?? null,
                        'notes' => $itemData['notes'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        activity()->performedOn($itineraryTemplate)->causedBy($request->user())
            ->log("Itinerary template \"{$itineraryTemplate->name}\" updated.");

        return redirect()
            ->route('itinerary-templates.index')
            ->with('status', "Template \"{$itineraryTemplate->name}\" updated.");
    }

    public function toggleActive(ItineraryTemplate $itineraryTemplate): RedirectResponse
    {
        $this->authorize('update', $itineraryTemplate);

        $itineraryTemplate->update(['is_active' => ! $itineraryTemplate->is_active]);

        $event = $itineraryTemplate->is_active ? 'activated' : 'deactivated';
        activity()->performedOn($itineraryTemplate)->causedBy(request()->user())
            ->log("Itinerary template \"{$itineraryTemplate->name}\" {$event}.");

        return back()->with('status', $itineraryTemplate->is_active ? 'Template activated.' : 'Template deactivated.');
    }

    public function destroy(ItineraryTemplate $itineraryTemplate): RedirectResponse
    {
        $this->authorize('delete', $itineraryTemplate);

        $name = $itineraryTemplate->name;
        $itineraryTemplate->delete();

        activity()->causedBy(request()->user())->log("Itinerary template \"{$name}\" deleted.");

        return redirect()
            ->route('itinerary-templates.index')
            ->with('status', "Template \"{$name}\" deleted.");
    }
}
