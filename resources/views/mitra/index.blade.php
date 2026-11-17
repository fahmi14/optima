@extends('layouts.app')

@section('title', 'Data Mitra KEPKA')

@section('content')
<div x-data="{ openAdd: false, openImport: false, detailMitra: null, editMitra: null }" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Data Mitra Statistik (KEPKA Tahunan)</h1>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Manajemen mitra terpisah per tahun KEPKA. Setiap tahun dapat diimport secara mandiri sehingga penugasan selalu mengacu pada daftar Mitra KEPKA resmi tahun bersangkutan.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button @click="openImport = true" class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold bg-white border border-slate-200/90 text-slate-700 hover:bg-slate-50 rounded-xl transition shadow-2xs cursor-pointer">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                <span>Import KEPKA (.xlsx)</span>
            </button>
            <button @click="openAdd = true" class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl transition shadow-2xs cursor-pointer">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Tambah Mitra</span>
            </button>
        </div>
    </div>

    <!-- Filter & Tahun KEPKA (Lega & Kalem) -->
    <form method="GET" action="{{ route('mitra.index') }}" class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs flex flex-wrap items-center gap-3.5">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500">Tahun KEPKA:</span>
            <select name="tahun" onchange="this.form.submit()" class="text-xs font-bold bg-slate-800 text-white rounded-xl px-4 py-2.5 shadow-2xs cursor-pointer focus:outline-none">
                @forelse($tahunList as $th)
                    <option value="{{ $th }}" {{ $selectedTahun == $th ? 'selected' : '' }}>KEPKA Tahun {{ $th }}</option>
                @empty
                    <option value="{{ date('Y') }}">KEPKA Tahun {{ date('Y') }}</option>
                @endforelse
            </select>
        </div>

        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, SOBAT ID, email, no telp, pekerjaan..." class="flex-1 min-w-[220px] text-xs bg-slate-50/80 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">

        <select name="kecamatan" class="text-xs bg-slate-50/80 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
            <option value="">Semua Kecamatan</option>
            @foreach($kecamatans as $kec)
                <option value="{{ $kec->nama }}" {{ request('kecamatan') === $kec->nama ? 'selected' : '' }}>Kec. {{ $kec->nama }}</option>
            @endforeach
        </select>

        <button type="submit" class="px-5 py-2.5 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer transition shadow-2xs">Filter</button>
        <a href="{{ route('mitra.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">Reset</a>
    </form>

    <!-- Table Mitra KEPKA -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-5">Nama &amp; SOBAT ID</th>
                        <th class="py-3.5 px-4">Kontak</th>
                        <th class="py-3.5 px-4">Domisili (Kec / Desa)</th>
                        <th class="py-3.5 px-4">Pendidikan &amp; Pekerjaan</th>
                        <th class="py-3.5 px-4">KLDI Lain</th>
                        <th class="py-3.5 px-4">Beban Jam Kerja</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($mitras as $m)
                        @php $w = $workloads[$m->user_id] ?? null; @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-5">
                                <p class="font-bold text-slate-800 text-sm">{{ $m->nama_lengkap }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    SOBAT ID: <span class="font-mono text-slate-600 font-semibold">{{ $m->sobat_id ?? '-' }}</span>
                                    @if($m->umur) &bull; {{ $m->umur }} th ({{ $m->jenis_kelamin ?? '-' }}) @endif
                                </p>
                            </td>
                            <td class="py-3.5 px-4">
                                <p class="text-slate-700 font-medium">{{ $m->email ?? '-' }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $m->no_telp ?? '-' }}</p>
                            </td>
                            <td class="py-3.5 px-4 max-w-[220px]">
                                <p class="font-semibold text-slate-700">{{ $m->alamat_kecamatan ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $m->alamat_desa ?? '-' }}</p>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5" title="{{ $m->alamat_detail }}">{{ $m->alamat_detail ?? '-' }}</p>
                            </td>
                            <td class="py-3.5 px-4">
                                <p class="text-slate-700 font-medium">{{ $m->pendidikan ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $m->pekerjaan ?? '-' }}</p>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full {{ $m->terikat_kldi === 'Ya' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $m->terikat_kldi ?? 'Tidak' }}
                                </span>
                                @if($m->nama_kldi)
                                    <p class="text-[10px] text-slate-400 mt-1 max-w-[140px] truncate" title="{{ $m->nama_kldi }}">{{ $m->nama_kldi }}</p>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 min-w-[150px]">
                                @if($w)
                                    <div class="flex items-center justify-between text-[11px] mb-1">
                                        <span class="font-bold text-slate-700">{{ $w['total_jam'] ?? $w['total_poin'] }} Jam</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $w['badge_class'] }}">{{ $w['status'] }}</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full {{ $w['bar_class'] }}" style="width: {{ $w['bar_persen'] }}%"></div>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-right space-x-1.5 whitespace-nowrap">
                                <button @click="detailMitra = {{ $m->toJson() }}" class="px-3 py-1.5 text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 rounded-xl transition cursor-pointer">Detail</button>
                                <button @click="editMitra = {{ $m->toJson() }}" class="px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl transition cursor-pointer">Edit</button>
                                <form method="POST" action="{{ route('mitra.destroy', $m) }}" class="inline" onsubmit="return optimaConfirm(event, 'Hapus mitra {{ $m->user?->name }} dari KEPKA tahun {{ $m->tahun }}?', 'Hapus Mitra KEPKA')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-xl transition cursor-pointer">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                Belum ada data mitra KEPKA tahun {{ $selectedTahun }}. Silakan import file Excel KEPKA untuk tahun ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination Rapi Sesuai Tema Pastel -->
        <div class="p-4 sm:p-5 border-t border-slate-100 bg-white">
            {{ $mitras->links() }}
        </div>
    </div>

    <!-- Modal Detail Mitra KEPKA (Lega & Kalem) -->
    <div x-show="detailMitra" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="detailMitra = null" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto shadow-2xl border border-slate-100" x-if="detailMitra">
            <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                <div class="space-y-1">
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-sky-50 text-sky-700 border border-sky-100">Profil Mitra KEPKA</span>
                    <h3 class="text-xl font-bold text-slate-800 tracking-tight" x-text="detailMitra.nama_lengkap"></h3>
                    <p class="text-xs text-slate-400">KEPKA Tahun <strong class="text-slate-700" x-text="detailMitra.tahun"></strong> &bull; SOBAT ID: <span class="font-mono text-slate-700 font-semibold" x-text="detailMitra.sobat_id || '-'"></span></p>
                </div>
                <button @click="detailMitra = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
                <p><strong>Email:</strong> <span class="text-slate-700" x-text="detailMitra.email || '-'"></span></p>
                <p><strong>No Telp:</strong> <span class="text-slate-700" x-text="detailMitra.no_telp || '-'"></span></p>
                <p><strong>TTL:</strong> <span class="text-slate-700" x-text="detailMitra.ttl || '-'"></span></p>
                <p><strong>Umur / JK:</strong> <span class="text-slate-700" x-text="(detailMitra.umur || '-') + ' th / ' + (detailMitra.jenis_kelamin || '-')"></span></p>
                <p><strong>Status Kawin:</strong> <span class="text-slate-700" x-text="detailMitra.status_kawin || '-'"></span></p>
                <p><strong>Pendidikan:</strong> <span class="text-slate-700" x-text="detailMitra.pendidikan || '-'"></span></p>
                <p><strong>Pekerjaan:</strong> <span class="text-slate-700" x-text="detailMitra.pekerjaan || '-'"></span></p>
                <p><strong>Pekerjaan Lain:</strong> <span class="text-slate-700" x-text="detailMitra.deskripsi_pekerjaan_lain || '-'"></span></p>
                <p><strong>Kecamatan:</strong> <span class="text-slate-700" x-text="detailMitra.alamat_kecamatan || '-'"></span></p>
                <p><strong>Desa / Kelurahan:</strong> <span class="text-slate-700" x-text="detailMitra.alamat_desa || '-'"></span></p>
                <p class="sm:col-span-2"><strong>Alamat Lengkap:</strong> <span class="text-slate-700" x-text="detailMitra.alamat_detail || '-'"></span></p>
                <p><strong>Terikat KLDI:</strong> <span class="text-slate-700" x-text="detailMitra.terikat_kldi || 'Tidak'"></span></p>
                <p><strong>Nama KLDI:</strong> <span class="text-slate-700" x-text="detailMitra.nama_kldi || '-'"></span></p>
            </div>
            <div class="flex justify-end pt-2">
                <button type="button" @click="detailMitra = null" class="px-5 py-2.5 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 transition shadow-2xs cursor-pointer">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Mitra (Lega & Kalem) -->
    <div x-show="openAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="openAdd = false" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto shadow-2xl border border-slate-100">
            <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">Entri Baru</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Tambah Mitra KEPKA</h3>
                </div>
                <button @click="openAdd = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="POST" action="{{ route('mitra.store') }}" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required placeholder="Nama Lengkap Mitra" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tahun KEPKA</label>
                        <input type="number" name="tahun" value="{{ $selectedTahun }}" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">SOBAT ID</label>
                        <input type="text" name="sobat_id" placeholder="3307..." class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Email</label>
                        <input type="email" name="email" required placeholder="email@gmail.com" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">No Telp</label>
                        <input type="text" name="no_telp" placeholder="08..." class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Domisili</label>
                        <select name="kecamatan_id" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="">- Pilih Kecamatan -</option>
                            @foreach($kecamatans as $kec)<option value="{{ $kec->id }}">{{ $kec->nama }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Alamat Detail</label>
                    <textarea name="alamat_detail" rows="2" placeholder="Nama Dusun, RT/RW, Desa..." class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="openAdd = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Mitra</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Mitra -->
    <div x-show="editMitra" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="editMitra = null" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto shadow-2xl border border-slate-100" x-if="editMitra">
            <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-sky-50 text-sky-700 border border-sky-100">Perbarui</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Edit Data Mitra KEPKA</h3>
                </div>
                <button @click="editMitra = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="POST" :action="'/mitra/' + editMitra.id" class="space-y-4 text-xs">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" x-model="editMitra.nama_lengkap" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">SOBAT ID</label>
                        <input type="text" name="sobat_id" x-model="editMitra.sobat_id" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Email</label>
                        <input type="email" name="email" x-model="editMitra.email" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">No Telp</label>
                        <input type="text" name="no_telp" x-model="editMitra.no_telp" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Pendidikan</label>
                        <input type="text" name="pendidikan" x-model="editMitra.pendidikan" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Pekerjaan</label>
                        <input type="text" name="pekerjaan" x-model="editMitra.pekerjaan" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Alamat Detail</label>
                    <textarea name="alamat_detail" x-model="editMitra.alamat_detail" rows="2" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="editMitra = null" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import KEPKA (Lega & Kalem) -->
    <div x-show="openImport" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="openImport = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100">
            <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-100">Import KEPKA</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Import Excel Mitra KEPKA Tahunan</h3>
                </div>
                <button @click="openImport = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">
                Mendukung langsung file format <strong>kepka.xlsx</strong> (Nama Lengkap, Alamat Detail, Provinsi, Kab/Kota, Kecamatan, Desa/Kel, TTL, Umur, JK, Status Kawin, Pendidikan, Pekerjaan, KLDI, No Telp, SOBAT ID, Email). <em>Setiap tahun KEPKA dapat diimport secara mandiri.</em>
            </p>
            <a href="{{ route('mitra.template') }}" class="inline-flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200/80 rounded-xl hover:bg-emerald-100 transition shadow-2xs">
                <i data-lucide="download" class="w-4 h-4 text-emerald-600"></i>
                <span>Download Template KEPKA (.xlsx)</span>
            </a>
            <form method="POST" action="{{ route('mitra.import') }}" enctype="multipart/form-data" class="space-y-4 text-xs pt-3 border-t border-slate-100">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Tahun KEPKA</label>
                    <input type="number" name="tahun" value="{{ $selectedTahun ?? date('Y') }}" min="2020" max="2099" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">File Excel (.xlsx / .csv)</label>
                    <input type="file" name="file" accept=".xlsx,.csv,.xls" required class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/60 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white cursor-pointer">
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="openImport = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
