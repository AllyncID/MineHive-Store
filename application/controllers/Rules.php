<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rules extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Muat helper yang mungkin diperlukan, seperti URL
        $this->load->helper('url');
    }

    /**
     * Menampilkan halaman utama peraturan.
     */
    public function index() {
        // Data untuk dikirim ke view, seperti judul halaman
        $data['title'] = 'Rules | MineHive';
        $data['meta_description'] = 'Baca peraturan resmi MineHive agar pengalaman bermain di server tetap aman, adil, dan nyaman untuk semua player.';

        // Memuat view secara berurutan
        // Penting: Asumsinya Anda memiliki file header dan footer di folder 'templates'
        // Jika lokasinya berbeda, silakan sesuaikan.
        $this->load->view('templates/header', $data);
        $this->load->view('rules_view', $data); // Ini adalah file view baru yang akan kita buat
        $this->load->view('templates/footer');
    }
}
