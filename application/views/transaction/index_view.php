<?php
$focused_transaction = is_array($focused_transaction ?? null) ? $focused_transaction : null;
$transactions = is_array($transactions ?? null) ? $transactions : [];
$pagination_links = is_array($pagination_links ?? null) ? $pagination_links : [];
?>

<div class="container transaction-page">
    <div class="page-header transaction-page-header">
        <a href="<?= base_url(); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>

        <div class="transaction-page-title">
            <h1>Riwayat Transaksi</h1>
        </div>

        <div class="transaction-header-stats">
            <div class="transaction-stat-box">
                <span>History</span>
                <strong><?= number_format((int) ($total_transactions ?? 0), 0, ',', '.'); ?> transaksi</strong>
            </div>
        </div>
    </div>

    <?php if (!empty($focused_transaction)): ?>
        <section class="cart-container transaction-focus-card" id="transactionFocusCard" data-transaction-id="<?= (int) $focused_transaction['id']; ?>">
            <div class="transaction-focus-head">
                <div class="transaction-focus-title">
                    <span class="transaction-section-label">Detail Transaksi</span>
                    <div class="transaction-focus-inline">
                        <span class="transaction-status-dot status-<?= html_escape((string) $focused_transaction['status_key']); ?>" id="focusStatusDot"></span>
                        <div>
                            <h2>Transaction #<?= (int) $focused_transaction['id']; ?></h2>
                            <p id="focusStatusDescription"><?= html_escape((string) $focused_transaction['status_description']); ?></p>
                        </div>
                    </div>
                </div>

                <span class="transaction-status-badge status-<?= html_escape((string) $focused_transaction['status_key']); ?>" id="focusStatusBadge">
                    <?= html_escape((string) $focused_transaction['status_label']); ?>
                </span>
            </div>

            <?php if (($payment_state ?? '') === 'failed' && ($focused_transaction['status_key'] ?? '') === 'pending'): ?>
                <div class="transaction-note warning">
                    Redirect pembayaran bilang transaksi ini belum selesai. Kalau kamu sebenarnya sudah bayar, halaman ini tetap nunggu webhook dan akan update otomatis.
                </div>
            <?php endif; ?>

            <div class="transaction-focus-grid">
                <div class="transaction-info-card">
                    <span>Total Pembayaran</span>
                    <strong><?= html_escape((string) $focused_transaction['grand_total_display']); ?></strong>
                </div>

                <div class="transaction-info-card">
                    <span>Waktu Dibuat</span>
                    <strong><?= html_escape((string) $focused_transaction['created_at_display']); ?></strong>
                </div>

                <?php if (!empty($focused_transaction['invoice_id'])): ?>
                    <div class="transaction-info-card">
                        <span>Invoice</span>
                        <strong><?= html_escape((string) $focused_transaction['invoice_id']); ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($focused_transaction['items'])): ?>
                <div class="transaction-item-tags">
                    <?php foreach (($focused_transaction['items'] ?? []) as $item_label): ?>
                        <span class="transaction-item-tag"><?= html_escape((string) $item_label); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($focused_transaction['has_bucks_kaget'])): ?>
                <div class="transaction-info-card transaction-bk-panel" id="focusBucksKagetBox" <?= !empty($focused_transaction['bucks_kaget']['ready']) ? '' : 'data-waiting="true"'; ?>>
                    <span class="transaction-section-label">Bucks Kaget</span>
                    <strong id="focusBucksKagetName"><?= html_escape((string) $focused_transaction['bucks_kaget']['name']); ?></strong>
                    <p id="focusBucksKagetDescription">
                        <?= !empty($focused_transaction['bucks_kaget']['ready']) ? 'Link claim sudah siap. Tinggal copy lalu bagikan ke player lain.' : 'Link claim akan muncul otomatis setelah transaksi selesai diproses.'; ?>
                    </p>

                    <div class="transaction-item-tags">
                        <span class="transaction-item-tag" id="focusBucksKagetTotalBucks"><?= number_format((int) ($focused_transaction['bucks_kaget']['total_bucks'] ?? 0), 0, ',', '.'); ?> Bucks</span>
                        <span class="transaction-item-tag" id="focusBucksKagetTotalRecipients"><?= number_format((int) ($focused_transaction['bucks_kaget']['total_recipients'] ?? 0), 0, ',', '.'); ?> Player</span>
                        <span class="transaction-item-tag" id="focusBucksKagetExpiryTag" <?= !empty($focused_transaction['bucks_kaget']['expires_at']) ? '' : 'hidden'; ?>>
                            <?= !empty($focused_transaction['bucks_kaget']['expires_at']) ? 'Expired: ' . html_escape((string) ($focused_transaction['bucks_kaget']['expires_at'] ?? '')) : ''; ?>
                        </span>
                    </div>

                    <div class="transaction-focus-actions" id="focusBucksKagetCopyWrap" <?= !empty($focused_transaction['bucks_kaget']['ready']) ? '' : 'hidden'; ?>>
                        <input type="text" id="focusBucksKagetLinkInput" class="copy-link-input" value="<?= html_escape((string) ($focused_transaction['bucks_kaget']['url'] ?? '')); ?>" readonly>
                        <button type="button" class="btn btn-primary" id="focusCopyBucksKagetBtn">Copy Link</button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($focused_transaction['can_pay'])): ?>
                <div class="transaction-focus-actions" id="focusPayAction">
                    <a href="<?= html_escape((string) $focused_transaction['invoice_url']); ?>" target="_blank" rel="noopener" class="btn btn-primary" id="focusPayButton">Lanjutkan Pembayaran</a>
                </div>
            <?php endif; ?>
        </section>

        <div class="transaction-mobile-divider" aria-hidden="true"></div>
    <?php endif; ?>

    <section class="cart-container transaction-history-card<?= empty($transactions) ? ' transaction-history-empty-card' : ''; ?>">
        <?php if (empty($transactions)): ?>
            <div class="transaction-empty-state">
                <h2>BELUM ADA RIWAYAT TRANSAKSI</h2>
                <p>Transaksi completed dan pending 24 jam terakhir akan tampil di sini. Kalau kamu baru checkout, tunggu sampai invoice berhasil dibuat atau pembayaran selesai diproses dulu.</p>
            </div>
        <?php else: ?>
            <div class="transaction-section-head">
                <div>
                    <span class="transaction-section-label">History</span>
                    <h2>Riwayat Pembelian</h2>
                    <p>
                        Menampilkan <?= number_format((int) ($completed_count ?? 0), 0, ',', '.'); ?> transaksi completed
                        dan <?= number_format((int) ($pending_recent_count ?? 0), 0, ',', '.'); ?> pending dalam 24 jam terakhir.
                    </p>
                </div>
            </div>

            <div class="transaction-history-list">
                <?php foreach ($transactions as $transaction): ?>
                    <article class="transaction-history-row transaction-activity-item <?= ((int) ($focused_transaction_id ?? 0) === (int) $transaction['id']) ? 'is-active' : ''; ?>" data-transaction-id="<?= (int) $transaction['id']; ?>">
                        <div class="transaction-history-main">
                            <div class="transaction-history-top">
                                <div>
                                    <a href="<?= base_url('transaction?' . http_build_query(['page' => max(1, (int) ($current_page ?? 1)), 'trx' => (int) $transaction['id']])); ?>" class="transaction-history-id">
                                        Transaction #<?= (int) $transaction['id']; ?>
                                    </a>
                                    <p class="transaction-history-items">
                                        <?php if (!empty($transaction['items'])): ?>
                                            <?= html_escape((string) implode(', ', $transaction['items'])); ?>
                                        <?php else: ?>
                                            Detail item tidak tersedia
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <div class="transaction-history-meta">
                                <span>Total: <?= html_escape((string) $transaction['grand_total_display']); ?></span>
                                <span><?= html_escape((string) $transaction['created_at_display']); ?></span>
                                <?php if (!empty($transaction['invoice_id'])): ?>
                                    <span>Invoice: <?= html_escape((string) $transaction['invoice_id']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($transaction['gift_recipient_username'])): ?>
                                    <span>Gift ke <?= html_escape((string) $transaction['gift_recipient_username']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($transaction['promo_code'])): ?>
                                    <span>Promo <?= html_escape((string) $transaction['promo_code']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($transaction['has_bucks_kaget'])): ?>
                                    <span>Bucks Kaget<?= !empty($transaction['bucks_kaget']['ready']) ? ' Ready' : '' ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="transaction-history-actions<?= !empty($transaction['can_pay']) ? ' has-pay-action' : ' has-status-only'; ?>">
                            <span class="transaction-status-badge status-<?= html_escape((string) $transaction['status_key']); ?>" data-role="status-badge">
                                <?= html_escape((string) $transaction['status_label']); ?>
                            </span>
                            <?php if (!empty($transaction['can_pay'])): ?>
                                <a href="<?= html_escape((string) $transaction['invoice_url']); ?>" target="_blank" rel="noopener" class="btn btn-primary">Bayar</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($pagination_links)): ?>
                <nav class="transaction-pagination" aria-label="Pagination transaksi">
                    <?php foreach ($pagination_links as $link): ?>
                        <a href="<?= html_escape((string) $link['url']); ?>" class="pagination-pill <?= !empty($link['active']) ? 'active' : ''; ?>">
                            <?= html_escape((string) $link['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<style>
.transaction-page {
    padding-bottom: 70px;
    overflow-x: clip;
}

.transaction-page-header {
    display: grid;
    grid-template-columns: minmax(170px, 1fr) auto minmax(170px, 1fr);
    align-items: center;
    gap: 24px;
}

.transaction-page-title {
    justify-self: center;
    min-width: 0;
}

.transaction-page-header > .btn {
    justify-self: flex-start;
}

.transaction-page-header h1 {
    text-align: center;
}

.transaction-page-title p {
    margin: 10px 0 0;
    color: #9f9183;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    text-align: center;
}

.transaction-header-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
    justify-self: flex-end;
}

.transaction-stat-box {
    min-width: 150px;
    padding: 12px 14px;
    background-color: #291E12;
    border: 1px solid #362617;
    border-radius: 5px;
    text-align: left;
}

.transaction-stat-box span,
.transaction-section-label,
.transaction-info-card span {
    display: block;
    color: #b87841;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.transaction-stat-box strong {
    display: block;
    margin-top: 8px;
    color: rgba(255, 255, 255, 0.92);
    font-family: 'Montserrat', sans-serif;
    font-size: 0.98rem;
    font-weight: 700;
}

.transaction-focus-card,
.transaction-history-card {
    margin-top: 0;
    margin-bottom: 24px;
    min-width: 0;
}

.transaction-mobile-divider {
    display: none;
}

.transaction-page .cart-container {
    width: 100%;
    box-sizing: border-box;
}

.transaction-history-empty-card {
    display: grid;
    place-items: center;
    min-height: 180px;
    padding: 18px 28px;
}

.transaction-page .cart-container p {
    width: auto;
    margin: 0;
    padding-left: 0;
    padding-bottom: 0;
}

.transaction-focus-head,
.transaction-focus-inline,
.transaction-history-top {
    display: flex;
    gap: 14px;
}

.transaction-focus-head,
.transaction-history-top {
    justify-content: space-between;
    align-items: flex-start;
}

.transaction-focus-title {
    flex: 1;
    min-width: 0;
}

.transaction-focus-inline > div {
    flex: 1;
    min-width: 0;
}

.transaction-history-main {
    min-width: 0;
}

.transaction-focus-inline {
    margin-top: 10px;
    align-items: flex-start;
}

.transaction-focus-card h2,
.transaction-history-card h2,
.transaction-empty-state h2 {
    margin: 0;
    color: rgba(255, 255, 255, 0.92);
}

.transaction-focus-card h2,
.transaction-history-card h2,
.transaction-empty-state h2 {
    font-family: 'Rubik One', sans-serif;
    font-weight: 400;
    display: block;
}

.transaction-focus-card h2,
.transaction-history-card h2 {
    font-size: 1.55rem;
}

.transaction-focus-inline p,
.transaction-bk-panel p,
.transaction-section-head p,
.transaction-empty-state p,
.transaction-history-items {
    margin-top: 8px;
    color: #b9aa9b;
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.transaction-history-items,
#focusStatusDescription,
.transaction-bk-panel p {
    display: block;
    width: 100%;
    text-align: left;
}

.transaction-status-dot {
    width: 12px;
    min-width: 12px;
    height: 12px;
    margin-top: 12px;
    border-radius: 999px;
    background-color: #d8a94d;
}

.transaction-status-dot.status-completed {
    background-color: #3cc66b;
}

.transaction-status-dot.status-failed {
    background-color: #de5d56;
}

.transaction-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 12px;
    border: 1px solid #4f3c23;
    border-radius: 5px;
    color: #f6dfbf;
    background-color: #2b1d0e;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    white-space: nowrap;
}

.transaction-status-badge.status-completed {
    color: #90efaf;
    border-color: rgba(60, 198, 107, 0.35);
    background-color: rgba(60, 198, 107, 0.12);
}

.transaction-status-badge.status-pending {
    color: #f6dfbf;
    border-color: rgba(184, 120, 65, 0.4);
    background-color: rgba(184, 120, 65, 0.12);
}

.transaction-status-badge.status-failed {
    color: #ffb5b0;
    border-color: rgba(222, 93, 86, 0.35);
    background-color: rgba(222, 93, 86, 0.12);
}

.transaction-note,
.transaction-progress,
.transaction-info-card,
.transaction-history-row {
    background-color: #1B160D;
    border: 1px solid #2f2417;
    border-radius: 5px;
}

.transaction-note {
    margin-top: 20px;
    padding: 14px 16px;
    color: #f0d7a7;
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    line-height: 1.7;
}

.transaction-note.warning {
    border-color: rgba(184, 120, 65, 0.45);
    background-color: rgba(184, 120, 65, 0.08);
}

.transaction-focus-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-top: 22px;
}

.transaction-info-card {
    padding: 16px 18px;
}

.transaction-info-card strong {
    display: block;
    margin-top: 10px;
    color: rgba(255, 255, 255, 0.92);
    font-family: 'Montserrat', sans-serif;
    font-size: 0.98rem;
    font-weight: 700;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.transaction-item-tags,
.transaction-history-meta,
.transaction-focus-actions,
.transaction-pagination {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.transaction-item-tags {
    margin-top: 16px;
}

.transaction-history-meta {
    margin-top: 5px;
}

.transaction-item-tag,
.transaction-history-meta span,
.pagination-pill {
    display: inline-flex;
    align-items: center;
    padding: 8px 10px;
    background-color: #24190f;
    border: 1px solid #332416;
    border-radius: 5px;
    color: #d7c1aa;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    max-width: 100%;
    overflow-wrap: anywhere;
}

.transaction-progress {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
    padding: 14px 16px;
    color: #e6d6c3;
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
}

.spinner-ring {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(184, 120, 65, 0.2);
    border-top-color: #b87841;
    border-radius: 999px;
    animation: transaction-spin 0.9s linear infinite;
}

.transaction-bk-panel {
    margin-top: 20px;
}

.transaction-bk-panel > strong {
    display: block;
    margin-top: 10px;
    color: rgba(255, 255, 255, 0.92);
    font-family: 'Montserrat', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.transaction-bk-panel .transaction-item-tags,
.transaction-bk-panel .transaction-focus-actions {
    margin-top: 16px;
}

.transaction-bk-panel p {
    text-align: left;
}

.copy-link-input {
    display: block;
    flex: 1 1 260px;
    min-width: 0;
    width: 100%;
    height: 56px;
    padding: 14px 16px;
    background-color: #140f0a;
    border: 1px solid #38291a;
    border-radius: 5px;
    box-sizing: border-box;
    color: rgba(255, 255, 255, 0.92);
    font-family: 'Montserrat', sans-serif;
    font-size: 0.92rem;
    font-weight: 600;
    line-height: 1.2;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.transaction-focus-actions {
    margin-top: 20px;
    align-items: center;
}

.transaction-section-head {
    margin-bottom: 16px;
}

.transaction-section-head > div {
    width: 100%;
}

.transaction-section-head p {
    margin-top: 4px;
    text-align: left;
}

.transaction-history-list {
    display: grid;
    gap: 14px;
}

.transaction-history-row {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    padding: 18px;
    transition: border-color 0.2s ease, transform 0.2s ease;
}

.transaction-history-row:hover {
    border-color: rgba(184, 120, 65, 0.45);
    transform: translateY(-2px);
}

.transaction-activity-item.is-active {
    border-color: rgba(184, 120, 65, 0.55);
    background-color: #23180f;
}

.transaction-history-main {
    flex: 1;
    min-width: 0;
}

.transaction-history-top > div {
    flex: 1;
    min-width: 0;
}

.transaction-history-id {
    display: block;
    color: rgba(255, 255, 255, 0.94);
    font-family: 'Montserrat', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    text-decoration: none;
    overflow-wrap: anywhere;
}

.transaction-history-id:hover {
    color: #d79a63;
}

.transaction-history-items {
    margin-top: 8px;
}

.transaction-history-actions {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    min-width: 0;
    align-self: center;
}

.transaction-history-actions .btn {
    flex: 0 0 auto;
    min-width: 118px;
    padding: 12px 18px;
    text-align: center;
}

.transaction-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    justify-items: center;
    gap: 12px;
    padding: 0;
    text-align: center;
    width: 100%;
    max-width: 620px;
    margin: 0 auto;
}

.transaction-empty-state h2 {
    justify-content: center;
    font-size: 1.95rem;
    line-height: 1.12;
    text-transform: uppercase;
    max-width: 520px;
    text-wrap: balance;
}

.transaction-empty-state p {
    max-width: 500px;
    margin: 0 auto;
    color: #9f9183;
    font-size: 0.98rem;
    font-weight: 600;
    line-height: 1.75;
    text-wrap: pretty;
}

.transaction-pagination {
    justify-content: center;
    margin-top: 22px;
}

.pagination-pill {
    min-width: 42px;
    justify-content: center;
}

.pagination-pill:hover,
.pagination-pill.active {
    color: #ffffff;
    background-color: #664529;
    border-color: #7a5331;
}

@keyframes transaction-spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 992px) {
    .transaction-page-header {
        align-items: center;
    }

    .transaction-focus-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .transaction-history-row {
        flex-direction: column;
    }

    .transaction-history-actions {
        flex-direction: row;
        justify-content: flex-start;
        min-width: 0;
        align-self: stretch;
    }
}

@media (max-width: 768px) {
    .transaction-page {
        padding-bottom: 60px;
    }

    .transaction-page-header {
        grid-template-columns: 1fr;
        align-items: stretch;
        justify-items: center;
        gap: 16px;
        margin-top: 24px;
        margin-bottom: 24px;
        padding: 16px 0 18px;
    }

    .transaction-page-header > .btn {
        order: 0;
        width: auto;
        justify-content: center;
        align-self: center;
        justify-self: center;
        padding: 10px 18px;
    }

    .transaction-page-title {
        order: 1;
        width: 100%;
        justify-self: center;
    }

    .transaction-page-title,
    .transaction-page-header h1,
    .transaction-page-title p {
        text-align: center;
    }

    .transaction-header-stats {
        order: 2;
        width: 100%;
        justify-content: center;
        justify-self: center;
    }

    .transaction-stat-box {
        flex: 0 1 320px;
        min-width: min(100%, 320px);
        width: min(100%, 320px);
        text-align: center;
    }

    .transaction-stat-box span,
    .transaction-stat-box strong {
        text-align: center;
    }

    .transaction-focus-head,
    .transaction-history-top {
        flex-direction: column;
        align-items: flex-start;
    }

    .transaction-focus-head {
        flex-direction: column;
        justify-content: flex-start;
        align-items: flex-start;
        width: 100%;
        gap: 10px;
    }

    .transaction-focus-head .transaction-status-badge {
        flex: 0 0 auto;
        align-self: flex-start;
        margin-top: 0;
    }

    .transaction-focus-inline {
        width: 100%;
        gap: 10px;
    }

    .transaction-focus-inline > div,
    .transaction-focus-inline h2,
    .transaction-focus-inline p {
        text-align: left;
    }

    #focusStatusDescription {
        width: 100%;
        max-width: none;
        margin-top: 4px;
        text-align: left;
    }

    .transaction-history-top {
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
        width: 100%;
        gap: 12px;
    }

    .transaction-focus-grid {
        grid-template-columns: 1fr;
        gap: 12px;
        margin-top: 18px;
    }

    .transaction-focus-card h2,
    .transaction-history-card h2,
    .transaction-empty-state h2 {
        font-size: 1.3rem;
    }

    .transaction-page .cart-container {
        padding: 22px 16px;
    }

    .transaction-focus-card,
    .transaction-history-card {
        margin-bottom: 18px;
    }

    .transaction-info-card {
        padding: 14px 16px;
    }

    .transaction-item-tags,
    .transaction-history-meta,
    .transaction-focus-actions,
    .transaction-pagination {
        gap: 8px;
    }

    .transaction-history-row {
        gap: 14px;
        padding: 14px;
    }

    .transaction-focus-actions,
    .transaction-history-actions {
        width: 100%;
    }

    .transaction-focus-actions,
    .transaction-history-actions {
        flex-wrap: wrap;
    }

    .transaction-history-actions .btn,
    .transaction-focus-actions .btn {
        flex: 1 1 auto;
        min-height: 0;
    }

    .transaction-bk-panel .transaction-focus-actions {
        align-items: stretch;
    }

    .transaction-history-meta span {
        width: 100%;
        justify-content: flex-start;
        text-align: left;
        padding: 10px 12px;
    }
}

@media (max-width: 576px) {
    .transaction-page {
        padding-bottom: 48px;
    }

    .transaction-mobile-divider {
        display: block;
        width: 100%;
        height: 0;
        margin: -4px 0 16px;
        border-top: 1px solid #2e2013;
    }

    .transaction-page-header {
        gap: 12px;
        margin-top: 20px;
        margin-bottom: 20px;
        padding: 14px 0 16px;
    }

    .transaction-focus-card {
        padding: 20px 14px;
    }

    .transaction-history-card {
        padding: 20px 14px;
    }

    .transaction-history-empty-card {
        min-height: 0;
        padding: 20px 14px;
    }

    .transaction-empty-state {
        width: 100%;
        max-width: none;
        padding: 0;
        gap: 10px;
        margin: 0 auto;
        align-items: center;
        justify-items: center;
        transform: none;
    }

    .transaction-empty-state h2 {
        width: 100%;
        margin: 0 auto;
        text-align: center;
        font-size: 1.3rem;
        line-height: 1.16;
        max-width: none;
        text-wrap: balance;
    }

    .transaction-empty-state p {
        width: 100%;
        margin: 0 auto;
        max-width: none;
        text-align: center;
        font-size: 0.9rem;
        line-height: 1.7;
        text-wrap: pretty;
    }

    .transaction-history-row {
        gap: 10px;
        padding: 14px 12px;
    }

    .transaction-history-id {
        display: block;
        width: 100%;
        margin: 0;
        font-size: 0.98rem;
        line-height: 1.35;
        text-align: left !important;
    }

    .transaction-history-top,
    .transaction-history-top > div,
    .transaction-history-main {
        width: 100%;
    }

    .transaction-history-items {
        display: block;
        width: 100%;
        margin-left: 0;
        margin-right: 0;
        padding-left: 0 !important;
        margin-top: 6px;
        text-align: left !important;
        line-height: 1.55;
    }

    .transaction-item-tag,
    .transaction-history-meta span {
        width: 100%;
        justify-content: flex-start;
        text-align: left;
        padding: 9px 11px;
    }

    .copy-link-input,
    .transaction-focus-actions .btn {
        width: 100%;
    }

    .transaction-focus-actions {
        flex-direction: column;
        gap: 8px;
    }

    .transaction-history-actions {
        gap: 8px;
        margin-top: 2px;
    }

    .transaction-history-actions.has-pay-action {
        display: grid;
        grid-template-columns: minmax(110px, 128px) minmax(0, 1fr);
        grid-auto-rows: 56px;
        align-items: stretch;
    }

    .transaction-history-actions.has-status-only {
        display: flex;
        flex-direction: row;
        align-items: stretch;
        justify-content: flex-start;
    }

    .transaction-section-head p {
        margin-top: 2px;
        text-align: left;
    }

    .transaction-status-badge {
        white-space: normal;
        text-align: center;
        min-height: 44px;
    }

    .transaction-focus-actions .btn {
        flex: 0 0 auto;
        padding: 12px 15px;
        font-size: 0.95rem;
    }

    .transaction-bk-panel > strong {
        font-size: 1rem;
    }

    .transaction-bk-panel p {
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .transaction-bk-panel .transaction-focus-actions {
        gap: 10px;
        align-items: stretch;
    }

    .copy-link-input {
        flex: 0 0 auto;
        height: 50px;
        padding: 0 14px;
        font-size: 0.85rem;
    }

    .transaction-history-actions.has-pay-action .transaction-status-badge,
    .transaction-history-actions .btn {
        display: flex;
        width: 100%;
        min-width: 0;
        height: 56px;
        min-height: 56px;
        padding: 12px 15px;
        font-size: 0.92rem;
        box-sizing: border-box;
        justify-content: center;
        align-items: center;
    }

    .transaction-focus-head .transaction-status-badge,
    .transaction-history-actions.has-status-only .transaction-status-badge {
        display: inline-flex;
        width: auto;
        min-width: 102px;
        height: 45px;
        min-height: 45px;
        padding: 9px 12px;
        font-size: 0.74rem;
        box-sizing: border-box;
        justify-content: center;
        align-items: center;
    }

    #focusPayAction {
        align-items: stretch;
    }

    #focusPayAction .btn {
        display: flex;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
        justify-content: center;
        align-items: center;
        text-align: center;
        overflow-wrap: anywhere;
    }

    .transaction-history-actions.has-pay-action {
        display: flex;
    }

    .transaction-history-actions.has-pay-action .transaction-status-badge {
        display: none;
    }

    .transaction-history-actions.has-pay-action .btn {
        width: 100%;
        min-width: 0;
        flex: 1 1 auto;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const focusCard = document.getElementById('transactionFocusCard');
    const statusEndpoint = <?= json_encode((string) ($status_endpoint ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const shouldAutoOpenPayment = <?= !empty($should_auto_open_payment) ? 'true' : 'false'; ?>;
    const invoiceUrl = <?= json_encode((string) ($focused_transaction['invoice_url'] ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const focusTransactionId = <?= (int) ($focused_transaction['id'] ?? 0); ?>;

    function setStatusBadge(status) {
        const badge = document.getElementById('focusStatusBadge');
        const dot = document.getElementById('focusStatusDot');
        if (!badge) {
            return;
        }

        badge.classList.remove('status-pending', 'status-completed', 'status-failed');
        if (dot) {
            dot.classList.remove('status-pending', 'status-completed', 'status-failed');
        }

        if (status === 'completed') {
            badge.classList.add('status-completed');
            badge.textContent = 'Completed';
            if (dot) {
                dot.classList.add('status-completed');
            }
        } else if (status === 'failed') {
            badge.classList.add('status-failed');
            badge.textContent = 'Failed';
            if (dot) {
                dot.classList.add('status-failed');
            }
        } else {
            badge.classList.add('status-pending');
            badge.textContent = 'Pending';
            if (dot) {
                dot.classList.add('status-pending');
            }
        }

        const listBadge = document.querySelector('.transaction-activity-item[data-transaction-id="' + focusTransactionId + '"] [data-role="status-badge"]');
        if (listBadge) {
            listBadge.className = 'transaction-status-badge status-' + status;
            listBadge.textContent = badge.textContent;
        }
    }

    function updateFocusedTransaction(payload) {
        if (!payload || !focusCard) {
            return 'pending';
        }

        const status = String(payload.status || 'pending').toLowerCase();
        const message = String(payload.message || 'Pembayaran kamu sedang kami proses.');
        const bucksKaget = payload.bucks_kaget || {};

        setStatusBadge(status);

        const desc = document.getElementById('focusStatusDescription');
        if (desc) {
            desc.textContent = message;
        }

        const progress = document.getElementById('focusProgress');
        if (progress) {
            progress.hidden = (status === 'completed' || status === 'failed' || status === 'not_found');
        }

        const bucksBox = document.getElementById('focusBucksKagetBox');
        const bucksName = document.getElementById('focusBucksKagetName');
        const bucksDesc = document.getElementById('focusBucksKagetDescription');
        const bucksTotal = document.getElementById('focusBucksKagetTotalBucks');
        const bucksRecipients = document.getElementById('focusBucksKagetTotalRecipients');
        const bucksExpiryTag = document.getElementById('focusBucksKagetExpiryTag');
        const bucksWrap = document.getElementById('focusBucksKagetCopyWrap');
        const bucksInput = document.getElementById('focusBucksKagetLinkInput');
        const payWrap = document.getElementById('focusPayAction');
        const payButton = document.getElementById('focusPayButton');

        if (bucksName && (bucksKaget.name || '')) {
            bucksName.textContent = bucksKaget.name || 'Bucks Kaget';
        }

        if (bucksTotal) {
            bucksTotal.textContent = new Intl.NumberFormat('id-ID').format(Number(bucksKaget.total_bucks || 0)) + ' Bucks';
        }

        if (bucksRecipients) {
            bucksRecipients.textContent = new Intl.NumberFormat('id-ID').format(Number(bucksKaget.total_recipients || 0)) + ' Player';
        }

        if (bucksBox) {
            if (bucksKaget.ready) {
                bucksBox.removeAttribute('data-waiting');
            } else {
                bucksBox.setAttribute('data-waiting', 'true');
            }
        }

        if (bucksDesc) {
            bucksDesc.textContent = bucksKaget.ready
                ? 'Link claim sudah siap. Tinggal copy lalu bagikan ke player lain.'
                : 'Link claim akan muncul otomatis setelah transaksi selesai diproses.';
        }

        if (bucksWrap) {
            bucksWrap.hidden = !bucksKaget.ready;
        }

        if (bucksInput) {
            bucksInput.value = bucksKaget.ready ? (bucksKaget.url || '') : '';
        }

        if (bucksExpiryTag) {
            bucksExpiryTag.hidden = !(bucksKaget.expires_at || '');
            bucksExpiryTag.textContent = bucksKaget.expires_at ? ('Expired: ' + bucksKaget.expires_at) : '';
        }

        if (payWrap) {
            payWrap.hidden = !(payload.can_pay && payload.invoice_url);
        }

        if (payButton && payload.invoice_url) {
            payButton.href = payload.invoice_url;
        }

        return status;
    }

    async function pollFocusedTransaction() {
        if (!statusEndpoint || !focusTransactionId) {
            return;
        }

        try {
            const response = await fetch(statusEndpoint, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const payload = await response.json();
            const status = updateFocusedTransaction(payload);

            if (status !== 'completed' && status !== 'failed' && status !== 'not_found') {
                window.setTimeout(pollFocusedTransaction, 3000);
            }
        } catch (error) {
            window.setTimeout(pollFocusedTransaction, 4000);
        }
    }

    const copyButton = document.getElementById('focusCopyBucksKagetBtn');
    const copyInput = document.getElementById('focusBucksKagetLinkInput');
    if (copyButton && copyInput) {
        copyButton.addEventListener('click', async function() {
            try {
                await navigator.clipboard.writeText(copyInput.value || '');
                copyButton.textContent = 'Copied';
                window.setTimeout(function() {
                    copyButton.textContent = 'Copy Link';
                }, 1600);
            } catch (error) {
                copyInput.select();
            }
        });
    }

    if (shouldAutoOpenPayment && invoiceUrl) {
        const sessionKey = 'minehive-transaction-opened-' + String(focusTransactionId);
        if (!sessionStorage.getItem(sessionKey)) {
            const opened = window.open(invoiceUrl, '_blank', 'noopener');
            if (opened) {
                sessionStorage.setItem(sessionKey, '1');
            }
        }

        try {
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('open');
            window.history.replaceState({}, '', cleanUrl.toString());
        } catch (error) {}
    }

    if (focusCard && String(document.getElementById('focusStatusBadge')?.textContent || '').toLowerCase() === 'pending') {
        window.setTimeout(pollFocusedTransaction, 1200);
    }
});
</script>
