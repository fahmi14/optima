@extends('layouts.app')

@section('title', $periode->masterKegiatan->nama . ' - ' . $periode->nama_periode)

@section('content')
<div x-data="{
    openSub: false,
    openClone: false,
    openImportTugas: false,
    assignSub: null,
    dapatTransport: false,
    verifyTask: null,
    editTask: null,
    editSub: null,
    activeSubId: {{ $periode->subKegiatans->first()?->id ?? 'null' }},

    openEditSubModal(sub) {
        this.editSub = {
            id: sub.id,
            nama: sub.nama || '',
            no_surat_tugas: sub.no_surat_tugas || '',
            jenis_tahapan: sub.jenis_tahapan || 'Lapangan',
            level_wilayah: sub.level_wilayah || 'kecamatan',
            estimasi_jam_per_satuan: Number(sub.estimasi_jam_per_satuan || 1),
            honor_per_satuan: Number(sub.honor_per_satuan || 0),
            deadline_tahapan: sub.deadline_tahapan ? String(sub.deadline_tahapan).substring(0, 10) : ''
        };
    },

    openEditModal(task) {
        this.editTask = {
            id: task.id,
            user_name: task.user ? task.user.name : '-',
            kecamatan_id: task.kecamatan_id || '',
            target_volume: task.target_volume || 1,
            satuan_volume: task.satuan_volume || 'Dokumen',
            deadline_tugas: task.deadline_tugas ? task.deadline_tugas.substring(0, 10) : '',
            status: task.status || 'Belum Mulai',
            wilayah_tugas: Array.isArray(task.wilayah_tugas) ? task.wilayah_tugas.join(', ') : (task.wilayah_tugas || '')
        };
    }
}">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('kegiatan.index') }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 hover:text-slate-800 mb-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali ke Daftar Kegiatan</span>
            </a>
            <h1 class="text-lg font-bold text-slate-800">{{ $periode->masterKegiatan->nama }} &mdash; {{ $periode->nama_periode }}</h1>
            <p class="text-xs text-slate-500">
                {{ $periode->masterKegiatan->timKerja?->nama }} &middot; {{ $periode->tgl_mulai->format('d M Y') }} s/d {{ $periode->tgl_selesai->format('d M Y') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($otherPeriodes->isNotEmpty())
            <button @click="openClone = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl cursor-pointer shadow-2xs">
                <i data-lucide="copy" class="w-3.5 h-3.5 text-amber-600"></i>
                <span>Salin Tahapan Periode Lain</span>
            </button>
            @endif
            <button @click="openImportTugas = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl cursor-pointer shadow-2xs">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Import Penugasan Massal</span>
            </button>
            <button @click="openSub = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl cursor-pointer shadow-2xs">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Tahapan Custom</span>
            </button>
        </div>
    </div>

    <!-- Pagu Transport & Honor Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
            <span class="text-[11px] font-medium text-slate-400">Pagu Transport Periode</span>
            <p class="text-lg font-bold text-slate-800 mt-1">Rp {{ number_format($periode->pagu_transport, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
            <span class="text-[11px] font-medium text-slate-400">Alokasi Transport Terpakai</span>
            <p class="text-lg font-bold text-amber-600 mt-1">Rp {{ number_format($terpakaiTransport, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
            <span class="text-[11px] font-medium text-slate-400">Sisa Pagu Transport</span>
            <p class="text-lg font-bold text-emerald-600 mt-1">Rp {{ number_format($sisaPagu, 0, ',', '.') }}</p>
        </div>
        <div class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-4 shadow-2xs">
            <span class="text-[11px] font-medium text-indigo-600">Pagu Honor Periode</span>
            <p class="text-lg font-bold text-indigo-800 mt-1">Rp {{ number_format($periode->pagu_honor ?? 0, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- TAMPILAN TAHAPAN KEGIATAN BERBENTUK TAB DENGAN EDIT PETUGAS -->
    @if($periode->subKegiatans->isNotEmpty())
        <div class="space-y-4">
            <!-- Tab Headers (Lega, Rapi, Pastel) -->
            <div class="bg-white p-2 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-wrap gap-1.5 items-center">
                @foreach($periode->subKegiatans as $subItem)
                    <button
                        type="button"
                        @click="activeSubId = {{ $subItem->id }}"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
                        :class="activeSubId === {{ $subItem->id }} 
                            ? 'bg-slate-800 text-white shadow-2xs' 
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                    >
                        <span>{{ $subItem->nama }}</span>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px]"
                            :class="activeSubId === {{ $subItem->id }} ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'"
                        >
                            {{ $subItem->penugasans->count() }}
                        </span>
                    </button>
                @endforeach
            </div>

            <!-- Tab Contents (Menampilkan 1 Tahapan yang Aktif) -->
            @foreach($periode->subKegiatans as $sub)
                <div x-show="activeSubId === {{ $sub->id }}" x-cloak class="bg-white border border-slate-200/80 rounded-3xl shadow-2xs overflow-hidden">
                    <div class="p-5 sm:p-6 bg-slate-50/70 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-slate-200/80 text-slate-700">{{ $sub->jenis_tahapan }}</span>
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    Level: {{ ucfirst($sub->level_wilayah ?: 'kecamatan') }}
                                </span>
                                @if($sub->deadline_tahapan)
                                    <span class="text-[11px] text-slate-500">Deadline: {{ $sub->deadline_tahapan->format('d M Y') }}</span>
                                @endif
                                <span class="text-[11px] font-semibold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md border border-sky-100">
                                    Estimasi: {{ (float) ($sub->estimasi_jam_per_satuan ?: 1) }} jam / satuan
                                </span>
                                @if($sub->honor_per_satuan > 0)
                                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                                        Honor: Rp {{ number_format($sub->honor_per_satuan, 0, ',', '.') }} / satuan
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-base font-bold text-slate-800">{{ $sub->nama }}</h3>
                            @if($sub->no_surat_tugas)
                                <p class="text-[11px] font-semibold text-slate-500">
                                    No. Surat Tugas: <span class="font-mono text-slate-700">{{ $sub->no_surat_tugas }}</span>
                                </p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('penugasan.wizard', $sub) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl transition shadow-2xs cursor-pointer">
                                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                <span>Distribusi Petugas (Wizard)</span>
                            </a>
                            <button type="button" @click="openEditSubModal({{ $sub->toJson() }})" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100 hover:bg-indigo-100 rounded-xl transition cursor-pointer" title="Edit tahapan ini">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                <span>Edit</span>
                            </button>
                            <form method="POST" action="{{ route('kegiatan.sub.destroy', $sub) }}" onsubmit="return optimaConfirm(event, 'Tahapan {{ $sub->nama }} beserta seluruh penugasan di dalamnya akan dihapus permanen. Lanjutkan?', 'Hapus Tahapan Kegiatan')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 rounded-xl hover:bg-rose-50 transition cursor-pointer" title="Hapus tahapan ini">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase bg-slate-50/40">
                                    <th class="py-3 px-4">Petugas &amp; Identitas</th>
                                    <th class="py-3 px-4">Wilayah Tugas</th>
                                    <th class="py-3 px-4">Target / Realisasi</th>
                                    <th class="py-3 px-4">Estimasi Jam &amp; Deadline</th>
                                    <th class="py-3 px-4">Pengawas (PML)</th>
                                    <th class="py-3 px-4">Transport Lokal</th>
                                    <th class="py-3 px-4">Status &amp; Bukti</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($sub->penugasans as $tugas)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-3.5 px-4">
                                            <p class="font-bold text-slate-800">{{ $tugas->user?->name }}</p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                <span class="capitalize">{{ $tugas->user?->tipe }}</span> &middot; ID: {{ $tugas->user?->nip ?: ($tugas->user?->id_sobat ?: '-') }}
                                            </p>
                                        </td>
                                        <td class="py-3.5 px-4 max-w-[200px]">
                                            @if(!empty($tugas->wilayah_tugas))
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach((array)$tugas->wilayah_tugas as $w)
                                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                            {{ $w }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-slate-600 font-medium">Kec. {{ $tugas->kecamatan?->nama ?? '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <p class="font-semibold text-slate-700">{{ $tugas->realisasi_volume }} / {{ $tugas->target_volume }} {{ $tugas->satuan_volume }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <div class="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500" style="width: {{ $tugas->progres_persen }}%"></div>
                                                </div>
                                                <span class="text-[10px] text-slate-500 font-bold">{{ $tugas->progres_persen }}%</span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="block font-bold text-slate-700">{{ $tugas->durasiJam() }} Jam</span>
                                            <span class="block text-[11px] text-slate-400">{{ $tugas->deadline_tugas?->format('d/m/Y') ?? '-' }}</span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            @if($tugas->pengawas)
                                                <span class="font-semibold text-slate-800">{{ $tugas->pengawas->name }}</span>
                                                <span class="block text-[10px] text-slate-400">{{ $tugas->pengawas->roleLabel() }}</span>
                                            @else
                                                <span class="text-slate-400 italic text-[11px]">- Mandiri -</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4">
                                            @if($tugas->alokasiTransports->isNotEmpty())
                                                @foreach($tugas->alokasiTransports as $tr)
                                                    @if($tr->tgl_tugas)
                                                        <span class="inline-block px-2 py-0.5 mb-0.5 text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md">
                                                            {{ $tr->tgl_tugas->format('d/m') }}: Rp {{ number_format($tr->nominal, 0, ',', '.') }}
                                                        </span>
                                                    @else
                                                        <span class="inline-block px-2 py-0.5 mb-0.5 text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200 rounded-md">
                                                            Hari ke-{{ $tr->hari_ke ?? 1 }} (Menunggu Petugas)
                                                        </span>
                                                    @endif
                                                @endforeach
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-block px-2.5 py-0.5 text-[10px] font-bold rounded-full {{ $tugas->status === 'Selesai' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $tugas->status }}
                                            </span>
                                            @if($tugas->file_bukti)
                                                <a href="{{ asset($tugas->file_bukti) }}" target="_blank" class="block text-[11px] text-sky-600 hover:underline mt-1 font-medium">Lihat Bukti</a>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                            <!-- Tombol Edit Petugas -->
                                            <button
                                                type="button"
                                                @click="openEditModal({{ json_encode($tugas) }})"
                                                class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition cursor-pointer"
                                                title="Edit penugasan petugas ini"
                                            >
                                                Edit
                                            </button>
                                            <button @click="verifyTask = {{ $tugas->toJson() }}" class="px-2.5 py-1 text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 rounded-lg cursor-pointer">
                                                Verifikasi
                                            </button>
                                            <form method="POST" action="{{ route('penugasan.destroy', $tugas) }}" class="inline" onsubmit="return optimaConfirm(event, 'Hapus penugasan untuk {{ $tugas->user?->name }}?', 'Hapus Penugasan')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-2 py-1 text-[11px] font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg cursor-pointer">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-10 px-4 text-center text-slate-400 italic">
                                            Belum ada petugas pada tahapan ini. Silakan gunakan tombol <strong class="text-slate-600">"Distribusi Petugas (Wizard)"</strong> di atas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white border border-slate-200/80 rounded-3xl p-10 text-center text-xs text-slate-400 shadow-2xs">
            Belum ada sub-kegiatan/tahapan. Tambahkan tahapan custom atau salin dari periode sebelumnya.
        </div>
    @endif

    <!-- MODAL EDIT PENUGASAN PETUGAS (LEGA, RAPI, PASTEL) -->
    <div x-show="editTask" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="editTask = null" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100" x-if="editTask">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">Edit Penugasan</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1" x-text="editTask.user_name"></h3>
                </div>
                <button @click="editTask = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/penugasan/' + editTask.id" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Target Volume</label>
                        <input type="number" name="target_volume" x-model.number="editTask.target_volume" min="1" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white font-bold text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Satuan Volume</label>
                        <input type="text" name="satuan_volume" x-model="editTask.satuan_volume" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Tugas / Domisili</label>
                    <select name="kecamatan_id" x-model="editTask.kecamatan_id" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                        <option value="">- Tanpa Kecamatan Khusus -</option>
                        @foreach($kecamatans as $kec)
                            <option value="{{ $kec->id }}">Kec. {{ $kec->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Wilayah Tugas Spesifik (Pisah Koma jika &gt; 1)</label>
                    <input type="text" name="wilayah_tugas" x-model="editTask.wilayah_tugas" placeholder="RT 001 RW 003, RT 002 RW 003" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    <p class="text-[10px] text-slate-400 mt-1">Dapat diisi SLS, Sub-SLS, Desa, atau NKS tugas petugas.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Deadline Tugas</label>
                        <input type="date" name="deadline_tugas" x-model="editTask.deadline_tugas" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Status Penugasan</label>
                        <select name="status" x-model="editTask.status" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-semibold">
                            <option value="Belum Mulai">Belum Mulai</option>
                            <option value="Berjalan">Berjalan</option>
                            <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                            <option value="Selesai">Selesai</option>
                            <option value="Revisi">Revisi</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="editTask = null" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Tahapan (Sub-Kegiatan) -->
    <div x-show="editSub" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="editSub = null" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100" x-if="editSub">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">Edit Tahapan</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1" x-text="editSub.nama"></h3>
                </div>
                <button @click="editSub = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/sub/' + editSub.id" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Nama Tahapan</label>
                    <input type="text" name="nama" x-model="editSub.nama" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white font-semibold focus:outline-none focus:ring-1 focus:ring-slate-400">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Nomor Surat Tugas <span class="text-slate-400 font-normal">(Opsional / Boleh Kosong)</span></label>
                    <input type="text" name="no_surat_tugas" x-model="editSub.no_surat_tugas" placeholder="Contoh: B-123/3307/ST/2026" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white font-mono focus:outline-none focus:ring-1 focus:ring-slate-400">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Jenis Tahapan</label>
                        <select name="jenis_tahapan" x-model="editSub.jenis_tahapan" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none">
                            <option value="Lapangan">Lapangan</option>
                            <option value="Teknis">Teknis</option>
                            <option value="Administrasi">Administrasi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Level Wilayah</label>
                        <select name="level_wilayah" x-model="editSub.level_wilayah" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none">
                            <option value="kecamatan">Kecamatan</option>
                            <option value="desa">Desa</option>
                            <option value="sls">SLS (RT/RW)</option>
                            <option value="sub_sls">Sub-SLS</option>
                            <option value="nks">NKS</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Estimasi Jam / Satuan</label>
                        <input type="number" step="0.5" name="estimasi_jam_per_satuan" x-model.number="editSub.estimasi_jam_per_satuan" min="0.1" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white font-bold">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Honor / Satuan (Rp)</label>
                        <input type="number" name="honor_per_satuan" x-model.number="editSub.honor_per_satuan" min="0" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white font-bold">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Deadline Tahapan</label>
                    <input type="date" name="deadline_tahapan" x-model="editSub.deadline_tahapan" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white">
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="editSub = null" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Sub-Kegiatan -->
    <div x-show="openSub" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="openSub = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">Tahapan Baru</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Tambah Tahapan Custom</h3>
                </div>
                <button @click="openSub = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('kegiatan.sub.store', $periode) }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Nama Tahapan</label>
                    <input type="text" name="nama" required placeholder="Contoh: Pencacahan Lapangan / Pengolahan Dokumen" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Nomor Surat Tugas <span class="text-slate-400 font-normal">(Opsional / Boleh Kosong)</span></label>
                    <input type="text" name="no_surat_tugas" placeholder="Contoh: B-123/3307/ST/2026" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-mono">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Jenis Tahapan</label>
                        <select name="jenis_tahapan" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="Lapangan">Lapangan</option>
                            <option value="Teknis">Teknis</option>
                            <option value="Administrasi">Administrasi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Level Wilayah</label>
                        <select name="level_wilayah" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="kecamatan">Kecamatan</option>
                            <option value="desa">Desa</option>
                            <option value="sls">SLS (RT/RW)</option>
                            <option value="sub_sls">Sub-SLS</option>
                            <option value="nks">NKS</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Estimasi Jam / Satuan</label>
                        <input type="number" step="0.5" name="estimasi_jam_per_satuan" value="7" min="0.5" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-bold">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Honor / Satuan (Rp)</label>
                        <input type="number" name="honor_per_satuan" value="0" min="0" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Deadline Tahapan</label>
                    <input type="date" name="deadline_tahapan" value="{{ $periode->tgl_selesai->format('Y-m-d') }}" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="openSub = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Tahapan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Salin Tahapan Periode Lain -->
    @if($otherPeriodes->isNotEmpty())
    <div x-show="openClone" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="openClone = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-amber-50 text-amber-800 border border-amber-100">Salin Otomatis</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Salin Struktur Tahapan</h3>
                </div>
                <button @click="openClone = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('kegiatan.sub.clone', $periode) }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Pilih Periode Sumber</label>
                    <select name="source_periode_id" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                        @foreach($otherPeriodes as $op)
                            <option value="{{ $op->id }}">{{ $op->nama_periode }} ({{ $op->subKegiatans->count() }} tahapan)</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="openClone = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Salin Tahapan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Modal Verifikasi Tugas -->
    <div x-show="verifyTask" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="verifyTask = null" class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100" x-if="verifyTask">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-100">Verifikasi</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Verifikasi Hasil Tugas</h3>
                </div>
                <button @click="verifyTask = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/penugasan/' + verifyTask.id + '/verify'" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Keputusan Verifikasi</label>
                    <select name="status" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-semibold">
                        <option value="Selesai">Selesai (Disetujui)</option>
                        <option value="Revisi">Revisi (Perlu Perbaikan)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Catatan Ketua Tim</label>
                    <textarea name="catatan" rows="3" placeholder="Tambahkan catatan hasil pemeriksaan tugas..." class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="verifyTask = null" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Verifikasi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Penugasan Massal -->
    <div x-show="openImportTugas" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="openImportTugas = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-100">Import Excel</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Import Penugasan &amp; Transport</h3>
                </div>
                <button @click="openImportTugas = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <p class="text-xs text-slate-500 leading-relaxed">Setiap baris otomatis melewati validasi Anti-Double SPJ dan Cek Sisa Pagu. Baris yang bentrok akan dilewati tanpa menggagalkan baris lainnya.</p>
            
            <a href="{{ route('penugasan.template') }}" class="inline-flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200/80 rounded-xl hover:bg-emerald-100 transition shadow-2xs">
                <i data-lucide="download" class="w-4 h-4 text-emerald-600"></i>
                <span>Download Template Excel (.xlsx)</span>
            </a>

            <form method="POST" action="{{ route('penugasan.import', $periode) }}" enctype="multipart/form-data" class="space-y-4 text-xs pt-3 border-t border-slate-100">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">File Penugasan (.xlsx / .csv)</label>
                    <input type="file" name="file" accept=".xlsx,.csv,.xls" required class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/60 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white cursor-pointer">
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="openImportTugas = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
