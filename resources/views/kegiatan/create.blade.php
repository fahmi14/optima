@extends('layouts.app')

@section('title', 'Tambah Kegiatan')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center justify-between pb-2">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Tambah Kegiatan Baru</h1>
            <p class="text-xs text-slate-500 mt-0.5">Jadwal pelaksanaan survei dan rincian administrasi anggaran.</p>
        </div>
        <a href="{{ route('kegiatan.index') }}" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition">
            &larr; Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-xs">
        <form action="{{ route('kegiatan.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kegiatan <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" value="{{ old('nama') }}" required placeholder="Contoh: Survei Sosial Ekonomi Nasional (SUSENAS)" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tim Pengampu</label>
                    <select name="id_tim" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                        <option value="">-- Pilih Tim --</option>
                        @foreach($tims as $tim)
                            <option value="{{ $tim->id }}" {{ old('id_tim') == $tim->id ? 'selected' : '' }}>{{ $tim->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="tgl_mulai" value="{{ old('tgl_mulai') }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="tgl_selesai" value="{{ old('tgl_selesai') }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
            </div>

            <!-- Administrasi (JSON) -->
            <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-lg space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 block">Administrasi & Anggaran (JSON)</span>
                    <span class="text-[10px] text-slate-400">Dinamis</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Honor (Rp)</label>
                        <input type="number" step="any" name="honor" value="{{ old('honor') }}" placeholder="3500000" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Jenis Honor</label>
                        <input type="text" name="jenis_honor" value="{{ old('jenis_honor') }}" placeholder="OB / Dokumen" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">SBML (Rp)</label>
                        <input type="number" step="any" name="sbml" value="{{ old('sbml') }}" placeholder="4000000" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Target Jumlah Petugas</label>
                        <input type="number" name="jml_petugas" value="{{ old('jml_petugas') }}" placeholder="20" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Target Beban / Volume</label>
                        <input type="number" name="beban" value="{{ old('beban') }}" placeholder="10" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                </div>

                <!-- Custom Fields -->
                <div class="pt-2 border-t border-slate-200">
                    <label class="block text-xs font-medium text-slate-600 mb-2">Isian Administrasi Tambahan:</label>
                    <div id="custom-fields-container" class="space-y-2"></div>
                    <button type="button" onclick="addCustomField()" class="mt-2 text-xs text-slate-700 hover:text-slate-900 font-semibold flex items-center gap-1">
                        <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                        <span>Tambah Field Custom</span>
                    </button>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
                <a href="{{ route('kegiatan.index') }}" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-white rounded-lg shadow-xs">Simpan Kegiatan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function addCustomField(key = '', val = '') {
        const container = document.getElementById('custom-fields-container');
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2';
        div.innerHTML = `
            <input type="text" name="custom_keys[]" value="${key}" placeholder="Nama Field" class="w-1/2 text-xs p-2 rounded-lg border border-slate-200 bg-white focus:outline-none">
            <input type="text" name="custom_values[]" value="${val}" placeholder="Nilai Isian" class="w-1/2 text-xs p-2 rounded-lg border border-slate-200 bg-white focus:outline-none">
            <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-rose-600 p-1"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
        `;
        container.appendChild(div);
        lucide.createIcons();
    }
</script>
@endpush
@endsection
