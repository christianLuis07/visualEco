{{--
    <x-clean-card> — Kartu minimalis medis dengan soft ambient shadow.
    Props:
      - padding : kelas padding (default p-6)
    Slot: konten kartu.
--}}
@props(['padding' => 'p-6'])

<div {{ $attributes->merge([
    'class' => "rounded-2xl border border-slate-100 bg-white $padding shadow-[0_1px_3px_rgba(15,23,42,0.04),0_12px_32px_-16px_rgba(15,23,42,0.12)]",
]) }}>
    {{ $slot }}
</div>
