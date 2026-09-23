<a href="{{ route('dashboard') }}"
   class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
    Dashboard
</a>

@hasanyrole('company_admin|company_user')
    <a href="{{ route('leads.index') }}"
       class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('leads.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
        Leads
    </a>
    <a href="{{ route('customers.index') }}"
       class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('customers.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
        Customers
    </a>
    <a href="{{ route('enquiries.index') }}"
       class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('enquiries.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
        Enquiries
    </a>
    <a href="{{ route('trips.index') }}"
       class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('trips.*', 'itineraries.*', 'quotation-versions.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
        Trips
    </a>
    <a href="{{ route('suppliers.index') }}"
       class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('suppliers.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
        Suppliers
    </a>
    <a href="{{ route('follow-ups.index') }}"
       class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('follow-ups.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
        Follow-ups
    </a>
@endhasanyrole

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
