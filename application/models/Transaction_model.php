<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transaction_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function log_transaction($data) {
        $this->db->insert('transactions', $data);
        return $this->db->insert_id(); // Mengembalikan ID transaksi yang baru dibuat
    }

    /**
     * [MODIFIKASI]
     * Query ini diubah untuk mengambil 'realm' dari produk yang dibeli.
     */
    private function _get_transactions_with_items() {
        $this->db->select("
            t.*, 
            GROUP_CONCAT(DISTINCT ti.product_name SEPARATOR ', ') as purchased_items,
            MAX(p.realm) as realm 
        "); // <-- 'MAX(p.realm)' ditambahkan
        $this->db->from('transactions as t');
        $this->db->join('transaction_items as ti', 't.id = ti.transaction_id', 'left');
        $this->db->join('products as p', 'ti.product_id = p.id', 'left'); // <-- JOIN ke tabel products ditambahkan
        $this->db->group_by('t.id');
    }

    private function _normalize_uuid($uuid) {
        $uuid = strtolower(trim((string) $uuid));
        if ($uuid === '') {
            return '';
        }

        return str_replace('-', '', $uuid);
    }

    private function _apply_player_scope($player_uuid, $player_username = '') {
        $player_uuid = trim((string) $player_uuid);
        $player_username = strtolower(trim((string) $player_username));
        $normalized_uuid = $this->_normalize_uuid($player_uuid);
        $conditions = [];

        if ($player_uuid !== '') {
            $conditions[] = 't.player_uuid = ' . $this->db->escape($player_uuid);
        }

        if ($normalized_uuid !== '') {
            $conditions[] = "REPLACE(LOWER(COALESCE(t.player_uuid, '')), '-', '') = " . $this->db->escape($normalized_uuid);
        }

        if ($player_username !== '') {
            $conditions[] = "LOWER(COALESCE(t.player_username, '')) = " . $this->db->escape($player_username);
        }

        if (!empty($conditions)) {
            $this->db->where('(' . implode(' OR ', array_values(array_unique($conditions))) . ')', null, false);
            return;
        }

        $this->db->where('1 = 0', null, false);
    }

    public function get_all_transactions($days = null) {
        $this->_get_transactions_with_items(); // Fungsi private ini tetap kita gunakan
        
        if ($days) {
            // Tambahkan filter WHERE jika ada parameter hari
            $this->db->where('t.created_at >=', 'CURDATE() - INTERVAL ' . (int)$days . ' DAY', FALSE);
        }

        $this->db->order_by('t.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_recent_transactions($limit = 5) {
        $this->_get_transactions_with_items();
    
        // === TAMBAHKAN BARIS INI UNTUK MEMFILTER HANYA YANG SUDAH BAYAR ===
        $this->db->where('t.status', 'completed');
        // =================================================================
    
        $this->db->order_by('t.created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function count_transactions_for_player($player_uuid, $player_username = '') {
        $this->db->from('transactions as t');
        $this->_apply_player_scope($player_uuid, $player_username);
        return (int) $this->db->count_all_results();
    }

    public function count_completed_transactions_for_player($player_uuid, $player_username = '') {
        $this->db->from('transactions as t');
        $this->_apply_player_scope($player_uuid, $player_username);
        $this->db->where('t.status', 'completed');
        return (int) $this->db->count_all_results();
    }

    public function count_history_transactions_for_player($player_uuid, $player_username = '', $pending_hours = 24) {
        $pending_cutoff = date('Y-m-d H:i:s', time() - (max(1, (int) $pending_hours) * 3600));

        $this->db->from('transactions as t');
        $this->_apply_player_scope($player_uuid, $player_username);
        $this->db->group_start();
            $this->db->where('t.status', 'completed');
            $this->db->or_group_start();
                $this->db->where('t.status', 'pending');
                $this->db->where('t.created_at >=', $pending_cutoff);
            $this->db->group_end();
        $this->db->group_end();

        return (int) $this->db->count_all_results();
    }

    public function get_transactions_for_player($player_uuid, $player_username = '', $limit = 10, $offset = 0) {
        $this->_get_transactions_with_items();
        $this->_apply_player_scope($player_uuid, $player_username);
        $this->db->order_by('t.created_at', 'DESC');
        $this->db->limit((int) $limit, (int) $offset);
        return $this->db->get()->result_array();
    }

    public function get_completed_transactions_for_player($player_uuid, $player_username = '') {
        $this->_get_transactions_with_items();
        $this->_apply_player_scope($player_uuid, $player_username);
        $this->db->where('t.status', 'completed');
        $this->db->order_by('t.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_history_transactions_for_player($player_uuid, $player_username = '', $pending_hours = 24, $limit = null, $offset = 0) {
        $pending_cutoff = date('Y-m-d H:i:s', time() - (max(1, (int) $pending_hours) * 3600));

        $this->_get_transactions_with_items();
        $this->_apply_player_scope($player_uuid, $player_username);
        $this->db->group_start();
            $this->db->where('t.status', 'completed');
            $this->db->or_group_start();
                $this->db->where('t.status', 'pending');
                $this->db->where('t.created_at >=', $pending_cutoff);
            $this->db->group_end();
        $this->db->group_end();
        $this->db->order_by('t.created_at', 'DESC');

        if ($limit !== null) {
            $this->db->limit(max(1, (int) $limit), max(0, (int) $offset));
        }

        return $this->db->get()->result_array();
    }

    public function get_latest_transaction_for_player($player_uuid, $player_username = '') {
        $rows = $this->get_transactions_for_player($player_uuid, $player_username, 1, 0);
        return !empty($rows) ? $rows[0] : null;
    }

    public function get_transaction_by_id_for_player($id, $player_uuid, $player_username = '') {
        $this->_get_transactions_with_items();
        $this->db->where('t.id', (int) $id);
        $this->_apply_player_scope($player_uuid, $player_username);
        return $this->db->get()->row_array();
    }

    public function log_transaction_item($data) {
        // Cukup insert data yang diberikan ke tabel 'transaction_items'
        $this->db->insert('transaction_items', $data);
    }

    public function get_transaction_by_id($id) {
        return $this->db->get_where('transactions', ['id' => $id])->row_array();
    }

    // (PERUBAHAN DI SINI)
    // Tambahkan parameter $commission_amount
    public function update_transaction_status($id, $status, $commission_amount = null) {
        $data = ['status' => $status];
        
        // Jika $commission_amount diberikan, tambahkan ke data update
        if ($commission_amount !== null) {
            $data['affiliate_commission_amount'] = $commission_amount;
        }
        
        $this->db->where('id', $id);
        $this->db->update('transactions', $data);
    }

    public function update_cart_data($id, $cart_data) {
        if (is_array($cart_data)) {
            $cart_data = json_encode($cart_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $this->db->where('id', $id);
        $this->db->update('transactions', [
            'cart_data' => (string) $cart_data
        ]);
    }

    /**
     * [MODIFIKASI] Fungsi baru untuk menghitung jumlah transaksi SELESAI
     * yang pernah dilakukan oleh seorang pemain.
     * @param string $player_uuid UUID pemain
     * @return int Jumlah transaksi
     */
    public function get_completed_transaction_count($player_uuid) {
        $normalized_uuid = $this->_normalize_uuid($player_uuid);
        if ($normalized_uuid === '') {
            return 0;
        }

        $this->db->where("REPLACE(LOWER(COALESCE(player_uuid, '')), '-', '') = " . $this->db->escape($normalized_uuid), null, false);
        $this->db->where('status', 'completed');
        return $this->db->count_all_results('transactions');
    }

    /**
     * [BARU] Mengambil daftar pelanggan dengan total belanja tertinggi/terendah
     * @param string $order_by 'ASC' atau 'DESC'
     * @param string $period 'all' atau 'month'
     */
    public function get_top_spenders($order_by = 'DESC', $period = 'all') {
        $this->db->select('player_username, player_uuid, SUM(grand_total) as total_spent, COUNT(id) as total_transactions, MAX(created_at) as last_transaction');
        $this->db->from('transactions');
        $this->db->where('status', 'completed');

        if ($period == 'month') {
            $this->db->where('MONTH(created_at)', date('m'));
            $this->db->where('YEAR(created_at)', date('Y'));
        }

        $this->db->group_by('player_username'); // Group berdasarkan username agar total dihitung per user
        
        // Sorting logic
        if ($order_by == 'ASC') {
            $this->db->order_by('total_spent', 'ASC');
        } else {
            $this->db->order_by('total_spent', 'DESC');
        }

        return $this->db->get()->result();
    }
}
