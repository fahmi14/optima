@extends('layouts.app')

@section('title', 'Kegiatan & Periode')

@section('content')
<div x-data="{ openMaster: false, openImport: false, addPeriodeMaster: null }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Daftar Kegiatan &amp; Periode</h1>
            <p class="text-xs text-slate-500">Hirarki 4 tingkat: Master Kegiatan Global &rarr; Periode &rarr; Sub-Kegiatan Custom &rarr; Distribusi Penugasan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button @click="openImport = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl cursor-pointer">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Import Kegiatan &amp; Tahapan</span>
            </button>
            <button @click="openMaster = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Master Kegiatan Baru</span>
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('kegiatan.index') }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 mb-6 flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kegiatan atau periode..." class="flex-1 min-w-[180px] text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">

        <select name="tim_kerja_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Tim Kerja</option>
            @foreach($timKerjas as $tim)
                <option value="{{ $tim->id }}" {{ request('tim_kerja_id') == $tim->id ? 'selected' : '' }}>{{ $tim->nama }}</option>
            @endforeach
        </select>

        <select name="tahun" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Tahun</option>
            @foreach($tahunList as $th)
                <option value="{{ $th }}" {{ request('tahun') == $th ? 'selected' : '' }}>{{ $th }}</option>
            @endforeach
        </select>

        <select name="periode" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Periode</option>
            @foreach($periodeList as $perNama)
                <option value="{{ $perNama }}" {{ request('periode') === $perNama ? 'selected' : '' }}>{{ $perNama }}</option>
            @endforeach
        </select>

        <input type="month" name="bulan" value="{{ request('bulan') }}" title="Filter Bulan Pelaksanaan" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">

        <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer">Filter</button>
        <a href="{{ route('kegiatan.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Reset</a>
    </form>

    <!-- Tabel Daftar Kegiatan & Periode -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/70 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase">
                        <th class="py-3 px-4">Periode &amp; Jadwal</th>
                        <th class="py-3 px-4">Tahapan &amp; Tugas</th>
                        <th class="py-3 px-4">Pagu Transport</th>
                        <th class="py-3 px-4">Serapan Transport</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                @forelse($kegiatans as $master)
                    <tbody x-data="{ open: true }" class="divide-y divide-slate-100 border-t border-slate-200/80">
                        <!-- Group Header Row per Master Kegiatan (Clickable Expand/Collapse) -->
                        <tr @click="open = !open" class="bg-slate-50/90 hover:bg-slate-100/70 cursor-pointer select-none transition-colors">
                            <td colspan="4" class="py-2.5 px-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-white border border-slate-200 text-slate-500 transition-transform duration-150" :class="open ? 'rotate-90' : ''">
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <span class="font-bold text-slate-800 text-xs">{{ $master->nama }}</span>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md bg-slate-200/80 text-slate-700">{{ $master->tahun }}</span>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md bg-sage-50 text-sage-700 border border-sage-200/70">{{ $master->timKerja?->nama }}</span>
                                    <span class="text-[11px] text-slate-400">&middot; {{ $master->periodes->count() }} periode</span>
                                </div>
                            </td>
                            <td @click.stop class="py-2.5 px-4 text-right whitespace-nowrap space-x-1">
                                <button @click="addPeriodeMaster = {{ $master->toJson() }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-lg cursor-pointer">
                                    + Tambah Periode
                                </button>
                                <form method="POST" action="{{ route('kegiatan.master.destroy', $master) }}" class="inline" onsubmit="return optimaConfirm(event, 'Hapus Master Kegiatan {{ $master->nama }} beserta seluruh periode dan penugasannya?', 'Hapus Master Kegiatan')">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Hapus Master Kegiatan" class="inline-flex items-center p-1 text-slate-400 hover:text-rose-600 rounded-lg cursor-pointer">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        @forelse($master->periodes as $per)
                            @php
                                $terpakai = $per->alokasiTransports->where('status_spj', '!=', 'Ditolak')->sum('nominal');
                                $allTasks = $per->subKegiatans->flatMap->penugasans;
                                $doneTasks = $allTasks->where('status', 'Selesai')->count();
                                $totalTasks = $allTasks->count();
                            @endphp
                            <tr x-show="open" x-transition.opacity.duration.150ms class="hover:bg-slate-50/50">
                                <td class="py-3 pl-10 pr-4">
                                    <p class="font-semibold text-slate-800">{{ $per->nama_periode }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $per->tgl_mulai->format('d M Y') }} &ndash; {{ $per->tgl_selesai->format('d M Y') }}</p>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    <span class="font-medium text-slate-800">{{ $per->subKegiatans->count() }} tahapan</span>
                                    <span class="text-[11px] text-slate-400 block">{{ $doneTasks }} / {{ $totalTasks }} tugas selesai</span>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-700">
                                    Rp {{ number_format($per->pagu_transport, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-emerald-700">
                                    Rp {{ number_format($terpakai, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
                                    <a href="{{ route('kegiatan.periode.show', $per) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-lg transition">
                                        <span>Kelola Tahapan &amp; Tugas</span>
                                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                    </a>
                                    <form method="POST" action="{{ route('kegiatan.periode.destroy', $per) }}" class="inline" onsubmit="return optimaConfirm(event, 'Hapus periode {{ $per->nama_periode }} beserta seluruh tahapan di dalamnya?', 'Hapus Periode')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Hapus Periode" class="inline-flex items-center p-1.5 text-slate-400 hover:text-rose-600 rounded-lg cursor-pointer">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr x-show="open">
                                <td colspan="5" class="py-3 pl-10 pr-4 text-slate-400 italic">Belum ada periode pada kegiatan ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">Tidak ada data kegiatan yang sesuai filter.</td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>
    </div>

    <!-- Modal Master Kegiatan Baru -->
    <div x-show="openMaster" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="openMaster = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800">Buat Master Kegiatan Global</h3>
            <form method="POST" action="{{ route('kegiatan.master.store') }}" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold mb-1">Tim Kerja</label>
                    <select name="tim_kerja_id" required class="w-full border border-slate-200 rounded-xl px-3 py-2">
                        @foreach($timKerjas as $tim)
                            <option value="{{ $tim->id }}">{{ $tim->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block font-semibold mb-1">Nama Kegiatan</label>
                        <input type="text" name="nama" required placeholder="Contoh: Susenas 2026" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Tahun</label>
                        <input type="number" name="tahun" value="{{ date('Y') }}" required class="w-full border border-slate-200 rounded-xl px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openMaster = false" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Periode -->
    <div x-show="addPeriodeMaster" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="addPeriodeMaster = null" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4" x-if="addPeriodeMaster">
            <h3 class="text-sm font-bold text-slate-800">Tambah Periode: <span x-text="addPeriodeMaster.nama"></span></h3>
            <form method="POST" :action="'/kegiatan/' + addPeriodeMaster.id + '/periode'" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold mb-1">Nama Periode</label>
                    <input type="text" name="nama_periode" required placeholder="Contoh: Periode April" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold mb-1">Tanggal Mulai</label>
                        <input type="date" name="tgl_mulai" required class="w-full border border-slate-200 rounded-xl px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Tanggal Selesai</label>
                        <input type="date" name="tgl_selesai" required class="w-full border border-slate-200 rounded-xl px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Pagu Transport Lokal (Rp)</label>
                    <input type="number" name="pagu_transport" value="0" min="0" required class="w-full border border-slate-200 rounded-xl px-3 py-2">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="addPeriodeMaster = null" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan Periode</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Kegiatan -->
    <div x-show="openImport" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="openImport = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800">Import Kegiatan, Periode &amp; Tahapan Custom</h3>
            <p class="text-xs text-slate-500">Master Kegiatan dan Periode otomatis dibuat dengan `firstOrCreate` agar tidak duplikat.</p>
            <a href="{{ route('kegiatan.template') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl hover:bg-emerald-100">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Download Template Excel (.xlsx)</span>
            </a>
            <form method="POST" action="{{ route('kegiatan.import') }}" enctype="multipart/form-data" class="space-y-3 text-xs pt-2 border-t border-slate-100">
                @csrf
                <input type="file" name="file" accept=".xlsx,.csv,.xls" required class="w-full border border-slate-200 rounded-xl p-2">
                <div class="flex justify-end gap-2">
                    <button type="button" @click="openImport = false" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
