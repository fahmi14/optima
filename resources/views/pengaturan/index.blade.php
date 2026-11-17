@extends('layouts.app')

@section('title', 'Pengaturan Standar & SBML')

@section('content')
<div class="max-w-2xl mx-auto space-y-5">
    <div>
        <h1 class="text-lg font-bold text-slate-800">Pengaturan Standar Biaya (SBML) &amp; Jam Kerja</h1>
        <p class="text-xs text-slate-500">Konfigurasi batas maksimal honor mitra per bulan (SBML) dan standar jam kerja per hari untuk menghitung kuota bulanan petugas.</p>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs">
        <form method="POST" action="{{ route('pengaturan.update') }}" class="space-y-5 text-xs">
            @csrf
            @method('PUT')

            <div>
                <label class="block font-bold text-slate-700 mb-1">Standar Biaya Masukan Lainnya (SBML) Mitra Bulanan (Rp)</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-2.5 text-slate-400 font-bold">Rp</span>
                    <input
                        type="number"
                        step="10000"
                        name="sbml_mitra_per_bulan"
                        value="{{ old('sbml_mitra_per_bulan', \App\Models\Pengaturan::get('sbml_mitra_per_bulan', 4000000)) }}"
                        required
                        class="w-full border border-slate-200 rounded-xl pl-10 pr-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-400"
                    >
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Batas akumulasi penerimaan honor mitra dalam satu bulan kalender. Jika terlampaui, indikator kalender akan menandai 'Melebihi SBML'.</p>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Standar Jam Kerja per Hari</label>
                <div class="relative">
                    <input
                        type="number"
                        step="0.5"
                        min="1"
                        max="24"
                        name="jam_kerja_per_hari"
                        value="{{ old('jam_kerja_per_hari', \App\Models\Pengaturan::get('jam_kerja_per_hari', 8)) }}"
                        required
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-400"
                    >
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Total jam kerja per bulan dihitung otomatis berdasarkan rumus: <strong>(Jam Kerja per Hari x Hari Kerja dalam Bulan berjalan)</strong>.</p>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-2">
                <span class="font-bold text-slate-700 text-xs flex items-center gap-1.5">
                    <i data-lucide="info" class="w-4 h-4 text-sky-600"></i>
                    Simulasi Kuota Bulan Ini:
                </span>
                @php
                    $workloadService = app(\App\Services\WorkloadService::class);
                    $hk = $workloadService->getHariKerjaBulan(now());
                    $totalK = $workloadService->getTotalJamKerjaBulan(now());
                @endphp
                <p class="text-slate-600 text-[11px]">
                    Bulan saat ini ({{ now()->translatedFormat('F Y') }}): <strong>{{ $hk }} hari kerja</strong> (Senin-Jumat).<br>
                    Kuota maksimal waktu kerja: <strong>{{ $totalK }} Jam</strong>.
                </p>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-5 py-2.5 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
