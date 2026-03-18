<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 15px;
            background: #fff;
        }

        /* Header */
        .invoice-header {
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-row {
            display: table;
            width: 100%;
        }

        .logo-section {
            display: table-cell;
            width: 25%;
            vertical-align: middle;
        }

        .logo-section img {
            max-width: 100px;
            height: auto;
        }

        .company-section {
            display: table-cell;
            width: 75%;
            text-align: right;
            vertical-align: middle;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .company-details {
            font-size: 10px;
            color: #333;
        }

        /* Invoice Title */
        .invoice-title {
            text-align: center;
            background: #000;
            color: #fff;
            padding: 8px;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            letter-spacing: 1px;
        }

        /* Info Sections */
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .info-block {
            display: table-cell;
            width: 50%;
            padding: 8px;
            vertical-align: top;
        }

        .info-block.bordered {
            border: 1px solid #000;
        }

        .info-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #ccc;
        }

        .info-row {
            margin-bottom: 3px;
            line-height: 1.4;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 110px;
            vertical-align: top;
        }

        .info-value {
            display: inline-block;
            vertical-align: top;
            max-width: calc(100% - 115px);
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .items-table thead {
            background: #000;
            color: #fff;
        }

        .items-table th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #000;
            font-size: 11px;
        }

        .items-table td {
            padding: 6px;
            border: 1px solid #000;
            font-size: 10px;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Summary */
        .summary-section {
            width: 380px;
            margin-left: auto;
            margin-top: 15px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }

        .summary-table .label {
            font-weight: bold;
            width: 60%;
            vertical-align: middle;
        }

        .summary-table .value {
            text-align: right;
            width: 40%;
            vertical-align: middle;
        }

        .summary-table .total-row td {
            background: #000;
            color: #fff;
            font-weight: bold;
            font-size: 13px;
            border: none;
            padding: 8px;
        }

        /* Discount Highlight */
        .discount-highlight {
            color: #d9534f;
            font-weight: bold;
        }

        /* Footer */
        .invoice-footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 2px solid #000;
            text-align: center;
            font-size: 10px;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 30px;
        }

        .signature-block {
            display: table-cell;
            width: 50%;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 180px;
            margin: 0 auto;
            padding-top: 4px;
            font-size: 10px;
        }

        /* Terms */
        .terms-section {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #000;
            background: #f9f9f9;
        }

        .terms-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .terms-list {
            font-size: 9px;
            padding-left: 18px;
        }

        .terms-list li {
            margin-bottom: 3px;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            background: #000;
            color: #fff;
            font-size: 9px;
            border-radius: 3px;
            margin-left: 5px;
            vertical-align: middle;
        }

        .badge-offer {
            background: #5cb85c;
        }

        .badge-promo {
            background: #f0ad4e;
        }

        /* Rupee Symbol */
        .rupee {
            font-family: Arial, sans-serif;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        {{-- Header --}}
        <div class="invoice-header">
            <div class="header-row">
                <div class="logo-section">
                    <img src="{{ public_path('assets/img/wd_logo.png') }}" alt="Company Logo">
                </div>
                <div class="company-section">
                    <div class="company-name">Wild Drum Beverages</div>
                    <div class="company-details">
                        123 Business Street, City, State - 123456<br>
                        Phone: +91 1234567890 | Email: info@company.com<br>
                        GSTIN: 22AAAAA0000A1Z5 | PAN: AAAAA0000A
                    </div>
                </div>
            </div>
        </div>

        {{-- Invoice Title --}}
        <div class="invoice-title">TAX INVOICE</div>

        {{-- Info Section --}}
        <div class="info-section">
            <div class="info-block bordered">
                <div class="info-title">Bill To:</div>
                <div class="info-row"><strong>{{ $order->store->name }}</strong></div>
                <div class="info-row">{{ $order->store->address }}</div>
                <div class="info-row">
                    {{ $order->store->city->name }}, {{ $order->store->state->name }} - {{ $order->store->pin_code }}
                </div>
                <div class="info-row">Contact: {{ $order->store->contact_number_1 }}</div>
                @if($order->store->email)
                    <div class="info-row">Email: {{ $order->store->email }}</div>
                @endif
            </div>

            <div class="info-block">
                <div class="info-row">
                    <span class="info-label">Invoice No:</span>
                    <span class="info-value"><strong>{{ $order->order_number }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value">{{ $order->created_at->format('d-M-Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Visit Date:</span>
                    <span class="info-value">{{ $order->visit->visit_date->format('d-M-Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sales Person:</span>
                    <span class="info-value">{{ $order->employee->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><span class="badge">{{ strtoupper($order->status) }}</span></span>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">S.No</th>
                    <th style="width: 40%;">Product Description</th>
                    <th style="width: 10%;" class="text-center">Qty</th>
                    <th style="width: 15%;" class="text-right">Unit Price</th>
                    <th style="width: 15%;" class="text-right">Discount</th>
                    <th style="width: 15%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item->product->name }}</strong><br>
                            <small>{{ $item->product->pCategory->name ?? '' }}</small>
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}
                        </td>
                        <td class="text-right">{{ number_format($item->discount, 2) }}
                        </td>
                        <td class="text-right">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="value">{{ number_format($order->subtotal, 2) }}</td>
                </tr>

                @if($order->offer_discount > 0)
                    <tr>
                        <td class="label">
                            Offer Discount
                            @if($order->offer)
                                <span class="badge badge-offer">{{ $order->offer->offer_percentage }}% OFF</span>
                            @endif
                        </td>
                        <td class="value discount-highlight">{{ number_format($order->offer_discount, 2) }}</td>
                    </tr>
                @endif

                @if($order->promocode_discount > 0)
                    <tr>
                        <td class="label">
                            Promocode ({{ $order->promocode }})
                            <span class="badge badge-promo">{{ number_format($order->promocode_discount_percentage, 2) }}%
                                OFF</span>
                        </td>
                        <td class="value discount-highlight">{{ number_format($order->promocode_discount, 2) }}</td>
                    </tr>
                @endif

                <tr>
                    <td class="label">Taxable Amount:</td>
                    <td class="value">{{ number_format($order->taxable_amount, 2) }}
                    </td>
                </tr>

                @if($order->cgst > 0)
                    <tr>
                        <td class="label">CGST (9%):</td>
                        <td class="value">{{ number_format($order->cgst, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">SGST (9%):</td>
                        <td class="value">{{ number_format($order->sgst, 2) }}</td>
                    </tr>
                @endif

                @if($order->igst > 0)
                    <tr>
                        <td class="label">IGST (18%):</td>
                        <td class="value">{{ number_format($order->igst, 2) }}</td>
                    </tr>
                @endif

                <tr class="total-row">
                    <td class="label">TOTAL AMOUNT:</td>
                    <td class="value">{{ number_format($order->total_amount, 2) }}
                    </td>
                </tr>
            </table>
        </div>

        {{-- Terms --}}
        <div class="terms-section">
            <div class="terms-title">Terms & Conditions:</div>
            <ul class="terms-list">
                <li>All disputes are subject to [City] jurisdiction only.</li>
                <li>Goods once sold will not be taken back.</li>
                <li>Payment terms: As per agreement.</li>
                <li>Interest @ 18% p.a. will be charged on delayed payments.</li>
            </ul>
        </div>

        {{-- Signature --}}
        <div class="signature-section">
            <div class="signature-block">
                <div style="margin-top: 40px;">
                    <div class="signature-line">Customer Signature</div>
                </div>
            </div>
            <div class="signature-block">
                <div style="margin-top: 40px;">
                    <div class="signature-line">Authorized Signatory</div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="invoice-footer">
            <strong>Thank you for your business!</strong><br>
            This is a computer-generated invoice and does not require a physical signature.
        </div>
    </div>
</body>

</html>