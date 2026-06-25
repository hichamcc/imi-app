<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employment Contract — {{ $person->full_name }}</title>
    <style>
        @@page { margin: 30mm 22mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111; line-height: 1.55; }
        h1 { font-size: 18pt; margin: 0 0 4pt; text-align: center; letter-spacing: 1px; }
        h2 { font-size: 12pt; margin: 18pt 0 6pt; border-bottom: 1px solid #888; padding-bottom: 2pt; }
        .muted { color: #555; }
        .right { text-align: right; }
        .header { margin-bottom: 18pt; }
        .header .company { font-weight: bold; font-size: 12pt; }
        .header .meta { color: #555; font-size: 10pt; }
        table.kv { width: 100%; border-collapse: collapse; margin-top: 6pt; }
        table.kv td { padding: 3pt 4pt; vertical-align: top; }
        table.kv td.label { width: 38%; color: #555; }
        ol.terms li { margin-bottom: 6pt; }
        .sig-grid { width: 100%; margin-top: 40pt; }
        .sig-grid td { width: 50%; padding-top: 30pt; border-top: 1px solid #333; vertical-align: top; font-size: 10pt; }
        .footer { position: fixed; bottom: -15mm; left: 0; right: 0; text-align: center; color: #888; font-size: 9pt; }
    </style>
</head>
<body>

<div class="header">
    <table style="width:100%">
        <tr>
            <td>
                <div class="company">{{ $company['name'] ?? '' }}</div>
                @if($company['registration'] ?? null)
                    <div class="meta">{{ __('Company Registration No') }}: {{ $company['registration'] }}</div>
                @endif
                @if($company['address_line'] ?? null)
                    <div class="meta">{{ $company['address_line'] }}</div>
                @endif
                <div class="meta">
                    {{ trim(($company['post_code'] ?? '') . ' ' . ($company['city'] ?? '')) }}
                    @if($company['country'] ?? null), {{ $company['country'] }}@endif
                </div>
                @if($company['phone'] ?? null)<div class="meta">{{ __('Phone') }}: {{ $company['phone'] }}</div>@endif
                @if($company['email'] ?? null)<div class="meta">{{ __('Email') }}: {{ $company['email'] }}</div>@endif
            </td>
            <td class="right">
                <div class="meta">{{ __('Date') }}: {{ now()->format('d/m/Y') }}</div>
                <div class="meta">{{ __('Document') }}: {{ __('Employment Contract') }}</div>
            </td>
        </tr>
    </table>
</div>

<h1>{{ __('EMPLOYMENT CONTRACT') }}</h1>
<p class="muted" style="text-align:center; margin-top:0;">{{ __('Between the Employer and the Employee identified below') }}</p>

<h2>{{ __('1. Parties') }}</h2>
<p>
    <strong>{{ __('Employer') }}:</strong> {{ $company['name'] ?? '—' }}@if($company['registration'] ?? null), {{ __('Reg. No') }} {{ $company['registration'] }}@endif,
    {{ trim(($company['address_line'] ?? '') . ', ' . ($company['post_code'] ?? '') . ' ' . ($company['city'] ?? '') . ($company['country'] ? ', ' . $company['country'] : ''), ', ') }}
    (hereinafter the “Employer”).
</p>
<p>
    <strong>{{ __('Employee') }}:</strong> {{ $person->full_name }},
    @if($person->date_of_birth) {{ __('born') }} {{ $person->date_of_birth->format('d/m/Y') }}, @endif
    @if($person->document_type){{ $person->document_type }}@endif
    @if($person->document_number) {{ __('no.') }} {{ $person->document_number }}@endif
    @if($person->document_issuing_country) ({{ $person->document_issuing_country }})@endif,
    {{ __('residing at') }} {{ trim(($person->address_street ?? '') . ', ' . ($person->address_post_code ?? '') . ' ' . ($person->address_city ?? '') . ($person->address_country ? ', ' . $person->address_country : ''), ', ') }}
    (hereinafter the “Employee”).
</p>

<h2>{{ __('2. Position and Duties') }}</h2>
<p>
    {{ __('The Employer hires the Employee in the position of') }} <strong>{{ $person->position ?? 'Driver' }}</strong>.
    {{ __('The Employee shall perform the duties customarily associated with this position, in line with applicable transport regulations and the Employer\'s reasonable instructions.') }}
</p>

<h2>{{ __('3. Commencement and Duration') }}</h2>
<p>
    {{ __('This contract takes effect on') }}
    <strong>{{ $person->contract_start_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</strong>
    {{ __('and is concluded for an indefinite period, subject to termination as set out below.') }}
</p>

<h2>{{ __('4. Remuneration') }}</h2>
<p>
    {{ __('The gross monthly salary is') }}
    <strong>{{ $person->monthly_salary ? number_format($person->monthly_salary, 2) . ' EUR' : '— EUR' }}</strong>,
    {{ __('payable monthly into the Employee\'s designated bank account.') }}
    @if($person->bank_iban)
        {{ __('Bank details') }}: <strong>{{ $person->bank_iban }}</strong>@if($person->bank_swift) — {{ $person->bank_swift }}@endif.
    @endif
    {{ __('Per diem allowances, where applicable, are paid in accordance with the Employer\'s policy and applicable law.') }}
</p>

<h2>{{ __('5. Working Time') }}</h2>
<p>
    {{ __('Working time follows the rules applicable to international road transport, including EU Regulation (EC) No 561/2006 on driving times, breaks and rest periods, as well as any national rules applicable to the place of work.') }}
</p>

<h2>{{ __('6. Applicable Law') }}</h2>
<p>
    {{ __('This contract is governed by the law of') }} <strong>{{ $person->applicable_law ?? $company['country'] ?? '—' }}</strong>.
    {{ __('Any dispute arising out of or in connection with this contract shall be referred to the competent courts of the same jurisdiction.') }}
</p>

<h2>{{ __('7. Confidentiality and Data') }}</h2>
<p>
    {{ __('The Employee shall keep confidential all information of the Employer to which they have access. The Employer processes the Employee\'s personal data for the purposes of this employment relationship and in accordance with applicable data-protection law.') }}
</p>

<h2>{{ __('8. Termination') }}</h2>
<p>
    {{ __('Either party may terminate this contract subject to the notice period required by applicable law and the Employer\'s internal rules. The contract may be terminated immediately for cause as recognised by applicable law.') }}
</p>

<h2>{{ __('9. Entire Agreement') }}</h2>
<p>
    {{ __('This document constitutes the entire agreement between the parties and supersedes any prior agreements or understandings regarding its subject matter.') }}
</p>

<table class="sig-grid">
    <tr>
        <td>
            <strong>{{ __('For the Employer') }}</strong><br>
            {{ $company['name'] ?? '' }}<br><br>
            ____________________________
        </td>
        <td>
            <strong>{{ __('The Employee') }}</strong><br>
            {{ $person->full_name }}<br><br>
            ____________________________
        </td>
    </tr>
</table>

<div class="footer">
    {{ $company['name'] ?? '' }} — {{ __('Employment Contract') }} — {{ now()->format('d/m/Y') }}
</div>

</body>
</html>
