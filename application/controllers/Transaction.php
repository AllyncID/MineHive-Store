<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transaction extends CI_Controller {

    protected $allow_public_preview = false;

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Transaction_model');

        $this->allow_public_preview = !$this->session->userdata('is_logged_in') && $this->_is_social_preview_request();

        if (!$this->session->userdata('is_logged_in') && !$this->allow_public_preview) {
            $this->session->set_flashdata('error', 'Anda harus login untuk melihat riwayat transaksi.');
            redirect(base_url());
        }
    }

    private function _is_social_preview_request() {
        $user_agent = strtolower(trim((string) $this->input->user_agent()));
        if ($user_agent === '') {
            return false;
        }

        $preview_agents = [
            'discordbot',
            'twitterbot',
            'facebookexternalhit',
            'facebot',
            'linkedinbot',
            'slackbot',
            'whatsapp',
            'telegrambot',
            'skypeuripreview',
            'googlebot',
            'bingbot'
        ];

        foreach ($preview_agents as $agent_fragment) {
            if (strpos($user_agent, $agent_fragment) !== false) {
                return true;
            }
        }

        return false;
    }

    private function _render_public_preview() {
        $data = [
            'title' => 'Transactions | MineHive',
            'meta_description' => 'Lihat riwayat transaksi, status pembayaran, invoice, dan link Bucks Kaget kamu langsung dari halaman transaksi MineHive.',
            'transactions' => [],
            'focused_transaction' => null,
            'focused_transaction_id' => 0,
            'payment_state' => '',
            'should_auto_open_payment' => false,
            'status_endpoint' => '',
            'current_page' => 1,
            'total_pages' => 1,
            'total_transactions' => 0,
            'completed_count' => 0,
            'pending_recent_count' => 0,
            'range_start' => 0,
            'range_end' => 0,
            'pagination_links' => []
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('transaction/index_view', $data);
        $this->load->view('templates/footer');
    }

    private function _build_status_meta($status) {
        $status = strtolower(trim((string) $status));

        if ($status === 'completed') {
            return [
                'key' => 'completed',
                'label' => 'Completed',
                'description' => 'Pembayaran sudah selesai dan item sudah diproses.'
            ];
        }

        if ($status === 'failed') {
            return [
                'key' => 'failed',
                'label' => 'Failed',
                'description' => 'Pembayaran gagal atau belum bisa diproses.'
            ];
        }

        return [
            'key' => 'pending',
            'label' => 'Pending',
            'description' => 'Menunggu pembayaran atau konfirmasi webhook Xendit.'
        ];
    }

    private function _decode_cart($raw_cart) {
        $cart = json_decode((string) $raw_cart, true);
        return is_array($cart) ? $cart : [];
    }

    private function _build_cart_signature(array $cart) {
        $items = [];

        foreach (($cart['items'] ?? []) as $item) {
            $items[] = [
                'id' => (int) ($item['id'] ?? 0),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'price' => (int) round((float) ($item['price'] ?? 0)),
                'unit_price' => (int) round((float) ($item['unit_price'] ?? ($item['price'] ?? 0))),
                'realm' => strtolower(trim((string) ($item['realm'] ?? ''))),
                'is_upgrade' => !empty($item['is_upgrade']),
                'is_bucks_kaget' => !empty($item['is_bucks_kaget']),
                'bucks_kaget_total_bucks' => (int) ($item['bucks_kaget_total_bucks'] ?? 0),
                'bucks_kaget_total_recipients' => (int) ($item['bucks_kaget_total_recipients'] ?? 0)
            ];
        }

        usort($items, static function ($left, $right) {
            $leftKey = implode(':', [
                $left['id'],
                $left['realm'],
                $left['price'],
                $left['quantity']
            ]);
            $rightKey = implode(':', [
                $right['id'],
                $right['realm'],
                $right['price'],
                $right['quantity']
            ]);

            return strcmp($leftKey, $rightKey);
        });

        return [
            'items' => $items,
            'subtotal' => (int) round((float) ($cart['subtotal'] ?? 0)),
            'grand_total' => (int) round((float) ($cart['grand_total'] ?? 0)),
            'cart_discount' => (int) round((float) ($cart['cart_discount'] ?? 0)),
            'promo_discount' => (int) round((float) ($cart['promo_discount'] ?? 0)),
            'referral_discount' => (int) round((float) ($cart['referral_discount'] ?? 0))
        ];
    }

    private function _session_cart_matches_transaction(array $session_cart, array $transaction) {
        if (empty($session_cart['items'])) {
            return false;
        }

        $transaction_cart = $this->_decode_cart($transaction['cart_data'] ?? '');
        if (empty($transaction_cart['items'])) {
            return false;
        }

        return $this->_build_cart_signature($session_cart) === $this->_build_cart_signature($transaction_cart);
    }

    private function _clear_stale_session_cart(array $rows) {
        $session_cart = $this->session->userdata('cart');
        if (!is_array($session_cart) || empty($session_cart['items'])) {
            return;
        }

        foreach ($rows as $row) {
            $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
            $should_clear = ($status === 'completed') || $this->_can_pay_transaction($row);

            if (!$should_clear) {
                continue;
            }

            if ($this->_session_cart_matches_transaction($session_cart, $row)) {
                $this->session->unset_userdata('cart');
                return;
            }
        }
    }

    private function _extract_item_labels(array $cart, $fallback_items = '') {
        $labels = [];

        foreach (($cart['items'] ?? []) as $item) {
            $name = trim(str_replace(' (Upgrade)', '', (string) ($item['name'] ?? '')));
            if ($name === '') {
                continue;
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $labels[] = ($quantity > 1 ? $quantity . 'x ' : '') . $name;
        }

        if (!empty($labels)) {
            return $labels;
        }

        $fallback_items = trim((string) $fallback_items);
        if ($fallback_items === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $fallback_items))));
    }

    private function _can_pay_transaction(array $transaction) {
        $status = strtolower(trim((string) ($transaction['status'] ?? 'pending')));
        $payment_method = strtolower(trim((string) ($transaction['payment_method'] ?? '')));
        $created_at = strtotime((string) ($transaction['created_at'] ?? ''));
        $cart = $this->_decode_cart($transaction['cart_data'] ?? '');
        $checkout_meta = is_array($cart['checkout_meta'] ?? null) ? $cart['checkout_meta'] : [];
        $invoice_url = trim((string) ($checkout_meta['invoice_url'] ?? ''));

        if ($status !== 'pending') {
            return false;
        }

        if ($payment_method !== 'xendit') {
            return false;
        }

        if ($invoice_url === '') {
            return false;
        }

        if ($created_at <= 0) {
            return false;
        }

        return $created_at >= (time() - 86400);
    }

    private function _format_transaction(array $transaction) {
        $cart = $this->_decode_cart($transaction['cart_data'] ?? '');
        $status_meta = $this->_build_status_meta($transaction['status'] ?? 'pending');

        $bucks_kaget_result = is_array($cart['bucks_kaget_result'] ?? null) ? $cart['bucks_kaget_result'] : null;
        $bucks_kaget_form = is_array($cart['bucks_kaget_form'] ?? null) ? $cart['bucks_kaget_form'] : null;
        $checkout_meta = is_array($cart['checkout_meta'] ?? null) ? $cart['checkout_meta'] : [];

        return [
            'id' => (int) ($transaction['id'] ?? 0),
            'status_key' => $status_meta['key'],
            'status_label' => $status_meta['label'],
            'status_description' => $status_meta['description'],
            'created_at_display' => !empty($transaction['created_at']) ? date('d M Y, H:i', strtotime($transaction['created_at'])) : '-',
            'grand_total_display' => 'Rp ' . number_format((float) ($transaction['grand_total'] ?? 0), 0, ',', '.'),
            'items' => $this->_extract_item_labels($cart, $transaction['purchased_items'] ?? ''),
            'gift_recipient_username' => trim((string) ($transaction['gift_recipient_username'] ?? '')),
            'is_gift' => !empty($transaction['is_gift']),
            'promo_code' => trim((string) ($transaction['promo_code_used'] ?? '')),
            'invoice_id' => trim((string) ($checkout_meta['invoice_id'] ?? '')),
            'invoice_url' => trim((string) ($checkout_meta['invoice_url'] ?? '')),
            'can_pay' => $this->_can_pay_transaction($transaction),
            'has_bucks_kaget' => ($bucks_kaget_result !== null || $bucks_kaget_form !== null),
            'bucks_kaget' => [
                'ready' => !empty($bucks_kaget_result['url']),
                'name' => (string) ($bucks_kaget_result['name'] ?? ($bucks_kaget_form['name'] ?? 'Bucks Kaget')),
                'url' => (string) ($bucks_kaget_result['url'] ?? ''),
                'total_bucks' => (int) ($bucks_kaget_result['total_bucks'] ?? ($bucks_kaget_form['total_bucks'] ?? 0)),
                'total_recipients' => (int) ($bucks_kaget_result['total_recipients'] ?? ($bucks_kaget_form['total_recipients'] ?? 0)),
                'expires_at' => (string) ($bucks_kaget_result['expires_at'] ?? ($bucks_kaget_form['expires_at'] ?? ''))
            ]
        ];
    }

    private function _build_page_url($page, $focused_transaction_id = 0) {
        $query = ['page' => max(1, (int) $page)];
        if ($focused_transaction_id > 0) {
            $query['trx'] = (int) $focused_transaction_id;
        }

        return base_url('transaction?' . http_build_query($query));
    }

    private function _build_pagination($current_page, $total_pages, $focused_transaction_id = 0) {
        if ($total_pages <= 1) {
            return [];
        }

        $window = 2;
        $start = max(1, $current_page - $window);
        $end = min($total_pages, $current_page + $window);
        $links = [];

        if ($current_page > 1) {
            $links[] = [
                'label' => 'Prev',
                'url' => $this->_build_page_url($current_page - 1, $focused_transaction_id),
                'active' => false
            ];
        }

        for ($page = $start; $page <= $end; $page++) {
            $links[] = [
                'label' => (string) $page,
                'url' => $this->_build_page_url($page, $focused_transaction_id),
                'active' => ($page === $current_page)
            ];
        }

        if ($current_page < $total_pages) {
            $links[] = [
                'label' => 'Next',
                'url' => $this->_build_page_url($current_page + 1, $focused_transaction_id),
                'active' => false
            ];
        }

        return $links;
    }

    public function index() {
        if (!$this->session->userdata('is_logged_in')) {
            $this->_render_public_preview();
            return;
        }

        $player_uuid = trim((string) $this->session->userdata('uuid'));
        $player_username = trim((string) $this->session->userdata('username'));
        $requested_transaction_id = max(0, (int) $this->input->get('trx'));
        $focused_transaction_id = $requested_transaction_id;
        $per_page = 10;
        $current_page = max(1, (int) $this->input->get('page'));
        $payment_state = strtolower(trim((string) $this->input->get('payment')));
        $should_auto_open_payment = ((string) $this->input->get('open') === '1');

        $total_transactions = $this->Transaction_model->count_history_transactions_for_player($player_uuid, $player_username, 24);
        $completed_count = $this->Transaction_model->count_completed_transactions_for_player($player_uuid, $player_username);
        $total_pages = max(1, (int) ceil($total_transactions / $per_page));
        $current_page = min($current_page, $total_pages);
        $offset = ($current_page - 1) * $per_page;

        $stale_rows = $this->Transaction_model->get_history_transactions_for_player($player_uuid, $player_username, 24, 50, 0);
        $this->_clear_stale_session_cart($stale_rows);

        $rows = $this->Transaction_model->get_history_transactions_for_player($player_uuid, $player_username, 24, $per_page, $offset);
        $transactions = array_map([$this, '_format_transaction'], $rows);
        $pending_recent_count = max(0, $total_transactions - $completed_count);

        $focused_transaction = null;
        if ($focused_transaction_id > 0) {
            $focused_row = $this->Transaction_model->get_transaction_by_id_for_player($focused_transaction_id, $player_uuid, $player_username);
            if ($focused_row) {
                $focused_transaction = $this->_format_transaction($focused_row);
            }
        }

        if ($focused_transaction === null && !empty($transactions)) {
            $focused_transaction = $transactions[0];
            $focused_transaction_id = (int) $focused_transaction['id'];
        }

        if ($focused_transaction === null) {
            $latest_row = $this->Transaction_model->get_latest_transaction_for_player($player_uuid, $player_username);
            if ($latest_row) {
                $focused_transaction = $this->_format_transaction($latest_row);
                $focused_transaction_id = (int) $focused_transaction['id'];
            }
        }

        $range_start = $total_transactions > 0 ? ($offset + 1) : 0;
        $range_end = $total_transactions > 0 ? min($total_transactions, $offset + count($transactions)) : 0;
        $pagination_links = $this->_build_pagination($current_page, $total_pages, $requested_transaction_id);

        $data['title'] = 'Transactions | MineHive';
        $data['meta_description'] = 'Lihat riwayat transaksi, status pembayaran, invoice, dan link Bucks Kaget kamu langsung dari halaman transaksi MineHive.';
        $data['transactions'] = $transactions;
        $data['focused_transaction'] = $focused_transaction;
        $data['focused_transaction_id'] = $focused_transaction_id;
        $data['payment_state'] = $payment_state;
        $data['should_auto_open_payment'] = ($should_auto_open_payment && !empty($focused_transaction['can_pay']));
        $data['status_endpoint'] = !empty($focused_transaction['id']) ? base_url('payment/check_status/' . (int) $focused_transaction['id']) : '';
        $data['current_page'] = $current_page;
        $data['total_pages'] = $total_pages;
        $data['total_transactions'] = $total_transactions;
        $data['completed_count'] = $completed_count;
        $data['pending_recent_count'] = $pending_recent_count;
        $data['range_start'] = $range_start;
        $data['range_end'] = $range_end;
        $data['pagination_links'] = $pagination_links;

        $this->load->view('templates/header', $data);
        $this->load->view('transaction/index_view', $data);
        $this->load->view('templates/footer');
    }
}
