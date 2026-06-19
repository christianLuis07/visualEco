@extends('layouts.auth')

@section('title', 'Log Aktivitas — Visueco')

@section('content')
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl">

        {{-- ═══ TOP BAR ═══ --}}
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-semibold text-[#0F172A]">Log Aktivitas Sistem</h1>
                        <x-status-badge tone="info">Admin</x-status-badge>
                    </div>
                    <p class="text-sm text-slate-500">Jejak seluruh aktivitas pada website dan aplikasi mobile</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-white">
                    Kembali ke Dashboard Admin
                </a>
            </div>
        </div>

        {{-- ═══ ACTIVITY LOG TABLE ═══ --}}
        <section>
            <x-clean-card padding="p-0" class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Waktu</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Pengguna</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Aksi</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Model Terkait</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Properti (Perubahan)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($activities as $activity)
                                <tr class="transition hover:bg-slate-50/50">
                                    <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-500">
                                        {{ $activity->created_at->format('d M Y, H:i:s') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-sm font-medium text-slate-800">
                                        @if($activity->causer)
                                            {{ $activity->causer->name ?? 'User ID: ' . $activity->causer_id }}
                                        @else
                                            <span class="text-slate-400 italic">Sistem (Otomatis)</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-sm">
                                        @php
                                            $eventName = empty($activity->event) ? $activity->description : $activity->event;
                                            $color = match($eventName) {
                                                'created' => 'text-emerald-600 bg-emerald-50 border-emerald-200',
                                                'updated' => 'text-blue-600 bg-blue-50 border-blue-200',
                                                'deleted' => 'text-rose-600 bg-rose-50 border-rose-200',
                                                'User logged in' => 'text-purple-600 bg-purple-50 border-purple-200',
                                                default => 'text-slate-600 bg-slate-50 border-slate-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $color }}">
                                            {{ strtoupper($eventName) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-slate-600">
                                        @if($activity->subject_type)
                                            {{ class_basename($activity->subject_type) }} <br>
                                            <span class="text-[10px] text-slate-400">ID: {{ $activity->subject_id }}</span>
                                        @else
                                            <span class="text-slate-400 italic">Sistem / Autentikasi</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-slate-600 font-mono">
                                        @if($activity->properties->count() > 0)
                                            @if($activity->properties->has('old') || $activity->properties->has('attributes'))
                                                @if($activity->properties->has('old'))
                                                    <div class="text-rose-500 mb-1 line-through">
                                                        {{ json_encode($activity->properties['old']) }}
                                                    </div>
                                                @endif
                                                @if($activity->properties->has('attributes'))
                                                    <div class="text-emerald-600">
                                                        {{ json_encode($activity->properties['attributes']) }}
                                                    </div>
                                                @endif
                                            @elseif($eventName === 'User logged in')
                                                <div class="space-y-1 text-xs">
                                                    <p><span class="font-semibold text-slate-400">IP Address:</span> <span class="text-slate-700">{{ $activity->properties['ip'] ?? '-' }}</span></p>
                                                    <p><span class="font-semibold text-slate-400">Jalur Akses:</span> <span class="text-slate-700 uppercase">{{ $activity->properties['source'] ?? '-' }}</span></p>
                                                    @php
                                                        $ua = $activity->properties['user_agent'] ?? '';
                                                        $os = 'Unknown OS';
                                                        if (stripos($ua, 'windows') !== false) $os = 'Windows';
                                                        elseif (stripos($ua, 'mac') !== false) $os = 'MacOS';
                                                        elseif (stripos($ua, 'linux') !== false) $os = 'Linux';
                                                        elseif (stripos($ua, 'android') !== false) $os = 'Android';
                                                        elseif (stripos($ua, 'iphone') !== false || stripos($ua, 'ipad') !== false) $os = 'iOS';
                                                    @endphp
                                                    <p><span class="font-semibold text-slate-400">Sistem Operasi:</span> <span class="text-slate-700">{{ $os }}</span></p>
                                                    <p class="truncate max-w-xs text-[10px] text-slate-400" title="{{ $ua }}">{{ $ua }}</p>
                                                </div>
                                            @else
                                                <div class="text-slate-600 whitespace-pre-wrap">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div>
                                            @endif
                                        @else
                                            <span class="text-slate-400 italic font-sans">Tidak ada properti terekam</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-400">Belum ada log aktivitas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-clean-card>
            <div class="mt-4">
                {{ $activities->links() }}
            </div>
        </section>

    </div>
</div>
@endsection
