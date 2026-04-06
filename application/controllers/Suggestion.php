<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Suggestion extends CI_Controller {

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
        $data['title'] = 'Suggestion | MineHive';
        $data['meta_description'] = 'Kirim saran, vote ide, dan bantu perkembangan MineHive lewat halaman masukan komunitas.';

        // Memuat view secara berurutan
        // Penting: Asumsinya Anda memiliki file header dan footer di folder 'templates'
        // Jika lokasinya berbeda, silakan sesuaikan.
        $this->load->view('suggestion_view', $data);
    }
}
