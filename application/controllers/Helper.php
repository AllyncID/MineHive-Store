<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Helper extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('session'); // Pastikan library session dimuat
    }

    public function index() {
        $data['title'] = 'Staff Helper Area | MineHive';

        // Cek apakah user sudah login session helper
        if ($this->session->userdata('helper_access') === TRUE) {
            $this->load->view('templates/header', $data);
            $this->load->view('helper/dashboard_view', $data);
            $this->load->view('templates/footer');
        } else {
            $this->load->view('templates/header', $data);
            $this->load->view('helper/login_view', $data);
            $this->load->view('templates/footer');
        }
    }

    public function auth() {
        $password = $this->input->post('password');
        
        // GANTI PASSWORD DI SINI
        $correct_password = 'helperminehive123'; 

        if ($password === $correct_password) {
            // Set session agar tidak perlu login ulang terus menerus
            $this->session->set_userdata('helper_access', TRUE);
            redirect('helper');
        } else {
            // Jika salah, kembalikan dengan pesan error (opsional: bisa pakai flashdata)
            echo "<script>alert('Password Salah!'); window.location.href='" . base_url('helper') . "';</script>";
        }
    }

    public function logout() {
        $this->session->unset_userdata('helper_access');
        redirect('helper');
    }
}