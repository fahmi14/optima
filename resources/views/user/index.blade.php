@extends('layouts.app')

@section('title', 'Data Pengguna')

@section('content')
<div class="space-y-4">
    <!-- Header Page Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Pengguna & Petugas</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola akun, tingkat akses (Admin/Operator), serta profil Pegawai Organik dan Mitra Lapangan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="document.getElementById('modal-import-user').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
                <i data-lucide="file-up" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Import Excel</span>
            </button>
            <a href="{{ route('user.export') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
                <i data-lucide="file-down" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Export Excel</span>
            </a>
            <a href="{{ route('user.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-slate-800 text-white rounded-lg hover:bg-slate-700 transition shadow-xs">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Pengguna</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('user.index') }}" class="flex flex-wrap items-center gap-2 w-full">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, NIP, NIK..." class="w-full text-xs pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400">
            </div>

            <select name="tipe" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-slate-700 focus:outline-none">
                <option value="">Semua Tipe</option>
                <option value="organik" {{ request('tipe') == 'organik' ? 'selected' : '' }}>Organik (PNS/PPPK)</option>
                <option value="mitra" {{ request('tipe') == 'mitra' ? 'selected' : '' }}>Mitra Statistik</option>
            </select>

            <select name="level" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-slate-700 focus:outline-none">
                <option value="">Semua Level</option>
                <option value="admin" {{ request('level') == 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="operator" {{ request('level') == 'operator' ? 'selected' : '' }}>Operator</option>
            </select>

            <button type="submit" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition shrink-0">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'tipe', 'level']))
                <a href="{{ route('user.index') }}" class="text-xs text-slate-400 hover:text-slate-600 shrink-0">Reset</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-semibold text-slate-400 tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Nama & Akun</th>
                        <th class="py-3 px-4 text-center">Level</th>
                        <th class="py-3 px-4 text-center">Tipe</th>
                        <th class="py-3 px-4">Atribut Khusus (Organik / Mitra)</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4">
                                <span class="font-semibold text-slate-800 block">{{ $u->name }}</span>
                                <span class="text-[11px] text-slate-400">{{ $u->email }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium {{ $u->level == 'admin' ? 'bg-[#f0eef6] text-[#4d3a77]' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($u->level) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $u->tipe == 'organik' ? 'bg-[#edf2ee] text-[#2d5f3e]' : 'bg-[#f6f2ec] text-[#8c5828]' }}">
                                    {{ ucfirst($u->tipe) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                @if($u->tipe == 'organik' && $u->organik)
                                    <div><span class="font-medium text-slate-700">NIP:</span> {{ $u->organik->nip ?? '-' }} &bull; <span class="font-medium text-slate-700">Jabatan:</span> {{ $u->organik->jabatan ?? '-' }} ({{ $u->organik->golongan ?? '-' }})</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5"><span class="font-medium text-slate-600">ID Tim:</span> {{ is_array($u->organik->id_tim) ? implode(', ', $u->organik->id_tim) : '-' }}</div>
                                @elseif($u->tipe == 'mitra' && $u->mitra)
                                    <div><span class="font-medium text-slate-700">NIK:</span> {{ $u->mitra->nik ?? '-' }} &bull; <span class="font-medium text-slate-700">Kec:</span> {{ $u->mitra->kecamatan ?? '-' }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5"><span class="font-medium text-slate-600">HP:</span> {{ $u->mitra->no_hp ?? '-' }}</div>
                                @else
                                    <span class="text-slate-400 italic">Belum ada detail khusus</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('user.edit', $u) }}" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-md transition" title="Edit">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <form action="{{ route('user.destroy', $u) }}" method="POST" onsubmit="return confirm('Hapus data pengguna ini?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition" title="Hapus">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 italic">Belum ada data pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Modal Import User -->
<div id="modal-import-user" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-slate-600"></i>
                Import Pengguna dari Excel
            </h3>
            <button onclick="document.getElementById('modal-import-user').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('user.import') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">File Spreadsheet (.xlsx, .xls, .csv)</label>
                <input type="file" name="file" required class="block w-full text-xs text-slate-500 border border-slate-200 rounded-lg p-2 bg-slate-50 focus:outline-none">
            </div>
            <p class="text-[11px] text-slate-400 leading-relaxed">
                Kolom Excel: A=ID, B=nama, C=email, D=level, E=tipe, F=nip, G=jabatan, H=golongan, I=id_tim, J=nik, K=kecamatan, L=no_hp.
            </p>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-import-user').classList.add('hidden')" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg">Batal</button>
                <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg">Mulai Import</button>
            </div>
        </form>
    </div>
</div>
@endsection
