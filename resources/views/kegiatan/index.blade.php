@extends('layouts.app')

@section('title', 'Data Kegiatan')

@section('content')
<div class="space-y-4">
    <!-- Header Page Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Kegiatan & Survei</h1>
            <p class="text-xs text-slate-500 mt-0.5">Jadwal survei statistik, tim pelaksana, serta pengelolaan administrasi & honor.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="document.getElementById('modal-import-kegiatan').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
                <i data-lucide="file-up" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Import Excel</span>
            </button>
            <a href="{{ route('kegiatan.export') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
                <i data-lucide="file-down" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Export Excel</span>
            </a>
            <a href="{{ route('kegiatan.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-slate-800 text-white rounded-lg hover:bg-slate-700 transition shadow-xs">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Kegiatan</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('kegiatan.index') }}" class="flex flex-wrap items-center gap-2 w-full">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kegiatan..." class="w-full text-xs pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400">
            </div>

            <select name="id_tim" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-slate-700 focus:outline-none">
                <option value="">Semua Tim</option>
                @foreach($tims as $t)
                    <option value="{{ $t->id }}" {{ request('id_tim') == $t->id ? 'selected' : '' }}>{{ $t->nama }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition shrink-0">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'id_tim']))
                <a href="{{ route('kegiatan.index') }}" class="text-xs text-slate-400 hover:text-slate-600 shrink-0">Reset</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-semibold text-slate-400 tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Nama Kegiatan</th>
                        <th class="py-3 px-4">Tim Pengampu</th>
                        <th class="py-3 px-4">Jadwal Pelaksanaan</th>
                        <th class="py-3 px-4">Administrasi & Anggaran (JSON)</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($kegiatans as $k)
                        @php $adm = $k->administrasi ?? []; @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4">
                                <span class="font-semibold text-slate-800 block text-xs">{{ $k->nama }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">ID #{{ $k->id }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium text-[11px]">
                                    {{ $k->tim?->nama ?? 'Tanpa Tim' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $k->tgl_mulai ? $k->tgl_mulai->format('d/m/Y') : '-' }} s/d {{ $k->tgl_selesai ? $k->tgl_selesai->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                @if(isset($adm['honor']))
                                    <div><span class="text-slate-400">Honor:</span> <span class="font-semibold text-slate-800">Rp {{ number_format($adm['honor'], 0, ',', '.') }}</span> <span class="text-[10px] text-slate-500">({{ $adm['jenis_honor'] ?? '-' }})</span></div>
                                @endif
                                @if(isset($adm['sbml']))
                                    <div class="text-[11px] text-slate-500"><span class="text-slate-400">SBML:</span> Rp {{ number_format($adm['sbml'], 0, ',', '.') }}</div>
                                @endif
                                <div class="text-[11px] text-slate-400">
                                    Petugas: <span class="font-medium text-slate-700">{{ $adm['jml_petugas'] ?? 0 }} org</span> &bull; Beban: <span class="font-medium text-slate-700">{{ $adm['beban'] ?? 0 }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('kegiatan.edit', $k) }}" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-md transition" title="Edit">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <form action="{{ route('kegiatan.destroy', $k) }}" method="POST" onsubmit="return confirm('Hapus kegiatan ini?')" class="inline">
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
                            <td colspan="5" class="py-8 text-center text-slate-400 italic">Belum ada kegiatan terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $kegiatans->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Modal Import Kegiatan -->
<div id="modal-import-kegiatan" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-slate-600"></i>
                Import Kegiatan dari Excel
            </h3>
            <button onclick="document.getElementById('modal-import-kegiatan').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('kegiatan.import') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">File Spreadsheet (.xlsx, .xls, .csv)</label>
                <input type="file" name="file" required class="block w-full text-xs text-slate-500 border border-slate-200 rounded-lg p-2 bg-slate-50 focus:outline-none">
            </div>
            <p class="text-[11px] text-slate-400 leading-relaxed">
                Format kolom: A=ID, B=nama kegiatan, C=tgl_mulai, D=tgl_selesai, E=id_tim, F=nama tim, G=honor, H=jenis_honor, I=sbml, J=jml_petugas, K=beban, L=administrasi.
            </p>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-import-kegiatan').classList.add('hidden')" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg">Batal</button>
                <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg">Mulai Import</button>
            </div>
        </form>
    </div>
</div>
@endsection
