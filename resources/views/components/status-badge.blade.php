{{--
    <x-status-badge> — Badge status transparan dengan warna pastel medis.
    Props:
      - tone : pending | completed | credit | debit | neutral | info
    Slot: teks badge.
--}}
@props(['tone' => 'neutral'])

@php
    $tones = [
        'pending'   => 'bg-amber-50 text-amber-700',
        'completed' => 'bg-emerald-50 text-emerald-700',
        'credit'    => 'bg-teal-50 text-[#0D9488]',
        'debit'     => 'bg-rose-50 text-rose-600',
        'info'      => 'bg-sky-50 text-sky-700',
        'neutral'   => 'bg-slate-100 text-slate-500',
    ];
    $classes = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . ($tones[$tone] ?? $tones['neutral']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</span>
