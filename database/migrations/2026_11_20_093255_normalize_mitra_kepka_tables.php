<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_mitras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sobat_id', 50)->nullable()->unique();
            $table->string('nama_lengkap');
            $table->string('posisi')->nullable();
            $table->string('status_seleksi')->nullable();
            $table->text('alamat_detail')->nullable();
            $table->string('alamat_provinsi')->nullable();
            $table->string('alamat_kab')->nullable();
            $table->string('alamat_kecamatan')->nullable();
            $table->string('alamat_desa')->nullable();
            $table->string('ttl')->nullable();
            $table->unsignedSmallInteger('umur')->nullable();
            $table->string('jenis_kelamin', 20)->nullable();
            $table->string('status_kawin', 50)->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('deskripsi_pekerjaan_lain')->nullable();
            $table->string('terikat_kldi', 20)->nullable();
            $table->string('nama_kldi')->nullable();
            $table->text('keterangan_kldi')->nullable();
            $table->string('no_telp', 50)->nullable();
            $table->string('email')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('alokasi_kepkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_mitra')->constrained('master_mitras')->cascadeOnDelete();
            $table->year('tahun');
            $table->timestamps();

            $table->unique(['id_mitra', 'tahun'], 'alokasi_kepka_mitra_tahun_unique');
        });

        // Migrasi data eksisting dari mitra_kepkas ke master_mitras & alokasi_kepkas
        if (Schema::hasTable('mitra_kepkas')) {
            $oldRows = DB::table('mitra_kepkas')->orderBy('id')->get();
            foreach ($oldRows as $row) {
                $existingMitra = null;
                if (!empty($row->sobat_id)) {
                    $existingMitra = DB::table('master_mitras')->where('sobat_id', $row->sobat_id)->first();
                }
                if (!$existingMitra && !empty($row->user_id)) {
                    $existingMitra = DB::table('master_mitras')->where('user_id', $row->user_id)->first();
                }

                if ($existingMitra) {
                    $mitraId = $existingMitra->id;
                } else {
                    $mitraId = DB::table('master_mitras')->insertGetId([
                        'user_id' => $row->user_id,
                        'sobat_id' => !empty($row->sobat_id) ? $row->sobat_id : null,
                        'nama_lengkap' => $row->nama_lengkap,
                        'posisi' => $row->posisi,
                        'status_seleksi' => $row->status_seleksi,
                        'alamat_detail' => $row->alamat_detail,
                        'alamat_provinsi' => $row->alamat_provinsi,
                        'alamat_kab' => $row->alamat_kab,
                        'alamat_kecamatan' => $row->alamat_kecamatan,
                        'alamat_desa' => $row->alamat_desa,
                        'ttl' => $row->ttl,
                        'umur' => $row->umur,
                        'jenis_kelamin' => $row->jenis_kelamin,
                        'status_kawin' => $row->status_kawin,
                        'pendidikan' => $row->pendidikan,
                        'pekerjaan' => $row->pekerjaan,
                        'deskripsi_pekerjaan_lain' => $row->deskripsi_pekerjaan_lain,
                        'terikat_kldi' => $row->terikat_kldi,
                        'nama_kldi' => $row->nama_kldi,
                        'keterangan_kldi' => $row->keterangan_kldi,
                        'no_telp' => $row->no_telp,
                        'email' => $row->email,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                }

                $existsAlokasi = DB::table('alokasi_kepkas')
                    ->where('id_mitra', $mitraId)
                    ->where('tahun', $row->tahun)
                    ->exists();

                if (!$existsAlokasi) {
                    DB::table('alokasi_kepkas')->insert([
                        'id_mitra' => $mitraId,
                        'tahun' => $row->tahun,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alokasi_kepkas');
        Schema::dropIfExists('master_mitras');
    }
};
