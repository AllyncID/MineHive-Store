<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Featured extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Featured_model');
        $this->load->model('Product_model');
        $this->load->model('Settings_model'); // Pastikan load model settings
        $this->load->library('form_validation');
    }

    public function index() {
        $data['title'] = 'Featured Products (Hot Deals)';
        $data['featured'] = $this->Featured_model->get_all();
        $data['total_items'] = $this->Featured_model->count_all();
        
        // [BARU] Ambil settingan design yang aktif
        $settings = $this->Settings_model->get_all_settings();
        $data['current_design'] = $settings['featured_design_style'] ?? 'marquee'; // Default marquee

        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/featured/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    // [FUNGSI BARU] Untuk menyimpan pilihan design
    public function update_design() {
        if ($this->input->post('featured_design_style')) {
            $data = [
                'featured_design_style' => $this->input->post('featured_design_style')
            ];
            $this->Settings_model->update_batch($data);
            $this->session->set_flashdata('success', 'Tampilan Hot Deals berhasil diubah!');
        }
        redirect('admin/featured');
    }

    public function add() {
        // ... (kode add yang lama tetap sama) ...
        // Cek batasan maksimal 12 item (DIUBAH DARI 6 KE 12)
        if ($this->Featured_model->count_all() >= 12) {
            $this->session->set_flashdata('error', 'Maksimal Featured Products adalah 12 item. Hapus salah satu terlebih dahulu.');
            redirect('admin/featured');
            return;
        }

        $this->form_validation->set_rules('product_id', 'Produk', 'required|numeric|is_unique[featured_products.product_id]');
        $this->form_validation->set_rules('discount_percentage', 'Diskon', 'required|numeric|greater_than_equal_to[0]|less_than[100]');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Tambah Featured Product';
            $data['products'] = $this->Product_model->get_all_products();
            
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/featured/form', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            $data = [
                'product_id' => $this->input->post('product_id'),
                'discount_percentage' => $this->input->post('discount_percentage'),
                'sort_order' => $this->input->post('sort_order') ?? 0
            ];
            $this->Featured_model->insert($data);
            $this->session->set_flashdata('success', 'Produk berhasil ditambahkan ke Featured.');
            redirect('admin/featured');
        }
    }

    public function edit($id) {
        // ... (kode edit yang lama tetap sama) ...
        $data['item'] = $this->Featured_model->get_by_id($id);
        if(!$data['item']) show_404();

        $this->form_validation->set_rules('discount_percentage', 'Diskon', 'required|numeric');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = 'Edit Featured Product';
            $data['products'] = $this->Product_model->get_all_products(); // Tetap load untuk display nama jika perlu
            
            $this->load->view('admin/templates/admin_header', $data);
            $this->load->view('admin/featured/form', $data);
            $this->load->view('admin/templates/admin_footer');
        } else {
            $update_data = [
                'discount_percentage' => $this->input->post('discount_percentage'),
                'sort_order' => $this->input->post('sort_order')
            ];
            // Jika user ingin ganti produk (opsional, biasanya di delete trus add baru, tapi ok)
            if($this->input->post('product_id')) {
                 $update_data['product_id'] = $this->input->post('product_id');
            }

            $this->Featured_model->update($id, $update_data);
            $this->session->set_flashdata('success', 'Featured product berhasil diupdate.');
            redirect('admin/featured');
        }
    }

    public function delete($id) {
        // ... (kode delete yang lama tetap sama) ...
        $this->Featured_model->delete($id);
        $this->session->set_flashdata('success', 'Produk dihapus dari list Featured.');
        redirect('admin/featured');
    }
}