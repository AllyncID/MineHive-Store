<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Discounts extends Admin_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Discount_model');
        $this->load->model('Product_model'); // Untuk mengambil daftar produk
        $this->load->library('form_validation'); // Load form validation
    }

    public function index() {
        $data['discounts'] = $this->Discount_model->get_all_discounts();
        $data['title'] = 'Manajemen Diskon Terjadwal';
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/discounts/index', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function add() {
        if ($this->input->post()) {
            $discount_data = [
                'name' => $this->input->post('name'),
                'discount_percentage' => $this->input->post('discount_percentage'),
                'starts_at' => $this->input->post('starts_at'),
                'ends_at' => $this->input->post('ends_at'),
                'is_active' => $this->input->post('is_active') ? 1 : 0
            ];

            $scopes_data = [];
            $scope_type = $this->input->post('scope_type');

            if ($scope_type == 'store-wide') {
                $scopes_data[] = ['scope_type' => 'store-wide', 'scope_value' => null];
            } else if ($scope_type == 'category') {
                // [PERUBAHAN] Validasi agar tidak kosong
                $categories = $this->input->post('categories') ?? [];
                if (empty($categories)) {
                    $this->session->set_flashdata('error', 'Anda harus memilih setidaknya satu kategori.');
                    redirect('admin/discounts/add');
                    return;
                }
                foreach($categories as $category) {
                    $scopes_data[] = ['scope_type' => 'category', 'scope_value' => $category];
                }
            } else if ($scope_type == 'product') {
                // [PERUBAHAN] Validasi agar tidak kosong
                $products = $this->input->post('products') ?? [];
                if (empty($products)) {
                    $this->session->set_flashdata('error', 'Anda harus memilih setidaknya satu produk.');
                    redirect('admin/discounts/add');
                    return;
                }
                foreach($products as $product_id) {
                    $scopes_data[] = ['scope_type' => 'product', 'scope_value' => $product_id];
                }
            }

            $this->Discount_model->insert_discount($discount_data, $scopes_data);
            $this->session->set_flashdata('success', 'Diskon baru berhasil dibuat.');
            redirect('admin/discounts');
        }

        $data['products'] = $this->Product_model->get_all_products();
        $data['title'] = 'Buat Diskon Baru';
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/discounts/form', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    /**
     * [FUNGSI BARU] Menampilkan form edit dan memproses update
     */
    public function edit($id) {
        $data['discount'] = $this->Discount_model->get_by_id($id);
        if (!$data['discount']) { show_404(); }

        // Ambil scope yang sudah ada
        $existing_scopes = $this->Discount_model->get_scopes_by_discount_id($id);
        $data['scopes'] = ['type' => 'store-wide', 'values' => []]; // Default
        if (!empty($existing_scopes)) {
            $data['scopes']['type'] = $existing_scopes[0]->scope_type;
            // Kumpulkan semua value (ID produk/nama kategori) ke dalam array
            $data['scopes']['values'] = array_column($existing_scopes, 'scope_value');
        }

        if ($this->input->post()) {
            $discount_data = [
                'name' => $this->input->post('name'),
                'discount_percentage' => $this->input->post('discount_percentage'),
                'starts_at' => $this->input->post('starts_at'),
                'ends_at' => $this->input->post('ends_at'),
                'is_active' => $this->input->post('is_active') ? 1 : 0
            ];

            $scopes_data = [];
            $scope_type = $this->input->post('scope_type');

            if ($scope_type == 'store-wide') {
                $scopes_data[] = ['scope_type' => 'store-wide', 'scope_value' => null];
            } else if ($scope_type == 'category') {
                // [PERUBAHAN] Validasi agar tidak kosong
                $categories = $this->input->post('categories') ?? [];
                if (empty($categories)) {
                    $this->session->set_flashdata('error', 'Anda harus memilih setidaknya satu kategori.');
                    redirect('admin/discounts/edit/' . $id);
                    return;
                }
                foreach($categories as $category) {
                    $scopes_data[] = ['scope_type' => 'category', 'scope_value' => $category];
                }
            } else if ($scope_type == 'product') {
                // [PERUBAHAN] Validasi agar tidak kosong
                $products = $this->input->post('products') ?? [];
                if (empty($products)) {
                    $this->session->set_flashdata('error', 'Anda harus memilih setidaknya satu produk.');
                    redirect('admin/discounts/edit/' . $id);
                    return;
                }
                foreach($products as $product_id) {
                    $scopes_data[] = ['scope_type' => 'product', 'scope_value' => $product_id];
                }
            }

            $this->Discount_model->update_discount($id, $discount_data, $scopes_data);
            $this->session->set_flashdata('success', 'Diskon berhasil diperbarui.');
            redirect('admin/discounts');
        }

        $data['products'] = $this->Product_model->get_all_products();
        $data['title'] = 'Edit Diskon';
        $this->load->view('admin/templates/admin_header', $data);
        $this->load->view('admin/discounts/form', $data);
        $this->load->view('admin/templates/admin_footer');
    }

    public function delete($id) {
        $this->Discount_model->delete_discount($id);
        $this->session->set_flashdata('success', 'Diskon berhasil dihapus.');
        redirect('admin/discounts');
    }
}