<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pterodactyl_service {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    public function send_proxy_command($command) {
        $server_id = $this->CI->config->item('pterodactyl_server_id_proxy');
        return $this->send_command($command, $server_id);
    }

    public function send_command($command, $server_id) {
        $command = trim((string) $command);
        $server_id = trim((string) $server_id);
        $panel_url = $this->CI->config->item('pterodactyl_panel_url');
        $api_key = $this->CI->config->item('pterodactyl_api_key');

        if ($command === '' || $server_id === '' || empty($panel_url) || empty($api_key)) {
            log_message('error', '[PterodactylService] Konfigurasi command tidak lengkap.');
            return false;
        }

        $api_url = rtrim($panel_url, '/') . '/api/client/servers/' . $server_id . '/command';
        $payload = json_encode(['command' => $command]);

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
            'Accept: Application/vnd.pterodactyl.v1+json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code !== 204) {
            log_message('error', '[PterodactylService] Gagal kirim command: ' . $command . ' | Server: ' . $server_id . ' | HTTP: ' . $http_code . ' | CURL: ' . $curl_error . ' | Response: ' . $response);
            return false;
        }

        return true;
    }
}
