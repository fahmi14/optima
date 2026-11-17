@extends('layouts.app')

@section('title', 'Data Pegawai Organik')

@section('content')
<div x-data="{ openAdd: false, openImport: false, editPegawai: null }">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Master Data Pegawai Organik</h1>
            <p class="text-xs text-slate-500">Kelola data pegawai BPS Kabupaten Wonosobo (NIP, email, jabatan, pangkat/golongan, nomor HP, alamat).</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button @click="openImport = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl cursor-pointer">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Import Excel Pegawai</span>
            </button>
            <button @click="openAdd = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900 rounded-xl cursor-pointer">
                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Pegawai</span>
            </button>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" action="{{ route('pegawai.index') }}" class="bg-white border border-slate-200/80 rounded-2xl p-4 mb-6 flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIP, email, jabatan, pangkat/gol..." class="flex-1 min-w-[220px] text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
        <select name="role" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Role</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin (IPDS)</option>
            <option value="pimpinan" {{ request('role') === 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
            <option value="katim" {{ request('role') === 'katim' ? 'selected' : '' }}>Ketua Tim</option>
            <option value="keuangan" {{ request('role') === 'keuangan' ? 'selected' : '' }}>Keuangan / Umum</option>
            <option value="organik" {{ request('role') === 'organik' ? 'selected' : '' }}>Pegawai Organik</option>
        </select>
        <select name="tim_kerja_id" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <option value="">Semua Tim Kerja</option>
            @foreach($timKerjas as $tim)
                <option value="{{ $tim->id }}" {{ request('tim_kerja_id') == $tim->id ? 'selected' : '' }}>{{ $tim->nama }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer">Filter</button>
        <a href="{{ route('pegawai.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Reset</a>
    </form>

    <!-- Table -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-semibold text-slate-500 uppercase">
                        <th class="py-3 px-4">Pegawai &amp; NIP</th>
                        <th class="py-3 px-4">Jabatan &amp; Pangkat/Gol</th>
                        <th class="py-3 px-4">Kontak &amp; Alamat</th>
                        <th class="py-3 px-4">Tim Kerja &amp; Role</th>
                        <th class="py-3 px-4">Beban Kerja</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pegawais as $p)
                        @php $w = $workloads[$p->id]; @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-4">
                                <p class="font-semibold text-slate-800">{{ $p->name }}</p>
                                <p class="text-[11px] text-slate-400">NIP: {{ $p->nip ?? '-' }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-medium text-slate-700">{{ $p->jabatan ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $p->pangkat_golongan ?? '-' }}</p>
                            </td>
                            <td class="py-3 px-4 max-w-[240px]">
                                <p class="text-slate-700 truncate">{{ $p->email }}</p>
                                <p class="text-[11px] text-slate-500">HP: {{ $p->no_hp ?? '-' }}</p>
                                @if($p->alamat)
                                    <p class="text-[10px] text-slate-400 truncate" title="{{ $p->alamat }}">{{ $p->alamat }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded-md bg-slate-100 text-slate-700">{{ $p->roleLabel() }}</span>
                                <p class="text-[11px] text-slate-500 mt-1">{{ $p->timKerja?->nama ?? '-' }}</p>
                            </td>
                            <td class="py-3 px-4 min-w-[150px]">
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="font-semibold">{{ $w['total_poin'] }}/{{ $w['kapasitas_maks'] }}</span>
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $w['badge_class'] }}">{{ $w['status'] }}</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full {{ $w['bar_class'] }}" style="width: {{ $w['bar_persen'] }}%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                <button @click="editPegawai = {{ $p->toJson() }}" class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg cursor-pointer">Edit</button>
                                @if($p->id !== auth()->id())
                                <form method="POST" action="{{ route('pegawai.destroy', $p) }}" class="inline" onsubmit="return optimaConfirm(event, 'Hapus pegawai {{ $p->name }}? Seluruh riwayat penugasan juga akan terpengaruh.', 'Hapus Pegawai')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg cursor-pointer">Hapus</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada data pegawai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">{{ $pegawais->links() }}</div>
    </div>

    <!-- Modal Tambah Pegawai -->
    <div x-show="openAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="openAdd = false" class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800">Tambah Pegawai Organik</h3>
                <button @click="openAdd = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form method="POST" action="{{ route('pegawai.store') }}" class="space-y-3 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Nama Lengkap</label><input type="text" name="name" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">NIP</label><input type="text" name="nip" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Email</label><input type="email" name="email" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">Username (opsional)</label><input type="text" name="username" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Jabatan</label><input type="text" name="jabatan" placeholder="Statistisi Ahli Pertama" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">Pangkat / Golongan</label><input type="text" name="pangkat_golongan" placeholder="Penata Muda Tk. I / III/b" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Nomor HP</label><input type="text" name="no_hp" placeholder="0812xxxxxxxx" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">Password</label><input type="password" name="password" value="bps3307" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Alamat</label>
                    <textarea name="alamat" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold mb-1">Role</label>
                        <select name="role" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                            <option value="organik">Pegawai Organik</option>
                            <option value="katim">Ketua Tim</option>
                            <option value="keuangan">Keuangan / Umum</option>
                            <option value="pimpinan">Pimpinan</option>
                            <option value="admin">Admin (IPDS)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Tim Kerja</label>
                        <select name="tim_kerja_id" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                            <option value="">- Pilih Tim -</option>
                            @foreach($timKerjas as $tim)<option value="{{ $tim->id }}">{{ $tim->nama }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="block font-semibold mb-1">Kapasitas Maks</label><input type="number" name="kapasitas_maks" value="100" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="flex justify-end gap-2 pt-3">
                    <button type="button" @click="openAdd = false" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Pegawai -->
    <div x-show="editPegawai" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="editPegawai = null" class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto" x-if="editPegawai">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800">Edit Data Pegawai</h3>
                <button @click="editPegawai = null" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form method="POST" :action="'/pegawai/' + editPegawai.id" class="space-y-3 text-xs">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Nama Lengkap</label><input type="text" name="name" x-model="editPegawai.name" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">NIP</label><input type="text" name="nip" x-model="editPegawai.nip" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Email</label><input type="email" name="email" x-model="editPegawai.email" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">Username</label><input type="text" name="username" x-model="editPegawai.username" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Jabatan</label><input type="text" name="jabatan" x-model="editPegawai.jabatan" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">Pangkat / Golongan</label><input type="text" name="pangkat_golongan" x-model="editPegawai.pangkat_golongan" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block font-semibold mb-1">Nomor HP</label><input type="text" name="no_hp" x-model="editPegawai.no_hp" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                    <div><label class="block font-semibold mb-1">Password Baru (opsional)</label><input type="password" name="password" placeholder="Kosongkan jika tetap" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Alamat</label>
                    <textarea name="alamat" x-model="editPegawai.alamat" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold mb-1">Role</label>
                        <select name="role" x-model="editPegawai.role" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                            <option value="organik">Pegawai Organik</option>
                            <option value="katim">Ketua Tim</option>
                            <option value="keuangan">Keuangan / Umum</option>
                            <option value="pimpinan">Pimpinan</option>
                            <option value="admin">Admin (IPDS)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Tim Kerja</label>
                        <select name="tim_kerja_id" x-model="editPegawai.tim_kerja_id" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                            <option value="">- Pilih Tim -</option>
                            @foreach($timKerjas as $tim)<option value="{{ $tim->id }}">{{ $tim->nama }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="block font-semibold mb-1">Kapasitas Maks</label><input type="number" name="kapasitas_maks" x-model="editPegawai.kapasitas_maks" class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                </div>
                <div class="flex justify-end gap-2 pt-3">
                    <button type="button" @click="editPegawai = null" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Pegawai -->
    <div x-show="openImport" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="openImport = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800">Import Excel Pegawai Organik</h3>
            <p class="text-xs text-slate-500">Unduh template Excel (`nip`, `nama_lengkap`, `email`, `jabatan`, `pangkat_golongan`, `no_hp`, `alamat`, `nama_tim_kerja`, `kapasitas_maks_poin`, `password_default`).</p>
            <a href="{{ route('pegawai.template') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl hover:bg-emerald-100">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Download Template Excel (.xlsx)</span>
            </a>
            <form method="POST" action="{{ route('pegawai.import') }}" enctype="multipart/form-data" class="space-y-3 text-xs pt-2 border-t border-slate-100">
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
