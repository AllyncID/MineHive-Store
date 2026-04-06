<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PENGATURAN ANNOUNCEMENT BAR
 * ==============================
 * Di sini Anda bisa dengan mudah mengaktifkan, menonaktifkan, 
 * dan mengubah teks pengumuman di bagian atas situs.
 */

// Ubah menjadi 'true' untuk menampilkan, atau 'false' untuk menyembunyikan.
$config['announcement_bar_enabled'] = false; 

// Teks yang akan ditampilkan. Anda bisa menggunakan emoji.
$config['announcement_bar_text'] = 'Diskon Awal Season Survival Up to 50%';

// (Opsional) Jika Anda ingin bar ini bisa diklik dan mengarah ke halaman tertentu.
// Kosongkan jika tidak ingin ada link. Contoh: 'store/show/survival/ranks'
$config['announcement_bar_link'] = 'store/show/survival/ranks';

// === TAMBAHKAN BARIS INI ===
// (Opsional) Countdown timer. Kosongkan jika tidak ingin ada timer.
// Format Wajib: 'YYYY-MM-DD HH:MM:SS'
$config['announcement_timer_end'] = '2025-07-04 23:59:59'; 