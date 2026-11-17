@extends('layouts.app')

@section('title', 'Pegawai & Mitra Statistik')

@section('content')
<div x-data="{
    openAdd: false,
    openImportOrganik: false,
    openImportMitra: false,
    editUser: null,
    formTipe: 'organik'
}">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Master Pegawai &amp; Mitra Statistik</h1>
            <p class="text-xs text-slate-500">Kelola data Pegawai Organik, Mitra Statistik (SOBAT), kapasitas beban kerja, dan import massal Excel.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button @click="openImportOrganik = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl transition cursor-pointer">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Import Organik</span>
            </button>
            <button @click="openImportMitra = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl transition cursor-pointer">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-sky-600"></i>
                <span>Import Mitra SOBAT</span>
            </button>
            <button @click="openAdd = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl transition cursor-pointer">
                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Pengguna</span>
            </button>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" action="{{ route('user.index') }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 mb-6 flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIP, ID SOBAT, email..." class="flex-1 min-w-[200px] text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
        <select name="tipe" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Tipe</option>
            <option value="organik" {{ request('tipe') === 'organik' ? 'selected' : '' }}>Organik</option>
            <option value="mitra" {{ request('tipe') === 'mitra' ? 'selected' : '' }}>Mitra</option>
        </select>
        <select name="tim_kerja_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Tim Kerja</option>
            @foreach($timKerjas as $tim)
                <option value="{{ $tim->id }}" {{ request('tim_kerja_id') == $tim->id ? 'selected' : '' }}>{{ $tim->nama }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer">Filter</button>
        <a href="{{ route('user.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Reset</a>
    </form>

    <!-- Table -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-semibold text-slate-500 uppercase">
                        <th class="py-3 px-4">Nama &amp; Identitas</th>
                        <th class="py-3 px-4">Role &amp; Tipe</th>
                        <th class="py-3 px-4">Tim Kerja / Domisili</th>
                        <th class="py-3 px-4">Beban Kerja</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                        @php $w = $workloads[$u->id]; @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-4">
                                <p class="font-semibold text-slate-800">{{ $u->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $u->email }} @if($u->username) &middot; @<span>{{ $u->username }}</span> @endif</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">
                                    @if($u->nip) NIP: {{ $u->nip }} @endif
                                    @if($u->id_sobat) ID SOBAT: {{ $u->id_sobat }} @endif
                                </p>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded-md bg-slate-100 text-slate-700">{{ $u->roleLabel() }}</span>
                                <span class="block text-[11px] text-slate-400 mt-1">{{ ucfirst($u->tipe) }}</span>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                <p>{{ $u->timKerja?->nama ?? '-' }}</p>
                                <p class="text-[11px] text-slate-400">Kec. {{ $u->kecamatan?->nama ?? '-' }}</p>
                            </td>
                            <td class="py-3 px-4 min-w-[160px]">
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="font-semibold">{{ $w['total_poin'] }}/{{ $w['kapasitas_maks'] }} poin</span>
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $w['badge_class'] }}">{{ $w['status'] }}</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full {{ $w['bar_class'] }}" style="width: {{ $w['bar_persen'] }}%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right space-x-1">
                                <button @click="editUser = {{ $u->toJson() }}" class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg cursor-pointer">Edit</button>
                                @if($u->id !== auth()->id())
                                <form method="POST" action="{{ route('user.destroy', $u) }}" class="inline" onsubmit="return optimaConfirm(event, 'Hapus pengguna {{ $u->name }}?', 'Hapus Pengguna')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg cursor-pointer">Hapus</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-400">Belum ada data pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">{{ $users->links() }}</div>
    </div>

    <!-- Modal Tambah Pengguna -->
    <div x-show="openAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="openAdd = false" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto shadow-2xl border border-slate-100">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">Pengguna Baru</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Tambah Pegawai / Mitra</h3>
                </div>
                <button @click="openAdd = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('user.store') }}" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" required placeholder="Nama Lengkap" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Username (opsional)</label>
                        <input type="text" name="username" placeholder="username" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Email</label>
                        <input type="email" name="email" required placeholder="email@bps.go.id" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Password</label>
                        <input type="password" name="password" required value="bps3307" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Role Akses</label>
                        <select name="role" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="organik">Pegawai Organik</option>
                            <option value="mitra">Mitra Statistik</option>
                            <option value="katim">Ketua Tim</option>
                            <option value="keuangan">Subbagian Umum / Keuangan</option>
                            <option value="pimpinan">Pimpinan (Kepala BPS)</option>
                            <option value="admin">Admin (IPDS)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tipe Petugas</label>
                        <select name="tipe" x-model="formTipe" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="organik">Organik</option>
                            <option value="mitra">Mitra</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div x-show="formTipe === 'organik'">
                        <label class="block font-semibold text-slate-700 mb-1.5">NIP</label>
                        <input type="text" name="nip" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div x-show="formTipe === 'mitra'">
                        <label class="block font-semibold text-slate-700 mb-1.5">ID SOBAT</label>
                        <input type="text" name="id_sobat" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Kapasitas Maks (Jam)</label>
                        <input type="number" name="kapasitas_maks" value="160" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tim Kerja</label>
                        <select name="tim_kerja_id" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="">- Pilih Tim -</option>
                            @foreach($timKerjas as $tim)
                                <option value="{{ $tim->id }}">{{ $tim->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Domisili</label>
                        <select name="kecamatan_id" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="">- Pilih Kecamatan -</option>
                            @foreach($kecamatans as $kec)
                                <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="openAdd = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Pengguna -->
    <div x-show="editUser" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="editUser = null" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto shadow-2xl border border-slate-100" x-if="editUser">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-sky-50 text-sky-700 border border-sky-100">Perbarui</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1">Edit Pengguna</h3>
                </div>
                <button @click="editUser = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" :action="'/user/' + editUser.id" class="space-y-4 text-xs">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" x-model="editUser.name" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Username</label>
                        <input type="text" name="username" x-model="editUser.username" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Email</label>
                        <input type="email" name="email" x-model="editUser.email" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Password Baru (opsional)</label>
                        <input type="password" name="password" placeholder="Kosongkan jika tetap" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Role Akses</label>
                        <select name="role" x-model="editUser.role" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="organik">Pegawai Organik</option>
                            <option value="mitra">Mitra Statistik</option>
                            <option value="katim">Ketua Tim</option>
                            <option value="keuangan">Subbagian Umum / Keuangan</option>
                            <option value="pimpinan">Pimpinan (Kepala BPS)</option>
                            <option value="admin">Admin (IPDS)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tipe Petugas</label>
                        <select name="tipe" x-model="editUser.tipe" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="organik">Organik</option>
                            <option value="mitra">Mitra</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">NIP</label>
                        <input type="text" name="nip" x-model="editUser.nip" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">ID SOBAT</label>
                        <input type="text" name="id_sobat" x-model="editUser.id_sobat" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Kapasitas Maks</label>
                        <input type="number" name="kapasitas_maks" x-model="editUser.kapasitas_maks" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Tim Kerja</label>
                        <select name="tim_kerja_id" x-model="editUser.tim_kerja_id" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="">- Pilih Tim -</option>
                            @foreach($timKerjas as $tim)
                                <option value="{{ $tim->id }}">{{ $tim->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Kecamatan Domisili</label>
                        <select name="kecamatan_id" x-model="editUser.kecamatan_id" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                            <option value="">- Pilih Kecamatan -</option>
                            @foreach($kecamatans as $kec)
                                <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="editUser = null" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Organik -->
    <div x-show="openImportOrganik" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="openImportOrganik = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800">Import Master Pegawai Organik</h3>
            <p class="text-xs text-slate-500">Unduh template Excel terlebih dahulu agar format kolom sesuai (`nip`, `nama_lengkap`, `email`, `jabatan`, `nama_tim_kerja`, `kapasitas_maks_poin`, `password_default`).</p>
            <a href="{{ route('user.template.organik') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl hover:bg-emerald-100">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Download Template Excel (.xlsx)</span>
            </a>
            <form method="POST" action="{{ route('user.import.organik') }}" enctype="multipart/form-data" class="space-y-3 text-xs pt-2 border-t border-slate-100">
                @csrf
                <input type="file" name="file" accept=".xlsx,.csv,.xls" required class="w-full border border-slate-200 rounded-xl p-2">
                <div class="flex justify-end gap-2">
                    <button type="button" @click="openImportOrganik = false" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Mitra -->
    <div x-show="openImportMitra" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="openImportMitra = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800">Import Master Mitra Statistik (SOBAT)</h3>
            <p class="text-xs text-slate-500">Unduh template Excel (`id_sobat`, `nama_mitra`, `email_atau_username`, `no_hp`, `kecamatan_domisili`, `kapasitas_maks_poin`).</p>
            <a href="{{ route('user.template.mitra') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200 rounded-xl hover:bg-sky-100">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Download Template Excel (.xlsx)</span>
            </a>
            <form method="POST" action="{{ route('user.import.mitra') }}" enctype="multipart/form-data" class="space-y-3 text-xs pt-2 border-t border-slate-100">
                @csrf
                <input type="file" name="file" accept=".xlsx,.csv,.xls" required class="w-full border border-slate-200 rounded-xl p-2">
                <div class="flex justify-end gap-2">
                    <button type="button" @click="openImportMitra = false" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
