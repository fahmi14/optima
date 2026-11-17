@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Header Summary -->
<div class="flex items-center justify-between pb-2">
    <div>
        <h1 class="text-xl font-bold text-slate-800 tracking-tight">Ringkasan Operasional</h1>
        <p class="text-xs text-slate-500 mt-0.5">Pemantauan kegiatan statistik, alokasi wilayah, dan matrik penugasan petugas.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('matrik.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white rounded-lg hover:bg-slate-700 transition shadow-xs">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            <span>Tugaskan Petugas</span>
        </a>
    </div>
</div>

<!-- 5 Stat Cards in Natural Muted Pastel -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
    <!-- Wilayah -->
    <a href="{{ route('wilayah.index') }}" class="bg-white p-4 rounded-xl border border-slate-200/80 hover:border-slate-300 hover:shadow-xs transition block group">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Wilayah</span>
            <div class="w-7 h-7 rounded-md bg-[#eef2f6] text-[#3b5998] flex items-center justify-center">
                <i data-lucide="map" class="w-3.5 h-3.5"></i>
            </div>
        </div>
        <div class="mt-2">
            <span class="text-2xl font-bold text-slate-800 tracking-tight">{{ $totalWilayah }}</span>
            <span class="text-xs text-slate-400 ml-1">SLS/Desa</span>
        </div>
    </a>

    <!-- Tim -->
    <a href="{{ route('tim.index') }}" class="bg-white p-4 rounded-xl border border-slate-200/80 hover:border-slate-300 hover:shadow-xs transition block group">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tim Kerja</span>
            <div class="w-7 h-7 rounded-md bg-[#e9f0ec] text-[#2d6a4f] flex items-center justify-center">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
            </div>
        </div>
        <div class="mt-2">
            <span class="text-2xl font-bold text-slate-800 tracking-tight">{{ $totalTim }}</span>
            <span class="text-xs text-slate-400 ml-1">Grup Fungsional</span>
        </div>
    </a>

    <!-- Pengguna -->
    <a href="{{ route('user.index') }}" class="bg-white p-4 rounded-xl border border-slate-200/80 hover:border-slate-300 hover:shadow-xs transition block group">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Petugas</span>
            <div class="w-7 h-7 rounded-md bg-[#f6f2ec] text-[#8c5828] flex items-center justify-center">
                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
            </div>
        </div>
        <div class="mt-2 flex items-baseline justify-between">
            <span class="text-2xl font-bold text-slate-800 tracking-tight">{{ $totalUser }}</span>
            <span class="text-[11px] text-slate-400">{{ $totalOrganik }} Org &bull; {{ $totalMitra }} Mit</span>
        </div>
    </a>

    <!-- Kegiatan -->
    <a href="{{ route('kegiatan.index') }}" class="bg-white p-4 rounded-xl border border-slate-200/80 hover:border-slate-300 hover:shadow-xs transition block group">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Kegiatan</span>
            <div class="w-7 h-7 rounded-md bg-[#f0eef6] text-[#5e4b8b] flex items-center justify-center">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
            </div>
        </div>
        <div class="mt-2">
            <span class="text-2xl font-bold text-slate-800 tracking-tight">{{ $totalKegiatan }}</span>
            <span class="text-xs text-slate-400 ml-1">Survei/Sensus</span>
        </div>
    </a>

    <!-- Matrik Tugas -->
    <a href="{{ route('matrik.index') }}" class="bg-white p-4 rounded-xl border border-slate-200/80 hover:border-slate-300 hover:shadow-xs transition block group">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tugas</span>
            <div class="w-7 h-7 rounded-md bg-[#eaf4f4] text-[#2c7a7b] flex items-center justify-center">
                <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
            </div>
        </div>
        <div class="mt-2">
            <span class="text-2xl font-bold text-slate-800 tracking-tight">{{ $totalMatrik }}</span>
            <span class="text-xs text-slate-400 ml-1">Alokasi Matrik</span>
        </div>
    </a>
</div>

<!-- 2 Balanced Columns for Operational Insight -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Kegiatan Terbaru Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar-check" class="w-4 h-4 text-slate-400"></i>
                <h2 class="text-sm font-bold text-slate-800">Kegiatan Statistik Berjalan</h2>
            </div>
            <a href="{{ route('kegiatan.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-medium">Lihat Semua &rarr;</a>
        </div>
        <div class="overflow-x-auto mt-2">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-100 uppercase text-[10px] tracking-wider font-semibold">
                        <th class="py-2.5">Nama Kegiatan</th>
                        <th class="py-2.5">Tim</th>
                        <th class="py-2.5 text-right">Periode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($recentKegiatan as $k)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-2.5 font-semibold text-slate-800 pr-2">{{ $k->nama }}</td>
                            <td class="py-2.5">
                                <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px] font-medium">
                                    {{ $k->tim?->nama ?? '-' }}
                                </span>
                            </td>
                            <td class="py-2.5 text-right text-slate-400">
                                {{ $k->tgl_mulai ? $k->tgl_mulai->format('d M') : '-' }} - {{ $k->tgl_selesai ? $k->tgl_selesai->format('d M Y') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-4 text-center text-slate-400 italic">Belum ada kegiatan terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Matrik Tugas Terbaru Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <i data-lucide="clipboard-check" class="w-4 h-4 text-slate-400"></i>
                <h2 class="text-sm font-bold text-slate-800">Alokasi Matrik Terbaru</h2>
            </div>
            <a href="{{ route('matrik.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-medium">Lihat Semua &rarr;</a>
        </div>
        <div class="overflow-x-auto mt-2">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-100 uppercase text-[10px] tracking-wider font-semibold">
                        <th class="py-2.5">Petugas</th>
                        <th class="py-2.5">Kegiatan</th>
                        <th class="py-2.5">Wilayah</th>
                        <th class="py-2.5 text-center">Beban</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($recentMatrik as $m)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-2.5">
                                <span class="font-semibold text-slate-800 block">{{ $m->user?->name }}</span>
                                <span class="text-[10px] text-slate-400 capitalize">{{ $m->user?->tipe }}</span>
                            </td>
                            <td class="py-2.5 text-slate-700 pr-2">{{ $m->kegiatan?->nama }}</td>
                            <td class="py-2.5 text-slate-500 text-[11px]">{{ $m->wilayah?->nama ?? '-' }}</td>
                            <td class="py-2.5 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full bg-[#f4ebe1] text-[#7d4e24] font-bold text-[11px]">
                                    {{ $m->beban }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-slate-400 italic">Belum ada penugasan matrik.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
