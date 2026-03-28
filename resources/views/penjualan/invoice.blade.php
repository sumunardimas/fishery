<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice #{{ $trx->id_penjualan }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            padding: 24px 28px;
        }

        /* ── Header ── */
        .header {
            margin-bottom: 18px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #1a3c6e;
            letter-spacing: 0.5px;
        }

        .company-meta {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
            line-height: 1.5;
        }

        .divider {
            border: none;
            border-top: 2px solid #1a3c6e;
            margin: 10px 0 14px;
        }

        .divider-thin {
            border: none;
            border-top: 1px solid #ddd;
            margin: 10px 0;
        }

        /* ── Invoice title row ── */
        .title-row {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }

        .title-row .left {
            display: table-cell;
            vertical-align: top;
        }

        .title-row .right {
            display: table-cell;
            text-align: right;
            vertical-align: top;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #1a3c6e;
            letter-spacing: 1px;
        }

        .invoice-meta {
            font-size: 10px;
            color: #555;
            line-height: 1.6;
        }

        .invoice-meta strong {
            color: #1a1a1a;
        }

        /* ── Parties ── */
        .parties {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }

        .party {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .party-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #888;
            margin-bottom: 4px;
        }

        .party-name {
            font-size: 12px;
            font-weight: bold;
        }

        .party-detail {
            font-size: 10px;
            color: #555;
            line-height: 1.5;
        }

        /* ── Items table ── */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        table.items thead tr {
            background-color: #1a3c6e;
            color: #fff;
        }

        table.items thead th {
            padding: 6px 8px;
            font-size: 10px;
            font-weight: bold;
            text-align: left;
        }

        table.items thead th.right {
            text-align: right;
        }

        table.items tbody tr:nth-child(even) {
            background-color: #f5f7fb;
        }

        table.items tbody td {
            padding: 6px 8px;
            font-size: 11px;
            border-bottom: 1px solid #e8e8e8;
        }

        table.items tbody td.right {
            text-align: right;
        }

        /* ── Payment summary ── */
        .payment-block {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }

        .payment-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }

        .payment-right {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }

        .bank-box {
            background: #f5f7fb;
            border: 1px solid #dde3ee;
            border-radius: 4px;
            padding: 9px 11px;
        }

        .bank-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #888;
            margin-bottom: 4px;
        }

        .bank-name {
            font-size: 12px;
            font-weight: bold;
            color: #1a3c6e;
        }

        .bank-no {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .bank-an {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        table.summary {
            width: 100%;
            border-collapse: collapse;
        }

        table.summary td {
            padding: 4px 6px;
            font-size: 11px;
        }

        table.summary td.label {
            color: #555;
        }

        table.summary td.value {
            text-align: right;
            font-weight: bold;
        }

        table.summary tr.total-row td {
            border-top: 2px solid #1a3c6e;
            padding-top: 6px;
            font-size: 12px;
            color: #1a3c6e;
        }

        table.summary tr.piutang-row td {
            color: #c0392b;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .status-lunas {
            background: #d4edda;
            color: #155724;
        }

        .status-piutang {
            background: #fff3cd;
            color: #856404;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #aaa;
        }
    </style>
</head>

<body>

    {{-- ══ Company header ══ --}}
    <div class="header">
        <div class="company-name">TPI Sadeng</div>
        <div class="company-meta">
            Sadeng, Songbanyu, Girisubo, Gunung Kidul, DIY<br>
            Telp.: +62 822 2702 4502
        </div>
    </div>

    <hr class="divider">

    {{-- ══ Invoice title + meta ══ --}}
    <div class="title-row">
        <div class="left">
            <div class="invoice-title">INVOICE</div>
        </div>
        <div class="right">
            <div class="invoice-meta">
                <strong>No. Invoice</strong> &nbsp;INV-{{ str_pad($trx->id_penjualan, 5, '0', STR_PAD_LEFT) }}<br>
                <strong>Tanggal</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                {{ \Carbon\Carbon::parse($trx->tanggal_penjualan)->translatedFormat('d F Y') }}<br>
                <strong>Waktu</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                {{ $trx->created_at?->format('H:i') }} WIB
            </div>
        </div>
    </div>

    {{-- ══ From / To ══ --}}
    <div class="parties">
        <div class="party">
            <div class="party-label">Dari</div>
            <div class="party-name">TPI Sadeng</div>
            <div class="party-detail">
                Sadeng, Girisubo, Gunung Kidul<br>
                +62 822 2702 4502
            </div>
        </div>
        <div class="party">
            <div class="party-label">Kepada</div>
            <div class="party-name">{{ $trx->nama_customer_display }}</div>
        </div>
    </div>

    <hr class="divider-thin">

    {{-- ══ Items ══ --}}
    <table class="items">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="right">Qty (kg)</th>
                <th class="right">Harga / kg</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $trx->nama_ikan }}</td>
                <td class="right">{{ number_format($trx->berat, 2) }}</td>
                <td class="right">Rp {{ number_format($trx->harga_per_kg, 2, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($trx->total_harga, 2, ',', '.') }}</td>
            </tr>
            @if ($trx->keterangan && $trx->keterangan !== 'Transaksi POS penjualan ikan')
                <tr>
                    <td colspan="4" style="color:#666; font-size:10px; font-style:italic;">
                        Catatan: {{ $trx->keterangan }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- ══ Bank info + Payment summary ══ --}}
    <div class="payment-block">
        <div class="payment-left">
            <div class="bank-box">
                <div class="bank-label">Transfer Pembayaran</div>
                <div class="bank-name">Bank BRI</div>
                <div class="bank-no">0029-01-004071-56-4</div>
                <div class="bank-an">A.N. Uum Faida</div>
            </div>
        </div>
        <div class="payment-right">
            <table class="summary">
                <tr>
                    <td class="label">Total Tagihan</td>
                    <td class="value">Rp {{ number_format($trx->total_harga, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Bayar Tunai</td>
                    <td class="value">Rp {{ number_format($trx->bayar_tunai ?? 0, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Bayar Transfer</td>
                    <td class="value">Rp {{ number_format($trx->bayar_transfer ?? 0, 2, ',', '.') }}</td>
                </tr>
                @if (($trx->piutang ?? 0) > 0)
                    <tr class="piutang-row">
                        <td class="label">Piutang (A/R)</td>
                        <td class="value">Rp {{ number_format($trx->piutang, 2, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td class="label">Status</td>
                    <td class="value">
                        @if (($trx->status_pembayaran ?? 'lunas') === 'lunas')
                            <span class="status-badge status-lunas">LUNAS</span>
                        @else
                            <span class="status-badge status-piutang">PIUTANG</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <hr class="divider-thin">

    <div class="footer">
        Dokumen ini dibuat secara otomatis oleh sistem. Terima kasih atas kepercayaan Anda.
    </div>

</body>

</html>
