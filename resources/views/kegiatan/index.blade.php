@extends('layouts.app')

@section('title', 'Kegiatan & Periode')

@section('content')
<div x-data="{
    openMaster: false,
    openImport: false,
    addPeriodeMaster: null,
    editMaster: null,
    editPeriode: null,

    openEditMaster(master) {
        this.editMaster = {
            id: master.id,
            tim_kerja_id: master.tim_kerja_id,
            nama: master.nama,
            tahun: master.tahun,
            deskripsi: master.deskripsi || ''
        };
    },

    openEditPeriode(per, masterNama) {
        this.editPeriode = {
            id: per.id,
            master_nama: masterNama,
            nama_periode: per.nama_periode,
            tgl_mulai: per.tgl_mulai ? String(per.tgl_mulai).substring(0, 10) : '',
            tgl_selesai: per.tgl_selesai ? String(per.tgl_selesai).substring(0, 10) : '',
            pagu_transport: Number(per.pagu_transport || 0),
            pagu_honor: Number(per.pagu_honor || 0)
        };
    }
}">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Daftar Kegiatan &amp; Periode</h1>
            <p class="text-xs text-slate-500">Hirarki 4 tingkat: Master Kegiatan Global &rarr; Periode &rarr; Sub-Kegiatan Custom &rarr; Distribusi Penugasan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button @click="openImport = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl cursor-pointer shadow-2xs transition">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Import Kegiatan &amp; Tahapan</span>
            </button>
            <button @click="openMaster = true" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl cursor-pointer shadow-2xs transition">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Master Kegiatan Baru</span>
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('kegiatan.index') }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 mb-6 flex flex-wrap items-center gap-3 shadow-2xs">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kegiatan atau periode..." class="flex-1 min-w-[180px] text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:outline-none">

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
    <div class="bg-white border border-slate-200/80 rounded-3xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/70 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase">
                        <th class="py-3.5 px-4">Periode &amp; Jadwal</th>
                        <th class="py-3.5 px-4">Tahapan &amp; Tugas</th>
                        <th class="py-3.5 px-4">Pagu Transport</th>
                        <th class="py-3.5 px-4">Pagu Honor</th>
                        <th class="py-3.5 px-4 min-w-[230px]">Serapan Transport &amp; Honor</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                @forelse($kegiatans as $master)
                    <tbody x-data="{ open: true }" class="divide-y divide-slate-100 border-t border-slate-200/80">
                        <!-- Group Header Row per Master Kegiatan (Clickable Expand/Collapse) -->
                        <tr @click="open = !open" class="bg-slate-50/90 hover:bg-slate-100/70 cursor-pointer select-none transition-colors">
                            <td colspan="5" class="py-3 px-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-white border border-slate-200 text-slate-500 transition-transform duration-150" :class="open ? 'rotate-90' : ''">
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <span class="font-bold text-slate-800 text-xs">{{ $master->nama }}</span>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md bg-slate-200/80 text-slate-700">{{ $master->tahun }}</span>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/70">{{ $master->timKerja?->nama }}</span>
                                    <span class="text-[11px] text-slate-400">&middot; {{ $master->periodes->count() }} periode</span>
                                </div>
                            </td>
                            <td @click.stop class="py-3 px-4 text-right whitespace-nowrap space-x-1">
                                <button @click="addPeriodeMaster = {{ $master->toJson() }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-lg cursor-pointer transition">
                                    + Tambah Periode
                                </button>
                                <button @click="openEditMaster({{ $master->toJson() }})" title="Edit Master Kegiatan" class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100 hover:bg-indigo-100 rounded-lg cursor-pointer transition">
                                    <i data-lucide="edit-3" class="w-3 h-3"></i>
                                    <span>Edit</span>
                                </button>
                                <form method="POST" action="{{ route('kegiatan.master.destroy', $master) }}" class="inline" onsubmit="return optimaConfirm(event, 'Hapus Master Kegiatan {{ $master->nama }} beserta seluruh periode dan penugasannya?', 'Hapus Master Kegiatan')">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Hapus Master Kegiatan" class="inline-flex items-center p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg cursor-pointer transition">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        @forelse($master->periodes as $per)
                            @php
                                $terpakaiTransport = (float) $per->alokasiTransports->where('status_spj', '!=', 'Ditolak')->sum('nominal');
                                $allTasks = $per->subKegiatans->flatMap->penugasans;
                                $doneTasks = $allTasks->where('status', 'Selesai')->count();
                                $totalTasks = $allTasks->count();

                                $terpakaiHonor = (float) $per->subKegiatans->sum(function ($sub) {
                                    return $sub->penugasans->sum(function ($t) use ($sub) {
                                        $satuan = (float) ($t->honor_satuan ?: ($sub->honor_per_satuan ?? 0));
                                        return (float) ($t->honor_total ?: ($satuan * (int) $t->target_volume));
                                    });
                                });

                                $paguTrans = (float) $per->pagu_transport;
                                $paguHonor = (float) ($per->pagu_honor ?? 0);

                                $persenTrans = $paguTrans > 0 ? round(($terpakaiTransport / $paguTrans) * 100, 1) : ($terpakaiTransport > 0 ? 100 : 0);
                                $persenHonor = $paguHonor > 0 ? round(($terpakaiHonor / $paguHonor) * 100, 1) : ($terpakaiHonor > 0 ? 100 : 0);
                            @endphp
                            <tr x-show="open" x-transition.opacity.duration.150ms class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3.5 pl-10 pr-4">
                                    <p class="font-semibold text-slate-800">{{ $per->nama_periode }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $per->tgl_mulai->format('d M Y') }} &ndash; {{ $per->tgl_selesai->format('d M Y') }}</p>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <span class="font-medium text-slate-800">{{ $per->subKegiatans->count() }} tahapan</span>
                                    <span class="text-[11px] text-slate-400 block">{{ $doneTasks }} / {{ $totalTasks }} tugas selesai</span>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-700 whitespace-nowrap">
                                    Rp {{ number_format($paguTrans, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-indigo-700 whitespace-nowrap">
                                    Rp {{ number_format($paguHonor, 0, ',', '.') }}
                                </td>
                                <!-- 1 Kolom Serapan Transport & Honor dengan Progress Bar Atas Bawah -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-2">
                                        <!-- Atas: Progress Bar Transport -->
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-[10px]">
                                                <span class="font-bold text-sky-700">Transport</span>
                                                <span class="font-semibold text-slate-700">
                                                    Rp {{ number_format($terpakaiTransport, 0, ',', '.') }}
                                                    <span class="text-slate-400">({{ $persenTrans }}%)</span>
                                                </span>
                                            </div>
                                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div
                                                    class="h-full rounded-full transition-all duration-300 {{ $paguTrans > 0 && $terpakaiTransport > $paguTrans ? 'bg-rose-500' : 'bg-sky-500' }}"
                                                    style="width: {{ min(100, $persenTrans) }}%"
                                                ></div>
                                            </div>
                                        </div>

                                        <!-- Bawah: Progress Bar Honor -->
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-[10px]">
                                                <span class="font-bold text-emerald-700">Honor</span>
                                                <span class="font-semibold text-slate-700">
                                                    Rp {{ number_format($terpakaiHonor, 0, ',', '.') }}
                                                    <span class="text-slate-400">({{ $persenHonor }}%)</span>
                                                </span>
                                            </div>
                                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div
                                                    class="h-full rounded-full transition-all duration-300 {{ $paguHonor > 0 && $terpakaiHonor > $paguHonor ? 'bg-rose-500' : 'bg-emerald-500' }}"
                                                    style="width: {{ min(100, $persenHonor) }}%"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                    <a href="{{ route('kegiatan.periode.show', $per) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-lg transition">
                                        <span>Kelola Tahapan &amp; Tugas</span>
                                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                    </a>
                                    <button type="button" @click="openEditPeriode({{ $per->toJson() }}, @js($master->nama))" title="Edit Periode" class="inline-flex items-center p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg cursor-pointer transition">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <form method="POST" action="{{ route('kegiatan.periode.destroy', $per) }}" class="inline" onsubmit="return optimaConfirm(event, 'Hapus periode {{ $per->nama_periode }} beserta seluruh tahapan di dalamnya?', 'Hapus Periode')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Hapus Periode" class="inline-flex items-center p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg cursor-pointer transition">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr x-show="open">
                                <td colspan="6" class="py-3.5 pl-10 pr-4 text-slate-400 italic">Belum ada periode pada kegiatan ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400">Tidak ada data kegiatan yang sesuai filter.</td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>
    </div>

    <!-- Modal Master Kegiatan Baru -->
    <div x-show="openMaster" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
        <div @click.away="openMaster = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 space-y-5 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800">Buat Master Kegiatan Global</h3>
                <button @click="openMaster = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form method="POST" action="{{ route('kegiatan.master.store') }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Tim Kerja</label>
                    <select name="tim_kerja_id" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none">
                        @foreach($timKerjas as $tim)
                            <option value="{{ $tim->id }}">{{ $tim->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1.5">Nama Kegiatan</label>
                        <input type="text" name="nama" required placeholder="Contoh: Susenas 2026" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tahun</label>
                        <input type="number" name="tahun" value="{{ date('Y') }}" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Deskripsi</label>
                    <textarea name="deskripsi" rows="2" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openMaster = false" class="px-4 py-2.5 rounded-xl border border-slate-200 font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 cursor-pointer">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Master Kegiatan -->
    <div x-show="editMaster" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
        <div @click.away="editMaster = null" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 space-y-5 shadow-2xl border border-slate-100" x-if="editMaster">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">Edit Kegiatan</span>
                    <h3 class="text-base font-bold text-slate-800 mt-1" x-text="editMaster.nama"></h3>
                </div>
                <button @click="editMaster = null" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form method="POST" :action="'/kegiatan/master/' + editMaster.id" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Tim Kerja</label>
                    <select name="tim_kerja_id" x-model="editMaster.tim_kerja_id" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none">
                        @foreach($timKerjas as $tim)
                            <option value="{{ $tim->id }}">{{ $tim->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1.5">Nama Kegiatan</label>
                        <input type="text" name="nama" x-model="editMaster.nama" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 font-semibold">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tahun</label>
                        <input type="number" name="tahun" x-model="editMaster.tahun" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Deskripsi</label>
                    <textarea name="deskripsi" x-model="editMaster.deskripsi" rows="2" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editMaster = null" class="px-4 py-2.5 rounded-xl border border-slate-200 font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Periode -->
    <div x-show="addPeriodeMaster" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
        <div @click.away="addPeriodeMaster = null" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 space-y-5 shadow-2xl border border-slate-100" x-if="addPeriodeMaster">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-100">Periode Baru</span>
                    <h3 class="text-base font-bold text-slate-800 mt-1" x-text="addPeriodeMaster.nama"></h3>
                </div>
                <button @click="addPeriodeMaster = null" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form method="POST" :action="'/kegiatan/' + addPeriodeMaster.id + '/periode'" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Nama Periode</label>
                    <input type="text" name="nama_periode" required placeholder="Contoh: Periode April" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tanggal Mulai</label>
                        <input type="date" name="tgl_mulai" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tanggal Selesai</label>
                        <input type="date" name="tgl_selesai" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Pagu Transport Lokal (Rp)</label>
                        <input type="number" name="pagu_transport" value="0" min="0" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Pagu Honor (Rp)</label>
                        <input type="number" name="pagu_honor" value="0" min="0" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addPeriodeMaster = null" class="px-4 py-2.5 rounded-xl border border-slate-200 font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 cursor-pointer">Simpan Periode</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Periode -->
    <div x-show="editPeriode" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
        <div @click.away="editPeriode = null" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 space-y-5 shadow-2xl border border-slate-100" x-if="editPeriode">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">Edit Periode</span>
                    <h3 class="text-base font-bold text-slate-800 mt-1" x-text="editPeriode.master_nama"></h3>
                </div>
                <button @click="editPeriode = null" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form method="POST" :action="'/periode/' + editPeriode.id" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Nama Periode</label>
                    <input type="text" name="nama_periode" x-model="editPeriode.nama_periode" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 font-semibold">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tanggal Mulai</label>
                        <input type="date" name="tgl_mulai" x-model="editPeriode.tgl_mulai" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tanggal Selesai</label>
                        <input type="date" name="tgl_selesai" x-model="editPeriode.tgl_selesai" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Pagu Transport Lokal (Rp)</label>
                        <input type="number" name="pagu_transport" x-model.number="editPeriode.pagu_transport" min="0" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Pagu Honor (Rp)</label>
                        <input type="number" name="pagu_honor" x-model.number="editPeriode.pagu_honor" min="0" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editPeriode = null" class="px-4 py-2.5 rounded-xl border border-slate-200 font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Kegiatan -->
    <div x-show="openImport" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
        <div @click.away="openImport = false" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 space-y-4 shadow-2xl border border-slate-100">
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
                    <button type="button" @click="openImport = false" class="px-4 py-2 rounded-xl border border-slate-200 cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold cursor-pointer">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
