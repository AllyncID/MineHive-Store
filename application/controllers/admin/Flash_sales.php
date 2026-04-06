<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Flash_sales extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        // Muat semua yang dibutuhkan di awal
        $this->load->model('Flash_sale_model');
        $this->load->model('Product_model'); // Dibutuhkan untuk dropdown produk
        $this->load->library('form_validation');
        $this->load->helper('url');
        
        // [BARU] Load Settings_model
        $this->load->model('Settings_model');
    }

    /**
     * Menampilkan halaman utama (daftar semua flash sale).
     */
    public function index() {
        $data['title'] = 'Manajemen Flash Sale';
        $data['flash_sales'] = $this->Flash_sale_model->get_all();
        
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/flash_sales/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    /**
     * [BARU] Halaman Pengaturan Random Flash Sale
     */
    public function settings() {
        // Atur judul halaman
        $data['title'] = 'Pengaturan Random Flash Sale';

        // Proses jika ada form yang disubmit
        if ($this->input->post()) {
            // Ambil data dari form
            $settings_data = [
                'random_fs_enabled'           => $this->input->post('random_fs_enabled'),
                'random_fs_min_discount'      => $this->input->post('random_fs_min_discount'),
                'random_fs_max_discount'      => $this->input->post('random_fs_max_discount'),
                'random_fs_min_duration_hours' => $this->input->post('random_fs_min_duration_hours'),
                'random_fs_max_duration_hours' => $this->input->post('random_fs_max_duration_hours'),
                // [BARU] Simpan Jumlah Penjualan
                'random_fs_count'             => $this->input->post('random_fs_count'),
                // Ambil array produk yang dikecualikan dan ubah jadi JSON
                'random_fs_excluded_products' => json_encode($this->input->post('random_fs_excluded_products') ?? []),
                // [BARU] Simpan URL Webhook
                'random_fs_webhook_url' => $this->input->post('random_fs_webhook_url')
            ];

            // Simpan pengaturan ke database
            $this->Settings_model->update_batch($settings_data);
            $this->session->set_flashdata('success', 'Pengaturan Random Flash Sale berhasil disimpan.');
            redirect('admin/flash_sales/settings');
        }

        // Ambil pengaturan yang ada dari database
        $data['settings'] = $this->Settings_model->get_all_settings();
        
        // Ambil semua produk untuk ditampilkan di list "kecualikan produk"
        $data['products'] = $this->Product_model->get_all_products();
        
        // Tampilkan view
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/flash_sales/settings', $data); // Ini view baru
        $this->load->view('admin/templates/admin_footer');
    }

    /**
     * Menampilkan form tambah & memproses data saat disubmit.
     */
    public function add() {
        // Aturan validasi untuk form
        $this->form_validation->set_rules('product_id', 'Produk', 'required|numeric');
        $this->form_validation->set_rules('discount_percentage', 'Persentase Diskon', 'required|numeric|greater_than[0]|less_than[101]');
        $this->form_validation->set_rules('stock_limit', 'Batas Stok', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('start_date', 'Waktu Mulai', 'required');
        $this->form_validation->set_rules('end_date', 'Waktu Berakhir', 'required');

        if ($this->form_validation->run() === FALSE) {
            // Jika validasi gagal, tampilkan form lagi dengan pesan error
            $data['title'] = 'Tambah Flash Sale Baru';
            // Ambil semua produk untuk ditampilkan di dropdown
            $products_obj = $this->Product_model->get_all_products();
            $data['products'] = json_decode(json_encode($products_obj), true);

            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/flash_sales/form', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            // Jika validasi sukses, siapkan data untuk disimpan
            $data_to_save = [
                'product_id'          => $this->input->post('product_id'),
                'discount_percentage' => $this->input->post('discount_percentage'),
                'start_date'          => $this->input->post('start_date'),
                'end_date'            => $this->input->post('end_date'),
                'stock_limit'         => $this->input->post('stock_limit'),
                'is_active'           => $this->input->post('is_active') ?? 0 // Jika checkbox tidak dicentang, nilainya 0
            ];

            $this->Flash_sale_model->insert($data_to_save);
            $this->session->set_flashdata('success', 'Flash sale baru berhasil dibuat.');
            redirect('admin/flash_sales');
        }
    }

    /**
     * Menampilkan form edit & memproses data saat disubmit.
     */
    public function edit($id) {
        $data['flash_sale'] = $this->Flash_sale_model->get_by_id($id);
        if (!$data['flash_sale']) { show_404(); }

        // Aturan validasi untuk form
        $this->form_validation->set_rules('product_id', 'Produk', 'required|numeric');
        $this->form_validation->set_rules('discount_percentage', 'Persentase Diskon', 'required|numeric|greater_than[0]|less_than[101]');
        $this->form_validation->set_rules('stock_limit', 'Batas Stok', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('start_date', 'Waktu Mulai', 'required');
        $this->form_validation->set_rules('end_date', 'Waktu Berakhir', 'required');
        $this->form_validation->set_rules('discount_percentage', 'Persentase Diskon', 'required|numeric');


        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Edit Flash Sale';
            $products_obj = $this->Product_model->get_all_products();
            $data['products'] = json_decode(json_encode($products_obj), true);

            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/flash_sales/form', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            $data_to_update = [
                'product_id'          => $this->input->post('product_id'),
                'discount_percentage' => $this->input->post('discount_percentage'),
                'start_date'          => $this->input->post('start_date'),
                'end_date'            => $this->input->post('end_date'),
                'stock_limit'         => $this->input->post('stock_limit'),
                'is_active'           => $this->input->post('is_active') ?? 0
            ];

            $this->Flash_sale_model->update($id, $data_to_update);
            $this->session->set_flashdata('success', 'Flash sale berhasil diperbarui.');
            redirect('admin/flash_sales');
        }
    }

    /**
     * Menghapus data flash sale.
     */
    public function delete($id) {
        $this->Flash_sale_model->delete($id);
        $this->session->set_flashdata('success', 'Flash sale berhasil dihapus.');
        redirect('admin/flash_sales');
    }
}