<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    <div class="min-h-full lg:flex">
        {{-- Sidebar --}}
        <aside class="lg:w-64 lg:flex-shrink-0 bg-slate-900 text-slate-200">
            <div class="flex items-center h-16 px-6 text-lg font-semibold text-white">
                {{ config('app.name') }}
            </div>
            <nav class="px-3 py-4 space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    Dashboard
                </a>

                @role('super_admin')
                    <a href="{{ route('admin.companies.index') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.companies.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        Companies
                    </a>
                @endrole

                @hasanyrole('super_admin|company_admin')
                    <a href="{{ route('admin.users.index') }}"
                       class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        Users
                    </a>
                @endhasanyrole
            </nav>
        </aside>

        <div class="flex-1 min-w-0">
            {{-- Top bar --}}
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6">
                <h1 class="text-lg font-semibold text-slate-900">@yield('title', 'Dashboard')</h1>

                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-500">
                        {{ auth()->user()->name }}
                        @if(auth()->user()->company)
                            &middot; {{ auth()->user()->company->name }}
                        @else
                            &middot; Super Admin
                        @endif
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-slate-500 hover:text-slate-900">Log out</button>
                    </form>
                </div>
            </header>

            <main class="p-4 sm:p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
