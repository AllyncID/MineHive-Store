<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cart_discount_model extends CI_Model {
    private $table = 'cart_discount_tiers';
    private $settings_key_enabled = 'cart_discount_enabled';

    public function __construct() {
        parent::__construct();
        // Load model Settings_model karena kita butuh fungsi get/update setting
        $this->load->model('Settings_model');
    }

    // --- Pengaturan Fitur ---

    /**
     * Mengecek apakah fitur diskon bertingkat aktif.
     * @return bool TRUE jika aktif, FALSE jika tidak.
     */
    public function is_enabled() {
        $settings = $this->Settings_model->get_all_settings();
        return isset($settings[$this->settings_key_enabled]) && $settings[$this->settings_key_enabled] == '1';
    }

    /**
     * Mengaktifkan atau menonaktifkan fitur diskon bertingkat.
     * @param bool $enabled TRUE untuk aktif, FALSE untuk nonaktif.
     * @return bool Status operasi update.
     */
    public function set_enabled_status($enabled) {
        $status_value = $enabled ? '1' : '0';
        return $this->Settings_model->update_batch([$this->settings_key_enabled => $status_value]);
    }

    // --- Operasi CRUD untuk Tingkatan Diskon ---

    /**
     * Mengambil semua tingkatan diskon, diurutkan dari minimum terbesar.
     * @return array Array of objects berisi data tingkatan.
     */
    public function get_all_tiers() {
        // Urutkan berdasarkan min_amount DESC agar mudah dicari yang paling tinggi nanti
        return $this->db->order_by('min_amount', 'DESC')->get($this->table)->result();
    }

    /**
     * Mengambil satu tingkatan diskon berdasarkan ID.
     * @param int $id ID tingkatan.
     * @return object|null Data tingkatan atau null jika tidak ditemukan.
     */
    public function get_tier_by_id($id) {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    /**
     * Menambahkan tingkatan diskon baru.
     * @param array $data Data tingkatan (min_amount, discount_percentage).
     * @return int ID tingkatan yang baru ditambahkan.
     */
    public function insert_tier($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Memperbarui data tingkatan diskon.
     * @param int $id ID tingkatan yang akan diupdate.
     * @param array $data Data baru.
     * @return bool Status operasi update.
     */
    public function update_tier($id, $data) {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    /**
     * Menghapus tingkatan diskon.
     * @param int $id ID tingkatan yang akan dihapus.
     * @return bool Status operasi delete.
     */
    public function delete_tier($id) {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    // --- Logika Pencarian Diskon ---

    /**
     * Mencari tingkatan diskon tertinggi yang berlaku untuk jumlah tertentu.
     * @param float $amount Jumlah total belanja (subtotal).
     * @return object|null Data tingkatan diskon yang berlaku, atau null jika tidak ada.
     */
    public function get_applicable_tier($amount) {
        if (!$this->is_enabled()) {
            return null; // Fitur tidak aktif
        }

        // Ambil SEMUA tier yang min_amount-nya <= $amount,
        // lalu urutkan dari min_amount TERBESAR (DESC).
        // Limit 1 akan mengambil tier tertinggi yang memenuhi syarat.
        $this->db->where('min_amount <=', $amount);
        $this->db->order_by('min_amount', 'DESC');
        $this->db->limit(1);
        return $this->db->get($this->table)->row();
    }

    /**
     * (FUNGSI BARU) Mencari tingkatan diskon TERDEKAT yang BELUM dicapai.
     * @param float $amount Jumlah total belanja (subtotal).
     * @return object|null Data tingkatan diskon berikutnya, atau null jika tidak ada.
     */
    public function get_next_tier($amount) {
        if (!$this->is_enabled()) {
            return null; // Fitur tidak aktif
        }

        // Amdil tier yang min_amount-nya > $amount
        // Urutkan dari min_amount TERKECIL (ASC)
        // Limit 1 akan mengambil tier terdekat yang belum dicapai
        $this->db->where('min_amount >', $amount);
        $this->db->order_by('min_amount', 'ASC');
        $this->db->limit(1);
        return $this->db->get($this->table)->row();
    }
}
