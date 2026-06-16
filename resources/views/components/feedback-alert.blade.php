{{--
    <x-feedback-alert> — Banner notifikasi/error transparan melayang.
    Props:
      - id      : id elemen (untuk dikontrol via JS)
      - floating : true = posisi fixed melayang di atas; false = inline
    Tersembunyi secara default (class hidden); JS yang menampilkan & mengisi teks.
--}}
@props([
    'id' => 'feedback-alert',
    'floating' => false,
])

@php
    $position = $floating
        ? 'fixed left-1/2 top-5 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 shadow-[0_18px_40px_-16px_rgba(15,23,42,0.3)]'
        : 'w-full';
@endphp

<div
    id="{{ $id }}"
    role="alert"
    {{ $attributes->merge([
        'class' => "hidden rounded-xl border px-4 py-3 text-sm backdrop-blur $position",
    ]) }}
    data-alert
>
    <span data-alert-text>{{ $slot }}</span>
</div>
