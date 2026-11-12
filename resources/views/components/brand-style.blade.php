@props(['color' => null])

@php
    // One colour drives the whole dashboard; theme.css derives every tint from these two variables.
    $color = is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color) ? $color : config('modules.brand.default');
    [$r, $g, $b] = sscanf($color, '#%02x%02x%02x');
@endphp
<style>:root{--brand:{{ $color }};--brand-rgb:{{ $r }},{{ $g }},{{ $b }}}</style>
