<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        // Sekarang kita butuh model ini, pastikan tidak ada comment
        $this->load->model('Admin_model'); 
    }

    public function login() {
        // Jika sudah login, redirect ke dashboard
        if ($this->session->userdata('is_admin_logged_in')) {
            redirect('admin/dashboard');
        }
        $this->load->view('admin/login_view');
    }

    /**
     * Ini adalah method yang kita ubah kembali ke versi database.
     */
    public function process_login() {
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        // 1. Ambil data admin dari database berdasarkan username
        $admin = $this->Admin_model->get_by_username($username);

        // 2. Verifikasi
        // - Cek apakah admin dengan username tersebut ada ($admin)
        // - Cek apakah password yang dimasukkan cocok dengan hash di database (password_verify)
        if ($admin && password_verify($password, $admin->password)) {
            
            // 3. Jika berhasil, buat session
            $session_data = [
                'admin_id'       => $admin->id,
                'admin_username' => $admin->username,
                'is_admin_logged_in' => TRUE
            ];
            $this->session->set_userdata($session_data);
            redirect('admin/dashboard');

        } else {
            // 4. Jika gagal, kembalikan ke halaman login dengan pesan error
            $this->session->set_flashdata('error', 'Username atau password salah.');
            redirect('admin/auth/login');
        }
    }

    public function logout() {
        $this->session->sess_destroy();
        redirect('admin/auth/login');
    }
}