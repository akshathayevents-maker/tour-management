@props(['label', 'name', 'required' => false, 'hint' => null, 'wide' => false])
<div class="{{ $wide ? 'sm:col-span-2' : '' }}">
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">
        {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
    </label>
    <div class="mt-1">{{ $slot }}</div>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
