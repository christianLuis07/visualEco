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
        User::create([
            'name'     => 'Budi Warga',
            'email'    => 'warga@visueco.test',
            'password' => Hash::make('password'),
            'role'     => 'user',
            'points_balance' => 100,
        ]);

        // ── Akun Admin (Pengurus RT) ──
        User::create([
            'name'     => 'Admin RT',
            'email'    => 'admin@visueco.test',
            'password' => Hash::make('password'),
            'role'     => 'admin',
            'points_balance' => 0,
        ]);

        // ── Kategori Sampah ──
        WasteCategory::insert([
            ['name' => 'Plastik',  'base_points' => 10, 'handling_instructions' => json_encode(['Kosongkan isi wadah', 'Lepaskan label jika memungkinkan', 'Remas untuk menghemat ruang', 'Masukkan ke tempat sampah plastik'])],
            ['name' => 'Kertas',   'base_points' => 8,  'handling_instructions' => json_encode(['Pastikan kertas kering', 'Lipat rapi agar tidak terbang', 'Pisahkan dari kertas berlaminasi', 'Masukkan ke tempat sampah kertas'])],
            ['name' => 'Logam',    'base_points' => 15, 'handling_instructions' => json_encode(['Kosongkan isi kaleng', 'Bilas ringan jika kotor', 'Tekan rata jika memungkinkan', 'Masukkan ke tempat sampah logam'])],
            ['name' => 'Kaca',     'base_points' => 12, 'handling_instructions' => json_encode(['Kosongkan isi botol', 'Bilas ringan', 'Bungkus jika pecah untuk keamanan', 'Masukkan ke tempat sampah kaca'])],
            ['name' => 'Organik',  'base_points' => 5,  'handling_instructions' => json_encode(['Pisahkan dari plastik atau kemasan', 'Potong kecil-kecil jika besar', 'Masukkan ke tempat kompos', 'Jangan campur dengan sampah anorganik'])],
        ]);

        // ── Katalog Reward ──
        Reward::insert([
            ['title' => 'Voucher Sembako Rp20.000',   'description' => 'Voucher belanja sembako senilai Rp20.000 di warung mitra RT.',          'points_required' => 40,  'stock' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Voucher Sembako Rp50.000',   'description' => 'Voucher belanja sembako senilai Rp50.000 di warung mitra RT.',          'points_required' => 90,  'stock' => 5,  'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Tiket Wisata Edukasi',        'description' => 'Tiket masuk wisata edukasi lingkungan untuk 1 orang.',                 'points_required' => 150, 'stock' => 3,  'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Kaos Visueco Eksklusif',      'description' => 'Kaos bertema lingkungan dengan desain eksklusif Visueco.',             'points_required' => 200, 'stock' => 8,  'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
