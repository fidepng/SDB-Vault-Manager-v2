<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Surat Pemberitahuan SDB {{ $unit->nomor_sdb }}</title>
    <style>
        body {
            font-family: serif;
            /* Font formal mirip Times New Roman */
            font-size: 14px;
            line-height: 1.6;
            color: #000;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        .header-logo {
            width: 80px;
            /* Sesuaikan ukuran logo */
            float: left;
        }

        .header-text {
            text-align: center;
            margin-left: 0;
            /* Ubah jika pakai logo */
        }

        .header-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }

        .header-address {
            font-size: 12px;
            margin: 0;
        }

        .content {
            margin-bottom: 50px;
        }

        .table-info {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }

        .table-info td {
            padding: 5px;
            vertical-align: top;
        }

        .footer {
            margin-top: 50px;
            width: 100%;
        }

        .signature {
            float: right;
            width: 250px;
            text-align: center;
        }

        .signature-line {
            margin-top: 80px;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }

        /* Utilitas Warna Status */
        .text-danger {
            color: #dc2626;
        }

        .text-warning {
            color: #d97706;
        }
    </style>
</head>

<body>

    {{-- KOP SURAT --}}
    <div class="header">
        {{-- TIPS: Gunakan public_path() untuk gambar di DomPDF --}}
        {{-- <img src="{{ public_path('images/logo-bank.png') }}" class="header-logo" alt="Logo Bank"> --}}

        <div class="header-text">
            <h1 class="header-title">BANK PT. CONTOH SEJAHTERA</h1>
            <p class="header-address">
                Jl. Jendral Sudirman No. 123, Jakarta Pusat<br>
                Telp: (021) 123-4567 | Email: cs@bankcontoh.co.id
            </p>
        </div>
        <div style="clear: both;"></div> {{-- Clear float --}}
    </div>

    {{-- INFO SURAT --}}
    <div style="margin-bottom: 20px;">
        <table width="100%">
            <tr>
                <td width="15%">Nomor</td>
                <td width="2%">:</td>
                <td>{{ $nomor_surat }}</td>
                <td width="30%" align="right">{{ $tanggal_cetak }}</td>
            </tr>
            <tr>
                <td>Perihal</td>
                <td>:</td>
                <td><strong>Pemberitahuan Jatuh Tempo SDB</strong></td>
                <td></td>
            </tr>
        </table>
    </div>

    {{-- ISI SURAT --}}
    <div class="content">
        <p>Kepada Yth,<br>
            <strong>Bapak/Ibu {{ $unit->nama_nasabah }}</strong><br>
            Di Tempat
        </p>

        <p>Dengan hormat,</p>

        <p>Terima kasih atas kepercayaan Bapak/Ibu menggunakan layanan Safe Deposit Box (SDB) kami.
            Berdasarkan data administrasi kami, kami informasikan bahwa masa sewa SDB Bapak/Ibu
            dengan rincian sebagai berikut:</p>

        <table class="table-info">
            <tr>
                <td width="30%"><strong>Nomor SDB</strong></td>
                <td width="5%">:</td>
                <td>{{ $unit->nomor_sdb }} (Tipe {{ $unit->tipe }})</td>
            </tr>
            <tr>
                <td><strong>Tanggal Jatuh Tempo</strong></td>
                <td>:</td>
                <td class="{{ $unit->status === 'lewat_jatuh_tempo' ? 'text-danger' : '' }}">
                    <strong>{{ \Carbon\Carbon::parse($unit->tanggal_jatuh_tempo)->translatedFormat('d F Y') }}</strong>
                </td>
            </tr>
            <tr>
                <td><strong>Status Saat Ini</strong></td>
                <td>:</td>
                <td>
                    @if ($unit->status === 'lewat_jatuh_tempo')
                        <span class="text-danger">LEWAT JATUH TEMPO ({{ abs($unit->days_until_expiry) }} Hari)</span>
                    @else
                        <span class="text-warning">Akan Habis dalam {{ $unit->days_until_expiry }} Hari</span>
                    @endif
                </td>
            </tr>
        </table>

        <p>
            Mengingat masa sewa tersebut
            @if ($unit->status === 'lewat_jatuh_tempo')
                <span class="text-danger">telah berakhir</span>,
            @else
                akan segera berakhir,
            @endif
            kami mohon kesediaan Bapak/Ibu untuk segera melakukan perpanjangan sewa
            atau konfirmasi penutupan fasilitas SDB.
        </p>

        <p>Demikian pemberitahuan ini kami sampaikan. Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.</p>
    </div>

    {{-- TANDA TANGAN --}}
    <div class="footer">
        <div class="signature">
            <p>Hormat Kami,<br>Pejabat Bank</p>

            {{-- Spacer untuk TTD basah --}}
            <div class="signature-line">{{ auth()->user()->name }}</div>
            <p style="margin:0; font-size:10px;">
                {{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Administrator' }}</p>
        </div>
    </div>

</body>

</html>
