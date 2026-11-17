@extends('layouts.app')

@section('title', 'Tugas Saya')

@section('content')
<div x-data="{ activeTask: null }" class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Tugas Saya</h1>
            <p class="text-xs text-slate-500">Daftar penugasan pribadi, deadline, rincian hak transport lokal, dan unggah bukti pelaksanaan.</p>
        </div>
        <form method="GET" action="{{ route('tugas-saya.index') }}" class="flex items-center gap-2">
            <select name="status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-xl px-3 py-2">
                <option value="">Semua Status</option>
                @foreach(['Belum Mulai', 'Berjalan', 'Menunggu Verifikasi', 'Revisi', 'Selesai'] as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Summary Cards (Mobile-Friendly) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Beban Kerja Aktif Saya</span>
                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $workload['badge_class'] }}">
                    {{ $workload['status'] }} ({{ $workload['persentase'] }}%)
                </span>
            </div>
            <p class="text-lg font-bold text-slate-800">{{ $workload['total_poin'] }} / {{ $workload['kapasitas_maks'] }} Poin</p>
            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full {{ $workload['bar_class'] }}" style="width: {{ $workload['bar_persen'] }}%"></div>
            </div>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 flex flex-col justify-center">
            <span class="text-xs font-medium text-slate-500">Total Hak Transport Lokal Saya</span>
            <p class="text-xl font-bold text-emerald-700 mt-1">Rp {{ number_format($totalHakTransport, 0, ',', '.') }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Akumulasi transport tugas yang aktif / layak SPJ</p>
        </div>
    </div>

    <!-- Task Cards (Responsive Grid for Mobile Mitra & Desktop Organik) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($penugasans as $tugas)
            @php
                $deadline = $tugas->deadline_tugas;
                $daysLeft = $deadline ? (int) now()->startOfDay()->diffInDays($deadline->startOfDay(), false) : null;
            @endphp
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between gap-4">
                <div class="space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                {{ $tugas->subKegiatan?->periodeKegiatan?->masterKegiatan?->nama }} &middot; {{ $tugas->subKegiatan?->periodeKegiatan?->nama_periode }}
                            </span>
                            <h3 class="text-sm font-bold text-slate-800 mt-0.5">{{ $tugas->subKegiatan?->nama }}</h3>
                        </div>
                        <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full bg-slate-100 text-slate-700 shrink-0">{{ $tugas->status }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-600">
                        <span class="inline-flex items-center gap-1 bg-slate-50 px-2 py-1 rounded-lg border border-slate-100">
                            <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>
                            Kec. {{ $tugas->kecamatan?->nama ?? '-' }}
                        </span>
                        <span class="inline-flex items-center gap-1 bg-slate-50 px-2 py-1 rounded-lg border border-slate-100">
                            <i data-lucide="target" class="w-3 h-3 text-slate-400"></i>
                            {{ $tugas->realisasi_volume }} / {{ $tugas->target_volume }} {{ $tugas->satuan_volume }}
                        </span>
                        @if($deadline)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border {{ $daysLeft < 0 ? 'bg-rose-50 text-rose-700 border-rose-200' : ($daysLeft <= 3 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-50 text-slate-600 border-slate-100') }}">
                                <i data-lucide="clock" class="w-3 h-3"></i>
                                @if($daysLeft < 0)
                                    Terlambat {{ abs($daysLeft) }} hari
                                @elseif($daysLeft === 0)
                                    Deadline Hari Ini!
                                @else
                                    H-{{ $daysLeft }} ({{ $deadline->format('d/m/Y') }})
                                @endif
                            </span>
                        @endif
                    </div>

                    <!-- Progress Bar -->
                    <div>
                        <div class="flex justify-between text-[11px] mb-1">
                            <span class="text-slate-500">Progres Pelaksanaan</span>
                            <span class="font-bold text-slate-800">{{ $tugas->progres_persen }}%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-sage-500 rounded-full" style="width: {{ $tugas->progres_persen }}%"></div>
                        </div>
                    </div>

                    @if($tugas->alokasiTransports->isNotEmpty())
                        <div class="p-3 bg-emerald-50/60 border border-emerald-200/80 rounded-xl space-y-1">
                            <p class="text-[11px] font-semibold text-emerald-800">Rincian Hak Transport Lokal:</p>
                            @foreach($tugas->alokasiTransports as $tr)
                                <p class="text-[11px] text-emerald-700">
                                    &bull; {{ $tr->tgl_tugas->format('d M Y') }}: <strong>Rp {{ number_format($tr->nominal, 0, ',', '.') }}</strong> ({{ $tr->status_spj }})
                                </p>
                            @endforeach
                        </div>
                    @endif

                    @if($tugas->catatan)
                        <p class="text-[11px] text-slate-600 bg-amber-50/70 border border-amber-200/70 rounded-xl p-2.5">
                            <strong>Catatan:</strong> {{ $tugas->catatan }}
                        </p>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100">
                    @if($tugas->file_bukti)
                        <a href="{{ asset($tugas->file_bukti) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-sky-600 hover:underline">
                            <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                            <span>Lihat Bukti Terunggah</span>
                        </a>
                    @else
                        <span class="text-[11px] text-slate-400">Belum ada bukti</span>
                    @endif

                    @if($tugas->status !== 'Selesai')
                        <button @click="activeTask = {{ $tugas->toJson() }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl cursor-pointer">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            <span>Update Progres &amp; Bukti</span>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white border border-slate-200/80 rounded-2xl p-8 text-center text-xs text-slate-400">
                Belum ada tugas yang diberikan kepada Anda.
            </div>
        @endforelse
    </div>

    <!-- Modal Update Progres & Upload Bukti -->
    <div x-show="activeTask" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="activeTask = null" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4" x-if="activeTask">
            <h3 class="text-sm font-bold text-slate-800">Update Progres &amp; Bukti Pelaksanaan</h3>
            <form method="POST" :action="'/tugas-saya/' + activeTask.id + '/progress'" enctype="multipart/form-data" class="space-y-3 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold mb-1">Realisasi Volume</label>
                        <input type="number" name="realisasi_volume" x-model="activeTask.realisasi_volume" min="0" required class="w-full border border-slate-200 rounded-xl px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Progres (%)</label>
                        <input type="number" name="progres_persen" x-model="activeTask.progres_persen" min="0" max="100" required class="w-full border border-slate-200 rounded-xl px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Status</label>
                    <select name="status" x-model="activeTask.status" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                        <option value="Belum Mulai">Belum Mulai</option>
                        <option value="Berjalan">Berjalan</option>
                        <option value="Menunggu Verifikasi">Ajukan Verifikasi (Selesai Dikerjakan)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Upload Foto Lapangan / Visum / Dokumen Bukti</label>
                    <input type="file" name="file_bukti" accept=".jpg,.jpeg,.png,.pdf,.webp" class="w-full border border-slate-200 rounded-xl p-2">
                    <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, PDF, WEBP (Maks 5MB).</p>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Catatan Pelaksanaan</label>
                    <textarea name="catatan" x-model="activeTask.catatan" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="activeTask = null" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan Progres</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
