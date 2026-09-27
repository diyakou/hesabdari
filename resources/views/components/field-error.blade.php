@props(['name'])

@error($name)
    <p {{ $attributes->merge(['class' => 'mt-1.5 text-sm text-rose-700']) }} id="{{ $name }}-error">{{ $message }}</p>
@enderror
