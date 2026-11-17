@extends('layouts.app')

@section('title', 'Dashboard Pegawai')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Dashboard {{ auth()->user()->role === 'mitra' ? 'Mitra Statistik' : 'Pegawai' }}</h1>
            <p class="text-xs text-slate-500">Halo, <strong>{{ auth()->user()->name }}</strong> &mdash; berikut daftar kegiatan yang belum selesai beserta informasi deadline Anda.</p>
        </div>
        <a href="{{ route('tugas-saya.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl transition shrink-0">
            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
            <span>Update Progres &amp; Bukti</span>
        </a>
    </div>

    <!-- KPI Pribadi -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Tugas Belum Selesai</span>
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                    <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl font-bold text-slate-800 mt-3">{{ number_format($totalTugasAktif) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Dialokasikan kepada Anda</p>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Mendekati Deadline (H-3)</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl font-bold text-amber-600 mt-3">{{ number_format($tugasMendekatiDeadline) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Segera jatuh tempo</p>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Tugas Overdue</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl font-bold text-rose-600 mt-3">{{ number_format($tugasOverdue) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Melewati batas deadline</p>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Beban Kerja Aktif</span>
                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $workload['badge_class'] }}">
                    {{ $workload['status'] }} ({{ $workload['persentase'] }}%)
                </span>
            </div>
            <p class="text-lg font-bold text-slate-800">{{ $workload['total_poin'] }} / {{ $workload['kapasitas_maks'] }} Poin</p>
            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full {{ $workload['bar_class'] }} rounded-full" style="width: {{ $workload['bar_persen'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- Daftar Kegiatan Belum Selesai & Informasi Deadline -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-slate-800">Kegiatan Belum Selesai &amp; Informasi Deadline</h2>
                <p class="text-[11px] text-slate-400">Diurutkan berdasarkan deadline terdekat</p>
            </div>
            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-xl">
                Hak Transport Aktif: Rp {{ number_format($totalHakTransport, 0, ',', '.') }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-semibold text-slate-500 uppercase">
                        <th class="py-3 px-4">Kegiatan &amp; Tahapan</th>
                        <th class="py-3 px-4">Wilayah Tugas</th>
                        <th class="py-3 px-4">Target &amp; Progres</th>
                        <th class="py-3 px-4">Informasi Deadline</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tugasAktif as $tugas)
                        @php
                            $deadline = $tugas->deadline_tugas;
                            $daysLeft = $deadline ? (int) now()->startOfDay()->diffInDays($deadline->startOfDay(), false) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-slate-800">{{ $tugas->subKegiatan?->nama }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $tugas->subKegiatan?->periodeKegiatan?->masterKegiatan?->nama }}
                                    &middot; {{ $tugas->subKegiatan?->periodeKegiatan?->nama_periode }}
                                </p>
                                <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-medium rounded-md bg-slate-100 text-slate-600">
                                    {{ $tugas->subKegiatan?->periodeKegiatan?->masterKegiatan?->timKerja?->nama }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                    Kec. {{ $tugas->kecamatan?->nama ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 min-w-[150px]">
                                <p class="font-semibold text-slate-700">{{ $tugas->realisasi_volume }} / {{ $tugas->target_volume }} {{ $tugas->satuan_volume }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-sage-500 rounded-full" style="width: {{ $tugas->progres_persen }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-500">{{ $tugas->progres_persen }}%</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($deadline)
                                    <p class="font-semibold text-slate-800">{{ $deadline->format('d M Y') }}</p>
                                    @if($daysLeft < 0)
                                        <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                            Terlambat {{ abs($daysLeft) }} hari
                                        </span>
                                    @elseif($daysLeft === 0)
                                        <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                            Deadline Hari Ini!
                                        </span>
                                    @elseif($daysLeft <= 3)
                                        <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                            H-{{ $daysLeft }} ({{ $daysLeft }} hari lagi)
                                        </span>
                                    @else
                                        <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ $daysLeft }} hari lagi
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $stClass = match($tugas->status) {
                                        'Revisi' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'Menunggu Verifikasi' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'Berjalan' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full border {{ $stClass }}">{{ $tugas->status }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('tugas-saya.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-[11px] font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-lg transition">
                                    <span>Kerjakan</span>
                                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400">
                                Tidak ada tugas yang belum selesai. Semua tugas Anda telah tuntas!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
