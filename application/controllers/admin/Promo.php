<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Controller ini mewarisi dari Admin_Controller, jadi otomatis aman
class Promo extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        // Memuat model yang dibutuhkan untuk semua fungsi di controller ini
        $this->load->model('Promo_model');
    }

    /**
     * Menampilkan halaman utama yang berisi daftar semua kode promo.
     */
    public function index() {
        // Mengambil semua data promo beserta jumlah penggunaannya
        $data['promos'] = $this->Promo_model->get_all_with_usage();
        $data['title'] = 'Manage Promo Codes';
        
        // Memuat template lengkap untuk halaman admin
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/promo/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    /**
     * Menampilkan form untuk menambah kode promo baru,
     * atau memproses data yang dikirim dari form tersebut.
     */
    public function add() {
        // Cek apakah ada data yang dikirim melalui metode POST
        if ($this->input->post()) {
            $data = [
                'code' => strtoupper($this->input->post('code')),
                'type' => $this->input->post('type'),
                'value' => $this->input->post('value'),
                // Menggunakan ternary operator untuk menyimpan NULL jika input kosong
                'expires_at' => $this->input->post('expires_at') ?: NULL,
                'usage_limit' => $this->input->post('usage_limit') ?: NULL,
                'allow_stacking' => $this->input->post('allow_stacking') ? 1 : 0 // <-- [PERUBAHAN] Menyimpan data stacking
            ];
            $this->Promo_model->insert($data);
            $this->session->set_flashdata('success', 'Kode promo berhasil ditambahkan.');
            redirect('admin/promo');
        }

        // Jika tidak ada data POST, tampilkan form kosong
        $data['title'] = 'Tambah Kode Promo';
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/promo/form', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    /**
     * Menampilkan form untuk mengedit kode promo yang sudah ada,
     * atau memproses data yang dikirim dari form tersebut.
     * @param int $id ID dari kode promo yang akan diedit.
     */
    public function edit($id) {
        // Cek apakah ada data yang dikirim melalui metode POST
        if ($this->input->post()) {
            $data = [
                'code' => strtoupper($this->input->post('code')),
                'type' => $this->input->post('type'),
                'value' => $this->input->post('value'),
                // Menggunakan ternary operator untuk menyimpan NULL jika input kosong
                'expires_at' => $this->input->post('expires_at') ?: NULL,
                'usage_limit' => $this->input->post('usage_limit') ?: NULL,
                'allow_stacking' => $this->input->post('allow_stacking') ? 1 : 0 // <-- [PERUBAHAN] Menyimpan data stacking
            ];
            $this->Promo_model->update($id, $data);
            $this->session->set_flashdata('success', 'Kode promo berhasil diperbarui.');
            redirect('admin/promo');
        }

        // Jika tidak ada data POST, ambil data lama dan tampilkan di form
        $data['promo'] = $this->Promo_model->get_by_id($id);
        $data['title'] = 'Edit Kode Promo';
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/promo/form', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    /**
     * Menghapus data kode promo berdasarkan ID.
     * @param int $id ID dari kode promo yang akan dihapus.
     */
    public function delete($id) {
        $this->Promo_model->delete($id);
        $this->session->set_flashdata('success', 'Kode promo berhasil dihapus.');
        redirect('admin/promo');
    }
}
