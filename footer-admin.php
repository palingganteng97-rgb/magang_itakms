<!-- MENUTUP TIGA STRUKTUR CONTAINER INDUK UTAMA AGAR TATA LETAK DESKTOP DAN MOBILE TETAP SEJAJAR -->
    </main> 
  </div> 
</div> 

<!-- INTERAKSI DOM DAN SINKRONISASI POSISI SCROLL SIDEBAR UTAMA (SMART CENTER COCOK UNTUK SEMUA OPSI) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const menuContainer = document.querySelector('.menu-scroll-container');
    const activeMenu = document.querySelector('.menu-scroll-container .active-style');
    
    if (menuContainer && activeMenu) {
        // 1. Ambil jarak koordinat menu aktif dari atap kontainer
        const activeOffsetTop = activeMenu.offsetTop;
        
        // 2. Ambil tinggi elemen menu itu sendiri (sekitar 44px)
        const activeHeight = activeMenu.offsetHeight;
        
        // 3. Ambil tinggi total area scroll sidebar yang terlihat di layar monitor Anda
        const containerHeight = menuContainer.clientHeight;
        
        // 4. Hitung posisi tengah yang ideal secara teoritis
        let scrollToCenter = activeOffsetTop - (containerHeight / 2) + (activeHeight / 2);
        
        // 5. BATAS CERDAS ATAS & BAWAH:
        // Ambil tinggi maksimal yang bisa di-scroll oleh kontainer sidebar Anda
        const maxScrollTop = menuContainer.scrollHeight - containerHeight;
        
        // Mencegah nilai minus jika menu yang dipilih berada di paling atas (Dashboard)
        if (scrollToCenter < 0) {
            scrollToCenter = 0;
        } 
        // Mencegah nilai meluap melebihi batas maksimal jika menu berada di paling bawah (User Profil)
        else if (scrollToCenter > maxScrollTop) {
            scrollToCenter = maxScrollTop;
        }
        
        // 6. Eksekusi penggulungan otomatis secara instan
        menuContainer.scrollTop = scrollToCenter;
    }

    // Bersihkan sisa bintik hover abu-abu setelah diklik di HP
    document.querySelectorAll('.sidebar-fixed .nav-link').forEach(link => {
        link.addEventListener('click', function() {
            this.blur(); 
        });
    });
});

// MODUL PENUTUP OTOMATIS ALERT & PEMBERSIH PARAMETER URL
document.addEventListener("click", function(t) {
    let alertBtn = t.target.closest('[data-bs-dismiss="alert"]');
    if (alertBtn) {
        let alertBox = t.target.closest('.alert');
        if (alertBox) {
            t.preventDefault();
            alertBox.remove(); 
            window.location.href = window.location.pathname; 
        }
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
