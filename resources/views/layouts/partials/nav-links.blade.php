@php
    $groups = [];

    $groups['Overview'] = [
        ['route' => 'dashboard', 'match' => ['dashboard'], 'label' => 'Dashboard', 'icon' => 'home'],
    ];

    if (auth()->user()->hasAnyRole(['company_admin', 'company_user'])) {
        $groups['Sales'] = [
            ['route' => 'leads.index', 'match' => ['leads.*'], 'label' => 'Leads', 'icon' => 'bolt'],
            ['route' => 'enquiries.index', 'match' => ['enquiries.*'], 'label' => 'Enquiries', 'icon' => 'chat'],
            ['route' => 'customers.index', 'match' => ['customers.*'], 'label' => 'Customers', 'icon' => 'users'],
            ['route' => 'follow-ups.index', 'match' => ['follow-ups.*'], 'label' => 'Follow-ups', 'icon' => 'bell'],
        ];

        $groups['Operations'] = [
            ['route' => 'trips.index', 'match' => ['trips.*', 'itineraries.*', 'quotation-versions.*'], 'label' => 'Trips', 'icon' => 'map'],
            ['route' => 'bookings.index', 'match' => ['bookings.*', 'invoices.*'], 'label' => 'Bookings', 'icon' => 'check'],
            ['route' => 'suppliers.index', 'match' => ['suppliers.*'], 'label' => 'Suppliers', 'icon' => 'truck'],
        ];
    }

    if (auth()->user()->isSuperAdmin()) {
        $groups['Administration'] = array_filter([
            ['route' => 'admin.companies.index', 'match' => ['admin.companies.*'], 'label' => 'Companies', 'icon' => 'building'],
            auth()->user()->hasAnyRole(['super_admin', 'company_admin']) ? ['route' => 'admin.users.index', 'match' => ['admin.users.*'], 'label' => 'Users', 'icon' => 'user-group'] : null,
        ]);
    } elseif (auth()->user()->hasRole('company_admin')) {
        $groups['Administration'] = [
            ['route' => 'admin.users.index', 'match' => ['admin.users.*'], 'label' => 'Users', 'icon' => 'user-group'],
        ];
    }

    $icons = [
        'home' => 'M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 11-1.06 1.06l-.97-.97V19.5a1.5 1.5 0 01-1.5 1.5h-3.25a.75.75 0 01-.75-.75V16a1 1 0 00-1-1h-1.36a1 1 0 00-1 1v4.25a.75.75 0 01-.75.75H6.31a1.5 1.5 0 01-1.5-1.5v-6.88l-.97.97a.75.75 0 11-1.06-1.06l8.69-8.69z',
        'bolt' => 'M13.5 2.25a.75.75 0 01.671.415l1.036 2.073 2.286.332a.75.75 0 01.416 1.279l-1.655 1.613.391 2.276a.75.75 0 01-1.088.79L13.5 9.876l-2.057 1.152a.75.75 0 01-1.088-.79l.39-2.276L9.09 6.35a.75.75 0 01.416-1.28l2.286-.331 1.037-2.073a.75.75 0 01.671-.415zM3 13.5a.75.75 0 01.75-.75h9a.75.75 0 010 1.5h-9A.75.75 0 013 13.5zm0 4.5a.75.75 0 01.75-.75h5.25a.75.75 0 010 1.5H3.75A.75.75 0 013 18z',
        'chat' => 'M4.804 21.644A6.707 6.707 0 006 21.75a33.23 33.23 0 0011.64-2.087c1.226-.463 2.109-1.522 2.109-2.827V6.187c0-1.478-1.1-2.72-2.575-2.857a48.99 48.99 0 00-11.348 0C4.35 3.467 3.25 4.71 3.25 6.187v10.75c0 1.478 1.1 2.72 2.575 2.857.42.038.842.07 1.267.096l-2.288 1.754z',
        'users' => 'M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z',
        'bell' => 'M5.25 9a6.75 6.75 0 0113.5 0v.75c0 2.123.8 4.057 2.118 5.52a.75.75 0 01-.297 1.206c-1.544.57-3.16.99-4.831 1.243a3.75 3.75 0 11-7.48 0 24.585 24.585 0 01-4.831-1.244.75.75 0 01-.298-1.205A8.217 8.217 0 005.25 9.75V9zm4.502 8.9a2.25 2.25 0 104.496 0 25.057 25.057 0 01-4.496 0z',
        'map' => 'M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144zM9 4.618v13.09l4.5 2.05V6.667L9 4.618z',
        'check' => 'M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z',
        'truck' => 'M8.25 18.75a1.5 1.5 0 01-3 0 1.5 1.5 0 013 0zM19.5 18.75a1.5 1.5 0 01-3 0 1.5 1.5 0 013 0z M3.375 4.5A2.625 2.625 0 00.75 7.125v9.75A2.625 2.625 0 003.375 19.5h.375a3 3 0 106 0h7.5a3 3 0 106 0h.375a1.125 1.125 0 001.125-1.125v-3.25a3.375 3.375 0 00-.988-2.386l-3.401-3.401A2.625 2.625 0 0018.512 8.25H16.5v-2.25A1.5 1.5 0 0015 4.5H3.375z',
        'building' => 'M4.5 2.25a.75.75 0 000 1.5v16.5h-.75a.75.75 0 000 1.5h16.5a.75.75 0 000-1.5H19.5V3.75a.75.75 0 000-1.5h-15zM9 6a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H9zm-.75 3.75A.75.75 0 019 9h1.5a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM9 12.75a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H9zm3.75-6.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5h-1.5zM12.75 12a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zM9 16.5a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H9zm3 .75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75z',
        'user-group' => 'M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z',
    ];
@endphp

@foreach ($groups as $groupLabel => $items)
    <div class="{{ $loop->first ? '' : 'mt-6' }}">
        <p class="px-3 mb-2 text-[10.5px] font-semibold uppercase tracking-widest text-brand-400/80">{{ $groupLabel }}</p>
        <div class="space-y-0.5">
            @foreach ($items as $item)
                @php $active = request()->routeIs(...$item['match']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="group relative flex items-center gap-2.5 pl-3.5 pr-3 py-2 rounded-lg text-[13.5px] font-medium transition-colors
                          {{ $active ? 'bg-gradient-to-r from-white/[0.09] to-white/[0.03] text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.06)]' : 'text-brand-200/80 hover:bg-white/[0.04] hover:text-white' }}">
                    @if ($active)
                        <span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-full bg-accent-500 shadow-[0_0_8px_theme(colors.accent.500)]"></span>
                    @endif
                    <svg class="h-[17px] w-[17px] shrink-0 {{ $active ? 'text-accent-400' : 'text-brand-400/70 group-hover:text-brand-300' }}" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="{{ $icons[$item['icon']] }}" clip-rule="evenodd" />
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
@endforeach
