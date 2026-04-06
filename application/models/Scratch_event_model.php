<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Scratch_event_model extends CI_Model {
    
    private $table_tiers = 'scratch_event_tiers';
    private $table_rewards = 'scratch_tier_rewards';
    private $table_bank = 'reward_bank';
    private $table_won = 'user_won_rewards';

    public function get_all_tiers_with_rewards() {
        $tiers = $this->db->order_by('min_amount', 'ASC')->get($this->table_tiers)->result();
        
        foreach ($tiers as $tier) {
            $this->db->select('b.display_name, b.reward_type');
            $this->db->from($this->table_rewards . ' as tr');
            $this->db->join($this->table_bank . ' as b', 'tr.reward_id = b.id');
            $this->db->where('tr.tier_id', $tier->id);
            $tier->rewards = $this->db->get()->result();
        }
        return $tiers;
    }

    public function get_tier_by_id($id) {
        return $this->db->get_where($this->table_tiers, ['id' => $id])->row();
    }

    public function get_reward_ids_for_tier($tier_id) {
        $rewards = $this->db->select('reward_id')->where('tier_id', $tier_id)->get($this->table_rewards)->result_array();
        return array_column($rewards, 'reward_id'); // Return array of IDs
    }

    public function add_tier($tier_data, $reward_ids) {
        $this->db->trans_start();
        
        $this->db->insert($this->table_tiers, $tier_data);
        $tier_id = $this->db->insert_id();

        if (!empty($reward_ids)) {
            $batch_data = [];
            foreach ($reward_ids as $reward_id) {
                $batch_data[] = [
                    'tier_id' => $tier_id,
                    'reward_id' => $reward_id
                ];
            }
            $this->db->insert_batch($this->table_rewards, $batch_data);
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function update_tier($id, $tier_data, $reward_ids) {
        $this->db->trans_start();

        // 1. Update data tier
        $this->db->update($this->table_tiers, $tier_data, ['id' => $id]);

        // 2. Hapus link hadiah lama
        $this->db->delete($this->table_rewards, ['tier_id' => $id]);

        // 3. Masukkan link hadiah baru
        if (!empty($reward_ids)) {
            $batch_data = [];
            foreach ($reward_ids as $reward_id) {
                $batch_data[] = [
                    'tier_id' => $id,
                    'reward_id' => $reward_id
                ];
            }
            $this->db->insert_batch($this->table_rewards, $batch_data);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function delete_tier($id) {
        // Hapus tier (ON DELETE CASCADE akan otomatis hapus relasi di scratch_tier_rewards)
        return $this->db->delete($this->table_tiers, ['id' => $id]);
    }

    // --- LOGIKA INTI WEBHOOK ---

    /**
     * Mencari tier yang berlaku untuk sejumlah total belanja.
     * @param float $amount Total belanja (grand_total).
     * @return object|null Data tier yang cocok.
     */
    public function get_applicable_tier($amount) {
        $this->db->where('is_active', 1);
        $this->db->where('min_amount <=', $amount);
        // Cek max_amount atau jika max_amount adalah NULL (untuk tier teratas)
        $this->db->where('(max_amount >= ' . $this->db->escape($amount) . ' OR max_amount IS NULL)');
        $this->db->order_by('min_amount', 'DESC'); // Ambil tier tertinggi yang memenuhi syarat
        $this->db->limit(1);
        return $this->db->get($this->table_tiers)->row();
    }

    /**
     * Mengambil satu hadiah acak dari pool hadiah sebuah tier.
     * @param int $tier_id ID tier.
     * @return object|null Data hadiah dari reward_bank.
     */
    public function get_random_reward_for_tier($tier_id) {
        // 1. Dapatkan semua ID hadiah yang mungkin untuk tier ini
        $reward_ids = $this->get_reward_ids_for_tier($tier_id);
        if (empty($reward_ids)) {
            return null; // Tidak ada hadiah di pool
        }

        // 2. Pilih satu ID secara acak
        $random_reward_id = $reward_ids[array_rand($reward_ids)];

        // 3. Ambil data lengkap hadiah dari bank
        return $this->db->get_where($this->table_bank, ['id' => $random_reward_id])->row();
    }

    /**
     * Mencatat hadiah yang dimenangkan oleh user.
     * @param array $data Data (transaction_id, player_uuid, reward_bank_id).
     * @return int ID data yang baru di-insert.
     */
    public function log_won_reward($data) {
        $this->db->insert($this->table_won, $data);
        return $this->db->insert_id();
    }

    // --- LOGIKA FRONTEND ---

    /**
     * Mengecek hadiah yang belum diklaim (dilihat) oleh user.
     * @param string $player_uuid UUID pemain.
     * @return object|null Data hadiah yang digabung.
     */
    public function get_unclaimed_reward($player_uuid) {
        $this->db->select('uw.*, rb.display_name, rb.reward_type, rb.reward_value');
        $this->db->from($this->table_won . ' as uw');
        $this->db->join($this->table_bank . ' as rb', 'uw.reward_bank_id = rb.id');
        $this->db->where('uw.player_uuid', $player_uuid);
        $this->db->where('uw.is_claimed', 0);
        $this->db->order_by('uw.created_at', 'ASC'); // Ambil yang paling lama dulu
        $this->db->limit(1);
        return $this->db->get()->row();
    }

    /**
     * Menandai hadiah sebagai sudah diklaim (dilihat).
     * @param int $won_reward_id ID dari tabel user_won_rewards.
     */
    public function mark_reward_as_claimed($won_reward_id) {
        $this->db->update($this->table_won, ['is_claimed' => 1], ['id' => $won_reward_id]);
    }
}