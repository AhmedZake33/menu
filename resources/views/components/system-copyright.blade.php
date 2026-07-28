@props(['class' => ''])

@php
    $startYear = 2026;
    $currentYear = now()->year;
    $yearText = $currentYear > $startYear ? $startYear.' - '.$currentYear : (string) $startYear;
@endphp

<p {{ $attributes->merge(['class' => trim('system-copyright '.$class)]) }}>
    © {{ $yearText }}
    <a href="https://tafratech.com" target="_blank" rel="noopener">tafratech.com</a>
</p>
