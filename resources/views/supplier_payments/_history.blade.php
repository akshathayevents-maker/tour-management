{{-- Expects $item (BookingItem with supplierPayments loaded) --}}
@if ($item->supplierPayments->isNotEmpty())
    <div class="mt-2 space-y-1">
        @foreach ($item->supplierPayments as $payment)
            <div class="flex items-center justify-between gap-2 text-xs">
                <span class="{{ $payment->isVoided() ? 'text-slate-400 line-through' : 'text-slate-500' }}">
                    {{ $payment->paid_at->format('d M Y') }}
                    @if ($payment->supplier_name_snapshot) &middot; paid to {{ $payment->supplier_name_snapshot }} @endif
                    @if ($payment->method) &middot; {{ $payment->method->label() }} @endif
                    @if ($payment->isVoided()) &middot; voided: {{ $payment->voided_reason }} @endif
                </span>
                <span class="flex items-center gap-2 shrink-0">
                    <span class="{{ $payment->isVoided() ? 'text-slate-400 line-through' : 'text-slate-700' }} tabular-nums">
                        {{ number_format($payment->amount, 2) }}
                    </span>
                    @unless ($payment->isVoided())
                        <details class="relative">
                            <summary class="list-none cursor-pointer text-slate-400 hover:text-red-600">Void</summary>
                            <form method="POST" action="{{ route('supplier-payments.void', $payment) }}"
                                  class="absolute right-0 mt-1 w-56 bg-white rounded-md shadow-lg border border-slate-200 p-3 z-10 space-y-2">
                                @csrf
                                <input name="reason" placeholder="Reason" required
                                       class="block w-full rounded-md border-slate-300 shadow-sm text-xs">
                                <button type="submit" class="w-full rounded-md bg-red-600 text-white text-xs font-medium px-3 py-1.5 hover:bg-red-700">
                                    Confirm void
                                </button>
                            </form>
                        </details>
                    @endunless
                </span>
            </div>
        @endforeach
    </div>
@endif
