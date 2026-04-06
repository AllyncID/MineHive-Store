<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Affiliates extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Affiliate_model');
    }

    public function index() {
        $data['affiliates'] = $this->Affiliate_model->get_all_affiliates();
        $data['title'] = 'Manajemen Afiliasi';
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/affiliates/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function add() {
        // Bagian ini berjalan JIKA form di-submit (metode POST)
        if ($this->input->post()) {
            $password = $this->input->post('password');
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $data = [
                'minecraft_username' => $this->input->post('minecraft_username'),
                'email' => $this->input->post('email'),
                'password' => $hashed_password
            ];
            $this->Affiliate_model->insert_affiliate($data);
            $this->session->set_flashdata('success', 'Afiliasi baru berhasil ditambahkan.');
            redirect('admin/affiliates');
        }
        
        // --- BAGIAN YANG HILANG SEBELUMNYA ADA DI SINI ---
        // Kode ini berjalan saat halaman diakses biasa (metode GET)
        // untuk menampilkan form-nya.
        $data['title'] = 'Tambah Afiliasi Baru';
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/affiliates/form', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function delete($id) {
        if ($this->Affiliate_model->delete_affiliate($id)) {
            $this->session->set_flashdata('success', 'Afiliasi berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus afiliasi.');
        }
        redirect('admin/affiliates');
    }

    // Nanti kita bisa tambahkan fungsi edit dan delete di sini
}