/**
 * Script Efek Salju Sederhana (Vanilla JS)
 * Dibuat untuk menggantikan firefly.js
 */
document.addEventListener('DOMContentLoaded', function() {
    const snowContainer = document.body;
    // Sesuaikan jumlah salju di sini
    const totalFlakes = 100; 

    // Jangan tampilkan salju jika di halaman admin
    if (document.body.classList.contains('admin-login-page') || window.location.href.includes('/admin')) {
        return;
    }

    for (let i = 0; i < totalFlakes; i++) {
        let flake = document.createElement('div');
        flake.className = 'snowflake';

        // Mengatur properti acak menggunakan CSS Variables
        // Posisi mulai horizontal (0% - 100% lebar layar)
        flake.style.setProperty('--start-x', Math.random() * 100 + 'vw');
        
        // Durasi jatuh (antara 10 s.d. 20 detik)
        flake.style.setProperty('--fall-duration', (Math.random() * 10 + 10) + 's'); 
        
        // Delay (agar salju tidak mulai bersamaan)
        // Nilai negatif berarti animasi sudah berjalan sekian detik
        flake.style.setProperty('--fall-delay', Math.random() * -30 + 's');
        
        // Goyangan horizontal (antara -15vw s.d. +15vw)
        flake.style.setProperty('--drift', (Math.random() * 30 - 15) + 'vw'); 
        
        // Ukuran (antara 2px s.d. 7px)
        flake.style.setProperty('--size', (Math.random() * 5 + 2) + 'px');
        
        // Opasitas (antara 0.3 s.d. 0.8)
        flake.style.opacity = Math.random() * 0.5 + 0.3;

        snowContainer.appendChild(flake);
    }
});