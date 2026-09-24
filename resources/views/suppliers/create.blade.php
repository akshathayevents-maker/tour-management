@extends('layouts.app')

@section('title', 'Add supplier')

@section('content')
    <a href="{{ route('suppliers.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-700 mb-3">
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
        Suppliers
    </a>

    <div class="mb-5">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-500 mb-1">New partner</p>
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Add supplier</h2>
        <p class="mt-0.5 text-sm text-slate-500 max-w-lg">Build your partner network by adding a hotel, transport provider or other travel partner.</p>
    </div>

    {{-- Mobile compact summary --}}
    <div class="md:hidden mb-5 bg-white rounded-xl border border-slate-200/80 px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Summary</p>
            <p id="mobile-summary-text" class="text-sm text-slate-700 truncate">Fill in the form to build a summary</p>
        </div>
        <span class="shrink-0 inline-flex items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 px-2 py-0.5 text-[11px] font-medium">New supplier</span>
    </div>

    <form method="POST" action="{{ route('suppliers.store') }}" class="pb-24 md:pb-0" id="supplier-form"
          oninput="const nameVal = document.getElementById('name').value.trim();
                   document.getElementById('preview-name').textContent = nameVal || 'New supplier';
                   document.getElementById('preview-initials').textContent = nameVal ? nameVal.split(' ').map(p => p[0]).slice(0,2).join('').toUpperCase() : '—';
                   const typeSel = document.getElementById('type');
                   const typeLabel = typeSel.value ? typeSel.options[typeSel.selectedIndex].text : null;
                   document.getElementById('preview-type').textContent = typeLabel || 'Partner profile';
                   const phone = document.getElementById('phone').value.trim();
                   const email = document.getElementById('email').value.trim();
                   const contactEl = document.getElementById('preview-contact');
                   contactEl.innerHTML = '';
                   if (phone) { const p = document.createElement('p'); p.textContent = phone; p.className = 'text-sm text-slate-700'; contactEl.appendChild(p); }
                   if (email) { const p = document.createElement('p'); p.textContent = email; p.className = 'text-sm text-slate-500'; contactEl.appendChild(p); }
                   if (!phone && !email) { const p = document.createElement('p'); p.textContent = 'No contact details yet'; p.className = 'text-xs text-slate-400'; contactEl.appendChild(p); }
                   document.getElementById('mobile-summary-text').textContent = [nameVal, typeLabel].filter(Boolean).join(' · ') || 'Fill in the form to build a summary';">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-8">
            {{-- Main form --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/80 px-5 sm:px-7">
                {{-- Supplier profile --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">01</span>
                        <h3 class="text-sm font-semibold text-slate-900">Supplier profile</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Who is this partner?</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="form-label">Supplier name <span class="form-required">*</span></label>
                            <input id="name" name="name" value="{{ old('name') }}" required autofocus
                                   placeholder="Enter supplier's name"
                                   class="form-input">
                            @error('name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="type" class="form-label">Type</label>
                            <select id="type" name="type" class="form-select">
                                <option value="">Select type</option>
                                @foreach (\App\Enums\SupplierType::cases() as $type)
                                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            <p class="form-help">Optional — helps you filter your supplier list.</p>
                            @error('type')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- Contact details --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">02</span>
                        <h3 class="text-sm font-semibold text-slate-900">Contact details</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">How can your team reach them?</p>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="phone" class="form-label">Phone</label>
                                <input id="phone" name="phone" value="{{ old('phone') }}"
                                       type="tel" inputmode="tel" autocomplete="tel"
                                       class="form-input">
                                @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="whatsapp" class="form-label">WhatsApp</label>
                                <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"
                                       placeholder="Optional — leave blank if same as phone"
                                       class="form-input">
                                @error('whatsapp')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                   class="form-input">
                            @error('email')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- Business details --}}
                <section class="py-5 sm:py-6">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">03</span>
                        <h3 class="text-sm font-semibold text-slate-900">Business details</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Terms and anything your team should remember.</p>

                    <div class="space-y-4">
                        <div>
                            <label for="payment_terms" class="form-label">Payment terms</label>
                            <input id="payment_terms" name="payment_terms" value="{{ old('payment_terms') }}"
                                   placeholder="e.g. 50% advance, balance on arrival"
                                   class="form-input">
                            @error('payment_terms')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="form-textarea leading-relaxed" style="min-height: 100px">{{ old('notes') }}</textarea>
                            @error('notes')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- Desktop actions --}}
                <div class="hidden md:flex items-center justify-end gap-2 py-5 border-t border-slate-100">
                    <x-button tag="a" href="{{ route('suppliers.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit">Create supplier</x-button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="hidden lg:block lg:sticky lg:top-20 lg:self-start space-y-4">
                <x-context-card title="Supplier preview">
                    <div class="text-center pb-4">
                        <span id="preview-initials" class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-600 text-lg font-semibold">{{ old('name') ? collect(explode(' ', trim(old('name'))))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('') : '—' }}</span>
                        <p id="preview-name" class="mt-3 text-sm font-semibold text-slate-900">{{ old('name') ?: 'New supplier' }}</p>
                        <p id="preview-type" class="text-xs text-slate-400">
                            {{ old('type') ? \App\Enums\SupplierType::from(old('type'))->label() : 'Partner profile' }}
                        </p>
                    </div>
                    <div id="preview-contact" class="space-y-1 border-t border-slate-50 pt-4">
                        @if (old('phone') || old('email'))
                            @if (old('phone'))<p class="text-sm text-slate-700">{{ old('phone') }}</p>@endif
                            @if (old('email'))<p class="text-sm text-slate-500">{{ old('email') }}</p>@endif
                        @else
                            <p class="text-xs text-slate-400">No contact details yet</p>
                        @endif
                    </div>
                </x-context-card>

                <x-context-card title="What happens next">
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Once created, this supplier can be linked to trip services and supplier payments.
                    </p>
                </x-context-card>
            </div>
        </div>

        {{-- Mobile sticky action bar --}}
        <div class="md:hidden fixed bottom-0 inset-x-0 z-20 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex items-center gap-2" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
            <x-button tag="a" href="{{ route('suppliers.index') }}" variant="secondary" class="flex-1 justify-center">Cancel</x-button>
            <x-button type="submit" class="flex-1 justify-center">Create supplier</x-button>
        </div>
    </form>
@endsection
