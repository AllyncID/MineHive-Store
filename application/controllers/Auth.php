<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('session');
    }

public function login() {
    header('Content-Type: application/json');
    
    // === PERBAIKAN DI SINI: Gunakan trim() untuk membersihkan input ===
    $username = trim($this->input->post('username'));
    // =================================================================

    $platform = $this->input->post('platform');

    if (empty($username) || empty($platform)) {
        echo json_encode(['status' => 'error', 'message' => 'Username dan platform harus diisi.']);
        return;
    }

    $user_data_from_db = $this->User_model->validate_user($username, $platform);

    if ($user_data_from_db) {
        
        $uuid = $user_data_from_db['uuid'];
        
        // --- INI DIA PERBAIKANNYA ---
        // Kita gunakan 'username' karena 'lastNickname' tidak selalu ada (terutama dari fallback)
        // Model User_model sudah kita atur untuk selalu mengembalikan key 'username'
        $correct_username = $user_data_from_db['username']; 
        // -----------------------------

        // Cek data afiliasi
        $this->load->model('Affiliate_model');
        $affiliate_data = $this->Affiliate_model->get_affiliate_by_username($correct_username);
        $affiliate_badge = null;
        if ($affiliate_data) {
            // Kita ambil badge yang sudah di-refresh dengan data terbaru
            $affiliate_badge = $this->Affiliate_model->get_badge_info($affiliate_data->total_sales, $affiliate_data->total_transactions)['badge'];
        }

        $session_data = [
            'uuid'         => $uuid,
            'username'     => $correct_username,
            'platform'     => $platform,
            'is_logged_in' => TRUE,
            'affiliate_badge' => $affiliate_badge // Simpan badge di session
        ];
        $this->session->set_userdata($session_data);

        echo json_encode(['status' => 'success', 'username' => $correct_username]);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Player tidak ditemukan. Pastikan nama yang Anda masukkan sudah benar.']);
    }
}

    public function logout() {
        $this->session->sess_destroy();
        redirect(base_url());
    }
}

