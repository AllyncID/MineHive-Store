<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lucky_spin_model extends CI_Model {

    private $table_campaigns = 'lucky_spin_campaigns';
    private $table_rewards = 'lucky_spin_rewards';
    private $table_entries = 'lucky_spin_entries';

    public function __construct() {
        parent::__construct();
        $this->ensure_tables_exist();
    }

    private function ensure_tables_exist() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$this->table_campaigns}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(120) NOT NULL,
                `token` VARCHAR(64) NOT NULL,
                `max_players` INT UNSIGNED NOT NULL DEFAULT 1,
                `max_spins_per_player` INT UNSIGNED NOT NULL DEFAULT 1,
                `expires_at` DATETIME NULL DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `lucky_spin_token_unique` (`token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$this->table_rewards}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `campaign_id` INT UNSIGNED NOT NULL,
                `label` VARCHAR(120) NOT NULL,
                `reward_type` VARCHAR(20) NOT NULL,
                `bucks_amount` INT UNSIGNED NULL DEFAULT NULL,
                `product_id` INT UNSIGNED NULL DEFAULT NULL,
                `weight` INT UNSIGNED NOT NULL DEFAULT 1,
                `stock` INT UNSIGNED NULL DEFAULT NULL,
                `won_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `lucky_spin_reward_campaign_idx` (`campaign_id`),
                KEY `lucky_spin_reward_product_idx` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$this->table_entries}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `campaign_id` INT UNSIGNED NOT NULL,
                `reward_id` INT UNSIGNED NULL DEFAULT NULL,
                `reward_type` VARCHAR(20) NOT NULL,
                `reward_label` VARCHAR(120) NOT NULL,
                `bucks_amount` INT UNSIGNED NULL DEFAULT NULL,
                `product_id` INT UNSIGNED NULL DEFAULT NULL,
                `product_name` VARCHAR(120) NULL DEFAULT NULL,
                `claimed_by_uuid` VARCHAR(64) NOT NULL,
                `claimed_by_username` VARCHAR(32) NOT NULL,
                `claimed_platform` VARCHAR(20) NULL DEFAULT NULL,
                `claimed_ip` VARCHAR(45) NULL DEFAULT NULL,
                `spun_at` DATETIME NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `lucky_spin_entry_campaign_idx` (`campaign_id`),
                KEY `lucky_spin_entry_uuid_idx` (`claimed_by_uuid`),
                KEY `lucky_spin_entry_reward_idx` (`reward_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function is_valid_token_format($token) {
        return is_string($token) && (bool) preg_match('/^[A-Za-z0-9]{16,64}$/', $token);
    }

    public function generate_unique_token($length = 24) {
        $length = max(16, (int) $length);

        do {
            $token = substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
        } while ($this->token_exists($token));

        return $token;
    }

    private function token_exists($token) {
        return $this->db->where('token', $token)->count_all_results($this->table_campaigns) > 0;
    }

    public function create_campaign(array $campaign_data, array $rewards) {
        if (empty($rewards)) {
            return false;
        }

        if (empty($campaign_data['token'])) {
            $campaign_data['token'] = $this->generate_unique_token();
        }

        $this->db->trans_begin();

        $this->db->insert($this->table_campaigns, $campaign_data);
        $campaign_id = (int) $this->db->insert_id();

        if ($campaign_id < 1) {
            $this->db->trans_rollback();
            return false;
        }

        $batch = [];
        foreach ($rewards as $index => $reward) {
            $batch[] = [
                'campaign_id' => $campaign_id,
                'label' => (string) ($reward['label'] ?? ''),
                'reward_type' => (string) ($reward['reward_type'] ?? 'zonk'),
                'bucks_amount' => isset($reward['bucks_amount']) ? (int) $reward['bucks_amount'] : null,
                'product_id' => isset($reward['product_id']) ? (int) $reward['product_id'] : null,
                'weight' => max(1, (int) ($reward['weight'] ?? 1)),
                'stock' => array_key_exists('stock', $reward) && $reward['stock'] !== null ? max(1, (int) $reward['stock']) : null,
                'won_count' => 0,
                'sort_order' => (int) ($reward['sort_order'] ?? $index)
            ];
        }

        $this->db->insert_batch($this->table_rewards, $batch);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return $campaign_id;
    }

    public function get_all_campaigns() {
        $campaigns = $this->db
            ->select("
                c.*,
                COUNT(se.id) AS total_spins_used,
                COUNT(DISTINCT se.claimed_by_uuid) AS participants_count
            ", FALSE)
            ->from($this->table_campaigns . ' c')
            ->join($this->table_entries . ' se', 'se.campaign_id = c.id', 'left')
            ->group_by('c.id')
            ->order_by('c.id', 'DESC')
            ->get()
            ->result();

        foreach ($campaigns as $campaign) {
            $this->hydrate_campaign_runtime($campaign);
        }

        return $campaigns;
    }

    public function get_campaign_by_id($id) {
        $campaign = $this->db
            ->select("
                c.*,
                COUNT(se.id) AS total_spins_used,
                COUNT(DISTINCT se.claimed_by_uuid) AS participants_count
            ", FALSE)
            ->from($this->table_campaigns . ' c')
            ->join($this->table_entries . ' se', 'se.campaign_id = c.id', 'left')
            ->where('c.id', (int) $id)
            ->group_by('c.id')
            ->get()
            ->row();

        if ($campaign) {
            $this->hydrate_campaign_runtime($campaign);
        }

        return $campaign;
    }

    public function get_campaign_by_token($token) {
        $campaign = $this->db
            ->select("
                c.*,
                COUNT(se.id) AS total_spins_used,
                COUNT(DISTINCT se.claimed_by_uuid) AS participants_count
            ", FALSE)
            ->from($this->table_campaigns . ' c')
            ->join($this->table_entries . ' se', 'se.campaign_id = c.id', 'left')
            ->where('c.token', $token)
            ->group_by('c.id')
            ->get()
            ->row();

        if ($campaign) {
            $this->hydrate_campaign_runtime($campaign);
        }

        return $campaign;
    }

    private function hydrate_campaign_runtime(&$campaign) {
        if (!$campaign) {
            return;
        }

        $campaign->max_players = (int) $campaign->max_players;
        $campaign->max_spins_per_player = (int) $campaign->max_spins_per_player;
        $campaign->total_spins_used = (int) $campaign->total_spins_used;
        $campaign->participants_count = (int) $campaign->participants_count;
        $campaign->remaining_player_slots = max(0, $campaign->max_players - $campaign->participants_count);

        $reward_summary = $this->get_reward_summary((int) $campaign->id);
        $campaign->total_rewards = (int) ($reward_summary['total_rewards'] ?? 0);
        $campaign->available_rewards = (int) ($reward_summary['available_rewards'] ?? 0);
        $campaign->remaining_reward_stock = (int) ($reward_summary['remaining_stock'] ?? 0);

        $status = 'active';
        $status_label = 'Aktif';
        $status_message = 'Masukkan nickname Minecraft kamu lalu mulai spin untuk mendapatkan hadiah random.';

        if ((int) $campaign->is_active !== 1) {
            $status = 'inactive';
            $status_label = 'Ditutup';
            $status_message = 'Lucky Spin ini sudah ditutup oleh admin.';
        } elseif (!empty($campaign->expires_at) && strtotime($campaign->expires_at) <= time()) {
            $status = 'expired';
            $status_label = 'Expired';
            $status_message = 'Lucky Spin ini sudah expired.';
        } elseif ((int) $campaign->available_rewards < 1) {
            $status = 'finished';
            $status_label = 'Habis';
            $status_message = 'Semua hadiah Lucky Spin ini sudah habis.';
        }

        $campaign->status = $status;
        $campaign->status_label = $status_label;
        $campaign->status_message = $status_message;
    }

    private function get_reward_summary($campaign_id) {
        $row = $this->db
            ->select("
                COUNT(*) AS total_rewards,
                SUM(CASE WHEN stock IS NULL OR won_count < stock THEN 1 ELSE 0 END) AS available_rewards,
                SUM(CASE WHEN stock IS NULL THEN 0 WHEN stock > won_count THEN stock - won_count ELSE 0 END) AS remaining_stock
            ", FALSE)
            ->from($this->table_rewards)
            ->where('campaign_id', (int) $campaign_id)
            ->get()
            ->row_array();

        return $row ?: [
            'total_rewards' => 0,
            'available_rewards' => 0,
            'remaining_stock' => 0
        ];
    }

    public function get_rewards_for_campaign($campaign_id) {
        return $this->db
            ->select('r.*, p.name AS product_name, p.category AS product_category, p.realm AS product_realm, p.product_type', FALSE)
            ->from($this->table_rewards . ' r')
            ->join('products p', 'p.id = r.product_id', 'left')
            ->where('r.campaign_id', (int) $campaign_id)
            ->order_by('r.sort_order', 'ASC')
            ->order_by('r.id', 'ASC')
            ->get()
            ->result();
    }

    public function get_public_rewards_for_campaign($campaign_id) {
        $rewards = $this->db
            ->select('r.id, r.label, r.reward_type, r.bucks_amount, r.product_id, r.weight, r.stock, r.won_count, p.name AS product_name', FALSE)
            ->from($this->table_rewards . ' r')
            ->join('products p', 'p.id = r.product_id', 'left')
            ->where('r.campaign_id', (int) $campaign_id)
            ->where('(r.stock IS NULL OR r.won_count < r.stock)', null, false)
            ->order_by('r.sort_order', 'ASC')
            ->order_by('r.id', 'ASC')
            ->get()
            ->result_array();

        return array_map([$this, 'format_reward_for_public'], $rewards);
    }

    private function format_reward_for_public(array $reward) {
        $reward_type = trim((string) ($reward['reward_type'] ?? 'zonk'));
        $label = trim((string) ($reward['label'] ?? ''));

        if ($label === '') {
            if ($reward_type === 'bucks') {
                $label = number_format((int) ($reward['bucks_amount'] ?? 0), 0, ',', '.') . ' Bucks';
            } elseif ($reward_type === 'product') {
                $label = trim((string) ($reward['product_name'] ?? 'Product Reward'));
            } else {
                $label = 'Zonk';
            }
        }

        $reward['label'] = $label;
        return $reward;
    }

    public function get_entries_for_campaign($campaign_id) {
        return $this->db
            ->from($this->table_entries)
            ->where('campaign_id', (int) $campaign_id)
            ->order_by('spun_at', 'DESC')
            ->order_by('id', 'DESC')
            ->get()
            ->result();
    }

    public function toggle_campaign_status($id) {
        $campaign = $this->db->get_where($this->table_campaigns, ['id' => (int) $id])->row();
        if (!$campaign) {
            return false;
        }

        $new_status = ((int) $campaign->is_active === 1) ? 0 : 1;
        return $this->db->update($this->table_campaigns, ['is_active' => $new_status], ['id' => (int) $id]);
    }

    public function delete_campaign($id) {
        $id = (int) $id;

        $this->db->trans_begin();
        $this->db->delete($this->table_entries, ['campaign_id' => $id]);
        $this->db->delete($this->table_rewards, ['campaign_id' => $id]);
        $this->db->delete($this->table_campaigns, ['id' => $id]);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return true;
    }

    public function spin_campaign($campaign_id, $player_uuid, $player_username, $platform, $claim_ip, callable $reward_executor) {
        $campaign_id = (int) $campaign_id;
        $player_uuid = trim((string) $player_uuid);
        $player_username = trim((string) $player_username);
        $platform = trim((string) $platform);
        $claim_ip = trim((string) $claim_ip);

        if ($campaign_id < 1 || $player_uuid === '' || $player_username === '') {
            return ['status' => 'error', 'message' => 'Data spin tidak valid.'];
        }

        $this->db->trans_begin();

        $campaign = $this->db
            ->query("SELECT * FROM `{$this->table_campaigns}` WHERE `id` = ? LIMIT 1 FOR UPDATE", [$campaign_id])
            ->row();

        if (!$campaign) {
            $this->db->trans_rollback();
            return ['status' => 'invalid', 'message' => 'Campaign Lucky Spin tidak ditemukan.'];
        }

        if ((int) $campaign->is_active !== 1) {
            $this->db->trans_commit();
            return ['status' => 'inactive', 'message' => 'Lucky Spin ini sudah ditutup oleh admin.'];
        }

        if (!empty($campaign->expires_at) && strtotime($campaign->expires_at) <= time()) {
            $this->db->trans_commit();
            return ['status' => 'expired', 'message' => 'Lucky Spin ini sudah expired.'];
        }

        $player_spins_used = (int) $this->db
            ->where('campaign_id', $campaign_id)
            ->where('claimed_by_uuid', $player_uuid)
            ->count_all_results($this->table_entries);

        if ($player_spins_used >= (int) $campaign->max_spins_per_player) {
            $this->db->trans_commit();
            return [
                'status' => 'spin_limit_reached',
                'message' => 'Nickname ini sudah mencapai batas spin untuk link Lucky Spin ini.',
                'spins_used' => $player_spins_used
            ];
        }

        $participants_count = (int) $this->db
            ->select('COUNT(DISTINCT claimed_by_uuid) AS total', FALSE)
            ->from($this->table_entries)
            ->where('campaign_id', $campaign_id)
            ->get()
            ->row('total');

        if ($player_spins_used < 1 && $participants_count >= (int) $campaign->max_players) {
            $this->db->trans_commit();
            return [
                'status' => 'player_limit_reached',
                'message' => 'Kuota peserta Lucky Spin ini sudah habis. Pemain pertama yang masuk sudah mengambil slot-nya.'
            ];
        }

        $available_rewards = $this->get_available_rewards_for_spin($campaign_id);
        if (empty($available_rewards)) {
            $this->db->trans_commit();
            return ['status' => 'finished', 'message' => 'Semua hadiah Lucky Spin ini sudah habis.'];
        }

        $selected_reward = $this->pick_weighted_reward($available_rewards);
        if (!$selected_reward) {
            $this->db->trans_rollback();
            return ['status' => 'error', 'message' => 'Gagal menentukan hadiah Lucky Spin.'];
        }

        $execution_result = call_user_func($reward_executor, $selected_reward);
        if (empty($execution_result['success'])) {
            $this->db->trans_rollback();
            return [
                'status' => 'reward_failed',
                'message' => $execution_result['message'] ?? 'Hadiah Lucky Spin gagal diproses.'
            ];
        }

        $this->db
            ->set('won_count', 'won_count + 1', false)
            ->where('id', (int) $selected_reward['id'])
            ->where('campaign_id', $campaign_id)
            ->where('(stock IS NULL OR won_count < stock)', null, false)
            ->update($this->table_rewards);

        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return ['status' => 'error', 'message' => 'Stok hadiah Lucky Spin berubah. Coba spin lagi.'];
        }

        $entry_data = [
            'campaign_id' => $campaign_id,
            'reward_id' => (int) $selected_reward['id'],
            'reward_type' => (string) $selected_reward['reward_type'],
            'reward_label' => (string) $selected_reward['label'],
            'bucks_amount' => isset($selected_reward['bucks_amount']) ? (int) $selected_reward['bucks_amount'] : null,
            'product_id' => isset($selected_reward['product_id']) ? (int) $selected_reward['product_id'] : null,
            'product_name' => !empty($selected_reward['product_name']) ? (string) $selected_reward['product_name'] : null,
            'claimed_by_uuid' => $player_uuid,
            'claimed_by_username' => $player_username,
            'claimed_platform' => $platform !== '' ? $platform : null,
            'claimed_ip' => $claim_ip !== '' ? $claim_ip : null,
            'spun_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert($this->table_entries, $entry_data);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return ['status' => 'error', 'message' => 'Gagal menyimpan hasil Lucky Spin.'];
        }

        $this->db->trans_commit();

        $spins_used_after = $player_spins_used + 1;
        $participants_after = $participants_count + ($player_spins_used < 1 ? 1 : 0);
        $remaining_spins_for_player = max(0, (int) $campaign->max_spins_per_player - $spins_used_after);
        $remaining_player_slots = max(0, (int) $campaign->max_players - $participants_after);

        return [
            'status' => 'success',
            'reward' => $this->format_reward_for_public($selected_reward),
            'player_username' => $player_username,
            'spins_used' => $spins_used_after,
            'remaining_spins_for_player' => $remaining_spins_for_player,
            'remaining_player_slots' => $remaining_player_slots,
            'can_spin_again' => $remaining_spins_for_player > 0
        ];
    }

    private function get_available_rewards_for_spin($campaign_id) {
        $rewards = $this->db
            ->select('r.*, p.name AS product_name', FALSE)
            ->from($this->table_rewards . ' r')
            ->join('products p', 'p.id = r.product_id', 'left')
            ->where('r.campaign_id', (int) $campaign_id)
            ->where('(r.stock IS NULL OR r.won_count < r.stock)', null, false)
            ->order_by('r.sort_order', 'ASC')
            ->order_by('r.id', 'ASC')
            ->get()
            ->result_array();

        return array_map([$this, 'format_reward_for_public'], $rewards);
    }

    private function pick_weighted_reward(array $rewards) {
        $total_weight = 0;
        foreach ($rewards as $reward) {
            $total_weight += max(1, (int) ($reward['weight'] ?? 1));
        }

        if ($total_weight < 1) {
            return null;
        }

        $roll = random_int(1, $total_weight);
        $running_weight = 0;

        foreach ($rewards as $reward) {
            $running_weight += max(1, (int) ($reward['weight'] ?? 1));
            if ($roll <= $running_weight) {
                return $reward;
            }
        }

        return end($rewards) ?: null;
    }
}
