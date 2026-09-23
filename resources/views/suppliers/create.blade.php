@extends('layouts.app')

@section('title', 'Add supplier')

@section('content')
    <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-3">
        <a href="{{ route('suppliers.index') }}" class="hover:text-slate-700">Suppliers</a>
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        <span class="text-slate-600">Add supplier</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Add supplier</h2>
            <p class="mt-0.5 text-sm text-slate-500">Build your partner network by adding a hotel, transport provider or other travel partner.</p>
        </div>
        <x-button tag="a" href="{{ route('suppliers.index') }}" variant="secondary" size="sm">Back to suppliers</x-button>
    </div>

    <form method="POST" action="{{ route('suppliers.store') }}"
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
                   if (!phone && !email) { const p = document.createElement('p'); p.textContent = 'No contact details yet'; p.className = 'text-xs text-slate-400'; contactEl.appendChild(p); }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-0">
                <x-form-section number="01" title="Supplier profile" description="Who is this partner?">
                    <x-field label="Supplier name" name="name" required wide>
                        <input id="name" name="name" value="{{ old('name') }}" required autofocus
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-[15px] py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Type" name="type" hint="Optional — helps you filter your supplier list.">
                        <select id="type" name="type" class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                            <option value="">Select type</option>
                            @foreach (\App\Enums\SupplierType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </x-field>
                </x-form-section>

                <x-form-section number="02" title="Contact information" description="How can your team reach them?">
                    <x-field label="Phone" name="phone">
                        <input id="phone" name="phone" value="{{ old('phone') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="WhatsApp" name="whatsapp" hint="Optional — leave blank if same as phone.">
                        <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Email" name="email" wide>
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                </x-form-section>

                <x-form-section number="03" title="Business details" description="Terms and anything your team should remember.">
                    <x-field label="Payment terms" name="payment_terms" wide hint="e.g. 50% advance, balance on arrival.">
                        <input id="payment_terms" name="payment_terms" value="{{ old('payment_terms') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Notes" name="notes" wide>
                        <textarea id="notes" name="notes" rows="4"
                                  class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">{{ old('notes') }}</textarea>
                    </x-field>
                </x-form-section>

                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-5 pb-2">
                    <p class="text-xs text-slate-400">Changes are saved only when you create the record.</p>
                    <div class="flex items-center gap-2 shrink-0 justify-end">
                        <x-button tag="a" href="{{ route('suppliers.index') }}" variant="secondary">Cancel</x-button>
                        <x-button type="submit">Create supplier</x-button>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
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
    </form>
@endsection
