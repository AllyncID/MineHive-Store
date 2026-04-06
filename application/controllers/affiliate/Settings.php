<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends Affiliate_Controller { // <-- extends Affiliate_Controller

    public function __construct() {
        parent::__construct();
        $this->load->model('Affiliate_model');
    }

    // Menampilkan halaman pengaturan
    public function index() {
        $affiliate_id = $this->session->userdata('affiliate_id');
        $data['payout_methods'] = $this->Affiliate_model->get_payout_methods($affiliate_id);
        $data['title'] = 'Atur Pembayaran | Mine Hive';
        $this->load->view('affiliate/settings_view', $data);
    }

    // Memproses penambahan metode baru
    public function add_method() {
        if ($this->input->post()) {
            $data = [
                'affiliate_id'    => $this->session->userdata('affiliate_id'),
                'method_type'     => $this->input->post('method_type'),
                'account_details' => $this->input->post('account_details'),
            ];
            $this->Affiliate_model->add_payout_method($data);
            $this->session->set_flashdata('success', 'Metode pembayaran baru berhasil ditambahkan.');
        }
        redirect('affiliate/settings');
    }

    // Menghapus metode pembayaran
    public function delete_method($method_id) {
        $affiliate_id = $this->session->userdata('affiliate_id');
        $this->Affiliate_model->delete_payout_method($method_id, $affiliate_id);
        $this->session->set_flashdata('success', 'Metode pembayaran telah dihapus.');
        redirect('affiliate/settings');
    }
}