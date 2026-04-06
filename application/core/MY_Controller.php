<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Induk untuk seluruh website.
 * CodeIgniter akan memuat ini secara otomatis.
 */
class MY_Controller extends CI_Controller {
    public function __construct() {
        parent::__construct();
        // Logika umum untuk seluruh website bisa ditaruh di sini
    }
}

/**
 * Controller Induk KHUSUS untuk Panel Admin.
 * Dia mewarisi (extends) dari MY_Controller.
 */
class Admin_Controller extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        // Keamanan: Cek apakah admin sudah login
        if (!$this->session->userdata('is_admin_logged_in')) {
            $this->session->set_flashdata('error', 'Anda harus login terlebih dahulu.');
            redirect('admin/auth/login');
        }
    }
}

/**
 * Controller Induk KHUSUS untuk Halaman Afiliasi.
 * Dia juga mewarisi (extends) dari MY_Controller.
 */
class Affiliate_Controller extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        // Keamanan: Cek apakah afiliasi sudah login
        if (!$this->session->userdata('is_affiliate_logged_in')) {
            $this->session->set_flashdata('error', 'Anda harus login untuk mengakses halaman ini.');
            redirect('affiliate/auth/login');
        }
    }
}