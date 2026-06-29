<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip — {{ $payslip->employee_name }}</title>
    <style>
        @@page { margin: 18mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 28pt; margin: 0; font-weight: bold; }
        .header { width: 100%; }
        .header td { vertical-align: top; }
        .right { text-align: right; }
        .muted { color: #555; font-size: 9pt; }
        hr.thick { border: none; border-top: 2px solid #111; margin: 10pt 0 18pt; }
        .meta { margin-top: 12pt; font-size: 10pt; }
        .meta div { margin-bottom: 2pt; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 18pt; }
        table.lines th, table.lines td { padding: 7pt 4pt; }
        table.lines thead th { font-size: 10pt; font-weight: bold; border-bottom: 1px solid #999; text-align: left; }
        table.lines thead th.right { text-align: right; }
        table.lines tbody td { border-bottom: 1px solid #eee; }
        table.lines tbody td.amount { text-align: right; }
        table.lines tr.net td { font-weight: bold; border-top: 1px solid #999; }
        .footer { margin-top: 30pt; }
        .notes { margin-top: 22pt; padding-top: 10pt; border-top: 1px solid #ccc; font-size: 9pt; color: #333; }
    </style>
</head>
<body>

<table class="header">
    <tr>
        <td><h1>Payslip</h1></td>
        <td class="right">
            <strong>{{ $company['name'] ?? '' }}</strong><br>
            @if($company['registration'] ?? null)<span class="muted">Company Registration No: {{ $company['registration'] }}</span><br>@endif
            @if($company['address_line'] ?? null)<span class="muted">{{ $company['address_line'] }}</span><br>@endif
            <span class="muted">{{ trim(($company['post_code'] ?? '') . ' ' . ($company['city'] ?? '')) }}@if($company['country'] ?? null), {{ $company['country'] }}@endif</span>
        </td>
    </tr>
</table>

<hr class="thick">

<div class="right"><strong>Generated Date:</strong> {{ ($payslip->generated_date ?? now())->format('d/m/Y') }}</div>

<div class="meta">
    <div><strong>Month:</strong> {{ $payslip->payroll_month->format('F Y') }}</div>
    <div><strong>Employee Name:</strong> {{ $payslip->employee_name }}</div>
    <div><strong>Position:</strong> {{ $payslip->position ?? 'Driver' }}</div>
</div>

<table class="lines">
    <thead>
        <tr>
            <th>Description</th>
            <th class="right">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Gross Salary</td>
            <td class="amount">{{ number_format($payslip->gross_salary, 2) }} {{ $payslip->currency === 'EUR' ? '€' : $payslip->currency }}</td>
        </tr>
        <tr>
            <td>Diems Money</td>
            <td class="amount">{{ number_format($payslip->per_diem, 2) }} {{ $payslip->currency === 'EUR' ? '€' : $payslip->currency }}</td>
        </tr>
        <tr>
            <td>Income Tax</td>
            <td class="amount">{{ number_format($payslip->income_tax, 2) }} {{ $payslip->currency === 'EUR' ? '€' : $payslip->currency }}</td>
        </tr>
        <tr>
            <td>Social Insurance</td>
            <td class="amount">{{ number_format($payslip->social_insurance, 2) }} {{ $payslip->currency === 'EUR' ? '€' : $payslip->currency }}</td>
        </tr>
        <tr>
            <td>GHS</td>
            <td class="amount">{{ number_format($payslip->ghs, 2) }} {{ $payslip->currency === 'EUR' ? '€' : $payslip->currency }}</td>
        </tr>
        <tr>
            <td>Other Deductions</td>
            <td class="amount">{{ number_format($payslip->other_deductions, 2) }} {{ $payslip->currency === 'EUR' ? '€' : $payslip->currency }}</td>
        </tr>
        <tr class="net">
            <td>Net Salary</td>
            <td class="amount">{{ number_format($payslip->net_salary, 2) }} {{ $payslip->currency === 'EUR' ? '€' : $payslip->currency }}</td>
        </tr>
        <tr>
            <td>Diems Money</td>
            <td class="amount">{{ number_format($payslip->per_diem, 2) }} {{ $payslip->currency === 'EUR' ? '€' : $payslip->currency }}</td>
        </tr>
    </tbody>
</table>

<div class="footer">
    <div><strong>Payment Date:</strong> {{ $payslip->payment_date->format('d/m/Y') }}</div>
    @if($payslip->bank_iban)
        <div><strong>Bank Details:</strong> {{ $payslip->bank_iban }}@if($payslip->bank_swift) -- {{ $payslip->bank_swift }}@endif</div>
    @endif
</div>

<div class="notes">
    <strong>Notes:</strong><br>
    - The employee is not a tax resident of Cyprus and does not perform duties within the Republic of Cyprus.<br>
    - No Cyprus taxes or social contributions are withheld for working outside Cyprus area.<br>
    - The employee is responsible for declaring and paying taxes in their country of tax residence, if required.
</div>

</body>
</html>
