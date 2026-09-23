<!DOCTYPE html>
<html lang="en" class="h-full bg-canvas">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    <div class="min-h-full lg:flex">
        {{-- Mobile top bar + nav --}}
        <details class="lg:hidden bg-brand-900 text-brand-100 group sticky top-0 z-30">
            <summary class="list-none flex items-center justify-between h-14 px-4 text-white cursor-pointer">
                <span class="flex items-center gap-2 text-base font-semibold">
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-600 text-white text-xs font-bold">{{ Str::substr(config('app.name'), 0, 1) }}</span>
                    {{ config('app.name') }}
                </span>
                <span class="flex items-center gap-1.5 text-sm text-brand-200 group-open:hidden">
                    Menu
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>
                </span>
                <span class="hidden text-sm text-brand-200 group-open:inline">Close</span>
            </summary>
            <nav class="px-3 pb-4 border-t border-brand-800/60 pt-3">
                @include('layouts.partials.nav-links')
            </nav>
        </details>

        {{-- Desktop sidebar --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:flex-shrink-0 bg-brand-900 text-brand-100">
            <div class="flex items-center gap-2.5 h-16 px-5 text-white shrink-0 border-b border-white/[0.06]">
                <span class="flex h-8 w-8 items-center justify-center rounded-[9px] bg-gradient-to-br from-brand-500 to-brand-700 text-white text-sm font-bold shadow-sm ring-1 ring-white/10">{{ Str::substr(config('app.name'), 0, 1) }}</span>
                <span class="text-[15px] font-semibold tracking-tight">{{ config('app.name') }}</span>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4">
                @include('layouts.partials.nav-links')
            </nav>

            <div class="border-t border-white/[0.06] p-3">
                <details class="relative">
                    <summary class="list-none flex cursor-pointer items-center gap-2.5 px-2 py-1.5 rounded-md hover:bg-white/[0.05]">
                        <x-avatar :name="auth()->user()->name" class="ring-2 ring-white/10" />
                        <div class="min-w-0 flex-1">
                            <p class="text-[13.5px] font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-brand-400 truncate">{{ auth()->user()->company->name ?? 'Super Admin' }}</p>
                        </div>
                        <svg class="h-4 w-4 text-brand-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                    </summary>
                    <div class="absolute left-0 bottom-full mb-2 w-full bg-white rounded-lg shadow-lg border border-slate-200 py-1.5 text-sm z-20">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-3.5 py-2 text-slate-600 hover:bg-slate-50">Log out</button>
                        </form>
                    </div>
                </details>
            </div>
        </aside>

        <div class="flex-1 min-w-0 flex flex-col">
            {{-- Header --}}
            <header class="h-14 bg-white/90 backdrop-blur border-b border-slate-200 flex items-center gap-3 px-4 sm:px-6 shrink-0 sticky top-0 z-20">
                <h1 class="hidden sm:block text-[15px] font-semibold text-slate-800 shrink-0">@yield('title', 'Dashboard')</h1>

                <div class="flex-1"></div>

                <div class="flex items-center gap-2 sm:gap-3">
                    @hasanyrole('company_admin|company_user')
                        <details class="relative">
                            <summary class="list-none flex cursor-pointer items-center gap-1.5 rounded-md bg-brand-700 text-white text-[13px] font-medium px-3 py-1.5 hover:bg-brand-800 shadow-sm">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                                <span class="hidden sm:inline">Quick add</span>
                            </summary>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1.5 text-sm z-20">
                                <a href="{{ route('leads.create') }}" class="block px-3.5 py-2 hover:bg-slate-50">Lead</a>
                                <a href="{{ route('customers.create') }}" class="block px-3.5 py-2 hover:bg-slate-50">Customer</a>
                                <a href="{{ route('enquiries.create') }}" class="block px-3.5 py-2 hover:bg-slate-50">Enquiry</a>
                                <a href="{{ route('trips.create') }}" class="block px-3.5 py-2 hover:bg-slate-50">Trip</a>
                                <a href="{{ route('suppliers.create') }}" class="block px-3.5 py-2 hover:bg-slate-50">Supplier</a>
                                <a href="{{ route('follow-ups.index') }}" class="block px-3.5 py-2 hover:bg-slate-50">Follow-up</a>
                            </div>
                        </details>
                    @endhasanyrole

                    <details class="relative lg:hidden">
                        <summary class="list-none flex cursor-pointer items-center gap-2 rounded-md px-1.5 py-1 hover:bg-slate-100">
                            <x-avatar :name="auth()->user()->name" size="sm" />
                        </summary>
                        <div class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-slate-200 py-1.5 text-sm z-20">
                            <div class="px-3.5 py-2 border-b border-slate-100">
                                <p class="text-sm font-medium text-slate-700 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ auth()->user()->company->name ?? 'Super Admin' }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-3.5 py-2 text-slate-600 hover:bg-slate-50">Log out</button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                @if (session('status'))
                    <x-alert type="success">{{ session('status') }}</x-alert>
                @endif

                @if ($errors->any())
                    <x-alert type="error">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
