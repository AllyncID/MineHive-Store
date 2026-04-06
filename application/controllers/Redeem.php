<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Redeem extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Redeem_model');
    }

    public function index() {
        if (!$this->session->userdata('is_logged_in')) {
            $this->session->set_flashdata('error', 'Anda harus login untuk menggunakan kode redeem.');
            redirect(base_url());
            return;
        }

        $data['title'] = 'Redeem Kode | MineHive';
        $data['meta_description'] = 'Masukkan kode redeem MineHive untuk klaim promo atau hadiah yang bisa dipakai saat checkout di store.';
        $this->load->view('templates/header', $data);
        $this->load->view('redeem_view', $data);
        $this->load->view('templates/footer');
    }

    public function process() {
        header('Content-Type: application/json');
        if (!$this->session->userdata('is_logged_in')) {
            echo json_encode(['status' => 'error', 'message' => 'Sesi login tidak valid.']);
            return;
        }

        $code = $this->input->post('redeem_code');
        $redeem_data = $this->Redeem_model->get_by_code($code);

        if (!$redeem_data) {
            echo json_encode(['status' => 'error', 'message' => 'Kode redeem yang Anda masukkan tidak valid.']);
            return;
        }

        if ($redeem_data->claimed_by_uuid !== null) {
            echo json_encode(['status' => 'error', 'message' => 'Maaf, kode ini sudah pernah digunakan oleh ' . $redeem_data->claimed_by_username . '.']);
            return;
        }

        // Jika valid dan belum diklaim, klaim kodenya
        $uuid = $this->session->userdata('uuid');
        $username = $this->session->userdata('username');
        $this->Redeem_model->claim_code($redeem_data->id, $uuid, $username);

        // Kirim kode promo sebagai hadiah
        echo json_encode(['status' => 'success', 'promo_code' => $redeem_data->promo_code_string]);
    }
}
