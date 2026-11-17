@extends('layouts.app')

@section('title', 'Rekap Transportasi Lokal & SPJ')

@section('content')
<div x-data="{ editSpj: null }" class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Rekap Transportasi Lokal &amp; Kelayakan SPJ</h1>
            <p class="text-xs text-slate-500">Pemantauan, verifikasi SPJ oleh Subbagian Umum/Keuangan, dan export Excel untuk Surat Tugas &amp; Kuitansi.</p>
        </div>
        <a href="{{ route('transport.export', request()->query()) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl transition">
            <i data-lucide="download" class="w-4 h-4"></i>
            <span>Export Rekap SPJ (.xlsx)</span>
        </a>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4">
            <span class="text-[11px] font-medium text-slate-400">Total Alokasi (Filter Aktif)</span>
            <p class="text-lg font-bold text-slate-800 mt-1">Rp {{ number_format($totalNominal, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4">
            <span class="text-[11px] font-medium text-slate-400">SPJ Layak / Dibayar</span>
            <p class="text-lg font-bold text-emerald-700 mt-1">Rp {{ number_format($totalLayak, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4">
            <span class="text-[11px] font-medium text-slate-400">Menunggu Verifikasi (Draft)</span>
            <p class="text-lg font-bold text-amber-600 mt-1">Rp {{ number_format($totalDraft, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('transport.index') }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 flex flex-wrap items-center gap-3">
        <select name="tim_kerja_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Tim Kerja</option>
            @foreach($timKerjas as $tim)
                <option value="{{ $tim->id }}" {{ request('tim_kerja_id') == $tim->id ? 'selected' : '' }}>{{ $tim->nama }}</option>
            @endforeach
        </select>
        <select name="master_kegiatan_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Kegiatan</option>
            @foreach($kegiatans as $keg)
                <option value="{{ $keg->id }}" {{ request('master_kegiatan_id') == $keg->id ? 'selected' : '' }}>{{ $keg->nama }} ({{ $keg->tahun }})</option>
            @endforeach
        </select>
        <select name="kecamatan_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Kecamatan</option>
            @foreach($kecamatans as $kec)
                <option value="{{ $kec->id }}" {{ request('kecamatan_id') == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
            @endforeach
        </select>
        <select name="status_spj" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Status SPJ</option>
            @foreach(['Draft', 'Layak', 'Dibayar', 'Ditolak'] as $st)
                <option value="{{ $st }}" {{ request('status_spj') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>
        <input type="month" name="bulan" value="{{ request('bulan') }}" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
        <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer">Filter</button>
        <a href="{{ route('transport.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Reset</a>
    </form>

    <!-- Table -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-semibold text-slate-500 uppercase">
                        <th class="py-3 px-4">Tanggal Tugas</th>
                        <th class="py-3 px-4">Petugas</th>
                        <th class="py-3 px-4">Kegiatan &amp; Tahapan</th>
                        <th class="py-3 px-4">Kecamatan</th>
                        <th class="py-3 px-4">Nominal</th>
                        <th class="py-3 px-4">Status SPJ</th>
                        @if(auth()->user()->hasAnyRole(['admin', 'keuangan']))
                            <th class="py-3 px-4 text-right">Verifikasi SPJ</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($alokasis as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $item->tgl_tugas->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-slate-800">{{ $item->user?->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ ucfirst($item->user?->tipe) }} &middot; {{ $item->user?->nip ?: $item->user?->id_sobat }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-medium text-slate-700">{{ $item->periodeKegiatan?->masterKegiatan?->nama }} ({{ $item->periodeKegiatan?->nama_periode }})</p>
                                <p class="text-[11px] text-slate-400">{{ $item->penugasan?->subKegiatan?->nama }} &middot; {{ $item->periodeKegiatan?->masterKegiatan?->timKerja?->nama }}</p>
                            </td>
                            <td class="py-3 px-4 text-slate-600">Kec. {{ $item->kecamatan?->nama ?? '-' }}</td>
                            <td class="py-3 px-4 font-bold text-slate-800">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                            <td class="py-3 px-4">
                                @php
                                    $spjBadge = match($item->status_spj) {
                                        'Layak' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Dibayar' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'Ditolak' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full border {{ $spjBadge }}">{{ $item->status_spj }}</span>
                                @if($item->catatan_spj)
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $item->catatan_spj }}</p>
                                @endif
                            </td>
                            @if(auth()->user()->hasAnyRole(['admin', 'keuangan']))
                                <td class="py-3 px-4 text-right">
                                    <button @click="editSpj = {{ $item->toJson() }}" class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg cursor-pointer">
                                        Verifikasi
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-400">Tidak ada data alokasi transport.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Verifikasi SPJ -->
    <div x-show="editSpj" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="editSpj = null" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4" x-if="editSpj">
            <h3 class="text-sm font-bold text-slate-800">Verifikasi Kelayakan SPJ Transport</h3>
            <form method="POST" :action="'/transport/' + editSpj.id + '/status'" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold mb-1">Status SPJ</label>
                    <select name="status_spj" x-model="editSpj.status_spj" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                        <option value="Draft">Draft</option>
                        <option value="Layak">Layak</option>
                        <option value="Dibayar">Dibayar</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Catatan Keuangan / PPK</label>
                    <textarea name="catatan_spj" x-model="editSpj.catatan_spj" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editSpj = null" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
