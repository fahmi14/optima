@extends('layouts.app')

@section('title', 'Matrik Tugas')

@section('content')
<div class="space-y-4">
    <!-- Header Page Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Matrik Penugasan Petugas</h1>
            <p class="text-xs text-slate-500 mt-0.5">Pemetaan petugas, peran survei (PCL, PML, dll - JSON), alokasi SLS/wilayah, pengawas, dan beban kerja.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="document.getElementById('modal-import-matrik').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
                <i data-lucide="file-up" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Import Excel</span>
            </button>
            <a href="{{ route('matrik.export') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
                <i data-lucide="file-down" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Export Excel</span>
            </a>
            <a href="{{ route('matrik.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-slate-800 text-white rounded-lg hover:bg-slate-700 transition shadow-xs">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Buat Alokasi Matrik</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('matrik.index') }}" class="flex flex-wrap items-center gap-2 w-full">
            <div class="relative flex-1 min-w-[180px]">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kegiatan, petugas, pengawas, wilayah..." class="w-full text-xs pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400">
            </div>

            <select name="id_kegiatan" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-slate-700 focus:outline-none">
                <option value="">Semua Kegiatan</option>
                @foreach($kegiatans as $k)
                    <option value="{{ $k->id }}" {{ request('id_kegiatan') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                @endforeach
            </select>

            <select name="id_user" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-slate-700 focus:outline-none">
                <option value="">Semua Petugas</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('id_user') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ ucfirst($u->tipe) }})</option>
                @endforeach
            </select>

            <button type="submit" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition shrink-0">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'id_kegiatan', 'id_user']))
                <a href="{{ route('matrik.index') }}" class="text-xs text-slate-400 hover:text-slate-600 shrink-0">Reset</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-semibold text-slate-400 tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Kegiatan</th>
                        <th class="py-3 px-4">Petugas</th>
                        <th class="py-3 px-4">Pengawas (PML)</th>
                        <th class="py-3 px-4">Role Penugasan (JSON)</th>
                        <th class="py-3 px-4">Wilayah Tugas</th>
                        <th class="py-3 px-4 text-center">Tanggal</th>
                        <th class="py-3 px-4 text-center">Beban</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($matriks as $m)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4">
                                <span class="font-semibold text-slate-800 block text-xs">{{ $m->kegiatan?->nama }}</span>
                                <span class="text-[10px] text-slate-400">Tim: {{ $m->kegiatan?->tim?->nama ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-semibold text-slate-800 block text-xs">{{ $m->user?->name }}</span>
                                <div class="text-[11px] text-slate-400">
                                    <span class="font-medium {{ $m->user?->tipe == 'organik' ? 'text-[#2d5f3e]' : 'text-[#8c5828]' }}">
                                        {{ ucfirst($m->user?->tipe) }}
                                    </span>
                                    @if($m->user?->tipe == 'organik' && $m->user?->organik)
                                        &bull; NIP: {{ $m->user->organik->nip }}
                                    @elseif($m->user?->tipe == 'mitra' && $m->user?->mitra)
                                        &bull; HP: {{ $m->user->mitra->no_hp }}
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                @if($m->pengawas)
                                    <span class="font-semibold text-slate-800 block text-xs">{{ $m->pengawas->name }}</span>
                                    <span class="text-[10px] text-slate-400 capitalize">Pengawas ({{ $m->pengawas->tipe }})</span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Tanpa Pengawas</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @if(is_array($m->role))
                                        @foreach($m->role as $r)
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ $r }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                <span class="font-medium text-slate-800 block text-xs">{{ $m->wilayah?->nama ?? '-' }}</span>
                                <span class="text-[10px] text-slate-400">{{ $m->wilayah?->kec }} &bull; {{ $m->wilayah?->desa }}</span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-slate-600 text-[11px]">
                                {{ $m->tgl ? $m->tgl->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-block px-2.5 py-0.5 rounded-full bg-[#f4ebe1] text-[#7d4e24] font-bold text-[11px]">
                                    {{ $m->beban }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('matrik.edit', $m) }}" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-md transition" title="Edit">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <form action="{{ route('matrik.destroy', $m) }}" method="POST" onsubmit="return optimaConfirm(event, 'Hapus alokasi matrik tugas ini?', 'Hapus Matrik Tugas')" class="inline">
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
                            <td colspan="8" class="py-8 text-center text-slate-400 italic">Belum ada alokasi matrik tugas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $matriks->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Modal Import Matrik -->
<div id="modal-import-matrik" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-slate-600"></i>
                Import Matrik Tugas dari Excel
            </h3>
            <button onclick="document.getElementById('modal-import-matrik').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('matrik.import') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">File Spreadsheet (.xlsx, .xls, .csv)</label>
                <input type="file" name="file" required class="block w-full text-xs text-slate-500 border border-slate-200 rounded-lg p-2 bg-slate-50 focus:outline-none">
            </div>
            <p class="text-[11px] text-slate-400 leading-relaxed">
                Format kolom: A=ID, B=id_kegiatan, C=nama kegiatan, D=id_user, E=nama petugas, F=tipe, G=id_pengawas, H=nama pengawas, I=role, J=id_wilayah, K=nama wilayah, L=tgl, M=beban.
            </p>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-import-matrik').classList.add('hidden')" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg">Batal</button>
                <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg">Mulai Import</button>
            </div>
        </form>
    </div>
</div>
@endsection
