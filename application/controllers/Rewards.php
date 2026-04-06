<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rewards extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Scratch_event_model');
    }

    /**
     * Endpoint AJAX untuk dicek oleh frontend.
     * Mengecek apakah user punya hadiah yang belum diklaim.
     */
    public function check_unclaimed() {
        header('Content-Type: application/json');

        // Wajib login
        if (!$this->session->userdata('is_logged_in')) {
            echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
            return;
        }

        $uuid = $this->session->userdata('uuid');
        $reward = $this->Scratch_event_model->get_unclaimed_reward($uuid);

        if ($reward) {
            // [PERUBAHAN] JANGAN tandai sebagai "claimed" dulu.
            // $this->Scratch_event_model->mark_reward_as_claimed($reward->id);
            
            // Kirim data hadiah ke frontend
            echo json_encode([
                'status' => 'success',
                // [PERUBAHAN] Kirim juga ID unik dari tabel user_won_rewards
                'won_reward_id' => $reward->id, 
                'reward' => [
                    'display_name' => $reward->display_name,
                    'reward_type' => $reward->reward_type,
                    'reward_value' => $reward->reward_value
                ]
            ]);
        } else {
            // Tidak ada hadiah baru
            echo json_encode(['status' => 'nothing']);
        }
    }

    /**
     * [FUNGSI BARU] Endpoint ini dipanggil oleh JS saat tombol "Tutup" diklik.
     */
    public function mark_as_claimed($won_reward_id = null) {
        header('Content-Type: application/json');
        if (!$this->session->userdata('is_logged_in')) {
            echo json_encode(['status' => 'error']);
            return;
        }

        if ($won_reward_id) {
            // Kita verifikasi sekali lagi apakah hadiah ini milik user yg login
            $uuid = $this->session->userdata('uuid');
            $reward = $this->Scratch_event_model->get_unclaimed_reward($uuid);

            if ($reward && $reward->id == $won_reward_id) {
                $this->Scratch_event_model->mark_reward_as_claimed($won_reward_id);
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        echo json_encode(['status' => 'failed']);
    }
}