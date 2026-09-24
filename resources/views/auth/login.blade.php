<!DOCTYPE html>
<html lang="en" class="h-full bg-canvas">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    <div class="min-h-screen lg:flex">
        {{-- Left: brand hero --}}
        <div class="relative hidden lg:flex lg:w-[58%] flex-col justify-between overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-12 py-10 xl:px-16">
            <svg class="absolute inset-0 h-full w-full text-white/[0.04]" viewBox="0 0 800 800" preserveAspectRatio="xMidYMid slice" fill="none">
                <path d="M-40 620 C 180 520, 280 480, 420 360 S 700 140, 860 60" stroke="currentColor" stroke-width="1.5" stroke-dasharray="2 10" stroke-linecap="round"/>
                <circle cx="420" cy="360" r="3" fill="currentColor"/>
                <circle cx="860" cy="60" r="3" fill="currentColor"/>
                <circle cx="-40" cy="620" r="3" fill="currentColor"/>
            </svg>
            <svg class="absolute -right-16 -top-16 h-80 w-80 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>

            <div class="relative flex items-center gap-2.5 text-white">
                <span class="flex h-8 w-8 items-center justify-center rounded-[9px] bg-gradient-to-br from-brand-500 to-brand-700 text-white text-sm font-bold shadow-sm ring-1 ring-white/10">{{ Str::substr(config('app.name'), 0, 1) }}</span>
                <span class="text-[15px] font-semibold tracking-tight">{{ config('app.name') }}</span>
            </div>

            <div class="relative max-w-md">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-400/90 mb-3">Travel Operations</p>
                <h1 class="text-[34px] xl:text-[40px] leading-[1.1] font-semibold tracking-tight text-white">
                    Every journey,<br>beautifully managed.
                </h1>
                <p class="mt-4 text-sm text-brand-200 max-w-sm">
                    Manage enquiries, customers, trips, bookings and follow-ups from one place.
                </p>
            </div>

            <p class="relative text-xs text-brand-300">One workspace for your entire travel business</p>
        </div>

        {{-- Right: auth panel --}}
        <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-10 lg:px-16 xl:px-20">
            <div class="mx-auto w-full max-w-sm">
                {{-- Mobile brand mark --}}
                <div class="flex items-center gap-2.5 mb-10 lg:hidden">
                    <span class="flex h-8 w-8 items-center justify-center rounded-[9px] bg-gradient-to-br from-brand-500 to-brand-700 text-white text-sm font-bold shadow-sm">{{ Str::substr(config('app.name'), 0, 1) }}</span>
                    <span class="text-[15px] font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                </div>

                <h2 class="text-[26px] font-semibold tracking-tight text-slate-900">Welcome back</h2>
                <p class="mt-1.5 text-sm text-slate-500">Sign in to continue managing your travel business.</p>

                @if ($errors->any())
                    <div class="mt-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                        <p class="text-sm font-medium text-red-800">Unable to sign you in</p>
                        <p class="mt-0.5 text-xs text-red-600">
                            {{ $errors->first() }}
                        </p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                               class="block w-full rounded-[10px] border-slate-300 shadow-sm text-[15px] py-3 px-3.5 transition-shadow">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               class="block w-full rounded-[10px] border-slate-300 shadow-sm text-[15px] py-3 px-3.5 transition-shadow">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600 select-none">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                        Remember me
                    </label>
                    <button type="submit"
                            class="w-full rounded-[10px] bg-brand-700 text-white text-sm font-semibold py-3 hover:bg-brand-800 active:scale-[0.99] transition-all shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                        Sign in
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-slate-400">Secure access to your travel workspace</p>
            </div>
        </div>
    </div>
</body>
</html>
