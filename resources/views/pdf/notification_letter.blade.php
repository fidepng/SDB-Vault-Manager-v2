<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Surat Pemberitahuan - SDB {{ $unit->nomor_sdb }}</title>
    <style>
        /* ====================================
           GLOBAL STYLES
           ==================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
            padding: 15mm 20mm;
        }

        /* ====================================
           HEADER SECTION (KOP SURAT CENTER)
           ==================================== */
        .header {
            border-bottom: 2px solid #003d7a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo-cell {
            width: 15%;
            /* Logo di kiri */
            vertical-align: middle;
            text-align: left;
        }

        .header-logo-cell img {
            max-width: 80px;
            /* Sesuaikan ukuran logo crop */
            height: auto;
        }

        .header-text-cell {
            width: 85%;
            /* Teks mengambil sisa ruang */
            vertical-align: middle;
            text-align: center;
            /* [FIX] TEXT CENTER */
            padding-right: 15%;
            /* Trik visual agar teks benar-benar di tengah halaman (mengimbangi lebar logo di kiri) */
        }

        .bank-name {
            font-size: 14pt;
            font-weight: bold;
            color: #003d7a;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .bank-tagline {
            font-size: 8pt;
            color: #e63323;
            font-style: italic;
            margin-bottom: 5px;
        }

        .bank-address {
            font-size: 8pt;
            color: #555;
            line-height: 1.3;
        }

        /* ====================================
           DOCUMENT INFO
           ==================================== */
        .doc-info {
            margin: 15px 0;
            font-size: 10pt;
        }

        .doc-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-info td {
            padding: 2px 0;
            vertical-align: top;
        }

        .doc-info .label {
            width: 12%;
        }

        .doc-info .colon {
            width: 2%;
        }

        .doc-info .value {
            width: 56%;
        }

        .doc-info .date {
            width: 30%;
            text-align: right;
        }

        /* ====================================
           CONTENT & INFO BOX
           ==================================== */
        .content {
            margin: 20px 0;
            text-align: justify;
        }

        .content p {
            margin-bottom: 10px;
        }

        .recipient {
            margin-bottom: 15px;
            font-weight: bold;
        }

        .info-box {
            border: 1px solid #ddd;
            border-left: 4px solid #003d7a;
            background-color: #fbfbfb;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
            border-bottom: 1px dashed #eee;
        }

        .info-table tr:last-child td {
            border-bottom: none;
        }

        .info-table .label {
            width: 30%;
            color: #444;
        }

        .info-table .colon {
            width: 2%;
        }

        .info-table .value {
            width: 68%;
            font-weight: bold;
            color: #000;
        }

        /* Status Badges - Tanpa Emoji */
        .status-danger {
            color: #dc2626;
            font-weight: bold;
        }

        .status-warning {
            color: #d97706;
            font-weight: bold;
        }

        /* ====================================
           FOOTER & SIGNATURE
           ==================================== */
        .contact-info {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 12px;
            margin: 15px 0;
            font-size: 9pt;
        }

        .signature-section {
            margin-top: 30px;
            text-align: right;
        }

        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }

        .signature-space {
            height: 60px;
        }

        .signature-name {
            font-weight: bold;
            border-top: 1px solid #ccc;
            padding-top: 5px;
            margin-top: 5px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 8pt;
            color: #999;
            text-align: center;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo-cell">
                    @if ($logo_base64)
                        <img src="{{ $logo_base64 }}" alt="Logo BTN">
                    @else
                        <b>BANK BTN</b>
                    @endif
                </td>
                <td class="header-text-cell">
                    <div class="bank-name">{{ $bank['name'] }}</div>
                    <div class="bank-address">
                        {{ $bank['address'] }} | Telp: {{ $bank['phone'] }}<br>
                        Email: {{ $bank['email'] }} | Web: {{ $bank['website'] }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- DOCUMENT INFO --}}
    <div class="doc-info">
        <table>
            <tr>
                <td class="label">Nomor</td>
                <td class="colon">:</td>
                <td class="value">{{ $nomor_surat }}</td>
                <td class="date">{{ $tanggal_cetak }}</td>
            </tr>
            <tr>
                <td class="label">Perihal</td>
                <td class="colon">:</td>
                <td class="value"><strong>Pemberitahuan Jatuh Tempo SDB</strong></td>
                <td class="date"></td>
            </tr>
        </table>
    </div>

    {{-- CONTENT --}}
    <div class="content">

        <div class="recipient">
            Kepada Yth.<br>
            {{ $unit->nama_nasabah }}<br>
            Penyewa Safe Deposit Box<br>
            Di Tempat
        </div>

        <p>Dengan hormat,</p>

        <p>
            Terima kasih atas kepercayaan Bapak/Ibu menggunakan layanan <strong>Safe Deposit Box (SDB)</strong> Bank
            BTN.
        </p>

        {{-- INFO BOX --}}
        <div class="info-box">
            <table class="info-table">
                <tr>
                    <td class="label">Nomor SDB</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $unit->nomor_sdb }} <span style="font-weight:normal; color:#666;">(Tipe
                            {{ $unit->tipe }})</span></td>
                </tr>
                <tr>
                    <td class="label">Jatuh Tempo</td>
                    <td class="colon">:</td>
                    <td class="value">
                        {{ \Carbon\Carbon::parse($unit->tanggal_jatuh_tempo)->translatedFormat('d F Y') }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="colon">:</td>
                    <td class="value">
                        {{-- [FIX] Menghapus emoji ⚠ dan ⏰ --}}
                        @if ($is_overdue)
                            <span class="status-danger">TELAH LEWAT {{ $days_info }} HARI</span>
                        @else
                            <span class="status-warning">Akan jatuh tempo dalam {{ $days_info }} hari</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <p>
            Masa sewa SDB Bapak/Ibu
            @if ($is_overdue)
                <span style="color: #dc2626; font-weight: bold;">telah berakhir</span>.
            @else
                <strong>akan segera berakhir</strong>.
            @endif
            Mohon kesediaan Bapak/Ibu untuk segera melakukan <strong>Perpanjangan Sewa</strong> atau konfirmasi
            penutupan layanan.
        </p>

        <p>
            Untuk menghindari hal-hal yang tidak diinginkan terkait keamanan barang yang tersimpan, kami mohon perhatian
            dan tindak lanjut segera.
        </p>

        {{-- CONTACT INFO --}}
        <div class="contact-info">
            <strong>Butuh Bantuan?</strong><br>
            Silakan hubungi <strong>{{ $contact_person }}</strong> ({{ $contact_position }}) di Telepon:
            {{ $bank['phone'] }}.
        </div>

        <p>
            Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.
        </p>
    </div>

    {{-- SIGNATURE --}}
    <div class="signature-section">
        <div class="signature-box">
            <p>Hormat Kami,</p>
            <p style="font-weight: bold; margin-bottom: 5px;">PT. Bank Tabungan Negara</p>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $contact_person }}</div>
            <div class="signature-position">{{ $contact_position }}</div>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        Dicetak otomatis oleh sistem SDB Vault Manager pada {{ now()->format('d/m/Y H:i') }}.<br>
        Ref: {{ $unit->nomor_sdb }}/{{ now()->timestamp }}
    </div>

</body>

</html>
