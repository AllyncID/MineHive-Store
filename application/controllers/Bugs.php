<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bugs extends CI_Controller {

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
        $data['title'] = 'Bugs | MineHive';
        $data['meta_description'] = 'Laporkan bug atau masalah yang kamu temukan di MineHive agar tim bisa cek dan memperbaikinya lebih cepat.';

        // Memuat view secara berurutan
        // Penting: Asumsinya Anda memiliki file header dan footer di folder 'templates'
        // Jika lokasinya berbeda, silakan sesuaikan.
        $this->load->view('bugs_view', $data);
    }
}
