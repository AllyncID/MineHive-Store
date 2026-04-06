<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }
    
public function validate_user($username, $platform) {
    $username = trim((string) $username);
    $platform = strtolower(trim((string) $platform));

    // Kita gunakan log_message('error', ...) agar pesannya pasti tercatat dan mudah dicari
    log_message('error', '--- DEBUG LOGIN BARU ---');
    log_message('error', 'Step 1: Data diterima dari controller. Username: [' . $username . '], Platform: [' . $platform . ']');

    $lookup_usernames = $this->_build_login_lookup_usernames($username, $platform);
    log_message('error', 'Step 2: Kandidat username untuk lookup: [' . implode(', ', $lookup_usernames) . ']');

    // Sambungkan ke database proxy
    $proxy_db = $this->load->database('proxy', TRUE);

    // Cek koneksi ke DB Proxy
    if (!$proxy_db || !$proxy_db->conn_id) {
        log_message('error', 'Step 4 GAGAL: Tidak bisa terhubung ke database "proxy"!');
        return false;
    }

    // Query utama ke tabel ua_accounts
    log_message('error', 'Step 4: Mencari di `ua_accounts` dengan username_lower IN [' . implode(', ', $lookup_usernames) . ']');
    $query = $proxy_db
                ->select('player_uuid, username_lower, last_name')
                ->from('ua_accounts')
                ->where_in('username_lower', $lookup_usernames)
                ->where('player_uuid IS NOT NULL', null, FALSE)
                ->where('player_uuid <>', '')
                ->limit(count($lookup_usernames))
                ->get();

    if ($query && $query->num_rows() >= 1) {
        log_message('error', 'Step 5: SUKSES ditemukan di `ua_accounts`.');

        $rows_by_username = [];
        foreach ($query->result_array() as $row) {
            $normalized_username = strtolower(trim((string) ($row['username_lower'] ?? '')));
            if ($normalized_username !== '') {
                $rows_by_username[$normalized_username] = $row;
            }
        }

        foreach ($lookup_usernames as $lookup_username) {
            if (!isset($rows_by_username[$lookup_username])) {
                continue;
            }

            $result = $rows_by_username[$lookup_username];
            return [
                'uuid' => trim((string) ($result['player_uuid'] ?? '')),
                'username' => $this->_resolve_account_username($result)
            ];
        }

        $result = $query->row_array();
        return [
            'uuid' => trim((string) ($result['player_uuid'] ?? '')),
            'username' => $this->_resolve_account_username($result)
        ];
    } else {
        log_message('error', 'Step 5: TIDAK ditemukan di `ua_accounts`. Mencoba fallback ke LuckPerms lobby...');
        
        // Fallback ke LuckPerms Lobby (jika Anda masih menggunakannya)
        $lobby_db = $this->load->database('lobby', TRUE);
        if (!$lobby_db || !$lobby_db->conn_id) {
            log_message('error', 'Step 6 GAGAL: Tidak bisa terhubung ke database "lobby"!');
            return false;
        }

        log_message('error', 'Step 6: Mencari di `luckperms_players` dengan username IN [' . implode(', ', $lookup_usernames) . ']');
        $query_lobby = $lobby_db
            ->select('uuid, username')
            ->from('luckperms_players')
            ->where_in('username', $lookup_usernames)
            ->limit(count($lookup_usernames))
            ->get();
        
        if ($query_lobby && $query_lobby->num_rows() >= 1) {
             log_message('error', 'Step 7: SUKSES ditemukan di `luckperms_players`.');

             $rows_by_username = [];
             foreach ($query_lobby->result_array() as $row) {
                 $normalized_username = strtolower(trim((string) ($row['username'] ?? '')));
                 if ($normalized_username !== '') {
                     $rows_by_username[$normalized_username] = $row;
                 }
             }

             foreach ($lookup_usernames as $lookup_username) {
                 if (isset($rows_by_username[$lookup_username])) {
                     return $rows_by_username[$lookup_username];
                 }
             }

             return $query_lobby->row_array();
        } else {
             log_message('error', 'Step 7: TIDAK ditemukan di mana pun. Login gagal.');
             return false;
        }
    }
}

public function search_usernames($keyword, $limit = 8) {
    $keyword = trim((string) $keyword);
    $keyword = preg_replace('/[^A-Za-z0-9_.]/', '', $keyword);
    $limit = max(1, min(10, (int) $limit));

    if ($keyword === '' || strlen($keyword) < 2) {
        return [];
    }

    $search_term = strtolower($keyword);
    $results = [];
    $seen = [];

    foreach ($this->_get_username_search_sources() as $source) {
        $search_db = $this->load->database($source['db_config'], TRUE);
        if (!$search_db || !$search_db->conn_id) {
            continue;
        }

        $matches = $this->_search_usernames_in_database(
            $search_db,
            $source['table'],
            $source['search_column'],
            $source['display_column'],
            $source['uuid_column'],
            $search_term,
            max(12, $limit * 3)
        );

        foreach ($matches as $match) {
            $this->_push_username_suggestion($results, $seen, $match['username'] ?? '', $match['uuid'] ?? null, $search_term);
        }
    }

    usort($results, function($left, $right) {
        if ($left['match_score'] !== $right['match_score']) {
            return $left['match_score'] - $right['match_score'];
        }

        $left_length = strlen($left['username']);
        $right_length = strlen($right['username']);
        if ($left_length !== $right_length) {
            return $left_length - $right_length;
        }

        return strcasecmp($left['username'], $right['username']);
    });

    $results = array_slice($results, 0, $limit);
    foreach ($results as &$result) {
        unset($result['match_score']);
    }
    unset($result);

    return $results;
}

private function _search_usernames_in_database($db, $table, $search_column, $display_column, $uuid_column, $search_term, $limit) {
    if (!$db || !$db->conn_id) {
        return [];
    }

    $db->db_debug = FALSE;

    if (method_exists($db, 'table_exists') && !$db->table_exists($table)) {
        return [];
    }

    $patterns = $this->_build_username_patterns($search_term);
    if (empty($patterns)) {
        return [];
    }

    $exact_terms = $this->_build_exact_username_terms($search_term);
    $where_clauses = [];
    foreach ($patterns as $_pattern) {
        $where_clauses[] = 'LOWER(' . $search_column . ') LIKE ?';
    }

    $query = $db->query(
        'SELECT ' . $uuid_column . ' AS uuid,
                COALESCE(NULLIF(' . $display_column . ", ''), " . $search_column . ') AS username
         FROM ' . $table . '
         WHERE ' . $search_column . ' IS NOT NULL
           AND ' . $search_column . " <> ''
           AND (" . implode(' OR ', $where_clauses) . ')
         ORDER BY CASE
             WHEN LOWER(' . $search_column . ') = ? THEN 0
             WHEN LOWER(' . $search_column . ') = ? THEN 1
             ELSE 2
         END,
         CHAR_LENGTH(' . $search_column . ') ASC,
         ' . $search_column . ' ASC
         LIMIT ' . (int) $limit,
        array_merge($patterns, [$exact_terms[0], $exact_terms[1]])
    );

    if (!$query) {
        return [];
    }

    return $query->result_array();
}

private function _build_username_patterns($search_term) {
    $search_term = strtolower(trim((string) $search_term));
    if ($search_term === '') {
        return [];
    }

    $patterns = [$search_term . '%'];
    if (substr($search_term, 0, 1) !== '.') {
        $patterns[] = '.' . $search_term . '%';
    }

    return array_values(array_unique($patterns));
}

private function _build_exact_username_terms($search_term) {
    $search_term = strtolower(trim((string) $search_term));
    $normalized = ltrim($search_term, '.');

    $terms = [$search_term];
    if ($normalized !== '') {
        $terms[] = $normalized;
        $terms[] = '.' . $normalized;
    }

    $terms = array_values(array_unique($terms));
    if (count($terms) === 1) {
        $terms[] = $terms[0];
    }

    return array_slice($terms, 0, 2);
}

private function _get_username_search_sources() {
    $active_group = null;
    $query_builder = null;
    $db = [];

    include APPPATH . 'config/database.php';

    $sources = [];
    $definitions = [
        ['group' => 'proxy', 'table' => 'ua_accounts', 'search_column' => 'username_lower', 'display_column' => 'last_name', 'uuid_column' => 'player_uuid'],
    ];

    foreach ($definitions as $definition) {
        $group = $definition['group'];
        if (empty($db[$group]) || !is_array($db[$group])) {
            continue;
        }

        $group_config = $db[$group];
        if (empty($group_config['hostname']) || empty($group_config['username']) || !array_key_exists('password', $group_config) || empty($group_config['database'])) {
            continue;
        }

        $definition['db_config'] = $group_config;
        $definition['db_config']['db_debug'] = FALSE;
        $definition['db_config']['save_queries'] = FALSE;
        $sources[] = $definition;
    }

    return $sources;
}

private function _build_login_lookup_usernames($username, $platform) {
    $username = strtolower(trim((string) $username));
    if ($username === '') {
        return [];
    }

    $lookup_usernames = [$username];
    $trimmed_username = ltrim($username, '.');

    if ($platform === 'bedrock') {
        if ($trimmed_username !== '') {
            $lookup_usernames = ['.' . $trimmed_username, $trimmed_username];
        }
    }

    return array_values(array_unique(array_filter($lookup_usernames, function ($value) {
        return trim((string) $value) !== '';
    })));
}

private function _resolve_account_username(array $account_row) {
    $display_username = trim((string) ($account_row['last_name'] ?? ''));
    if ($display_username !== '') {
        return $display_username;
    }

    return trim((string) ($account_row['username_lower'] ?? ''));
}

private function _push_username_suggestion(&$results, &$seen, $username, $uuid, $search_term) {
    $username = trim((string) $username);
    if ($username === '') {
        return;
    }

    $normalized_username = strtolower($username);
    if (isset($seen[$normalized_username])) {
        return;
    }

    $seen[$normalized_username] = true;
    $results[] = [
        'username' => $username,
        'uuid' => $uuid,
        'platform' => (substr($username, 0, 1) === '.') ? 'Bedrock' : 'Java',
        'match_score' => $this->_get_username_match_score($username, $search_term)
    ];
}

private function _get_username_match_score($username, $search_term) {
    $username = strtolower((string) $username);
    $search_term = strtolower((string) $search_term);
    $normalized_username = ltrim($username, '.');
    $normalized_search_term = ltrim($search_term, '.');

    if ($username === $search_term) {
        return 0;
    }

    if ($normalized_username !== '' && $normalized_username === $normalized_search_term) {
        return 1;
    }

    if (strpos($username, $search_term) === 0) {
        return 2;
    }

    if ($normalized_search_term !== '' && strpos($normalized_username, $normalized_search_term) === 0) {
        return 3;
    }

    if ($normalized_search_term !== '' && strpos($normalized_username, $normalized_search_term) !== false) {
        return 4;
    }

    return 5;
}
}
