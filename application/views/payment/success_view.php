<?php
$status_payload = is_array($status_payload ?? null) ? $status_payload : null;
$initial_status = $status_payload['status'] ?? 'pending';
$initial_message = $status_payload['message'] ?? 'Pembayaran kamu sedang kami proses.';
$bucks_kaget = is_array($status_payload['bucks_kaget'] ?? null) ? $status_payload['bucks_kaget'] : [
    'ready' => false,
    'name' => 'Bucks Kaget',
    'url' => '',
    'total_bucks' => 0,
    'total_recipients' => 0,
    'expires_at' => ''
];
?>

<div class="payment-status-wrapper">
    <div class="payment-status-container success payment-status-live" id="paymentStatusCard">
        <div class="status-icon" id="paymentStatusIcon">
            <i class="fas <?= ($initial_status === 'completed') ? 'fa-check-circle' : 'fa-hourglass-half'; ?>"></i>
        </div>
        <h1 id="paymentStatusTitle"><?= ($initial_status === 'completed') ? 'Pembayaran Berhasil!' : 'Menunggu Konfirmasi Pembayaran'; ?></h1>
        <p id="paymentStatusMessage"><?= html_escape($initial_message); ?></p>

        <?php if (!empty($transaction_id)): ?>
            <div class="payment-status-chip">Transaction #<?= (int) $transaction_id; ?></div>
        <?php endif; ?>

        <p class="payment-status-redirect-note" id="paymentStatusRedirectNote">
            Setelah pembayaran terverifikasi, kamu akan dialihkan otomatis ke riwayat transaksi.
        </p>

        <div class="payment-status-progress" id="paymentStatusProgress" <?= ($initial_status === 'completed') ? 'hidden' : ''; ?>>
            <span class="spinner-ring" aria-hidden="true"></span>
            <span>Webhook Xendit sedang kami tunggu. Halaman ini akan update otomatis.</span>
        </div>

        <div class="bucks-kaget-result" id="bucksKagetResult" <?= !empty($bucks_kaget['ready']) ? '' : 'hidden'; ?>>
            <h3><?= html_escape((string) ($bucks_kaget['name'] ?? 'Bucks Kaget')); ?></h3>
            <p>Link claim random kamu sudah siap. Tinggal copy lalu bagikan ke pemain lain.</p>

            <div class="bucks-kaget-meta">
                <span><?= number_format((int) ($bucks_kaget['total_bucks'] ?? 0), 0, ',', '.'); ?> Bucks</span>
                <span><?= number_format((int) ($bucks_kaget['total_recipients'] ?? 0), 0, ',', '.'); ?> penerima</span>
                <span <?= !empty($bucks_kaget['expires_at']) ? '' : 'style="display:none;"'; ?>><?= !empty($bucks_kaget['expires_at']) ? 'Expired: ' . html_escape((string) $bucks_kaget['expires_at']) : ''; ?></span>
            </div>

            <div class="copy-link-group copy-link-group-lg">
                <input type="text" id="bucksKagetLinkInput" class="copy-link-input" value="<?= html_escape((string) ($bucks_kaget['url'] ?? '')); ?>" readonly>
                <button type="button" class="btn btn-primary copy-link-btn" id="copyBucksKagetLinkBtn">Copy Link</button>
            </div>
        </div>

        <div class="payment-status-actions">
            <a href="<?= base_url('transaction?trx=' . (int) ($transaction_id ?? 0)); ?>" class="btn btn-primary">Buka Riwayat</a>
            <a href="<?= base_url(); ?>" class="btn btn-secondary">Kembali ke Home</a>
        </div>
    </div>
</div>

<style>
.payment-status-live {
    max-width: 640px;
    display: grid;
    gap: 16px;
}
.payment-status-chip {
    justify-self: center;
    background: rgba(236, 202, 1, 0.1);
    border: 1px solid rgba(236, 202, 1, 0.2);
    border-radius: 999px;
    color: #ECCA01;
    font-weight: 700;
    padding: 8px 14px;
}
.payment-status-redirect-note {
    margin: -4px auto 0;
    max-width: 460px;
    color: #d8c8b6;
    font-size: 0.95rem;
    line-height: 1.7;
    text-align: center;
}
.payment-status-progress {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #F4EEE6;
}
.spinner-ring {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid rgba(236, 202, 1, 0.25);
    border-top-color: #ECCA01;
    animation: payment-spin 0.9s linear infinite;
}
.bucks-kaget-result {
    text-align: left;
    background: rgba(236, 202, 1, 0.06);
    border: 1px solid rgba(236, 202, 1, 0.16);
    border-radius: 14px;
    padding: 18px;
}
.bucks-kaget-result h3 {
    margin: 0 0 8px;
    color: #FFFFFF;
}
.bucks-kaget-result p {
    margin: 0 0 14px;
}
.bucks-kaget-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 14px;
}
.bucks-kaget-meta span {
    background: rgba(0, 0, 0, 0.18);
    border-radius: 999px;
    color: #F8E7AF;
    font-size: 13px;
    padding: 6px 10px;
}
.payment-status-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}
.copy-link-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.copy-link-group-lg .copy-link-input {
    flex: 1 1 260px;
}
.copy-link-input {
    width: 100%;
    background: rgba(0, 0, 0, 0.22);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    color: #F7F0E6;
    padding: 12px 14px;
}
@keyframes payment-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const transactionId = <?= (int) ($transaction_id ?? 0); ?>;
    const statusEndpoint = <?= json_encode(!empty($transaction_id) ? base_url('payment/check_status/' . (int) $transaction_id) : ''); ?>;
    const transactionUrl = <?= json_encode(!empty($transaction_id) ? base_url('transaction?trx=' . (int) $transaction_id) : base_url('transaction')); ?>;
    const failedTransactionUrl = <?= json_encode(!empty($transaction_id) ? base_url('transaction?trx=' . (int) $transaction_id . '&payment=failed') : base_url('transaction?payment=failed')); ?>;
    const titleEl = document.getElementById('paymentStatusTitle');
    const messageEl = document.getElementById('paymentStatusMessage');
    const progressEl = document.getElementById('paymentStatusProgress');
    const iconEl = document.getElementById('paymentStatusIcon');
    const redirectNoteEl = document.getElementById('paymentStatusRedirectNote');
    const bucksResultEl = document.getElementById('bucksKagetResult');
    const bucksLinkInput = document.getElementById('bucksKagetLinkInput');
    const copyButton = document.getElementById('copyBucksKagetLinkBtn');
    let redirectTimer = null;

    function scheduleRedirect(status) {
        if (redirectTimer || !transactionId) {
            return;
        }

        if (status === 'completed') {
            redirectTimer = window.setTimeout(function() {
                window.location.replace(transactionUrl);
            }, 1800);
            return;
        }

        if (status === 'failed' || status === 'not_found') {
            redirectTimer = window.setTimeout(function() {
                window.location.replace(failedTransactionUrl);
            }, 2200);
        }
    }

    function updateStatus(payload) {
        const status = String(payload.status || 'pending').toLowerCase();
        const message = payload.message || 'Pembayaran kamu sedang kami proses.';
        const bucksKaget = payload.bucks_kaget || {};

        if (titleEl) {
            if (status === 'completed') {
                titleEl.textContent = 'Pembayaran Berhasil!';
            } else if (status === 'failed') {
                titleEl.textContent = 'Pembayaran Gagal';
            } else {
                titleEl.textContent = 'Menunggu Konfirmasi Pembayaran';
            }
        }

        if (messageEl) {
            messageEl.textContent = message;
        }

        if (iconEl) {
            if (status === 'completed') {
                iconEl.innerHTML = '<i class="fas fa-check-circle"></i>';
            } else if (status === 'failed') {
                iconEl.innerHTML = '<i class="fas fa-times-circle"></i>';
            } else {
                iconEl.innerHTML = '<i class="fas fa-hourglass-half"></i>';
            }
        }

        if (progressEl) {
            progressEl.hidden = status === 'completed' || status === 'failed' || status === 'not_found';
        }

        if (redirectNoteEl) {
            if (status === 'completed') {
                redirectNoteEl.textContent = 'Pembayaran sudah masuk. Mengalihkan kamu ke riwayat transaksi...';
            } else if (status === 'failed' || status === 'not_found') {
                redirectNoteEl.textContent = 'Status pembayaran belum valid. Mengalihkan kamu ke halaman transaksi...';
            } else {
                redirectNoteEl.textContent = 'Setelah pembayaran terverifikasi, kamu akan dialihkan otomatis ke riwayat transaksi.';
            }
        }

        if (bucksResultEl) {
            bucksResultEl.hidden = !bucksKaget.ready;
        }

        if (bucksKaget.ready && bucksLinkInput) {
            bucksLinkInput.value = bucksKaget.url || '';

            const heading = bucksResultEl.querySelector('h3');
            if (heading) {
                heading.textContent = bucksKaget.name || 'Bucks Kaget';
            }

            const metaSpans = bucksResultEl.querySelectorAll('.bucks-kaget-meta span');
            if (metaSpans[0]) {
                metaSpans[0].textContent = new Intl.NumberFormat('id-ID').format(Number(bucksKaget.total_bucks || 0)) + ' Bucks';
            }
            if (metaSpans[1]) {
                metaSpans[1].textContent = new Intl.NumberFormat('id-ID').format(Number(bucksKaget.total_recipients || 0)) + ' penerima';
            }
            if (metaSpans[2]) {
                metaSpans[2].textContent = bucksKaget.expires_at ? ('Expired: ' + bucksKaget.expires_at) : '';
                metaSpans[2].style.display = bucksKaget.expires_at ? 'inline-flex' : 'none';
            }
        }

        scheduleRedirect(status);

        return status;
    }

    async function pollStatus() {
        if (!transactionId || !statusEndpoint) {
            return;
        }

        try {
            const response = await fetch(statusEndpoint, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const payload = await response.json();
            const status = updateStatus(payload);

            if (status !== 'completed' && status !== 'failed' && status !== 'not_found') {
                window.setTimeout(pollStatus, 3000);
            }
        } catch (error) {
            window.setTimeout(pollStatus, 4000);
        }
    }

    if (copyButton && bucksLinkInput) {
        copyButton.addEventListener('click', async function() {
            try {
                await navigator.clipboard.writeText(bucksLinkInput.value || '');
                copyButton.textContent = 'Copied';
                window.setTimeout(function() {
                    copyButton.textContent = 'Copy Link';
                }, 1600);
            } catch (error) {
                bucksLinkInput.select();
            }
        });
    }

    const initialPayload = <?= json_encode($status_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    if (initialPayload) {
        const currentStatus = updateStatus(initialPayload);
        if (currentStatus !== 'completed' && currentStatus !== 'failed' && currentStatus !== 'not_found') {
            window.setTimeout(pollStatus, 2500);
        }
    } else {
        window.setTimeout(pollStatus, 1200);
    }
});
</script>
