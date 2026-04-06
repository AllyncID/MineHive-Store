<div class="payment-status-wrapper">
    <div class="payment-status-container failed payment-status-live">
        <div class="status-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <h1>Pembayaran Gagal</h1>
        <p>Pembayaran dibatalkan atau belum berhasil diselesaikan. Keranjang kamu tetap aman, jadi bisa dicoba lagi kapan saja.</p>

        <?php if (!empty($transaction_id)): ?>
            <div class="payment-status-chip">Transaction #<?= (int) $transaction_id; ?></div>
        <?php endif; ?>

        <div class="payment-status-actions">
            <a href="<?= base_url('cart'); ?>" class="btn btn-primary">Kembali ke Keranjang</a>
            <a href="<?= base_url(); ?>" class="btn btn-secondary">Kembali ke Home</a>
        </div>
    </div>
</div>

<style>
.payment-status-live {
    max-width: 620px;
    display: grid;
    gap: 16px;
}
.payment-status-chip {
    justify-self: center;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 999px;
    color: #F4EEE6;
    font-weight: 700;
    padding: 8px 14px;
}
.payment-status-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}
</style>
