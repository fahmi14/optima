@extends('layouts.app')

@section('title', 'Tugas Saya')

@section('content')
<div x-data="tugasSayaPage()" class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Tugas Saya</h1>
            <p class="text-xs text-slate-500 mt-1">Daftar penugasan pribadi, pengisian tujuan translok lapangan, deadline, dan unggah bukti pelaksanaan.</p>
        </div>
        <form method="GET" action="{{ route('tugas-saya.index') }}" class="flex items-center gap-2">
            <select name="status" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-xl px-4 py-2.5 shadow-2xs focus:outline-none focus:ring-1 focus:ring-slate-400">
                <option value="">Semua Status</option>
                @foreach(['Belum Mulai', 'Berjalan', 'Menunggu Verifikasi', 'Revisi', 'Selesai'] as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Summary Cards (Pastel, Lega, Kalem) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-slate-50 to-white border border-slate-200/80 rounded-2xl p-6 shadow-2xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Beban Kerja Aktif Saya</span>
                <span class="px-3 py-1 text-[11px] font-bold rounded-full border {{ $workload['badge_class'] }}">
                    {{ $workload['status'] }} ({{ $workload['persentase'] }}%)
                </span>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $workload['total_jam'] ?? $workload['total_poin'] }} <span class="text-xs font-normal text-slate-400">/ {{ $workload['kuota_jam'] ?? $workload['kapasitas_maks'] }} Jam Kuota</span></p>
            <div class="w-full h-2.5 bg-slate-200/70 rounded-full overflow-hidden">
                <div class="h-full {{ $workload['bar_class'] }} transition-all" style="width: {{ $workload['bar_persen'] }}%"></div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-50/40 to-white border border-emerald-100 rounded-2xl p-6 shadow-2xs flex flex-col justify-center">
            <span class="text-xs font-semibold text-slate-500">Total Hak Transport Lokal Saya</span>
            <p class="text-2xl font-bold text-emerald-700 mt-1.5">Rp {{ number_format($totalHakTransport, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Akumulasi transport tugas yang aktif / layak SPJ</p>
        </div>
    </div>

    <!-- Task Cards (Responsive Grid) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @forelse($penugasans as $tugas)
            @php
                $deadline = $tugas->deadline_tugas;
                $daysLeft = $deadline ? (int) now()->startOfDay()->diffInDays($deadline->startOfDay(), false) : null;
                $transloks = $tugas->alokasiTransports->where('status_spj', '!=', 'Ditolak');
                $belumIsiCount = $transloks->filter(fn($tr) => !$tr->tujuan_terisi || !$tr->tgl_tugas)->count();
            @endphp
            <div class="bg-white border {{ $belumIsiCount > 0 ? 'border-amber-300 ring-1 ring-amber-100' : 'border-slate-200/80' }} rounded-2xl p-6 shadow-2xs flex flex-col justify-between gap-5 hover:border-slate-300 transition">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                                {{ $tugas->subKegiatan?->periodeKegiatan?->masterKegiatan?->nama }} &bull; {{ $tugas->subKegiatan?->periodeKegiatan?->nama_periode }}
                            </span>
                            <h3 class="text-base font-bold text-slate-800">{{ $tugas->subKegiatan?->nama }}</h3>
                        </div>
                        <span class="px-3 py-1 text-[10px] font-bold rounded-lg bg-slate-100 text-slate-700 shrink-0">{{ $tugas->status }}</span>
                    </div>

                    <!-- Banner Wajib Isi Tujuan Translok (Jika ada hari translok yang belum diisi tujuan/tanggalnya) -->
                    @if($transloks->isNotEmpty())
                        <div class="p-3 rounded-xl border text-xs {{ $belumIsiCount > 0 ? 'bg-amber-50/80 border-amber-200 text-amber-900' : 'bg-sky-50/50 border-sky-100 text-sky-900' }} flex items-center justify-between gap-2">
                            <div>
                                <p class="font-bold flex items-center gap-1.5">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                    @if($belumIsiCount > 0)
                                        <span>Wajib Isi Tujuan Translok ({{ $belumIsiCount }}/{{ $transloks->count() }} Hari)</span>
                                    @else
                                        <span>Translok: {{ $transloks->count() }} Hari (Sudah Lengkap)</span>
                                    @endif
                                </p>
                                <p class="text-[10px] opacity-80 mt-0.5">
                                    Total Alokasi: Rp {{ number_format($transloks->sum('nominal'), 0, ',', '.') }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="openTranslokModal({{ $tugas->toJson() }})"
                                class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition cursor-pointer shrink-0 {{ $belumIsiCount > 0 ? 'bg-amber-600 text-white hover:bg-amber-700' : 'bg-white text-sky-700 border border-sky-200 hover:bg-sky-50' }}"
                            >
                                {{ $belumIsiCount > 0 ? 'Isi Tujuan' : 'Lihat / Ubah' }}
                            </button>
                        </div>
                    @endif

                    <!-- Progress Bar -->
                    <div class="space-y-1.5 pt-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Realisasi: <strong>{{ $tugas->realisasi_volume }}/{{ $tugas->target_volume }} {{ $tugas->satuan_volume }}</strong></span>
                            <span class="font-bold text-slate-700">{{ $tugas->progres_persen }}%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full transition-all" style="width: {{ $tugas->progres_persen }}%"></div>
                        </div>
                    </div>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100">
                        <div>
                            <span class="text-[11px] text-slate-400 block">Estimasi Waktu:</span>
                            <p class="font-bold text-slate-700">{{ $tugas->durasiJam() }} Jam</p>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-400 block">Deadline:</span>
                            <p class="font-semibold {{ $daysLeft !== null && $daysLeft < 0 ? 'text-rose-600 font-bold' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-600' : 'text-slate-700') }}">
                                {{ $deadline?->format('d M Y') ?? '-' }}
                                @if($daysLeft !== null)
                                    <span class="text-[10px] font-normal">({{ $daysLeft < 0 ? 'Lewat ' . abs($daysLeft) . ' hari' : ($daysLeft === 0 ? 'Hari ini' : "H-{$daysLeft}") }})</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        @if($tugas->file_bukti)
                            <a href="{{ asset($tugas->file_bukti) }}" target="_blank" class="text-xs font-semibold text-sky-600 hover:text-sky-700 flex items-center gap-1">
                                <i data-lucide="file-check" class="w-3.5 h-3.5"></i>
                                <span>Lihat Bukti</span>
                            </a>
                        @endif
                    </div>
                    <button
                        type="button"
                        @click="activeTask = {{ $tugas->toJson() }}"
                        class="px-4 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 transition shadow-2xs cursor-pointer"
                    >
                        Update Progres
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white border border-slate-200/80 rounded-2xl p-12 text-center text-xs text-slate-400">
                Belum ada penugasan kerja yang ditugaskan kepada Anda saat ini.
            </div>
        @endforelse
    </div>

    <!-- MODAL ATUR TUJUAN & TANGGAL TRANSLOK PER HARI (WAJIB DIISI PETUGAS) -->
    <div x-show="translokModalTask" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6">
        <div @click.away="translokModalTask = null" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 max-h-[92vh] overflow-y-auto" x-if="translokModalTask">
            <div class="flex items-start justify-between gap-3 pb-4 border-b border-slate-100">
                <div>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-amber-50 text-amber-800 border border-amber-200">Rincian Translok Harian</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight mt-1" x-text="translokModalTask.nama_tahapan"></h3>
                    <p class="text-xs text-slate-400 mt-0.5">Isi tanggal turun lapangan &amp; lokasi tujuan untuk setiap hari alokasi translok. 1 hari bisa beberapa kecamatan.</p>
                </div>
                <button @click="translokModalTask = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 cursor-pointer"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>

            <div class="space-y-4">
                <template x-for="(item, idx) in translokModalTask.items" :key="item.id">
                    <form method="POST" :action="'/tugas-saya/translok/' + item.id" class="p-4 rounded-2xl border space-y-3 text-xs"
                          :class="item.tujuan_terisi ? 'bg-emerald-50/40 border-emerald-200' : 'bg-amber-50/40 border-amber-200'">
                        @csrf
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg font-bold bg-slate-800 text-white text-[11px]" x-text="'Hari ke-' + item.hari_ke"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                      :class="item.tujuan_terisi ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                      x-text="item.tujuan_terisi ? 'Sudah Terisi' : 'Wajib Diisi'"></span>
                            </div>
                            <span class="font-extrabold text-slate-800">
                                Rp <span x-text="new Intl.NumberFormat('id-ID').format(item.nominal)"></span>
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Tugas Lapangan <span class="text-rose-500">*</span></label>
                                <input type="date" name="tgl_tugas" x-model="item.tgl_tugas" required class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-white font-semibold">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Jenis Perjalanan Dinas</label>
                                <select name="jenis_perjalanan" x-model="item.jenis_perjalanan" @change="recalcTranslok(item)" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-white font-semibold">
                                    <option value="dalam_kab">Dalam Kabupaten Wonosobo</option>
                                    <option value="luar_kab">Luar Kabupaten</option>
                                </select>
                            </div>
                        </div>

                        <!-- Dalam Kabupaten -->
                        <div x-show="item.jenis_perjalanan === 'dalam_kab'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 rounded-xl bg-white/90 border border-slate-200/70">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kecamatan Asal</label>
                                <select name="asal_kec_id" x-model="item.asal_kec_id" @change="recalcTranslok(item)" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-slate-50 font-semibold">
                                    @foreach($kecamatans as $kec)
                                        <option value="{{ $kec->id }}">Kec. {{ $kec->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block font-semibold text-slate-700">Kecamatan Tujuan (Bisa &gt;1) <span class="text-rose-500">*</span></label>
                                    <button type="button" @click="addRute(item)" class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md cursor-pointer">+ Rute</button>
                                </div>
                                <div class="space-y-1.5">
                                    <template x-for="(rId, rIdx) in item.rute_kecamatan_ids" :key="rIdx">
                                        <div class="flex items-center gap-1.5">
                                            <select name="rute_kecamatan_ids[]" x-model="item.rute_kecamatan_ids[rIdx]" @change="recalcTranslok(item)" class="flex-1 border border-slate-200 rounded-xl px-3 py-2 bg-white font-semibold">
                                                <option value="">- Pilih Kec. Tujuan -</option>
                                                @foreach($kecamatans as $kec)
                                                    <option value="{{ $kec->id }}">Kec. {{ $kec->nama }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" @click="removeRute(item, rIdx)" x-show="item.rute_kecamatan_ids.length > 1" class="p-1 text-slate-400 hover:text-rose-600 cursor-pointer"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Luar Kabupaten -->
                        <div x-show="item.jenis_perjalanan === 'luar_kab'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 rounded-xl bg-white/90 border border-indigo-100">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Asal</label>
                                <input type="text" name="asal_perjalanan" value="Kabupaten Wonosobo" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-slate-50 font-semibold">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kabupaten / Kota Tujuan <span class="text-rose-500">*</span></label>
                                <input type="text" name="tujuan_luar_kab" x-model="item.tujuan_luar_kab" placeholder="Kota Semarang" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-white font-semibold">
                            </div>
                        </div>

                        <div class="flex justify-end pt-1">
                            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition cursor-pointer">
                                Simpan Hari ke-<span x-text="item.hari_ke"></span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Dialog Update Progres (Lega & Kalem) -->
    <div x-show="activeTask" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 sm:p-6 transition-all">
        <div @click.away="activeTask = null" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 max-h-[92vh] overflow-y-auto" x-if="activeTask">
            <div class="flex items-start justify-between gap-3 pb-4 border-b border-slate-100">
                <div class="space-y-1">
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-100">Update Tugas</span>
                    <h3 class="text-lg font-bold text-slate-800 tracking-tight">Progres &amp; Bukti Pelaksanaan</h3>
                </div>
                <button @click="activeTask = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="'/tugas-saya/' + activeTask.id + '/progress'" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Realisasi Volume</label>
                        <input type="number" name="realisasi_volume" x-model="activeTask.realisasi_volume" min="0" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Progres (%)</label>
                        <input type="number" name="progres_persen" x-model="activeTask.progres_persen" min="0" max="100" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Status Pelaksanaan</label>
                    <select name="status" x-model="activeTask.status" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 font-semibold">
                        <option value="Belum Mulai">Belum Mulai</option>
                        <option value="Berjalan">Berjalan</option>
                        <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Catatan Pelaksanaan</label>
                    <textarea name="catatan" rows="2" x-model="activeTask.catatan" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400"></textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Unggah Bukti Pelaksanaan (Foto / PDF, Maks 5MB)</label>
                    <input type="file" name="file_bukti" accept=".jpg,.jpeg,.png,.pdf,.webp" class="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/60 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white cursor-pointer">
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="activeTask = null" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900 transition shadow-2xs cursor-pointer">Simpan Progres</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function tugasSayaPage() {
    return {
        activeTask: null,
        translokModalTask: null,
        transportMatrix: @json($transportMatrix),
        defaultWonosoboId: '{{ $defaultWonosoboId }}',
        maksDalamKab: {{ (float) $maksDalamKab }},
        tarifLuarKab: {{ (float) $tarifLuarKab }},
        kecamatanNamesMap: @json($kecamatans->pluck('nama', 'id')),

        openTranslokModal(task) {
            const list = (task.alokasi_transports || []).map(al => ({
                id: al.id,
                hari_ke: al.hari_ke || 1,
                tgl_tugas: al.tgl_tugas ? String(al.tgl_tugas).substring(0, 10) : '',
                jenis_perjalanan: al.jenis_perjalanan || 'dalam_kab',
                asal_kec_id: '{{ $defaultWonosoboId }}',
                asal_perjalanan: al.asal_perjalanan || ((al.jenis_perjalanan === 'luar_kab') ? 'Kabupaten Wonosobo' : 'Kecamatan Wonosobo'),
                tujuan_luar_kab: al.tujuan_luar_kab || '',
                rute_kecamatan_ids: (Array.isArray(al.rute_kecamatan_ids) && al.rute_kecamatan_ids.length > 0) ? al.rute_kecamatan_ids.map(String) : [''],
                nominal: Number(al.nominal || 0),
                tujuan_terisi: Boolean(al.tujuan_terisi && al.tgl_tugas)
            }));
            this.translokModalTask = {
                id: task.id,
                nama_tahapan: task.sub_kegiatan ? task.sub_kegiatan.nama : 'Penugasan',
                items: list
            };
            this.$nextTick(() => {
                list.forEach(item => this.recalcTranslok(item));
                lucide.createIcons();
            });
        },

        addRute(item) {
            item.rute_kecamatan_ids.push('');
            this.$nextTick(() => { lucide.createIcons(); });
        },

        removeRute(item, idx) {
            if (item.rute_kecamatan_ids.length > 1) {
                item.rute_kecamatan_ids.splice(idx, 1);
                this.recalcTranslok(item);
            }
        },

        recalcTranslok(item) {
            if (item.jenis_perjalanan === 'luar_kab') {
                item.nominal = this.tarifLuarKab;
                return;
            }
            let prevId = item.asal_kec_id || this.defaultWonosoboId;
            let sum = 0;
            (item.rute_kecamatan_ids || []).filter(v => v !== '').forEach(nextId => {
                sum += Number(this.transportMatrix?.[prevId]?.[nextId] ?? 0);
                prevId = nextId;
            });
            item.nominal = Math.min(sum, this.maksDalamKab);
        }
    };
}
</script>
@endsection
