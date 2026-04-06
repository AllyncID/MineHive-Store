<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bucks_kaget extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Bucks_kaget_model');
        $this->load->model('User_model');
        $this->load->library('Pterodactyl_service');
        $this->load->library('session');
    }

    public function resolve() {
        $token = trim((string) $this->uri->uri_string(), '/');

        if ($token === '' || strpos($token, '/') !== false || !$this->Bucks_kaget_model->is_valid_token_format($token)) {
            show_404();
        }

        $campaign = $this->Bucks_kaget_model->get_campaign_by_token($token);
        if (!$campaign) {
            show_404();
        }

        $data['title'] = 'Bucks Kaget | MineHive';
        $data['meta_description'] = 'Claim link Bucks Kaget di MineHive, masukkan nickname Minecraft kamu, lalu ambil bagian hadiah Bucks yang masih tersedia.';
        $data['campaign'] = $campaign;
        $data['viewer'] = [
            'is_logged_in' => (bool) $this->session->userdata('is_logged_in'),
            'username' => trim((string) $this->session->userdata('username')),
            'platform' => trim((string) $this->session->userdata('platform'))
        ];
        $data['page_stylesheets'] = [
            base_url('assets/css/bucks-kaget.css?v=' . (file_exists('assets/css/bucks-kaget.css') ? filemtime('assets/css/bucks-kaget.css') : time()))
        ];
        $data['page_scripts'] = [
            base_url('assets/js/bucks-kaget.js?v=' . (file_exists('assets/js/bucks-kaget.js') ? filemtime('assets/js/bucks-kaget.js') : time()))
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('bucks_kaget/claim_view', $data);
        $this->load->view('templates/footer', $data);
    }

    public function claim($token = null) {
        header('Content-Type: application/json');

        $token = trim((string) $token);
        if ($token === '' || !$this->Bucks_kaget_model->is_valid_token_format($token)) {
            echo json_encode(['status' => 'invalid', 'message' => 'Link Bucks Kaget tidak valid.']);
            return;
        }

        $campaign = $this->Bucks_kaget_model->get_campaign_by_token($token);
        if (!$campaign) {
            echo json_encode(['status' => 'invalid', 'message' => 'Link Bucks Kaget tidak ditemukan.']);
            return;
        }

        $username = trim((string) $this->input->post('username'));
        $platform = trim((string) $this->input->post('platform'));

        if ($username === '' || $platform === '') {
            echo json_encode(['status' => 'error', 'message' => 'Nickname dan platform wajib diisi.']);
            return;
        }

        $user_data = $this->User_model->validate_user($username, $platform);
        if (!$user_data) {
            echo json_encode(['status' => 'player_not_found', 'message' => 'Nickname Minecraft tidak ditemukan. Pastikan penulisannya benar.']);
            return;
        }

        $player_uuid = $user_data['uuid'];
        $correct_username = $user_data['username'];
        $claim_result = $this->Bucks_kaget_model->claim_campaign(
            $campaign->id,
            $player_uuid,
            $correct_username,
            $platform,
            $this->input->ip_address(),
            function ($amount) use ($correct_username) {
                $command = 'bucks add ' . $correct_username . ' ' . (int) $amount;
                return $this->pterodactyl_service->send_proxy_command($command);
            }
        );

        switch ($claim_result['status']) {
            case 'success':
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Kamu berhasil mendapatkan ' . number_format((int) $claim_result['amount'], 0, ',', '.') . ' Bucks!',
                    'amount' => (int) $claim_result['amount'],
                    'username' => $correct_username
                ]);
                return;

            case 'already_claimed':
                echo json_encode([
                    'status' => 'already_claimed',
                    'message' => 'Nickname ini sudah claim sebelumnya dan mendapatkan ' . number_format((int) $claim_result['amount'], 0, ',', '.') . ' Bucks.',
                    'amount' => (int) $claim_result['amount']
                ]);
                return;

            case 'inactive':
                echo json_encode(['status' => 'inactive', 'message' => 'Bucks Kaget ini sudah ditutup oleh admin.']);
                return;

            case 'expired':
                echo json_encode(['status' => 'expired', 'message' => 'Bucks Kaget ini sudah expired.']);
                return;

            case 'finished':
                echo json_encode(['status' => 'finished', 'message' => 'Bucks Kaget ini sudah habis.']);
                return;

            case 'command_failed':
                echo json_encode(['status' => 'command_failed', 'message' => 'Server game sedang sibuk. Coba claim lagi sebentar ya.']);
                return;

            default:
                echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat memproses claim.']);
                return;
        }
    }
}
