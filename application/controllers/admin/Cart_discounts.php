<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cart_discounts extends Admin_Controller { // Pastikan extends dari Admin_Controller

    public function __construct() {
        parent::__construct();
        $this->load->model('Cart_discount_model');
        $this->load->library('form_validation'); // Untuk validasi form
        $this->load->helper('url');
    }

    /**
     * Menampilkan halaman utama manajemen diskon keranjang.
     */
    public function index() {
        $data['title'] = 'Diskon Berdasarkan Total Keranjang';
        $data['tiers'] = $this->Cart_discount_model->get_all_tiers();
        $data['is_enabled'] = $this->Cart_discount_model->is_enabled(); // Ambil status enabled

        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/cart_discounts/index', $data); // View baru
        $this->load->view('admin/templates/admin_footer');
    }

    /**
     * Mengaktifkan atau menonaktifkan fitur.
     */
    public function toggle_status() {
        $current_status = $this->Cart_discount_model->is_enabled();
        $new_status = !$current_status; // Balikkan status

        if ($this->Cart_discount_model->set_enabled_status($new_status)) {
            $this->session->set_flashdata('success', 'Status fitur diskon keranjang berhasil ' . ($new_status ? 'diaktifkan.' : 'dinonaktifkan.'));
        } else {
            $this->session->set_flashdata('error', 'Gagal mengubah status fitur.');
        }
        redirect('admin/cart_discounts');
    }

    /**
     * Menampilkan form tambah atau memproses penambahan data.
     */
    public function add() {
        // Aturan validasi
        $this->form_validation->set_rules('min_amount', 'Total Keranjang Minimum', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('discount_percentage', 'Persentase Diskon', 'required|numeric|greater_than[0]|less_than_equal_to[100]');

        if ($this->form_validation->run() === FALSE) {
            // Jika validasi gagal, tampilkan form
            $data['title'] = 'Tambah Tingkatan Diskon Keranjang';
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/cart_discounts/form', $data); // View baru
            $this->load->view('admin/templates/admin_footer');
        } else {
            // Jika validasi sukses, simpan data
            $data_to_save = [
                'min_amount' => $this->input->post('min_amount'),
                'discount_percentage' => $this->input->post('discount_percentage')
            ];
            $this->Cart_discount_model->insert_tier($data_to_save);
            $this->session->set_flashdata('success', 'Tingkatan diskon baru berhasil ditambahkan.');
            redirect('admin/cart_discounts');
        }
    }

    /**
     * Menampilkan form edit atau memproses pembaruan data.
     * @param int $id ID tingkatan yang akan diedit.
     */
    public function edit($id) {
        $data['tier'] = $this->Cart_discount_model->get_tier_by_id($id);
        if (!$data['tier']) { show_404(); } // Tampilkan 404 jika ID tidak valid

        // Aturan validasi (sama seperti add)
        $this->form_validation->set_rules('min_amount', 'Total Keranjang Minimum', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('discount_percentage', 'Persentase Diskon', 'required|numeric|greater_than[0]|less_than_equal_to[100]');

        if ($this->form_validation->run() === FALSE) {
            // Jika validasi gagal, tampilkan form dengan data lama
            $data['title'] = 'Edit Tingkatan Diskon Keranjang';
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/cart_discounts/form', $data); // View baru
            $this->load->view('admin/templates/admin_footer');
        } else {
            // Jika validasi sukses, update data
            $data_to_update = [
                'min_amount' => $this->input->post('min_amount'),
                'discount_percentage' => $this->input->post('discount_percentage')
            ];
            $this->Cart_discount_model->update_tier($id, $data_to_update);
            $this->session->set_flashdata('success', 'Tingkatan diskon berhasil diperbarui.');
            redirect('admin/cart_discounts');
        }
    }

    /**
     * Menghapus tingkatan diskon.
     * @param int $id ID tingkatan yang akan dihapus.
     */
    public function delete($id) {
        $this->Cart_discount_model->delete_tier($id);
        $this->session->set_flashdata('success', 'Tingkatan diskon berhasil dihapus.');
        redirect('admin/cart_discounts');
    }
}
