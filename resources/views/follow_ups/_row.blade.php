{{-- Expects $followUp, $urgency ('overdue'|'today'|'upcoming'), $destination (nullable string) --}}

{{-- Desktop row --}}
<div class="hidden sm:flex items-center gap-3.5 px-4 py-3.5">
    <div class="w-16 shrink-0 text-center">
        <p class="text-sm font-semibold {{ $urgency === 'overdue' ? 'text-red-600' : 'text-slate-700' }}">{{ $followUp->due_at->format('g:i A') }}</p>
        @if ($urgency === 'upcoming')
            <p class="text-[11px] text-slate-400 mt-0.5">{{ $followUp->due_at->format('d M') }}</p>
        @endif
    </div>

    <span class="h-8 w-px shrink-0 {{ $urgency === 'overdue' ? 'bg-red-200' : 'bg-slate-100' }}"></span>

    <x-avatar :name="$followUp->subjectLabel()" size="sm" />

    <div class="min-w-0 flex-1">
        <a href="{{ $followUp->subjectUrl() }}" class="text-sm font-medium text-slate-900 hover:text-brand-700 truncate block">
            {{ $followUp->subjectLabel() }}
        </a>
        @if ($destination)
            <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5 min-w-0">
                <svg class="h-3 w-3 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                <span class="truncate">{{ $destination }}</span>
            </p>
        @endif
        @if ($followUp->reason)
            <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $followUp->reason }}</p>
        @endif
    </div>

    @if ($urgency === 'overdue')
        <span class="shrink-0 text-xs font-medium text-red-600">{{ $followUp->due_at->diffForHumans(null, true) }} overdue</span>
    @endif

    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ $followUp->subjectUrl() }}" class="text-xs font-medium text-slate-600 hover:text-brand-700 rounded-md border border-slate-200 px-2.5 py-1.5 hover:border-brand-200 hover:bg-brand-50/50 transition-colors">Open</a>

        <details class="relative">
            <summary class="list-none text-xs font-medium text-slate-600 hover:text-brand-700 rounded-md border border-slate-200 px-2.5 py-1.5 hover:border-brand-200 hover:bg-brand-50/50 transition-colors cursor-pointer select-none">Reschedule</summary>
            <form method="POST" action="{{ route('follow-ups.reschedule', $followUp) }}" class="absolute right-0 top-full mt-1.5 z-10 w-64 bg-white rounded-lg border border-slate-200 shadow-lg p-3.5">
                @csrf @method('PATCH')
                <label class="block text-xs font-medium text-slate-600 mb-1">New due date &amp; time</label>
                <input type="datetime-local" name="due_at" required
                       value="{{ $followUp->due_at->format('Y-m-d\TH:i') }}"
                       class="w-full rounded-md border border-slate-300 text-sm px-2.5 py-1.5 mb-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" />
                <label class="block text-xs font-medium text-slate-600 mb-1">Notes (optional)</label>
                <textarea name="notes" rows="2" class="w-full rounded-md border border-slate-300 text-sm px-2.5 py-1.5 mb-2.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">{{ $followUp->notes }}</textarea>
                <button class="w-full text-xs font-medium text-white bg-brand-700 hover:bg-brand-800 rounded-md px-2.5 py-1.5">Save new time</button>
            </form>
        </details>

        <form method="POST" action="{{ route('follow-ups.cancel', $followUp) }}"
              onsubmit="return confirm('Cancel this follow-up? It will no longer appear as pending.');">
            @csrf @method('PATCH')
            <button class="text-xs font-medium text-slate-500 hover:text-red-700 rounded-md border border-slate-200 px-2.5 py-1.5 hover:bg-red-50 hover:border-red-200 transition-colors">Cancel</button>
        </form>

        <form method="POST" action="{{ route('follow-ups.complete', $followUp) }}">
            @csrf @method('PATCH')
            <button class="text-xs font-medium text-emerald-700 hover:text-emerald-800 rounded-md border border-slate-200 px-2.5 py-1.5 hover:bg-emerald-50 hover:border-emerald-200 transition-colors">Complete</button>
        </form>
    </div>
</div>

{{-- Mobile card --}}
<div class="sm:hidden px-3.5 py-3">
    <div class="flex items-start gap-2.5">
        <x-avatar :name="$followUp->subjectLabel()" size="sm" />
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <a href="{{ $followUp->subjectUrl() }}" class="text-sm font-medium text-slate-900 truncate">{{ $followUp->subjectLabel() }}</a>
                <span class="shrink-0 text-xs font-semibold {{ $urgency === 'overdue' ? 'text-red-600' : 'text-slate-700' }}">
                    {{ $urgency === 'upcoming' ? $followUp->due_at->format('d M, g:i A') : $followUp->due_at->format('g:i A') }}
                </span>
            </div>
            @if ($destination)
                <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5 min-w-0">
                    <svg class="h-3 w-3 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                    <span class="truncate">{{ $destination }}</span>
                </p>
            @endif
            @if ($followUp->reason)
                <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $followUp->reason }}</p>
            @endif
            @if ($urgency === 'overdue')
                <p class="text-xs font-medium text-red-600 mt-0.5">{{ $followUp->due_at->diffForHumans(null, true) }} overdue</p>
            @endif

            <div class="mt-2.5 flex items-center gap-2">
                <a href="{{ $followUp->subjectUrl() }}" class="flex-1 text-center text-xs font-medium text-slate-600 rounded-md border border-slate-200 px-2.5 py-2 hover:bg-slate-50">Open</a>
                <form method="POST" action="{{ route('follow-ups.complete', $followUp) }}" class="flex-1">
                    @csrf @method('PATCH')
                    <button class="w-full text-xs font-medium text-emerald-700 rounded-md border border-slate-200 px-2.5 py-2 hover:bg-emerald-50">Complete</button>
                </form>
            </div>

            <div class="mt-2 flex items-center gap-2">
                <details class="flex-1">
                    <summary class="list-none text-center text-xs font-medium text-slate-600 rounded-md border border-slate-200 px-2.5 py-2 hover:bg-slate-50 cursor-pointer select-none">Reschedule</summary>
                    <form method="POST" action="{{ route('follow-ups.reschedule', $followUp) }}" class="mt-1.5 bg-white rounded-lg border border-slate-200 p-3">
                        @csrf @method('PATCH')
                        <label class="block text-xs font-medium text-slate-600 mb-1">New due date &amp; time</label>
                        <input type="datetime-local" name="due_at" required
                               value="{{ $followUp->due_at->format('Y-m-d\TH:i') }}"
                               class="w-full rounded-md border border-slate-300 text-sm px-2.5 py-1.5 mb-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" />
                        <label class="block text-xs font-medium text-slate-600 mb-1">Notes (optional)</label>
                        <textarea name="notes" rows="2" class="w-full rounded-md border border-slate-300 text-sm px-2.5 py-1.5 mb-2.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">{{ $followUp->notes }}</textarea>
                        <button class="w-full text-xs font-medium text-white bg-brand-700 hover:bg-brand-800 rounded-md px-2.5 py-1.5">Save new time</button>
                    </form>
                </details>
                <form method="POST" action="{{ route('follow-ups.cancel', $followUp) }}" class="flex-1"
                      onsubmit="return confirm('Cancel this follow-up? It will no longer appear as pending.');">
                    @csrf @method('PATCH')
                    <button class="w-full text-xs font-medium text-slate-500 rounded-md border border-slate-200 px-2.5 py-2 hover:bg-red-50 hover:text-red-700">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>
