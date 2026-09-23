{{-- Expects $item (BookingItem with supplier loaded), $locked (bool) --}}
@php $locked = $locked ?? false; @endphp
<div class="py-3 border-b border-slate-50 last:border-0 text-sm">
    <div class="flex items-start justify-between gap-2">
        <div class="flex-1 min-w-0">
            <span class="text-slate-900 font-medium">{{ $item->description }}</span>
            @if ($item->category)
                <span class="text-xs text-slate-400">&middot; {{ $item->category->label() }}</span>
            @endif
            <div class="text-xs text-slate-500 mt-0.5 space-x-2">
                @if ($item->supplier || $item->supplier_name_snapshot)
                    <span>{{ $item->supplier?->name ?? $item->supplier_name_snapshot }}</span>
                @endif
                @if ($item->start_at)
                    <span>{{ $item->start_at->format('d M Y') }}@if($item->end_at) &ndash; {{ $item->end_at->format('d M Y') }}@endif</span>
                @endif
                @if ($item->confirmation_number)
                    <span>Ref: {{ $item->confirmation_number }}</span>
                @endif
            </div>
            @if ($item->notes)
                <p class="text-xs text-slate-400 mt-0.5">{{ $item->notes }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if ($item->sell_price !== null)
                <x-currency :amount="$item->sell_price" size="sm" />
            @endif
            <x-status-badge :color="$item->status->badgeColor()" :label="$item->status->label()" />
        </div>
    </div>

    @unless ($locked)
        <div class="flex flex-wrap items-center gap-2 mt-2">
            @if ($item->status !== \App\Enums\BookingItemStatus::Cancelled)
                @foreach (\App\Enums\BookingItemStatus::cases() as $status)
                    @continue($status === $item->status)
                    <form method="POST" action="{{ route('booking-items.status', $item) }}"
                          @if($status === \App\Enums\BookingItemStatus::Cancelled) onsubmit="return confirm('Cancel this service?');" @endif>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $status->value }}">
                        @if ($status === \App\Enums\BookingItemStatus::Cancelled)
                            <button type="submit" class="text-xs rounded-md border border-slate-200 px-2 py-1 text-red-500 hover:bg-red-50 hover:text-red-700 hover:border-red-200">
                                Mark {{ $status->label() }}
                            </button>
                        @else
                            <button type="submit" class="text-xs rounded-md border border-slate-300 px-2 py-1 text-slate-600 hover:bg-slate-50">
                                Mark {{ $status->label() }}
                            </button>
                        @endif
                    </form>
                @endforeach
            @endif

            <details class="relative">
                <summary class="list-none cursor-pointer text-xs text-slate-500 hover:text-slate-900 px-2 py-1">Edit</summary>
                <form method="POST" action="{{ route('booking-items.update', $item) }}"
                      class="absolute left-0 mt-1 w-64 bg-white rounded-md shadow-lg border border-slate-200 p-3 z-10 space-y-2">
                    @csrf
                    @method('PATCH')
                    <input name="description" value="{{ $item->description }}" required
                           class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                    <select name="category" class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                        <option value="">Category</option>
                        @foreach (\App\Enums\QuotationLineCategory::cases() as $category)
                            <option value="{{ $category->value }}" @selected($item->category === $category)>{{ $category->label() }}</option>
                        @endforeach
                    </select>
                    <select name="supplier_id" class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                        <option value="">Supplier (optional)</option>
                        @foreach (\App\Models\Supplier::where('is_active', true)->orderBy('name')->get() as $supplier)
                            <option value="{{ $supplier->id }}" @selected($item->supplier_id === $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" min="0" name="sell_price" value="{{ $item->sell_price }}" placeholder="Selling price"
                           class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                    <input type="number" step="0.01" min="0" name="cost" value="{{ $item->cost }}" placeholder="Cost (optional)"
                           class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                    <input name="confirmation_number" value="{{ $item->confirmation_number }}" placeholder="Confirmation number"
                           class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                    <input name="notes" value="{{ $item->notes }}" placeholder="Notes"
                           class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                    <button type="submit" class="w-full rounded-md bg-brand-700 text-white text-xs font-medium px-3 py-1.5 hover:bg-brand-800">
                        Save
                    </button>
                </form>
            </details>
        </div>
    @endunless
</div>
