<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat tabel pt jika belum ada
        if (! Schema::hasTable('pt')) {
            Schema::create('pt', function (Blueprint $table) {
                $table->id();
                $table->string('nama_pt', 150);
                $table->string('kode_pt', 50)->unique();
                $table->text('keterangan')->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        // 2. Tambah foreign key pt_id pada tabel distrik
        if (Schema::hasTable('distrik')) {
            Schema::table('distrik', function (Blueprint $table) {
                if (! Schema::hasColumn('distrik', 'pt_id')) {
                    $table->foreignId('pt_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('pt')
                        ->nullOnDelete()
                        ->cascadeOnUpdate();
                }
            });
        }

        // 3. Seeding data PT default dan kaitkan ke distrik yang sudah ada
        $waktu = now();
        $ptPabId = DB::table('pt')->insertGetId([
            'nama_pt' => 'PT Panlibata Andalas Bertuah',
            'kode_pt' => 'PAB',
            'keterangan' => 'Holding & Unit Operasional Utama',
            'aktif' => true,
            'created_at' => $waktu,
            'updated_at' => $waktu,
        ]);

        DB::table('pt')->insert([
            [
                'nama_pt' => 'PT Agro Andalas Nusantara',
                'kode_pt' => 'AAN',
                'keterangan' => 'Unit Perkebunan Kelapa Sawit Wilayah Riau',
                'aktif' => true,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ],
            [
                'nama_pt' => 'PT Sawit Riau Makmur',
                'kode_pt' => 'SRM',
                'keterangan' => 'Unit Perkebunan Kelapa Sawit Wilayah Pesisir',
                'aktif' => true,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ],
        ]);

        // Kaitkan distrik yang belum memiliki pt_id ke PT Panlibata Andalas Bertuah
        DB::table('distrik')->whereNull('pt_id')->update(['pt_id' => $ptPabId]);
    }

    public function down(): void
    {
        if (Schema::hasTable('distrik')) {
            Schema::table('distrik', function (Blueprint $table) {
                if (Schema::hasColumn('distrik', 'pt_id')) {
                    $table->dropForeign(['pt_id']);
                    $table->dropColumn('pt_id');
                }
            });
        }

        Schema::dropIfExists('pt');
    }
};

