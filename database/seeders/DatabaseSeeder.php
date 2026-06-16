<?php

namespace Database\Seeders;

use App\Models\Reward;
use App\Models\User;
use App\Models\WasteCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── Akun Warga (User Biasa) ──
        // updateOrCreate berdasarkan email -> idempotent, aman dijalankan ulang.
        User::updateOrCreate(
            ['email' => 'warga@visueco.test'],
            [
                'name'           => 'Budi Warga',
                'password'       => Hash::make('password'),
                'role'           => 'user',
                'points_balance' => 100,
            ]
        );

        // ── Akun Admin (Pengurus RT) ──
        User::updateOrCreate(
            ['email' => 'admin@visueco.test'],
            [
                'name'           => 'Admin RT',
                'password'       => Hash::make('password'),
                'role'           => 'admin',
                'points_balance' => 0,
            ]
        );

        // ── Kategori Sampah ──
        // PENTING: id 1..5 dikunci eksplisit agar SELALU sinkron dengan
        // ml-service/category_map.py (ML mengembalikan category_id 1..5).
        // updateOrInsert membuat seeder idempotent & aman dijalankan ulang
        // tanpa menggeser id (tidak perlu migrate:fresh).
        $categories = [
            ['id' => 1, 'name' => 'Plastik',  'base_points' => 10, 'handling_instructions' => ['Kosongkan isi wadah', 'Lepaskan label jika memungkinkan', 'Remas untuk menghemat ruang', 'Masukkan ke tempat sampah plastik']],
            ['id' => 2, 'name' => 'Kertas',   'base_points' => 8,  'handling_instructions' => ['Pastikan kertas kering', 'Lipat rapi agar tidak terbang', 'Pisahkan dari kertas berlaminasi', 'Masukkan ke tempat sampah kertas']],
            ['id' => 3, 'name' => 'Logam',    'base_points' => 15, 'handling_instructions' => ['Kosongkan isi kaleng', 'Bilas ringan jika kotor', 'Tekan rata jika memungkinkan', 'Masukkan ke tempat sampah logam']],
            ['id' => 4, 'name' => 'Kaca',     'base_points' => 12, 'handling_instructions' => ['Kosongkan isi botol', 'Bilas ringan', 'Bungkus jika pecah untuk keamanan', 'Masukkan ke tempat sampah kaca']],
            ['id' => 5, 'name' => 'Organik',  'base_points' => 5,  'handling_instructions' => ['Pisahkan dari plastik atau kemasan', 'Potong kecil-kecil jika besar', 'Masukkan ke tempat kompos', 'Jangan campur dengan sampah anorganik']],
        ];
        foreach ($categories as $cat) {
            WasteCategory::updateOrCreate(
                ['id' => $cat['id']],
                ['name' => $cat['name'], 'base_points' => $cat['base_points'], 'handling_instructions' => $cat['handling_instructions']]
            );
        }

        // ── Katalog Reward ── (idempotent berdasarkan title)
        $rewards = [
            ['title' => 'Voucher Sembako Rp20.000', 'description' => 'Voucher belanja sembako senilai Rp20.000 di warung mitra RT.', 'points_required' => 40,  'stock' => 10],
            ['title' => 'Voucher Sembako Rp50.000', 'description' => 'Voucher belanja sembako senilai Rp50.000 di warung mitra RT.', 'points_required' => 90,  'stock' => 5],
            ['title' => 'Tiket Wisata Edukasi',      'description' => 'Tiket masuk wisata edukasi lingkungan untuk 1 orang.',        'points_required' => 150, 'stock' => 3],
            ['title' => 'Kaos Visueco Eksklusif',    'description' => 'Kaos bertema lingkungan dengan desain eksklusif Visueco.',    'points_required' => 200, 'stock' => 8],
        ];
        foreach ($rewards as $r) {
            Reward::updateOrCreate(['title' => $r['title']], $r);
        }
    }
}
