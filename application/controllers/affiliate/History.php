<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller untuk menangani halaman riwayat transaksi afiliasi.
 * Diletakkan di: application/controllers/affiliate/History.php
 */
class History extends CI_Controller {

    public function __construct() {
        parent::__construct();

        // Keamanan: Wajib login sebagai afiliasi untuk mengakses halaman ini.
        if (!$this->session->userdata('is_affiliate_logged_in')) {
            $this->session->set_flashdata('error', 'Anda harus login untuk melihat halaman ini.');
            redirect('affiliate/auth/login');
        }

        // Muat model yang diperlukan.
        $this->load->model('Affiliate_model');
    }

    /**
     * Menampilkan halaman utama riwayat transaksi.
     */
    public function index() {
        // 1. Ambil ID afiliasi dari session.
        $affiliate_id = $this->session->userdata('affiliate_id');

        // 2. Ambil SEMUA riwayat aktivitas untuk afiliasi ini dari database.
        // Kita panggil get_activities() tanpa memberikan parameter kedua (limit).
        $data['history'] = $this->Affiliate_model->get_activities($affiliate_id);

        // 3. Siapkan judul halaman.
        $data['title'] = 'Riwayat Transaksi | Mine Hive';

        // 4. Muat view dan kirim data riwayat ke dalamnya.
        $this->load->view('affiliate/history_view', $data);
    }
}