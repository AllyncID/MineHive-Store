<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load model yang sudah ada dari project kamu
        $this->load->model('Admin_model');
        $this->load->model('Product_model');
        $this->load->model('Transaction_model');
        
        // Mengizinkan akses CORS jika nanti dibutuhkan (opsional)
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    }

    // 1. Endpoint Login Admin
    public function login() {
        // Ambil input JSON dari Flutter
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $username = isset($data['username']) ? $data['username'] : null;
        $password = isset($data['password']) ? $data['password'] : null;

        if (!$username || !$password) {
            echo json_encode(['status' => false, 'message' => 'Username dan password wajib diisi']);
            return;
        }

        // Gunakan logic yang sama dengan Admin_model kamu
        $admin = $this->Admin_model->get_by_username($username);

        if ($admin && password_verify($password, $admin->password)) {
            // Login sukses
            echo json_encode([
                'status' => true,
                'message' => 'Login Berhasil',
                'data' => [
                    'admin_id' => $admin->id,
                    'username' => $admin->username
                ]
            ]);
        } else {
            echo json_encode(['status' => false, 'message' => 'Username atau password salah']);
        }
    }

    // 2. Endpoint Dashboard Stats (Data Utama)
    public function dashboard_stats() {
        // Pada aplikasi nyata, kamu harus verifikasi Token/Session di sini untuk keamanan.
        // Untuk demo, kita langsung ambil datanya.

        // Gunakan filter default 30 hari seperti di controller Dashboard.php kamu
        $days = 30; 

        // Menggunakan model yang sudah kamu punya untuk ambil data real
        $total_products = $this->Admin_model->count_total_products();
        $total_transactions = $this->Admin_model->count_total_transactions($days);
        $total_revenue = $this->Admin_model->calculate_total_revenue($days);
        
        // Ambil transaksi terbaru
        $recent_transactions = $this->Transaction_model->get_recent_transactions(5);

        // Format data transaksi agar bersih saat dikirim JSON
        $formatted_transactions = [];
        foreach($recent_transactions as $trx) {
            $formatted_transactions[] = [
                'username' => $trx->player_username,
                'amount' => 'Rp ' . number_format($trx->grand_total, 0, ',', '.'),
                'items' => $trx->purchased_items,
                'date' => date('d M Y, H:i', strtotime($trx->created_at)),
                'status' => $trx->status
            ];
        }

        echo json_encode([
            'status' => true,
            'data' => [
                'total_products' => $total_products,
                'total_transactions' => $total_transactions,
                'total_revenue' => 'Rp ' . number_format($total_revenue, 0, ',', '.'),
                'recent_transactions' => $formatted_transactions
            ]
        ]);
    }
}