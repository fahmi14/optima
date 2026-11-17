@extends('layouts.app')

@section('title', 'Master Tim')

@section('content')
<div class="space-y-4">
    <!-- Header Page Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Master Tim Kerja</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola tim kerja fungsional survei dan penanggung jawab (PJ).</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="document.getElementById('modal-import-tim').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
                <i data-lucide="file-up" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Import Excel</span>
            </button>
            <a href="{{ route('tim.export') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
                <i data-lucide="file-down" class="w-3.5 h-3.5 text-slate-500"></i>
                <span>Export Excel</span>
            </a>
            <a href="{{ route('tim.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-slate-800 text-white rounded-lg hover:bg-slate-700 transition shadow-xs">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Tambah Tim</span>
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200/80 flex items-center justify-between gap-3">
        <form method="GET" action="{{ route('tim.index') }}" class="flex items-center gap-2 w-full max-w-md">
            <div class="relative w-full">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama tim atau penanggung jawab..." class="w-full text-xs pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400">
            </div>
            <button type="submit" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition shrink-0">
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route('tim.index') }}" class="text-xs text-slate-400 hover:text-slate-600 shrink-0">Reset</a>
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
                        <th class="py-3 px-4">Nama Tim</th>
                        <th class="py-3 px-4">Penanggung Jawab (PJ)</th>
                        <th class="py-3 px-4 text-center">Beban Kegiatan</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tims as $t)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 text-slate-400 font-mono text-[11px]">#{{ $t->id }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $t->nama }}</td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ $t->pj ?? '-' }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-block px-2.5 py-0.5 rounded-full bg-[#edf2ee] text-[#2d5f3e] font-semibold text-[11px]">
                                    {{ $t->kegiatans_count }} Kegiatan
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('tim.edit', $t) }}" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-md transition" title="Edit">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <form action="{{ route('tim.destroy', $t) }}" method="POST" onsubmit="return optimaConfirm(event, 'Hapus Tim Kerja {{ $t->nama }}? Tindakan ini tidak dapat dibatalkan.', 'Hapus Tim Kerja')" class="inline">
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
                            <td colspan="5" class="py-8 text-center text-slate-400 italic">Belum ada data tim.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $tims->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Modal Import Tim -->
<div id="modal-import-tim" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-slate-600"></i>
                Import Tim dari Excel
            </h3>
            <button onclick="document.getElementById('modal-import-tim').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('tim.import') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">File Spreadsheet (.xlsx, .xls, .csv)</label>
                <input type="file" name="file" required class="block w-full text-xs text-slate-500 border border-slate-200 rounded-lg p-2 bg-slate-50 focus:outline-none">
            </div>
            <p class="text-[11px] text-slate-400">Kolom Excel: Kolom A=ID, B=nama tim, C=pj.</p>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-import-tim').classList.add('hidden')" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg">Batal</button>
                <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg">Mulai Import</button>
            </div>
        </form>
    </div>
</div>
@endsection
