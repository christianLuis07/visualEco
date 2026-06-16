{{--
    <x-action-button> — Tombol premium Teal dengan transisi halus & state disabled.
    Props:
      - type    : tipe tombol (default button)
      - variant : primary (teal solid) | ghost (outline) | soft (teal lembut)
    Slot: label tombol.
--}}
@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#0D9488]/40 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40';

    $variants = [
        'primary' => 'btn-sheen bg-[#0D9488] text-white shadow-[0_10px_24px_-12px_rgba(13,148,136,0.7)] hover:bg-[#0f766e] hover:-translate-y-0.5',
        'ghost'   => 'border border-slate-200 bg-white/60 text-slate-700 backdrop-blur hover:border-[#0D9488]/40 hover:text-[#0D9488]',
        'soft'    => 'bg-teal-50 text-[#0D9488] hover:bg-teal-100',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
