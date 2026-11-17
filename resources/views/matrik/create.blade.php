@extends('layouts.app')

@section('title', 'Alokasi Matrik Tugas')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Page Actions -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-200/80">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Buat Alokasi Matrik Tugas</h1>
            <p class="text-xs text-slate-500 mt-0.5">Ikuti panduan langkah 1 sampai 7 untuk memetakan penugasan petugas ke wilayah.</p>
        </div>
        <a href="{{ route('matrik.index') }}" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition shadow-xs">
            &larr; Kembali
        </a>
    </div>

    <form action="{{ route('matrik.store') }}" method="POST" id="form-matrik" class="space-y-6">
        @csrf

        <!-- LANGKAH 1: PILIH KEGIATAN -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-6 h-6 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-bold">1</span>
                <h2 class="text-sm font-bold text-slate-800">Pilih Kegiatan Survei / Sensus</h2>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Kegiatan <span class="text-rose-500">*</span></label>
                <select name="id_kegiatan" id="select-kegiatan" required class="w-full text-xs px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 font-medium text-slate-800">
                    <option value="">-- Pilih Kegiatan Survei / Sensus --</option>
                    @foreach($kegiatans as $k)
                        <option value="{{ $k->id }}" data-tim="{{ $k->tim?->nama ?? '-' }}" data-beban="{{ $k->administrasi['beban'] ?? 0 }}">
                            {{ $k->nama }} (Tim: {{ $k->tim?->nama ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- LANGKAH 2: PILIH PETUGAS (ORGANIK ATAU MITRA) -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-bold">2</span>
                    <h2 class="text-sm font-bold text-slate-800">Pilih Petugas (Organik atau Mitra)</h2>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-400">Pilih satu atau beberapa petugas:</span>
                    <button type="button" onclick="toggleSelectAll('petugas')" class="text-xs font-semibold text-slate-700 hover:underline">Pilih Semua</button>
                </div>
            </div>

            <!-- Filter Tab Organik / Mitra -->
            <div class="flex items-center gap-2 mb-3 text-xs">
                <button type="button" onclick="filterPetugasList('all')" id="tab-all" class="px-2.5 py-1 rounded-md font-semibold bg-slate-800 text-white">Semua (<span id="count-all">{{ $users->count() }}</span>)</button>
                <button type="button" onclick="filterPetugasList('organik')" id="tab-organik" class="px-2.5 py-1 rounded-md font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200">Organik ({{ $users->where('tipe', 'organik')->count() }})</button>
                <button type="button" onclick="filterPetugasList('mitra')" id="tab-mitra" class="px-2.5 py-1 rounded-md font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200">Mitra ({{ $users->where('tipe', 'mitra')->count() }})</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-56 overflow-y-auto p-1 border border-slate-100 rounded-lg">
                @foreach($users as $u)
                    <label class="petugas-card flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200/80 bg-slate-50/60 hover:bg-slate-100/60 cursor-pointer transition select-none" data-tipe="{{ $u->tipe }}">
                        <input type="checkbox" name="selected_petugas[]" value="{{ $u->id }}" data-name="{{ $u->name }}" data-tipe="{{ $u->tipe }}" onchange="updateAllocationMatrix()" class="petugas-checkbox mt-0.5 rounded text-slate-800 focus:ring-0">
                        <div class="min-w-0 flex-1">
                            <span class="text-xs font-semibold text-slate-800 block truncate">{{ $u->name }}</span>
                            <span class="text-[10px] inline-block px-1.5 py-0.2 rounded font-medium {{ $u->tipe == 'organik' ? 'bg-[#edf2ee] text-[#2d5f3e]' : 'bg-[#f6f2ec] text-[#8c5828]' }}">
                                {{ ucfirst($u->tipe) }}
                            </span>
                            @if($u->tipe == 'organik' && $u->organik?->nip)
                                <span class="text-[10px] text-slate-400 block truncate">NIP: {{ $u->organik->nip }}</span>
                            @elseif($u->tipe == 'mitra' && $u->mitra?->kecamatan)
                                <span class="text-[10px] text-slate-400 block truncate">Kec: {{ $u->mitra->kecamatan }}</span>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- LANGKAH 3: PILIH WILAYAH -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-bold">3</span>
                    <h2 class="text-sm font-bold text-slate-800">Pilih Wilayah Tugas (Target Alokasi)</h2>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-400">Pilih satu atau beberapa wilayah:</span>
                    <button type="button" onclick="toggleSelectAll('wilayah')" class="text-xs font-semibold text-slate-700 hover:underline">Pilih Semua</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-56 overflow-y-auto p-1 border border-slate-100 rounded-lg">
                @foreach($wilayahs as $w)
                    <label class="wilayah-card flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200/80 bg-slate-50/60 hover:bg-slate-100/60 cursor-pointer transition select-none">
                        <input type="checkbox" name="selected_wilayah[]" value="{{ $w->id }}" data-name="{{ $w->nama }}" onchange="updateAllocationMatrix()" class="wilayah-checkbox mt-0.5 rounded text-slate-800 focus:ring-0">
                        <div class="min-w-0 flex-1">
                            <span class="text-xs font-semibold text-slate-800 block truncate">{{ $w->nama }}</span>
                            <span class="text-[10px] text-slate-500 block truncate">Kec. {{ $w->kec }} &bull; {{ $w->desa }}</span>
                            @if($w->sls)
                                <span class="text-[10px] text-slate-400 block truncate">SLS: {{ $w->sls }}</span>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- LANGKAH 4, 5, 6, 7: TABEL ALOKASI, BEBAN, DAN PENGAWAS -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-bold">4-7</span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">Alokasikan Petugas, Beban, & Penugasan Pengawas</h2>
                        <p class="text-[11px] text-slate-500">Tabel alokasi akan terisi otomatis berdasarkan petugas dan wilayah yang Anda pilih di atas.</p>
                    </div>
                </div>

                <!-- Tanyakan apakah ada pengawas? (LANGKAH 6) -->
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg">
                    <span class="text-xs font-semibold text-slate-700">Apakah ada Pengawas?</span>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="toggle-pengawas" onchange="togglePengawasColumn()" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-slate-800"></div>
                        <span id="pengawas-status-text" class="ml-2 text-xs font-semibold text-slate-500">Tidak</span>
                    </label>
                </div>
            </div>

            <!-- Tabel Matrik Alokasi Dinamis -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600" id="table-allocations">
                    <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-semibold text-slate-400 tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3">Petugas</th>
                            <th class="py-2.5 px-3">Wilayah Tugas</th>
                            <th class="py-2.5 px-3 w-32">Beban (Volume)</th>
                            <th class="py-2.5 px-3 w-36">Peran (Role)</th>
                            <th class="py-2.5 px-3 pengawas-col hidden">Pengawas Lapangan (PML)</th>
                            <th class="py-2.5 px-3 text-center w-12">Hapus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="matrix-tbody">
                        <tr id="empty-row">
                            <td colspan="6" class="py-8 text-center text-slate-400 italic">
                                Belum ada petugas & wilayah yang dipilih. Silakan centang Petugas (Langkah 2) dan Wilayah (Langkah 3).
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Quick Bulk Settings -->
            <div id="bulk-bar" class="hidden flex flex-wrap items-center justify-between gap-3 p-3 bg-slate-50 rounded-lg text-xs">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-slate-700">Set Beban Seragam:</span>
                    <input type="number" id="bulk-beban-input" value="10" min="0" class="w-20 px-2 py-1 bg-white border border-slate-200 rounded text-xs">
                    <button type="button" onclick="applyBulkBeban()" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded font-semibold text-xs">Terapkan ke Semua</button>
                </div>
                <div class="text-slate-400">
                    Total Baris Alokasi: <span id="total-rows-count" class="font-bold text-slate-800">0</span>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Simpan -->
        <div class="flex items-center justify-end gap-2 pt-2">
            <a href="{{ route('matrik.index') }}" class="px-4 py-2.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition">Batal</a>
            <button type="submit" id="btn-submit" disabled class="px-5 py-2.5 text-xs font-semibold bg-slate-800 text-white rounded-lg hover:bg-slate-700 transition shadow-xs disabled:opacity-40 disabled:cursor-not-allowed">
                Simpan Semua Alokasi Matrik Tugas
            </button>
        </div>
    </form>
</div>

<!-- DATA USER UNTUK DROPDOWN PENGAWAS -->
<div id="users-data" class="hidden">
    @foreach($users as $u)
        <option value="{{ $u->id }}">{{ $u->name }} ({{ ucfirst($u->tipe) }})</option>
    @endforeach
</div>

@push('scripts')
<script>
    let hasPengawas = false;

    function togglePengawasColumn() {
        const checkbox = document.getElementById('toggle-pengawas');
        hasPengawas = checkbox.checked;
        const statusText = document.getElementById('pengawas-status-text');
        statusText.innerText = hasPengawas ? 'Ya' : 'Tidak';
        statusText.className = hasPengawas ? 'ml-2 text-xs font-bold text-slate-800' : 'ml-2 text-xs font-semibold text-slate-500';

        const cols = document.querySelectorAll('.pengawas-col');
        cols.forEach(el => {
            if (hasPengawas) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });
    }

    function filterPetugasList(type) {
        document.getElementById('tab-all').className = type === 'all' ? 'px-2.5 py-1 rounded-md font-semibold bg-slate-800 text-white' : 'px-2.5 py-1 rounded-md font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200';
        document.getElementById('tab-organik').className = type === 'organik' ? 'px-2.5 py-1 rounded-md font-semibold bg-slate-800 text-white' : 'px-2.5 py-1 rounded-md font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200';
        document.getElementById('tab-mitra').className = type === 'mitra' ? 'px-2.5 py-1 rounded-md font-semibold bg-slate-800 text-white' : 'px-2.5 py-1 rounded-md font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200';

        const cards = document.querySelectorAll('.petugas-card');
        cards.forEach(card => {
            if (type === 'all' || card.getAttribute('data-tipe') === type) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

    function toggleSelectAll(kind) {
        const checkboxes = document.querySelectorAll('.' + kind + '-checkbox');
        const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
        checkboxes.forEach(cb => cb.checked = anyUnchecked);
        updateAllocationMatrix();
    }

    function updateAllocationMatrix() {
        const checkedPetugas = Array.from(document.querySelectorAll('.petugas-checkbox:checked')).map(cb => ({
            id: cb.value,
            name: cb.getAttribute('data-name'),
            tipe: cb.getAttribute('data-tipe')
        }));

        const checkedWilayah = Array.from(document.querySelectorAll('.wilayah-checkbox:checked')).map(cb => ({
            id: cb.value,
            name: cb.getAttribute('data-name')
        }));

        const tbody = document.getElementById('matrix-tbody');
        const bulkBar = document.getElementById('bulk-bar');
        const btnSubmit = document.getElementById('btn-submit');
        const pengawasOptionsHtml = document.getElementById('users-data').innerHTML;

        if (checkedPetugas.length === 0 || checkedWilayah.length === 0) {
            tbody.innerHTML = `
                <tr id="empty-row">
                    <td colspan="6" class="py-8 text-center text-slate-400 italic">
                        Belum ada petugas & wilayah yang dipilih. Silakan centang Petugas (Langkah 2) dan Wilayah (Langkah 3).
                    </td>
                </tr>
            `;
            bulkBar.classList.add('hidden');
            btnSubmit.disabled = true;
            return;
        }

        bulkBar.classList.remove('hidden');
        btnSubmit.disabled = false;

        let rowsHtml = '';
        let rowIndex = 0;

        checkedPetugas.forEach(petugas => {
            checkedWilayah.forEach(wilayah => {
                rowsHtml += `
                    <tr class="hover:bg-slate-50/60 transition" id="row-${rowIndex}">
                        <td class="py-2.5 px-3">
                            <span class="font-semibold text-slate-800 block">${petugas.name}</span>
                            <span class="text-[10px] text-slate-400 capitalize">${petugas.tipe}</span>
                            <input type="hidden" name="allocations[${rowIndex}][id_user]" value="${petugas.id}">
                        </td>
                        <td class="py-2.5 px-3">
                            <span class="font-medium text-slate-800">${wilayah.name}</span>
                            <input type="hidden" name="allocations[${rowIndex}][id_wilayah]" value="${wilayah.id}">
                        </td>
                        <td class="py-2.5 px-3">
                            <input type="number" name="allocations[${rowIndex}][beban]" value="10" min="0" required class="beban-field w-24 px-2 py-1 bg-white border border-slate-200 rounded text-xs font-bold text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-400">
                        </td>
                        <td class="py-2.5 px-3">
                            <select name="allocations[${rowIndex}][role]" class="px-2 py-1 bg-white border border-slate-200 rounded text-xs text-slate-700">
                                <option value="PCL (Pencacah Lapangan)" selected>PCL</option>
                                <option value="PML (Pengawas Lapangan)">PML</option>
                                <option value="Koseka">Koseka</option>
                                <option value="Pemeriksa">Pemeriksa</option>
                                <option value="Operator Entri">Operator Entri</option>
                            </select>
                        </td>
                        <td class="py-2.5 px-3 pengawas-col ${hasPengawas ? '' : 'hidden'}">
                            <select name="allocations[${rowIndex}][id_pengawas]" class="px-2 py-1 bg-white border border-slate-200 rounded text-xs text-slate-700 w-full">
                                <option value="">-- Pilih Pengawas --</option>
                                ${pengawasOptionsHtml}
                            </select>
                        </td>
                        <td class="py-2.5 px-3 text-center">
                            <button type="button" onclick="document.getElementById('row-${rowIndex}').remove(); checkRowsCount();" class="text-slate-400 hover:text-rose-600 p-1">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </td>
                    </tr>
                `;
                rowIndex++;
            });
        });

        tbody.innerHTML = rowsHtml;
        document.getElementById('total-rows-count').innerText = rowIndex;
        lucide.createIcons();
    }

    function checkRowsCount() {
        const rows = document.querySelectorAll('#matrix-tbody tr');
        document.getElementById('total-rows-count').innerText = rows.length;
        if (rows.length === 0) {
            document.getElementById('btn-submit').disabled = true;
            document.getElementById('bulk-bar').classList.add('hidden');
        }
    }

    function applyBulkBeban() {
        const val = document.getElementById('bulk-beban-input').value;
        document.querySelectorAll('.beban-field').forEach(input => input.value = val);
    }
</script>
@endpush
@endsection
