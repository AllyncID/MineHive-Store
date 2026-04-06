<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Withdrawals extends Affiliate_Controller { // <-- Menggunakan base controller Anda, sudah benar.

    public function __construct() {
        parent::__construct();
        $this->load->model('Affiliate_model');
    }

    // Menampilkan halaman form penarikan
    public function index() {
        $affiliate_id = $this->session->userdata('affiliate_id');
        $data['affiliate'] = $this->Affiliate_model->get_affiliate_by_id($affiliate_id);
        $data['payout_methods'] = $this->Affiliate_model->get_payout_methods($affiliate_id);
        
        // Pengecekan ini sangat bagus!
        if(empty($data['payout_methods'])) {
            $this->session->set_flashdata('info', 'Anda harus menambahkan metode pembayaran terlebih dahulu sebelum bisa menarik dana.');
            redirect('affiliate/settings');
        }

        $data['title'] = 'Tarik Dana | Mine Hive';
        // Pastikan nama view Anda 'withdraw_view.php' atau sesuaikan
        $this->load->view('affiliate/withdraw_view', $data);
    }

    public function submit_request() {
        $affiliate_id = $this->session->userdata('affiliate_id');

        // ==========================================================
        // === VALIDASI BARU: Cek batasan penarikan 1x24 jam      ===
        // ==========================================================
        if ($this->Affiliate_model->has_withdrawn_today($affiliate_id)) {
            $this->session->set_flashdata('error', 'Anda hanya bisa melakukan permintaan penarikan dana satu kali setiap 24 jam.');
            redirect('affiliate/dashboard'); // Langsung arahkan ke dashboard
            return; // Hentikan eksekusi
        }
        // ==========================================================

        $amount = (float) $this->input->post('amount');
        $payout_method_id = $this->input->post('payout_method_id');
        
        $affiliate = $this->Affiliate_model->get_affiliate_by_id($affiliate_id);

        // Validasi lain yang sudah ada (tidak berubah)
        if ($amount < 20000) {
            $this->session->set_flashdata('error', 'Jumlah penarikan minimal adalah Rp 20.000.');
            redirect('affiliate/withdrawals'); return;
        }
        if ($amount > $affiliate->wallet_balance) {
            $this->session->set_flashdata('error', 'Saldo Anda tidak mencukupi untuk melakukan penarikan ini.');
            redirect('affiliate/withdrawals'); return;
        }
        if (empty($payout_method_id)) {
            $this->session->set_flashdata('error', 'Anda harus memilih metode pembayaran.');
            redirect('affiliate/withdrawals'); return;
        }

        // Mulai proses database (tidak berubah)
        $this->db->trans_start();
        
        // 1. Kurangi Saldo
        $this->Affiliate_model->deduct_from_wallet($affiliate_id, $amount);
        
        // 2. Catat Permintaan Penarikan
        $this->Affiliate_model->request_withdrawal([
            'affiliate_id' => $affiliate_id,
            'amount' => $amount,
            'payout_method_id' => $payout_method_id,
            'status' => 'pending'
        ]);

        // 3. Catat Aktivitas Penarikan
        $method = $this->db->get_where('affiliate_payout_methods', ['id' => $payout_method_id])->row();
        $this->Affiliate_model->log_activity([
            'affiliate_id' => $affiliate_id,
            'type'         => 'withdrawal',
            'amount'       => -$amount,
            'description'  => 'Penarikan dana ke ' . ($method ? $method->method_type : 'N/A')
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Terjadi kesalahan database. Coba lagi.');
        } else {
            $this->_send_discord_notification($affiliate->minecraft_username, $amount, $payout_method_id);
            $this->session->set_flashdata('success', 'Permintaan penarikan Anda telah terkirim dan akan diproses dalam 1x24 jam.');
        }

        redirect('affiliate/dashboard');
    }

    // Fungsi notifikasi Discord (Sudah Benar)
    private function _send_discord_notification($username, $amount, $payout_method_id) {
        $webhook_url = $this->config->item('discord_webhook_url_affiliate');
        if(empty($webhook_url)) return;

        $method_details_raw = $this->db->get_where('affiliate_payout_methods', ['id' => $payout_method_id])->row();
        $method_details = $method_details_raw ? ($method_details_raw->method_type . ' - ' . $method_details_raw->account_details) : 'Metode tidak ditemukan';
        
        $embed = [[
            'title' => '🔔 Permintaan Penarikan Dana Baru!',
            'color' => 16776960, // Gold
            'fields' => [
                ['name' => 'Afiliasi', 'value' => $username, 'inline' => true],
                ['name' => 'Jumlah Penarikan', 'value' => 'Rp ' . number_format($amount, 0, ',', '.'), 'inline' => true],
                ['name' => 'Metode Pembayaran', 'value' => "```" . $method_details . "```", 'inline' => false],
            ],
            'footer' => ['text' => 'Segera proses permintaan ini di Panel Admin.'],
            'timestamp' => date('c')
        ]];
        $json_data = json_encode(['embeds' => $embed]);
        
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }
}