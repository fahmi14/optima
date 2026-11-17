@extends('layouts.app')

@section('title', 'Tugas Saya')

@section('content')
<div x-data="{ activeTask: null }" class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Tugas Saya</h1>
            <p class="text-xs text-slate-500 mt-1">Daftar penugasan pribadi, deadline, rincian hak transport lokal, dan unggah bukti pelaksanaan.</p>
        </div>
        <form method="GET" action="{{ route('tugas-saya.index') }}" class="flex items-center gap-2">
            <select name="status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-xl px-4 py-2.5 shadow-2xs focus:outline-none focus:ring-1 focus:ring-slate-400">
                <option value="">Semua Status</option>
                @foreach(['Belum Mulai', 'Berjalan', 'Menunggu Verifikasi', 'Revisi', 'Selesai'] as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Summary Cards (Pastel, Lega, Kalem) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-slate-50 to-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Beban Kerja Aktif Saya</span>
                <span class="px-3 py-1 text-[11px] font-bold rounded-full border {{ $workload['badge_class'] }}">
                    {{ $workload['status'] }} ({{ $workload['persentase'] }}%)
                </span>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $workload['total_jam'] ?? $workload['total_poin'] }} <span class="text-xs font-normal text-slate-400">/ {{ $workload['kuota_jam'] ?? $workload['kapasitas_maks'] }} Jam Kuota</span></p>
            <div class="w-full h-2.5 bg-slate-200/70 rounded-full overflow-hidden">
                <div class="h-full {{ $workload['bar_class'] }} transition-all" style="width: {{ $workload['bar_persen'] }}%"></div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-50/40 to-white border border-emerald-100 rounded-2xl p-6 shadow-2xs flex flex-col justify-center">
            <span class="text-xs font-semibold text-slate-500">Total Hak Transport Lokal Saya</span>
            <p class="text-2xl font-bold text-emerald-700 mt-1.5">Rp {{ number_format($totalHakTransport, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Akumulasi transport tugas yang aktif / layak SPJ</p>
        </div>
    </div>

    <!-- Task Cards (Responsive Grid) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @forelse($penugasans as $tugas)
            @php
                $deadline = $tugas->deadline_tugas;
                $daysLeft = $deadline ? (int) now()->startOfDay()->diffInDays($deadline->startOfDay(), false) : null;
            @endphp
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs flex flex-col justify-between gap-5 hover:border-slate-300 transition">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                                {{ $tugas->subKegiatan?->periodeKegiatan?->masterKegiatan?->nama }} &bull; {{ $tugas->subKegiatan?->periodeKegiatan?->nama_periode }}
                            </span>
                            <h3 class="text-base font-bold text-slate-800">{{ $tugas->subKegiatan?->nama }}</h3>
                        </div>
                        <span class="px-3 py-1 text-[10px] font-bold rounded-lg bg-slate-100 text-slate-700 shrink-0">{{ $tugas->status }}</span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-1.5 pt-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Realisasi: <strong>{{ $tugas->realisasi_volume }}/{{ $tugas->target_volume }} {{ $tugas->satuan_volume }}</strong></span>
                            <span class="font-bold text-slate-700">{{ $tugas->progres_persen }}%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full transition-all" style="width: {{ $tugas->progres_persen }}%"></div>
                        </div>
                    </div>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100">
                        <div>
                            <span class="text-[11px] text-slate-400 block">Estimasi Waktu:</span>
                            <p class="font-bold text-slate-700">{{ $tugas->durasiJam() }} Jam</p>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-400 block">Deadline:</span>
                            <p class="font-semibold {{ $daysLeft !== null && $daysLeft < 0 ? 'text-rose-600 font-bold' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-600' : 'text-slate-700') }}">
                                {{ $deadline?->format('d M Y') ?? '-' }}
                                @if($daysLeft !== null)
                                    <span class="text-[10px] font-normal">({{ $daysLeft < 0 ? 'Lewat ' . abs($daysLeft) . ' hari' : ($daysLeft === 0 ? 'Hari ini' : "H-{$daysLeft}") }})</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        @if($tugas->file_bukti)
                            <a href="{{ asset($tugas->file_bukti) }}" target="_blank" class="text-xs font-semibold text-sky-600 hover:text-sky-700 flex items-center gap-1">
                                <i data-lucide="file-check" class="w-3.5 h-3.5"></i>
                                <span>Lihat Bukti</span>
                            </a>
                        @endif
                    </div>
                    <button
                        type="button"
                        @click="activeTask = {{ $tugas->toJson() }}"
                        class="px-4 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 transition shadow-2xs cursor-pointer"
                    >
                        Update Progres
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white border border-slate-200/80 rounded-2xl p-12 text-center text-xs text-slate-400">
                Belum ada penugasan kerja yang ditugaskan kepada Anda saat ini.
            </div>
        @endforelse
    </div>

    <!-- Modal Dialog Update Progres (Lega & Kalem) -->
    <div x-show="activeTask" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="activeTask = null" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 max-h-[92vh] overflow-y-auto" x-if="activeTask">
            <div class="flex items-start justify-between gap-3 pb-4 border-b border-slate-100">
                <div class="space-y-1">
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-100">Update Tugas</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight">Progres &amp; Bukti Pelaksanaan</h3>
                </div>
                <button @click="activeTask = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/tugas-saya/' + activeTask.id + '/progress'" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Realisasi Volume</label>
                        <input type="number" name="realisasi_volume" x-model="activeTask.realisasi_volume" min="0" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Progres (%)</label>
                        <input type="number" name="progres_persen" x-model="activeTask.progres_persen" min="0" max="100" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Status Pengerjaan</label>
                    <select name="status" x-model="activeTask.status" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                        <option value="Belum Mulai">Belum Mulai</option>
                        <option value="Berjalan">Sedang Berjalan</option>
                        <option value="Menunggu Verifikasi">Ajukan Verifikasi (Selesai Dikerjakan)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Upload Foto Lapangan / Visum / Dokumen Bukti</label>
                    <input type="file" name="file_bukti" accept=".jpg,.jpeg,.png,.pdf,.webp" class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/60 text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white cursor-pointer">
                    <p class="text-[10px] text-slate-400 mt-1.5">Format didukung: JPG, PNG, PDF, WEBP (Maksimal 5MB).</p>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Catatan Pelaksanaan</label>
                    <textarea name="catatan" x-model="activeTask.catatan" rows="3" placeholder="Tuliskan keterangan singkat progres tugas Anda..." class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="activeTask = null" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Progres</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
