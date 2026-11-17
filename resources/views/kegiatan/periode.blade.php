@extends('layouts.app')

@section('title', $periode->masterKegiatan->nama . ' - ' . $periode->nama_periode)

@section('content')
<div x-data="{
    openSub: false,
    openClone: false,
    openImportTugas: false,
    assignSub: null,
    dapatTransport: false,
    verifyTask: null
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
            <button @click="openClone = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl cursor-pointer">
                <i data-lucide="copy" class="w-3.5 h-3.5 text-amber-600"></i>
                <span>Salin Tahapan Periode Lain</span>
            </button>
            @endif
            <button @click="openImportTugas = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl cursor-pointer">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Import Penugasan Massal</span>
            </button>
            <button @click="openSub = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Tahapan Custom</span>
            </button>
        </div>
    </div>

    <!-- Pagu Transport Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4">
            <span class="text-[11px] font-medium text-slate-400">Pagu Transport Periode</span>
            <p class="text-lg font-bold text-slate-800 mt-1">Rp {{ number_format($periode->pagu_transport, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4">
            <span class="text-[11px] font-medium text-slate-400">Alokasi Transport Terpakai</span>
            <p class="text-lg font-bold text-amber-600 mt-1">Rp {{ number_format($terpakaiTransport, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4">
            <span class="text-[11px] font-medium text-slate-400">Sisa Pagu Transport</span>
            <p class="text-lg font-bold text-emerald-600 mt-1">Rp {{ number_format($sisaPagu, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Sub-Kegiatans & Penugasan -->
    <div class="space-y-5">
        @forelse($periode->subKegiatans as $sub)
            <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 bg-slate-50/70 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-200/80 text-slate-700">{{ $sub->jenis_tahapan }}</span>
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
                        <h3 class="text-sm font-bold text-slate-800 mt-1">{{ $sub->nama }}</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="assignSub = {{ $sub->toJson() }}; dapatTransport = false" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl cursor-pointer">
                            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                            <span>Distribusikan Tugas</span>
                        </button>
                        <form method="POST" action="{{ route('kegiatan.sub.destroy', $sub) }}" onsubmit="return confirm('Hapus tahapan ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg cursor-pointer"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase">
                                <th class="py-2.5 px-4">Petugas &amp; Wilayah</th>
                                <th class="py-2.5 px-4">Target / Realisasi</th>
                                <th class="py-2.5 px-4">Estimasi Waktu &amp; Deadline</th>
                                <th class="py-2.5 px-4">Transport Lokal</th>
                                <th class="py-2.5 px-4">Status &amp; Bukti</th>
                                <th class="py-2.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($sub->penugasans as $tugas)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-4">
                                        <p class="font-semibold text-slate-800">{{ $tugas->user?->name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ ucfirst($tugas->user?->tipe) }} &middot; Kec. {{ $tugas->kecamatan?->nama ?? '-' }}</p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="font-semibold text-slate-700">{{ $tugas->realisasi_volume }} / {{ $tugas->target_volume }} {{ $tugas->satuan_volume }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-sage-500" style="width: {{ $tugas->progres_persen }}%"></div>
                                            </div>
                                            <span class="text-[10px] text-slate-500">{{ $tugas->progres_persen }}%</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="block font-bold text-slate-700">{{ $tugas->durasiJam() }} Jam</span>
                                        <span class="block text-[11px] text-slate-400">{{ $tugas->deadline_tugas?->format('d/m/Y') ?? '-' }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($tugas->alokasiTransports->isNotEmpty())
                                            @foreach($tugas->alokasiTransports as $tr)
                                                <span class="inline-block px-2 py-0.5 mb-0.5 text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md">
                                                    {{ $tr->tgl_tugas->format('d/m') }}: Rp {{ number_format($tr->nominal, 0, ',', '.') }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-100 text-slate-700">{{ $tugas->status }}</span>
                                        @if($tugas->file_bukti)
                                            <a href="{{ asset($tugas->file_bukti) }}" target="_blank" class="block text-[11px] text-sky-600 hover:underline mt-1">Lihat Bukti</a>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right space-x-1">
                                        <button @click="verifyTask = {{ $tugas->toJson() }}" class="px-2.5 py-1 text-[11px] font-semibold bg-sage-50 text-sage-700 border border-sage-200 rounded-lg cursor-pointer">Verifikasi</button>
                                        <form method="POST" action="{{ route('penugasan.destroy', $tugas) }}" class="inline" onsubmit="return confirm('Hapus penugasan ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="px-2 py-1 text-[11px] font-semibold bg-rose-50 text-rose-700 rounded-lg cursor-pointer">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 px-4 text-center text-slate-400 italic">Belum ada penugasan pada tahapan ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center text-xs text-slate-400">
                Belum ada sub-kegiatan/tahapan. Tambahkan tahapan custom atau salin dari periode sebelumnya.
            </div>
        @endforelse
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
                    <input type="text" name="nama" required placeholder="Contoh: Updating Dokumen" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Jenis Tahapan</label>
                        <select name="jenis_tahapan" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="Teknis">Teknis</option>
                            <option value="Lapangan">Lapangan</option>
                            <option value="Administrasi">Administrasi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Deadline Tahapan</label>
                        <input type="date" name="deadline_tahapan" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80">
                    <div>
                        <label class="block font-semibold mb-1 text-slate-700">Waktu per Satuan (Jam)</label>
                        <input type="number" step="0.5" name="estimasi_jam_per_satuan" value="7" placeholder="Contoh: 7" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                        <span class="text-[10px] text-slate-400 mt-1 block">Misal: 7 jam / 1 dokumen</span>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-slate-700">Honor per Satuan (Rp)</label>
                        <input type="number" step="1000" name="honor_per_satuan" value="0" placeholder="0" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                        <span class="text-[10px] text-slate-400 mt-1 block">Untuk kalkulasi SBML</span>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="openSub = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Tahapan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Clone Sub-Kegiatan -->
    <div x-show="openClone" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="openClone = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-amber-50 text-amber-800 border border-amber-100">Duplikasi</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Salin Tahapan Periode Lain</h3>
                </div>
                <button @click="openClone = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">Seluruh tahapan custom dari periode sumber akan diduplikasi ke <strong>{{ $periode->nama_periode }}</strong> dalam 1 kali klik.</p>
            <form method="POST" action="{{ route('kegiatan.sub.clone', $periode) }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Pilih Periode Sumber</label>
                    <select name="source_periode_id" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                        @foreach($otherPeriodes as $op)
                            <option value="{{ $op->id }}">{{ $op->masterKegiatan?->nama }} - {{ $op->nama_periode }} ({{ $op->subKegiatans->count() }} tahapan)</option>
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

    <!-- Modal Distribusi Penugasan & Transport -->
    <div x-show="assignSub" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="assignSub = null" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto shadow-2xl border border-slate-100" x-if="assignSub">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-sky-50 text-sky-800 border border-sky-100">Penugasan</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Distribusikan Tugas: <span x-text="assignSub.nama"></span></h3>
                </div>
                <button @click="assignSub = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/sub/' + assignSub.id + '/penugasan'" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Pegawai / Mitra</label>
                        <select name="user_id" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            @foreach($petugasList as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ ucfirst($p->tipe) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Tugas</label>
                        <select name="kecamatan_id" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="">- Pilih Kecamatan -</option>
                            @foreach($kecamatans as $kec)
                                <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div><label class="block font-semibold text-slate-700 mb-1.5">Target Volume</label><input type="number" name="target_volume" value="1" min="1" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"></div>
                    <div><label class="block font-semibold text-slate-700 mb-1.5">Satuan</label><input type="text" name="satuan_volume" value="Dokumen" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"></div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Waktu / Satuan (Jam)</label>
                        <input type="number" step="0.5" name="estimasi_jam_satuan" :value="assignSub.estimasi_jam_per_satuan || 7" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Honor / Satuan (Rp)</label>
                        <input type="number" step="1000" name="honor_satuan" :value="assignSub.honor_per_satuan || 0" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Deadline Tugas</label>
                    <input type="date" name="deadline_tugas" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                </div>

                <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200 space-y-3">
                    <label class="inline-flex items-center gap-2.5 font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="dapat_transport" value="1" x-model="dapatTransport" class="w-4 h-4 rounded border-slate-300 text-slate-800 focus:ring-slate-400">
                        <span>Alokasikan Transportasi Lokal (Validasi Anti-Double SPJ &amp; Pagu)</span>
                    </label>
                    <div x-show="dapatTransport" class="grid grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Tanggal Tugas (YYYY-MM-DD, pisah koma)</label>
                            <input type="text" name="tgl_tugas_transport" placeholder="2026-02-05, 2026-02-06" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Nominal per Hari (Rp)</label>
                            <input type="number" name="nominal_transport" value="150000" min="0" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="assignSub = null" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Penugasan</button>
                </div>
            </form>
        </div>
    </div>

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
                    <select name="status" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
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
