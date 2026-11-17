@extends('layouts.app')

@section('title', 'Data Mitra KEPKA')

@section('content')
<div x-data="{ openAdd: false, openImport: false, detailMitra: null, editMitra: null }">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Data Mitra Statistik (KEPKA Tahunan)</h1>
            <p class="text-xs text-slate-500">Manajemen mitra terpisah per tahun KEPKA. Mendukung import langsung dari file Excel format KEPKA/SOBAT BPS.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button @click="openImport = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl cursor-pointer">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Import KEPKA (.xlsx)</span>
            </button>
            <button @click="openAdd = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl cursor-pointer">
                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Mitra</span>
            </button>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" action="{{ route('mitra.index') }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 mb-6 flex flex-wrap items-center gap-3">
        <select name="tahun" onchange="this.form.submit()" class="text-xs font-semibold bg-slate-800 text-white rounded-xl px-3 py-2">
            @forelse($tahunList as $th)
                <option value="{{ $th }}" {{ $selectedTahun == $th ? 'selected' : '' }}>KEPKA Tahun {{ $th }}</option>
            @empty
                <option value="{{ date('Y') }}">KEPKA Tahun {{ date('Y') }}</option>
            @endforelse
        </select>

        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, SOBAT ID, email, no telp, pekerjaan..." class="flex-1 min-w-[200px] text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">

        <select name="kecamatan" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Kecamatan</option>
            @foreach($kecamatans as $kec)
                <option value="{{ $kec->nama }}" {{ request('kecamatan') === $kec->nama ? 'selected' : '' }}>Kec. {{ $kec->nama }}</option>
            @endforeach
        </select>

        <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer">Filter</button>
        <a href="{{ route('mitra.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Reset</a>
    </form>

    <!-- Table -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-semibold text-slate-500 uppercase">
                        <th class="py-3 px-4">Nama &amp; SOBAT ID</th>
                        <th class="py-3 px-4">Kontak</th>
                        <th class="py-3 px-4">Domisili (Kec / Desa)</th>
                        <th class="py-3 px-4">Pendidikan &amp; Pekerjaan</th>
                        <th class="py-3 px-4">KLDI Lain</th>
                        <th class="py-3 px-4">Beban Kerja</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($mitras as $m)
                        @php $w = $workloads[$m->user_id] ?? null; @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-4">
                                <p class="font-semibold text-slate-800">{{ $m->nama_lengkap }}</p>
                                <p class="text-[11px] text-slate-500">SOBAT ID: {{ $m->sobat_id ?? '-' }}</p>
                                <p class="text-[10px] text-slate-400">{{ $m->jenis_kelamin ?? '-' }} &middot; {{ $m->umur ? $m->umur . ' thn' : '-' }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="text-slate-700">{{ $m->email ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $m->no_telp ?? '-' }}</p>
                            </td>
                            <td class="py-3 px-4 max-w-[220px]">
                                <p class="font-medium text-slate-700">{{ $m->alamat_kecamatan ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $m->alamat_desa ?? '-' }}</p>
                                <p class="text-[10px] text-slate-400 truncate" title="{{ $m->alamat_detail }}">{{ $m->alamat_detail ?? '-' }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="text-slate-700">{{ $m->pendidikan ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $m->pekerjaan ?? '-' }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $m->terikat_kldi === 'Ya' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $m->terikat_kldi ?? 'Tidak' }}
                                </span>
                                @if($m->nama_kldi)
                                    <p class="text-[10px] text-slate-400 mt-1 max-w-[140px] truncate" title="{{ $m->nama_kldi }}">{{ $m->nama_kldi }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-4 min-w-[140px]">
                                @if($w)
                                    <div class="flex items-center justify-between text-[11px] mb-1">
                                        <span>{{ $w['total_poin'] }}/{{ $w['kapasitas_maks'] }}</span>
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $w['badge_class'] }}">{{ $w['status'] }}</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full {{ $w['bar_class'] }}" style="width: {{ $w['bar_persen'] }}%"></div>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                <button @click="detailMitra = {{ $m->toJson() }}" class="px-2 py-1 text-[11px] font-semibold bg-sky-50 text-sky-700 rounded-lg cursor-pointer">Detail</button>
                                <button @click="editMitra = {{ $m->toJson() }}" class="px-2 py-1 text-[11px] font-semibold bg-slate-100 text-slate-700 rounded-lg cursor-pointer">Edit</button>
                                <form method="POST" action="{{ route('mitra.destroy', $m) }}" class="inline" onsubmit="return confirm('Hapus mitra dari KEPKA tahun {{ $m->tahun }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-[11px] font-semibold bg-rose-50 text-rose-700 rounded-lg cursor-pointer">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-400">Belum ada data mitra KEPKA tahun {{ $selectedTahun }}. Silakan import file Excel KEPKA.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">{{ $mitras->links() }}</div>
    </div>

    <!-- Modal Detail Mitra KEPKA -->
    <div x-show="detailMitra" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="detailMitra = null" class="bg-white rounded-2xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto" x-if="detailMitra">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-800" x-text="detailMitra.nama_lengkap"></h3>
                    <p class="text-[11px] text-slate-400">KEPKA Tahun <span x-text="detailMitra.tahun"></span> &middot; SOBAT ID: <span x-text="detailMitra.sobat_id || '-'"></span></p>
                </div>
                <button @click="detailMitra = null" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-slate-50 p-4 rounded-xl">
                <p><strong>Email:</strong> <span x-text="detailMitra.email || '-'"></span></p>
                <p><strong>No Telp:</strong> <span x-text="detailMitra.no_telp || '-'"></span></p>
                <p><strong>TTL:</strong> <span x-text="detailMitra.ttl || '-'"></span></p>
                <p><strong>Umur / JK:</strong> <span x-text="(detailMitra.umur || '-') + ' / ' + (detailMitra.jenis_kelamin || '-')"></span></p>
                <p><strong>Status Kawin:</strong> <span x-text="detailMitra.status_kawin || '-'"></span></p>
                <p><strong>Pendidikan:</strong> <span x-text="detailMitra.pendidikan || '-'"></span></p>
                <p><strong>Pekerjaan:</strong> <span x-text="detailMitra.pekerjaan || '-'"></span></p>
                <p><strong>Pekerjaan Lain:</strong> <span x-text="detailMitra.deskripsi_pekerjaan_lain || '-'"></span></p>
                <p><strong>Provinsi:</strong> <span x-text="detailMitra.alamat_provinsi || '-'"></span></p>
                <p><strong>Kab/Kota:</strong> <span x-text="detailMitra.alamat_kab || '-'"></span></p>
                <p><strong>Kecamatan:</strong> <span x-text="detailMitra.alamat_kecamatan || '-'"></span></p>
                <p><strong>Desa/Kel:</strong> <span x-text="detailMitra.alamat_desa || '-'"></span></p>
                <p class="sm:col-span-2"><strong>Alamat Detail:</strong> <span x-text="detailMitra.alamat_detail || '-'"></span></p>
                <p><strong>Terikat Kontrak KLDI Lain:</strong> <span x-text="detailMitra.terikat_kldi || '-'"></span></p>
                <p><strong>Nama KLDI:</strong> <span x-text="detailMitra.nama_kldi || '-'"></span></p>
                <p class="sm:col-span-2"><strong>Keterangan KLDI:</strong> <span x-text="detailMitra.keterangan_kldi || '-'"></span></p>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Mitra -->
    <div x-show="openAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="openAdd = false" class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-sm font-bold text-slate-800">Tambah Mitra KEPKA</h3>
            <form method="POST" action="{{ route('mitra.store') }}" class="space-y-3 text-xs">
                @csrf
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2"><label class="block font-semibold mb-1">Nama Lengkap</label><input type="text" name="nama_lengkap" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">Tahun KEPKA</label><input type="number" name="tahun" value="{{ $selectedTahun }}" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">SOBAT ID</label><input type="text" name="sobat_id" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">Email</label><input type="email" name="email" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">No Telp</label><input type="text" name="no_telp" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div>
                        <label class="block font-semibold mb-1">Kecamatan</label>
                        <select name="kecamatan_id" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                            <option value="">- Pilih Kecamatan -</option>
                            @foreach($kecamatans as $kec)<option value="{{ $kec->id }}">{{ $kec->nama }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div><label class="block font-semibold mb-1">Alamat Detail</label><textarea name="alamat_detail" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2"></textarea></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openAdd = false" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Mitra -->
    <div x-show="editMitra" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="editMitra = null" class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto" x-if="editMitra">
            <h3 class="text-sm font-bold text-slate-800">Edit Data Mitra KEPKA</h3>
            <form method="POST" :action="'/mitra/' + editMitra.id" class="space-y-3 text-xs">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Nama Lengkap</label><input type="text" name="nama_lengkap" x-model="editMitra.nama_lengkap" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">SOBAT ID</label><input type="text" name="sobat_id" x-model="editMitra.sobat_id" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Email</label><input type="email" name="email" x-model="editMitra.email" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">No Telp</label><input type="text" name="no_telp" x-model="editMitra.no_telp" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Pendidikan</label><input type="text" name="pendidikan" x-model="editMitra.pendidikan" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">Pekerjaan</label><input type="text" name="pekerjaan" x-model="editMitra.pekerjaan" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div><label class="block font-semibold mb-1">Alamat Detail</label><textarea name="alamat_detail" x-model="editMitra.alamat_detail" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2"></textarea></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editMitra = null" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import KEPKA -->
    <div x-show="openImport" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="openImport = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800">Import Excel Mitra KEPKA</h3>
            <p class="text-xs text-slate-500">Mendukung langsung file format <strong>kepka.xlsx</strong> (Nama Lengkap, Alamat Detail, Alamat Provinsi, Kab/Kota, Kecamatan, Desa/Kel, TTL, Umur, JK, Status Kawin, Pendidikan, Pekerjaan, KLDI, No Telp, SOBAT ID, Email).</p>
            <a href="{{ route('mitra.template') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl hover:bg-emerald-100">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Download Template KEPKA (.xlsx)</span>
            </a>
            <form method="POST" action="{{ route('mitra.import') }}" enctype="multipart/form-data" class="space-y-3 text-xs pt-2 border-t border-slate-100">
                @csrf
                <div>
                    <label class="block font-semibold mb-1">Tahun KEPKA</label>
                    <input type="number" name="tahun" value="{{ date('Y') }}" min="2020" max="2099" required class="w-full border border-slate-200 rounded-xl px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold mb-1">File Excel (.xlsx / .csv)</label>
                    <input type="file" name="file" accept=".xlsx,.csv,.xls" required class="w-full border border-slate-200 rounded-xl p-2">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="openImport = false" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
