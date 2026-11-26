<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Antrean Pasien - Praktik Dokter Mandiri</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-canvas: #f1f5f9;
            --primary-blue: #0284c7;
            --navy-dark: #0f172a;
            --navy-surface: #1e293b;
            --teal: #0d9488;
            --teal-light: #ccfbf1;
            --amber-call: #f59e0b;
            --amber-light: #fef3c7;
            --green: #10b981;
            --green-light: #d1fae5;
            --white: #ffffff;
            --text-dark: #0f172a;
            --text-gray: #64748b;
            --border-light: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #e0f2fe 0%, #f8fafc 50%, #f0fdf4 100%);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            user-select: none;
        }

        /* ===== 16:9 CONTAINER LOCK ===== */
        .display-container {
            width: 100%;
            max-width: 1800px;
            margin: 0 auto;
            padding: 16px 24px;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 16px;
        }

        /* ===== HEADER TV ===== */
        .tv-header {
            background: var(--white);
            border-radius: 20px;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(2, 132, 199, 0.15);
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 28px;
            font-weight: 800;
            box-shadow: 0 8px 16px rgba(2, 132, 199, 0.25);
        }

        .brand-title {
            font-size: 24px;
            font-weight: 900;
            color: var(--navy-dark);
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-badge {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 1px;
        }

        .brand-subtitle {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-gray);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .tv-controls {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .audio-toggle-btn {
            background: var(--teal-light);
            color: var(--teal);
            border: 1px solid rgba(13, 148, 136, 0.2);
            padding: 10px 18px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .audio-toggle-btn:hover {
            transform: scale(1.02);
            background: #bbf7d0;
        }

        .date-display {
            background: #f8fafc;
            border: 1px solid var(--border-light);
            color: var(--navy-dark);
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .clock-display {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: var(--white);
            padding: 10px 24px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 1px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ===== MAIN DISPLAY GRID (DUA KOLOM LANDSCAPE) ===== */
        .tv-grid {
            display: grid;
            grid-template-columns: 1.45fr 1fr;
            gap: 20px;
            flex: 1;
        }

        /* ===== CARD UTAMA: NOMOR DIPANGGIL ===== */
        .calling-card {
            background: var(--white);
            border-radius: 28px;
            border: 3px solid #38bdf8;
            box-shadow: 0 12px 36px rgba(2, 132, 199, 0.12);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        .card-topbar {
            background: linear-gradient(90deg, #0284c7, #0ea5e9);
            color: var(--white);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-topbar-title {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pulse-dot {
            width: 12px;
            height: 12px;
            background: #facc15;
            border-radius: 50%;
            animation: pulse-ring 1.5s infinite;
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.9); opacity: 1; box-shadow: 0 0 0 0 rgba(250, 204, 21, 0.7); }
            70% { transform: scale(1.3); opacity: 0.8; box-shadow: 0 0 0 12px rgba(250, 204, 21, 0); }
            100% { transform: scale(0.9); opacity: 1; }
        }

        .calling-body {
            padding: 24px 32px;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 14px;
        }

        .call-badge {
            background: var(--amber-light);
            color: #b45309;
            border: 2px solid #fde68a;
            padding: 8px 24px;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 1px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        .big-number {
            font-size: 120px;
            font-weight: 900;
            line-height: 1;
            color: var(--navy-dark);
            letter-spacing: -2px;
            margin: 6px 0;
            text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .number-underline {
            width: 140px;
            height: 8px;
            background: linear-gradient(90deg, #0284c7, #38bdf8);
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .patient-mask-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-gray);
        }

        .patient-tag {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 13px;
        }

        .destination-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px dashed #0284c7;
            border-radius: 20px;
            padding: 18px 28px;
            width: 100%;
            margin-top: 10px;
        }

        .dest-subtitle {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-gray);
            margin-bottom: 6px;
        }

        .dest-title {
            font-size: 26px;
            font-weight: 900;
            color: #0284c7;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .dest-doctor {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            margin-top: 4px;
        }

        /* ===== KOLOM KANAN: DAFTAR ANTREAN BERIKUTNYA ===== */
        .queue-list-card {
            background: var(--white);
            border-radius: 28px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .queue-list-topbar {
            background: linear-gradient(90deg, #0284c7, #0ea5e9);
            color: var(--white);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .queue-items-container {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
            justify-content: flex-start;
        }

        .queue-item {
            background: #f8fafc;
            border: 1px solid var(--border-light);
            border-radius: 16px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
        }

        .queue-item.first-next {
            background: #fffbeb;
            border: 2px solid #fde68a;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.12);
        }

        .queue-item-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .item-number-badge {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #e2e8f0;
            color: var(--navy-dark);
            font-size: 16px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .queue-item.first-next .item-number-badge {
            background: #f59e0b;
            color: var(--white);
        }

        .item-antrean-code {
            font-size: 28px;
            font-weight: 900;
            color: var(--navy-dark);
            line-height: 1;
        }

        .item-poli-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-gray);
            margin-top: 3px;
        }

        .item-status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #e2e8f0;
            color: #475569;
        }

        .queue-item.first-next .item-status-badge {
            background: #fef08a;
            color: #854d0e;
        }

        /* ===== BOTTOM SUMMARY TILES ===== */
        .bottom-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .summary-tile {
            background: var(--white);
            border-radius: 20px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-light);
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .summary-icon.blue { background: #e0f2fe; color: #0284c7; }
        .summary-icon.teal { background: var(--teal-light); color: var(--teal); }
        .summary-icon.amber { background: var(--amber-light); color: var(--amber-call); }
        .summary-icon.green { background: var(--green-light); color: var(--green); }

        .summary-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-gray);
            letter-spacing: 0.5px;
        }

        .summary-val {
            font-size: 26px;
            font-weight: 900;
            color: var(--navy-dark);
            line-height: 1.2;
        }

        .summary-sub {
            font-size: 12px;
            color: var(--text-gray);
            font-weight: 500;
        }

        /* ===== FOOTER RUNNING TEXT ===== */
        .tv-footer {
            background: var(--navy-dark);
            color: var(--white);
            border-radius: 16px;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.2);
        }

        .footer-pill {
            background: #f59e0b;
            color: #78350f;
            font-size: 12px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            letter-spacing: 1px;
            flex-shrink: 0;
        }

        .marquee-wrapper {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
        }

        .marquee-text {
            display: inline-block;
            animation: marquee 25s linear infinite;
            font-size: 15px;
            font-weight: 600;
            color: #e2e8f0;
        }

        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        /* Fullscreen button pill */
        .fullscreen-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--white);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="display-container">

    <!-- HEADER -->
    <header class="tv-header">
        <div class="brand-section">
            <div class="brand-icon">+</div>
            <div>
                <div class="brand-title">
                    KLINIK SEHAT PRATAMA
                    <span class="brand-badge">LIVE</span>
                </div>
                <div class="brand-subtitle">Praktik Dokter Mandiri • Sistem Pemanggilan Antrean Terpadu</div>
            </div>
        </div>

        <div class="tv-controls">
            <button class="audio-toggle-btn" id="btnAudio" onclick="toggleAudio()">
                <span id="audioIcon">🔊</span>
                <span id="audioText">AUDIO AKTIF</span>
            </button>
            <div class="date-display" id="dateDisplay">
                📅 Memuat tanggal...
            </div>
            <div class="clock-display" id="clockDisplay">
                🕒 00:00:00 WIB
            </div>
        </div>
    </header>

    <!-- GRID UTAMA (LANDSCAPE 16:9) -->
    <main class="tv-grid">

        <!-- KIRI: NOMOR ANTRIAN DIPANGGIL -->
        <section class="calling-card">
            <div class="card-topbar">
                <div class="card-topbar-title">
                    <span class="pulse-dot"></span>
                    Nomor Antrean Dipanggil
                </div>
                <span style="font-size: 13px; font-weight: 700; opacity: 0.9;">PANGGILAN SUARA REAL-TIME</span>
            </div>

            <div class="calling-body">
                <div class="call-badge" id="callBadge">
                    📢 SEDANG DIPANGGIL
                </div>

                <div class="big-number" id="callingNumber">--</div>
                <div class="number-underline"></div>

                <div class="patient-mask-info">
                    <span class="patient-tag" id="callingBadgeJenis">UMUM</span>
                    <span id="callingPatientMask">Pasien: Menunggu Panggilan</span>
                </div>

                <div class="destination-box">
                    <div class="dest-subtitle">Silakan segera menuju ke ruangan:</div>
                    <div class="dest-title" id="callingDestination">
                        🚪 RUANG POLI PEMERIKSAAN
                    </div>
                    <div class="dest-doctor">Dokter Pemeriksa: drg. Azwari / Tim Medis</div>
                </div>
            </div>
        </section>

        <!-- KANAN: DAFTAR ANTRIAN BERIKUTNYA -->
        <section class="queue-list-card">
            <div class="queue-list-topbar">
                <div style="font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                    📋 DAFTAR ANTREAN
                </div>
                <span style="font-size: 12px; font-weight: 800; background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 20px;">URUTAN BERIKUTNYA</span>
            </div>

            <div class="queue-items-container" id="queueListContainer">
                <!-- Data antrean diisi via AJAX -->
                <div style="text-align: center; color: #94a3b8; padding: 40px 0; font-size: 14px;">
                    Memuat antrean berikutnya...
                </div>
            </div>
        </section>

    </main>

    <!-- TILES SUMMARY RINGKASAN ANTREAN -->
    <section class="bottom-summary-grid">
        <div class="summary-tile">
            <div class="summary-icon blue">🩺</div>
            <div>
                <div class="summary-label">Sedang Dilayani (Poli 1)</div>
                <div class="summary-val" id="sumPoli1">-</div>
                <div class="summary-sub">Poli Umum</div>
            </div>
        </div>

        <div class="summary-tile">
            <div class="summary-icon teal">🦷</div>
            <div>
                <div class="summary-label">Sedang Dilayani (Poli Gigi)</div>
                <div class="summary-val" id="sumPoli2">-</div>
                <div class="summary-sub">drg. Azwari</div>
            </div>
        </div>

        <div class="summary-tile">
            <div class="summary-icon amber">👥</div>
            <div>
                <div class="summary-label">Antrean Menunggu</div>
                <div class="summary-val" id="sumMenunggu">0 Pasien</div>
                <div class="summary-sub">Pelayanan Berjalan Lancar</div>
            </div>
        </div>

        <div class="summary-tile">
            <div class="summary-icon green">✅</div>
            <div>
                <div class="summary-label">Selesai Dilayani</div>
                <div class="summary-val" id="sumSelesai">0 Pasien</div>
                <div class="summary-sub">Total akumulasi hari ini</div>
            </div>
        </div>
    </section>

    <!-- FOOTER RUNNING TEXT -->
    <footer class="tv-footer">
        <div class="footer-pill">INFORMASI</div>
        <div class="marquee-wrapper">
            <div class="marquee-text">
                📢 Mohon selalu perhatikan nomor antrean yang ditampilkan pada layar TV display. Apabila nomor Anda terlewat, harap melapor kembali ke petugas Loket Pendaftaran. Terima kasih atas kerja sama Anda.
            </div>
        </div>
        <button class="fullscreen-btn" onclick="toggleFullScreen()">⛶ Fullscreen</button>
    </footer>

</div>

<script>
let audioEnabled = true;
let lastCalledId = null;
let lastCalledNumber = null;

// Clock Realtime
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('clockDisplay').textContent = `🕒 ${hours}:${minutes}:${seconds} WIB`;

    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    document.getElementById('dateDisplay').textContent = `📅 ${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}
setInterval(updateClock, 1000);
updateClock();

// Text To Speech Web Speech API
function speakQueue(nomor, loket) {
    if (!audioEnabled || !('speechSynthesis' in window)) return;
    
    // Matikan suara sebelumnya jika ada
    window.speechSynthesis.cancel();

    // Format nomor A-001 menjadi: "Nomor antrean, A, kosong kosong satu, silakan menuju..."
    let cleanNo = nomor.replace('-', ' ');
    let text = `Nomor antrean, ${cleanNo}, silakan menuju ke ${loket}`;

    let utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 0.9;
    utterance.pitch = 1.0;

    // Coba temukan suara bahasa Indonesia jika tersedia
    let voices = window.speechSynthesis.getVoices();
    let idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
    if (idVoice) {
        utterance.voice = idVoice;
    }

    window.speechSynthesis.speak(utterance);
}

function toggleAudio() {
    audioEnabled = !audioEnabled;
    const btn = document.getElementById('btnAudio');
    const icon = document.getElementById('audioIcon');
    const text = document.getElementById('audioText');
    if (audioEnabled) {
        btn.style.background = 'var(--teal-light)';
        btn.style.color = 'var(--teal)';
        icon.textContent = '🔊';
        text.textContent = 'AUDIO AKTIF';
        // Test speak
        speakQueue('Tes Audio', 'Ruang Tunggu');
    } else {
        btn.style.background = '#fee2e2';
        btn.style.color = '#dc2626';
        icon.textContent = '🔇';
        text.textContent = 'AUDIO BISU';
        window.speechSynthesis.cancel();
    }
}

function toggleFullScreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {});
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

// Polling data antrean dari database setiap 2.5 detik
async function fetchQueueData() {
    try {
        const res = await fetch('api_antrean.php?aksi=get_antrean_publik&t=' + new Date().getTime());
        const data = await res.json();

        if (data.status === 'success') {
            // 1. Update Nomor Dipanggil
            const callBox = document.getElementById('callingNumber');
            const destBox = document.getElementById('callingDestination');
            const patientMask = document.getElementById('callingPatientMask');
            const badgeJenis = document.getElementById('callingBadgeJenis');

            if (data.sedang_dipanggil) {
                const currentNo = data.sedang_dipanggil.nomor_antrean;
                const currentId = data.sedang_dipanggil.id_kunjungan;
                const dest = data.sedang_dipanggil.loket_meja || 'Ruang Pemeriksaan';

                callBox.textContent = currentNo;
                destBox.textContent = '🚪 ' + dest.toUpperCase();
                patientMask.textContent = 'Pasien: ' + (data.sedang_dipanggil.nama_mask || 'Terdaftar');
                badgeJenis.textContent = data.sedang_dipanggil.jenis_pasien || 'BPJS / UMUM';

                // Bunyikan suara jika ada nomor baru yang dipanggil
                if (lastCalledId !== currentId || lastCalledNumber !== currentNo) {
                    lastCalledId = currentId;
                    lastCalledNumber = currentNo;
                    speakQueue(currentNo, dest);
                }
            } else {
                callBox.textContent = '--';
                destBox.textContent = '🚪 MENUNGGU PANGGILAN PETUGAS';
                patientMask.textContent = 'Pasien: -';
                badgeJenis.textContent = 'STANDBY';
            }

            // 2. Update Daftar Antrean Berikutnya
            const container = document.getElementById('queueListContainer');
            if (data.daftar_berikutnya && data.daftar_berikutnya.length > 0) {
                let html = '';
                data.daftar_berikutnya.forEach((item, index) => {
                    const isFirst = (index === 0);
                    html += `
                        <div class="queue-item ${isFirst ? 'first-next' : ''}">
                            <div class="queue-item-left">
                                <div class="item-number-badge">${item.urutan}</div>
                                <div>
                                    <div class="item-antrean-code">${item.nomor_antrean}</div>
                                    <div class="item-poli-name">${item.poli_tujuan}</div>
                                </div>
                            </div>
                            <div class="item-status-badge">
                                ${isFirst ? '🟡 ' + item.estimasi : item.status}
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div style="text-align: center; color: #94a3b8; padding: 40px 0;">
                        <div style="font-size: 32px; margin-bottom: 8px;">🎉</div>
                        <strong>Tidak ada antrean menunggu saat ini.</strong>
                        <div style="font-size: 12px; margin-top: 4px;">Semua pasien telah dilayani.</div>
                    </div>
                `;
            }

            // 3. Update Summary Tiles
            document.getElementById('sumPoli1').textContent = data.dilayani_poli1 || '-';
            document.getElementById('sumPoli2').textContent = data.dilayani_poli2 || '-';
            document.getElementById('sumMenunggu').textContent = (data.stat ? data.stat.menunggu : 0) + ' Pasien';
            document.getElementById('sumSelesai').textContent = (data.stat ? data.stat.selesai : 0) + ' Pasien';
        }
    } catch (err) {
        console.error('Gagal mengambil data antrean:', err);
    }
}

// Mulai polling interval 2.5 detik
setInterval(fetchQueueData, 2500);
fetchQueueData();
</script>

</body>
</html>
