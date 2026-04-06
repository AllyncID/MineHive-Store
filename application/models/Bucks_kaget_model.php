<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bucks_kaget_model extends CI_Model {

    private $table_campaigns = 'bucks_kaget_campaigns';
    private $table_claims = 'bucks_kaget_claims';

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
                `total_bucks` INT UNSIGNED NOT NULL,
                `total_recipients` INT UNSIGNED NOT NULL,
                `expires_at` DATETIME NULL DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `bucks_kaget_token_unique` (`token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$this->table_claims}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `campaign_id` INT UNSIGNED NOT NULL,
                `amount` INT UNSIGNED NOT NULL,
                `claimed_by_uuid` VARCHAR(64) NULL DEFAULT NULL,
                `claimed_by_username` VARCHAR(32) NULL DEFAULT NULL,
                `claimed_platform` VARCHAR(20) NULL DEFAULT NULL,
                `claimed_ip` VARCHAR(45) NULL DEFAULT NULL,
                `claimed_at` DATETIME NULL DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `bucks_kaget_campaign_idx` (`campaign_id`),
                KEY `bucks_kaget_claimed_uuid_idx` (`claimed_by_uuid`)
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

    public function generate_random_allocations($total_bucks, $total_recipients) {
        $total_bucks = (int) $total_bucks;
        $total_recipients = (int) $total_recipients;

        if ($total_bucks < 1 || $total_recipients < 1 || $total_bucks < $total_recipients) {
            return [];
        }

        $allocations = [];
        $remaining_bucks = $total_bucks;
        $remaining_recipients = $total_recipients;

        while ($remaining_recipients > 1) {
            $minimum_left_for_rest = $remaining_recipients - 1;
            $max_for_current = $remaining_bucks - $minimum_left_for_rest;
            $average = (int) floor($remaining_bucks / $remaining_recipients);
            $upper_bound = min($max_for_current, max(1, $average * 2));

            $amount = random_int(1, $upper_bound);
            $allocations[] = $amount;

            $remaining_bucks -= $amount;
            $remaining_recipients--;
        }

        $allocations[] = $remaining_bucks;
        shuffle($allocations);

        return $allocations;
    }

    public function create_campaign($campaign_data, array $allocations) {
        if (empty($allocations)) {
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
        foreach ($allocations as $amount) {
            $batch[] = [
                'campaign_id' => $campaign_id,
                'amount' => (int) $amount
            ];
        }

        $this->db->insert_batch($this->table_claims, $batch);

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
                COUNT(cl.id) AS total_slots,
                COALESCE(SUM(cl.amount), 0) AS total_allocated_bucks,
                COALESCE(SUM(CASE WHEN cl.claimed_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS claimed_slots,
                COALESCE(SUM(CASE WHEN cl.claimed_at IS NOT NULL THEN cl.amount ELSE 0 END), 0) AS claimed_bucks
            ", FALSE)
            ->from($this->table_campaigns . ' c')
            ->join($this->table_claims . ' cl', 'cl.campaign_id = c.id', 'left')
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
                COUNT(cl.id) AS total_slots,
                COALESCE(SUM(cl.amount), 0) AS total_allocated_bucks,
                COALESCE(SUM(CASE WHEN cl.claimed_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS claimed_slots,
                COALESCE(SUM(CASE WHEN cl.claimed_at IS NOT NULL THEN cl.amount ELSE 0 END), 0) AS claimed_bucks
            ", FALSE)
            ->from($this->table_campaigns . ' c')
            ->join($this->table_claims . ' cl', 'cl.campaign_id = c.id', 'left')
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
                COUNT(cl.id) AS total_slots,
                COALESCE(SUM(cl.amount), 0) AS total_allocated_bucks,
                COALESCE(SUM(CASE WHEN cl.claimed_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS claimed_slots,
                COALESCE(SUM(CASE WHEN cl.claimed_at IS NOT NULL THEN cl.amount ELSE 0 END), 0) AS claimed_bucks
            ", FALSE)
            ->from($this->table_campaigns . ' c')
            ->join($this->table_claims . ' cl', 'cl.campaign_id = c.id', 'left')
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

        $campaign->total_slots = (int) $campaign->total_slots;
        $campaign->total_allocated_bucks = (int) $campaign->total_allocated_bucks;
        $campaign->claimed_slots = (int) $campaign->claimed_slots;
        $campaign->claimed_bucks = (int) $campaign->claimed_bucks;
        $campaign->remaining_slots = max(0, $campaign->total_slots - $campaign->claimed_slots);
        $campaign->remaining_bucks = max(0, $campaign->total_allocated_bucks - $campaign->claimed_bucks);

        $status = 'active';
        $status_label = 'Aktif';
        $status_message = 'Masukkan nickname Minecraft kamu untuk claim bucks kaget ini.';

        if ((int) $campaign->is_active !== 1) {
            $status = 'inactive';
            $status_label = 'Ditutup';
            $status_message = 'Link Bucks Kaget ini sudah ditutup oleh admin.';
        } elseif (!empty($campaign->expires_at) && strtotime($campaign->expires_at) <= time()) {
            $status = 'expired';
            $status_label = 'Expired';
            $status_message = 'Bucks Kaget ini sudah expired.';
        } elseif ($campaign->remaining_slots < 1 || $campaign->remaining_bucks < 1) {
            $status = 'finished';
            $status_label = 'Habis';
            $status_message = 'Bucks Kaget ini sudah habis diklaim.';
        }

        $campaign->status = $status;
        $campaign->status_label = $status_label;
        $campaign->status_message = $status_message;
    }

    public function get_claims_for_campaign($campaign_id) {
        return $this->db
            ->from($this->table_claims)
            ->where('campaign_id', (int) $campaign_id)
            ->order_by('claimed_at IS NULL', 'ASC', FALSE)
            ->order_by('claimed_at', 'DESC')
            ->order_by('id', 'ASC')
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
        $this->db->delete($this->table_claims, ['campaign_id' => $id]);
        $this->db->delete($this->table_campaigns, ['id' => $id]);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return true;
    }

    public function count_active_campaigns() {
        $campaigns = $this->get_all_campaigns();
        $count = 0;

        foreach ($campaigns as $campaign) {
            if ($campaign->status === 'active') {
                $count++;
            }
        }

        return $count;
    }

    public function count_total_remaining_slots() {
        $campaigns = $this->get_all_campaigns();
        $total = 0;

        foreach ($campaigns as $campaign) {
            if ($campaign->status === 'active') {
                $total += (int) $campaign->remaining_slots;
            }
        }

        return $total;
    }

    public function claim_campaign($campaign_id, $player_uuid, $player_username, $platform, $claim_ip, callable $command_executor) {
        $campaign_id = (int) $campaign_id;
        $player_uuid = trim((string) $player_uuid);
        $player_username = trim((string) $player_username);
        $platform = trim((string) $platform);
        $claim_ip = trim((string) $claim_ip);

        if ($campaign_id < 1 || $player_uuid === '' || $player_username === '') {
            return ['status' => 'error'];
        }

        $this->db->trans_begin();

        $campaign = $this->db
            ->query("SELECT * FROM `{$this->table_campaigns}` WHERE `id` = ? LIMIT 1 FOR UPDATE", [$campaign_id])
            ->row();

        if (!$campaign) {
            $this->db->trans_rollback();
            return ['status' => 'invalid'];
        }

        if ((int) $campaign->is_active !== 1) {
            $this->db->trans_commit();
            return ['status' => 'inactive'];
        }

        if (!empty($campaign->expires_at) && strtotime($campaign->expires_at) <= time()) {
            $this->db->trans_commit();
            return ['status' => 'expired'];
        }

        $existing_claim = $this->db
            ->query("
                SELECT * FROM `{$this->table_claims}`
                WHERE `campaign_id` = ? AND `claimed_by_uuid` = ?
                LIMIT 1
                FOR UPDATE
            ", [$campaign_id, $player_uuid])
            ->row();

        if ($existing_claim) {
            $this->db->trans_commit();
            return [
                'status' => 'already_claimed',
                'amount' => (int) $existing_claim->amount
            ];
        }

        $allocation = $this->db
            ->query("
                SELECT * FROM `{$this->table_claims}`
                WHERE `campaign_id` = ? AND `claimed_at` IS NULL
                ORDER BY `id` ASC
                LIMIT 1
                FOR UPDATE
            ", [$campaign_id])
            ->row();

        if (!$allocation) {
            $this->db->trans_commit();
            return ['status' => 'finished'];
        }

        $amount = (int) $allocation->amount;
        $command_ok = call_user_func($command_executor, $amount);

        if (!$command_ok) {
            $this->db->trans_rollback();
            return ['status' => 'command_failed'];
        }

        $this->db
            ->where('id', (int) $allocation->id)
            ->where('claimed_at IS NULL', null, false)
            ->update($this->table_claims, [
                'claimed_by_uuid' => $player_uuid,
                'claimed_by_username' => $player_username,
                'claimed_platform' => $platform,
                'claimed_ip' => $claim_ip !== '' ? $claim_ip : null,
                'claimed_at' => date('Y-m-d H:i:s')
            ]);

        if ($this->db->affected_rows() !== 1 || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return ['status' => 'error'];
        }

        $this->db->trans_commit();
        return [
            'status' => 'success',
            'amount' => $amount
        ];
    }
}
