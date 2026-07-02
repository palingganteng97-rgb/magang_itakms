<script>
const ticketId = <?= $ticket_id; ?>;
const chatWindow = document.getElementById('chatWindow');
let lastChatCount = -1; // Set ke -1 agar muat pertama kali selalu jalan

function loadChatsRealtime() {
    fetch(`get_comments.php?id=${ticketId}`)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                // Jika jumlah pesan berubah (ada chat baru/dihapus), perbarui DOM
                if (res.data.length !== lastChatCount) {
                    
                    if (res.data.length === 0) {
                        chatWindow.innerHTML = `
                            <div class="text-center my-auto text-muted py-5">
                                <i class="bi bi-chat-left-text fs-1 d-block mb-2 text-secondary-subtle"></i>
                                Belum ada riwayat percakapan pada tiket ini.
                            </div>`;
                        lastChatCount = 0;
                        return;
                    }

                    let chatHtml = '';

                    res.data.forEach(msg => {
                        const isMe = (msg.user_id == res.current_user_id);
                        const bubbleClass = isMe ? 'chat-bubble-right my-chat-bubble' : 'chat-bubble-left';
                        
                        let senderHtml = '';
                        if (!isMe) {
                            const namaPengirim = msg.nama_komentator ? msg.nama_komentator : 'User';
                            senderHtml = `<span class="chat-sender text-success">${escapeHtml(namaPengirim)}</span>`;
                        }

                        // Logika Tampilan File Lampiran
                        let attachmentHtml = '';
                        if (msg.lampiran) {
                            const ext = msg.lampiran.split('.').pop().toLowerCase();
                            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                            if (imageExtensions.includes(ext)) {
                                attachmentHtml = `
                                    <a href="uploads/${msg.lampiran}" target="_blank">
                                        <img src="uploads/${msg.lampiran}" class="chat-img-preview border shadow-sm" alt="Lampiran">
                                    </a>`;
                            } else {
                                attachmentHtml = `
                                    <div class="mb-2">
                                        <a href="uploads/${msg.lampiran}" target="_blank" class="btn btn-sm btn-light border text-dark fw-semibold">
                                            <i class="bi bi-file-earmark-arrow-down-fill text-primary"></i> Dokumen Lampiran (.${ext})
                                        </a>
                                    </div>`;
                            }
                        }

                        // Logika Tampilan Teks Chat
                        let textHtml = '';
                        if (msg.isi_chat) {
                            // nl2br versi JavaScript sederhana untuk mengganti enter jadi <br>
                            const formattedText = escapeHtml(msg.isi_chat).replace(/\n/g, '<br>');
                            textHtml = `<div class="chat-text text-wrap">${formattedText}</div>`;
                        }

                        // Logika Centang Dua WhatsApp untuk pengirim
                        const checkIcon = isMe ? '<i class="bi bi-check2-all text-primary ms-1"></i>' : '';

                        // Gabungkan seluruh struktur balon chat
                        chatHtml += `
                            <div class="${bubbleClass}" data-comment-id="${msg.id}" style="cursor: context-menu;">
                                ${senderHtml}
                                ${attachmentHtml}
                                ${textHtml}
                                <span class="chat-time">
                                    Terkirim ${checkIcon}
                                </span>
                            </div>
                        `;
                    });

                    chatWindow.innerHTML = chatHtml;
                    
                    // Gulir otomatis layar ke chat paling bawah setiap ada pesan baru masuk
                    chatWindow.scrollTop = chatWindow.scrollHeight;
                    
                    // Perbarui jumlah tracker pesan saat ini
                    lastChatCount = res.data.length;

                    // Opsional: Jika Anda punya fungsi inisialisasi ulang menu klik kanan kustom (Context Menu), 
                    // panggil fungsinya di sini agar gelembung chat baru tetap bisa di-klik kanan/edit/hapus.
                    if (typeof initContextMenu === 'function') {
                        initContextMenu();
                    }
                }
            }
        })
        .catch(err => console.error("Gagal melakukan sinkronisasi chat:", err));
}

// Fungsi pengaman XSS agar text chat tidak merusak kode HTML (seperti htmlspecialchars di PHP)
function escapeHtml(string) {
    return String(string).replace(/[&<>"']/g, function (s) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s];
    });
}

// Jalankan pencarian berkala setiap 2000ms (2 detik)
loadChatsRealtime();
setInterval(loadChatsRealtime, 2000);
</script>
