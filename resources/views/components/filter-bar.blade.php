@props(['searchName' => 'q', 'searchPlaceholder' => 'Search…', 'searchValue' => null])
<form method="GET" class="mb-4 flex flex-wrap items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2.5">
    <div class="relative flex-1 min-w-[200px]">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
        <input type="search" name="{{ $searchName }}" value="{{ $searchValue }}" placeholder="{{ $searchPlaceholder }}"
               class="w-full rounded-md border-slate-300 pl-8 shadow-sm text-sm">
    </div>
    {{ $slot }}
    @if (request()->anyFilled(array_diff(array_keys(request()->query()), ['page'])))
        <a href="{{ url()->current() }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Clear</a>
    @endif
</form>
