<!-- Wrapper Utama (Sama persis dengan Rules Page) -->
<div class="rules-wrapper" data-aos="fade-up">

    <!-- Header Area -->
    <div class="rules-header">
        <h1>Staff Dashboard</h1>
        <p>Panduan cepat (Cheat Sheet), daftar command, dan SOP penanganan player untuk Staff yang bertugas.</p>
        
        <!-- Tombol Logout -->
        <a href="<?= base_url('helper/logout'); ?>" style="display: inline-flex; align-items: center; gap: 8px; margin-top: 15px; padding: 8px 20px; border: 1px solid #C52E2E; color: #C52E2E; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 0.85rem; transition: all 0.3s;">
            <i class='bx bx-log-out'></i> LOGOUT SESI
        </a>
    </div>

    <!-- Navigasi Tab (Menggunakan class style.css) -->
    <div class="tab-nav">
        <button class="tab-nav-link active" data-tab="cmd-helper">
            Command Helper
        </button>
        <button class="tab-nav-link" data-tab="coreprotect">
            CoreProtect (Log)
        </button>
        <button class="tab-nav-link" data-tab="sop-rules">
            SOP & Hukuman
        </button>
    </div>

    <div class="tab-content-wrapper">

        <!-- TAB 1: COMMAND HELPER -->
        <div id="cmd-helper" class="tab-pane active">
            <div class="content-card">
                <h3><i class='bx bx-terminal'></i> Command Moderasi</h3>
                <ol class="rule-list">
                    <li>
                        <strong>WARN</strong><br>
                        <small><em>Sanksi: Peringatan Ringan</em></small><br>
                        <code>/warn &lt;player&gt; &lt;alasan&gt;</code><br>
                        Memberikan peringatan resmi. 3x Warn akan menyebabkan sanksi otomatis dari sistem.
                    </li>
                    <li>
                        <strong>MUTE</strong><br>
                        <small><em>Sanksi: Pembatasan Chat</em></small><br>
                        <code>/mute &lt;player&gt; &lt;waktu&gt;</code><br>
                        Membungkam chat player untuk sementara. Contoh waktu: <code>10m</code>, <code>1h</code>.
                    </li>
                    <li>
                        <strong>JAIL</strong><br>
                        <small><em>Sanksi: Isolasi Fisik</em></small><br>
                        <code>/jail &lt;player&gt; &lt;waktu&gt; &lt;alasan&gt;</code><br>
                        Memenjarakan player, membatasi interaksi dan command mereka.
                    </li>
                    <li>
                        <strong>VANISH (GHOST MODE)</strong><br>
                        <small><em>Fitur: Investigasi</em></small><br>
                        <code>/vanish</code><br>
                        Menghilang dari pandangan untuk memantau pemain yang dicurigai tanpa terdeteksi.
                    </li>
                    <li>
                        <strong>FORCE TELEPORT</strong><br>
                        <small><em>Fitur: Utilitas Staff</em></small><br>
                        <code>/tpo &lt;player&gt;</code><br>
                        Teleport paksa ke lokasi pemain tanpa memerlukan persetujuan mereka.
                    </li>
                    <li>
                        <strong>OFFLINE TELEPORT</strong><br>
                        <small><em>Fitur: Utilitas Staff</em></small><br>
                        <code>/otp &lt;player&gt;</code><br>
                        Teleport ke lokasi terakhir pemain saat mereka logout.
                    </li>
                </ol>
            </div>
        </div>

        <!-- TAB 2: COREPROTECT -->
        <div id="coreprotect" class="tab-pane">
            <div class="content-card">
                <h3><i class='bx bx-search-alt'></i> Investigasi (CoreProtect)</h3>
                <ol class="rule-list">
                    <li>
                        <strong>Inspector Mode</strong><br>
                        <small><em>Fungsi: Cek cepat griefing & maling</em></small><br>
                        <code>/co i</code><br>
                        Aktifkan mode inspeksi. Klik kiri block untuk melihat siapa yang menghancurkan, klik kanan untuk melihat siapa yang menaruh. Klik kanan chest untuk riwayat item.
                    </li>
                    <li>
                        <strong>Lookup Block</strong><br>
                        <small><em>Fungsi: Laporan detail history block</em></small><br>
                        <code>/co l block</code><br>
                        Gunakan filter tambahan seperti <code>u:NamaPlayer</code> (user), <code>t:2d</code> (time), <code>r:10</code> (radius).
                    </li>
                    <li>
                        <strong>Lookup Container</strong><br>
                        <small><em>Fungsi: Laporan detail maling item</em></small><br>
                        <code>/co l container</code><br>
                        Melihat riwayat transaksi item di dalam Chest, Furnace, dll.
                    </li>
                    <li>
                        <strong>Lookup Inventory</strong><br>
                        <small><em>Fungsi: Laporan detail transaksi item player</em></small><br>
                        <code>/co l inventory</code><br>
                        Melihat riwayat item yang di-drop atau di-pickup oleh pemain.
                    </li>
                    <li>
                        <strong>Lookup Chat</strong><br>
                        <small><em>Fungsi: Laporan detail riwayat chat</em></small><br>
                        <code>/co l chat</code><br>
                        Berguna untuk mencari bukti percakapan toxic atau pelanggaran chat lainnya.
                    </li>
                </ol>
            </div>
        </div>

        <!-- TAB 3: SOP & HUKUMAN -->
        <div id="sop-rules" class="tab-pane">
            <div class="content-card">
                <h3><i class='bx bx-error-circle'></i> Panduan Hukuman</h3>
                <ol class="rule-list">
                    <li>
                        <strong>Toxic / Kasar</strong><br>
                        <small><em>Sanksi: Mute 5 - 15 Menit</em></small><br>
                        Untuk pelanggaran chat ringan.
                    </li>
                    <li>
                        <strong>Spam Chat</strong><br>
                        <small><em>Sanksi: Warn / Mute 5 Menit</em></small><br>
                        Jika pemain mengirim pesan berulang kali dan mengganggu.
                    </li>
                    <li>
                        <strong>Mengganggu Player Lain</strong><br>
                        <small><em>Sanksi: Jail 20 - 40 Menit</em></small><br>
                        Untuk gangguan gameplay yang tidak merusak.
                    </li>
                    <li>
                        <strong>Griefing Ringan</strong><br>
                        <small><em>Sanksi: Jail 30m - 1 Jam (+Rollback)</em></small><br>
                        Merusak bangunan kecil, harus disertai perbaikan area (rollback).
                    </li>
                    <li>
                        <strong>X-Ray Mining</strong><br>
                        <small><em>Sanksi: Jail 1 - 3 Jam (Ambil Itemnya)</em></small><br>
                        Jika terbukti menggunakan X-Ray, penjarakan dan sita hasil mining ilegal.
                    </li>
                    <li>
                        <strong>Hacking (Fly/Killaura)</strong><br>
                        <small><em>Sanksi: Jail 5 Jam / Request Ban</em></small><br>
                        Pelanggaran berat, segera isolasi dan laporkan ke Admin untuk ban permanen.
                    </li>
                </ol>

                <h3 style="margin-top: 40px;">Peraturan Internal Staff</h3>
                <ol class="rule-list">
                    <li><strong>Dilarang Jual Beli Jabatan/Item Staff:</strong> Item hasil /kit staff atau akses command tidak boleh dijual (RMT/Ingame Money).</li>
                    <li><strong>No Abuse:</strong> Jangan pakai /fly, /vanish, atau /tpo untuk keuntungan pribadi di survival (misal: cari base musuh untuk di-raid).</li>
                    <li><strong>Netralitas:</strong> Helper harus netral. Tidak boleh memihak clan/tim tertentu saat menangani kasus.</li>
                    <li><strong>Sopan & Profesional:</strong> Dilarang toxic saat memakai tag [Helper]. Jaga citra server.</li>
                </ol>
            </div>
        </div>

    </div>
</div>

<!-- Script Tab Logic (Sama seperti Rules Page) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.tab-nav-link');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const tabId = link.getAttribute('data-tab');

            tabLinks.forEach(item => item.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));

            link.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>