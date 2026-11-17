@extends('layouts.app')

@section('title', 'Master Wilayah')

@section('content')
<div class="space-y-4">
    <!-- Header Page Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Master Wilayah</h1>
            <p class="text-xs text-slate-500 mt-0.5">Hierarki wilayah kerja BPS: Kabupaten, Kecamatan, Desa/Kelurahan, hingga SLS.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="document.getElementById('modal-import').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
                <i data-lucide="file-up" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Import Excel</span>
            </button>
            <a href="{{ route('wilayah.export') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
                <i data-lucide="file-down" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Export Excel</span>
            </a>
            <a href="{{ route('wilayah.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-slate-800 text-white rounded-lg hover:bg-slate-700 transition shadow-xs">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Wilayah</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200/80 flex items-center justify-between gap-3">
        <form method="GET" action="{{ route('wilayah.index') }}" class="flex items-center gap-2 w-full max-w-md">
            <div class="relative w-full">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kec, desa, SLS..." class="w-full text-xs pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400">
            </div>
            <button type="submit" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition shrink-0">
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route('wilayah.index') }}" class="text-xs text-slate-400 hover:text-slate-600 shrink-0">Reset</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-semibold text-slate-400 tracking-wider">
                    <tr>
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Nama Wilayah</th>
                        <th class="py-3 px-4">Kabupaten</th>
                        <th class="py-3 px-4">Kecamatan</th>
                        <th class="py-3 px-4">Desa/Kel</th>
                        <th class="py-3 px-4">SLS [Kode]</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($wilayahs as $w)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 text-slate-400 font-mono text-[11px]">#{{ $w->id }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">
                                {{ $w->nama }}
                                @if($w->subsls)
                                    <span class="text-[10px] text-slate-400 font-normal block">Sub SLS: {{ $w->subsls }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $w->kab ?? '-' }}
                                @if($w->kd_kab)<span class="text-[10px] text-slate-400">[{{ $w->kd_kab }}]</span>@endif
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $w->kec ?? '-' }}
                                @if($w->kd_kec)<span class="text-[10px] text-slate-400">[{{ $w->kd_kec }}]</span>@endif
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $w->desa ?? '-' }}
                                @if($w->kd_desa)<span class="text-[10px] text-slate-400">[{{ $w->kd_desa }}]</span>@endif
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $w->sls ?? '-' }}
                                @if($w->kd_sls)<span class="text-[10px] text-slate-400">[{{ $w->kd_sls }}]</span>@endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('wilayah.edit', $w) }}" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-md transition" title="Edit">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <form action="{{ route('wilayah.destroy', $w) }}" method="POST" onsubmit="return confirm('Hapus wilayah ini?')" class="inline">
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
                            <td colspan="7" class="py-8 text-center text-slate-400 italic">Belum ada data wilayah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $wilayahs->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Modal Import Excel -->
<div id="modal-import" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-slate-600"></i>
                Import Wilayah dari Excel
            </h3>
            <button onclick="document.getElementById('modal-import').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('wilayah.import') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">File Spreadsheet (.xlsx, .xls, .csv)</label>
                <input type="file" name="file" required class="block w-full text-xs text-slate-500 border border-slate-200 rounded-lg p-2 bg-slate-50 focus:outline-none">
            </div>
            <p class="text-[11px] text-slate-400">
                Kolom Excel: A=ID, B=nama, C=kab, D=kd_kab, E=kec, F=kd_kec, G=desa, H=kd_desa, I=sls, J=kd_sls, K=subsls.
            </p>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-import').classList.add('hidden')" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg">Batal</button>
                <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg">Mulai Import</button>
            </div>
        </form>
    </div>
</div>
@endsection
