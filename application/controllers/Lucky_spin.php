<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lucky_spin extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Lucky_spin_model');
        $this->load->model('User_model');
        $this->load->model('Store_model');
        $this->load->helper('url');
        $this->load->library('Pterodactyl_service');
        $this->load->library('Product_execution_service');
    }

    public function show($token = null) {
        $token = trim((string) $token);

        if ($token === '' || !$this->Lucky_spin_model->is_valid_token_format($token)) {
            show_404();
        }

        $campaign = $this->Lucky_spin_model->get_campaign_by_token($token);
        if (!$campaign) {
            show_404();
        }

        $data['title'] = 'Lucky Spin';
        $data['meta_description'] = 'Ikut Lucky Spin MineHive, masukkan nickname Minecraft, lalu putar hadiah random selama event masih aktif.';
        $data['campaign'] = $campaign;
        $data['rewards'] = $this->Lucky_spin_model->get_public_rewards_for_campaign((int) $campaign->id);
        $this->load->view('lucky_spin/spin_view', $data);
    }

    public function play($token = null) {
        header('Content-Type: application/json');

        $token = trim((string) $token);
        if ($token === '' || !$this->Lucky_spin_model->is_valid_token_format($token)) {
            echo json_encode(['status' => 'invalid', 'message' => 'Link Lucky Spin tidak valid.']);
            return;
        }

        $campaign = $this->Lucky_spin_model->get_campaign_by_token($token);
        if (!$campaign) {
            echo json_encode(['status' => 'invalid', 'message' => 'Link Lucky Spin tidak ditemukan.']);
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

        $spin_result = $this->Lucky_spin_model->spin_campaign(
            (int) $campaign->id,
            $player_uuid,
            $correct_username,
            $platform,
            $this->input->ip_address(),
            function (array $reward) use ($correct_username) {
                return $this->execute_spin_reward($reward, $correct_username);
            }
        );

        switch ($spin_result['status']) {
            case 'success':
                $reward = $spin_result['reward'] ?? [];
                echo json_encode([
                    'status' => 'success',
                    'message' => $this->build_reward_message($reward),
                    'reward' => $reward,
                    'username' => $correct_username,
                    'spins_used' => (int) ($spin_result['spins_used'] ?? 1),
                    'remaining_spins_for_player' => (int) ($spin_result['remaining_spins_for_player'] ?? 0),
                    'remaining_player_slots' => (int) ($spin_result['remaining_player_slots'] ?? 0),
                    'can_spin_again' => !empty($spin_result['can_spin_again'])
                ]);
                return;

            case 'player_not_found':
                echo json_encode(['status' => 'player_not_found', 'message' => $spin_result['message'] ?? 'Nickname Minecraft tidak ditemukan.']);
                return;

            case 'spin_limit_reached':
                echo json_encode(['status' => 'spin_limit_reached', 'message' => $spin_result['message'] ?? 'Batas spin untuk nickname ini sudah habis.']);
                return;

            case 'player_limit_reached':
                echo json_encode(['status' => 'player_limit_reached', 'message' => $spin_result['message'] ?? 'Kuota peserta Lucky Spin sudah habis.']);
                return;

            case 'inactive':
            case 'expired':
            case 'finished':
            case 'reward_failed':
            case 'invalid':
            case 'error':
            default:
                echo json_encode([
                    'status' => $spin_result['status'] ?? 'error',
                    'message' => $spin_result['message'] ?? 'Terjadi kesalahan saat memproses Lucky Spin.'
                ]);
                return;
        }
    }

    private function execute_spin_reward(array $reward, $username) {
        $reward_type = trim((string) ($reward['reward_type'] ?? 'zonk'));

        if ($reward_type === 'zonk') {
            return [
                'success' => true,
                'message' => 'Spin selesai.'
            ];
        }

        if ($reward_type === 'bucks') {
            $amount = max(0, (int) ($reward['bucks_amount'] ?? 0));
            if ($amount < 1) {
                return [
                    'success' => false,
                    'message' => 'Nominal Bucks untuk hadiah Lucky Spin tidak valid.'
                ];
            }

            $command = 'bucks add ' . $username . ' ' . $amount;
            if (!$this->pterodactyl_service->send_proxy_command($command)) {
                return [
                    'success' => false,
                    'message' => 'Server game sedang sibuk. Coba spin lagi sebentar ya.'
                ];
            }

            return [
                'success' => true,
                'message' => 'Bucks berhasil dikirim.'
            ];
        }

        if ($reward_type === 'product') {
            $product_id = (int) ($reward['product_id'] ?? 0);
            if ($product_id < 1) {
                return [
                    'success' => false,
                    'message' => 'Produk hadiah Lucky Spin tidak valid.'
                ];
            }

            $product = $this->Store_model->get_product_by_id($product_id);
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Produk hadiah Lucky Spin tidak ditemukan.'
                ];
            }

            $execution = $this->product_execution_service->execute_product_purchase($product, $username, 1, [
                'include_donation_counter' => false
            ]);

            return [
                'success' => !empty($execution['success']),
                'message' => $execution['message'] ?? 'Hadiah produk Lucky Spin gagal dieksekusi.'
            ];
        }

        return [
            'success' => false,
            'message' => 'Tipe hadiah Lucky Spin tidak dikenali.'
        ];
    }

    private function build_reward_message(array $reward) {
        $reward_type = trim((string) ($reward['reward_type'] ?? 'zonk'));
        $label = trim((string) ($reward['label'] ?? ''));

        if ($reward_type === 'zonk') {
            if ($label === '' || strtolower($label) === 'zonk') {
                return 'Yah, kali ini kamu belum beruntung.';
            }
            return 'Hasil spin kamu: ' . $label;
        }

        if ($reward_type === 'bucks') {
            $amount = max(0, (int) ($reward['bucks_amount'] ?? 0));
            return 'Kamu mendapatkan ' . number_format($amount, 0, ',', '.') . ' Bucks!';
        }

        if ($reward_type === 'product') {
            return 'Kamu mendapatkan hadiah: ' . ($label !== '' ? $label : 'Product Reward') . '!';
        }

        return 'Spin berhasil diproses.';
    }
}
