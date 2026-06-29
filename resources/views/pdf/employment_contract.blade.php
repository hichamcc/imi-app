<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employment Agreement — {{ $person->full_name }}</title>
    <style>
        @@page { margin: 22mm 22mm 22mm 22mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #000; line-height: 1.45; }
        h1 { font-size: 16pt; text-align: center; font-weight: bold; text-decoration: underline; margin: 0 0 18pt; }
        h2 { font-size: 11pt; font-weight: bold; text-decoration: underline; margin: 14pt 0 6pt; }
        p { margin: 4pt 0; text-align: justify; }
        .clause { margin: 6pt 0 6pt 0; padding-left: 28pt; text-indent: -22pt; }
        .sub { margin: 4pt 0 4pt 28pt; padding-left: 24pt; text-indent: -20pt; }
        .party { margin: 6pt 0 6pt 24pt; }
        ol.alpha { list-style: none; padding-left: 22pt; }
        ol.alpha > li { margin-bottom: 8pt; }
        .center { text-align: center; }
        .sig { width: 100%; margin-top: 28pt; }
        .sig td { width: 50%; vertical-align: top; padding-right: 16pt; }
        .sig .label { font-weight: bold; }
        .notes-box { margin-top: 18pt; padding: 10pt 12pt; border: 1pt solid #999; background: #fafafa; }
        .employee-data { margin-top: 30pt; }
        .employee-data .heading { font-weight: bold; margin-bottom: 4pt; }
    </style>
</head>
<body>

<h1>EMPLOYMENT AGREEMENT</h1>

<p>
    This Employment Agreement (hereinafter referred to as the “<strong>Agreement</strong>”) is made and entered into on this
    <strong>{{ now()->format('d F Y') }}</strong> by and between:
</p>

<h2>PARTIES</h2>

<ol class="alpha">
    <li>
        <strong>(a) {{ strtoupper($company['name'] ?? 'PWR PERFECT WORKER RECRUITMENT SERVICES LIMITED') }}</strong>, a company duly incorporated and registered under the laws of the Republic of Cyprus with Registration No.
        <strong>{{ $company['registration'] ?? 'HE467938' }}</strong>, having its registered office at
        {{ $company['address_line'] ?? '30 Costa Anaxagorou & Limassol Avenue' }},
        {{ trim(($company['post_code'] ?? '2014') . ' ' . ($company['city'] ?? 'Nicosia')) }},
        {{ $company['country'] ?? 'Cyprus' }}, duly represented by its authorised director
        (hereinafter referred to as the <strong>"Employer"</strong> or the <strong>"Company"</strong>)
    </li>
    <li>and</li>
    <li>
        <strong>(b) {{ strtoupper($person->full_name) }}</strong>, holder of ID/Passport number ID:
        <strong>{{ $person->document_number ?? '—' }}</strong> residing at
        <strong>{{ trim(
            ($person->address_street ?? '') . ', ' .
            ($person->address_post_code ?? '') . ' ' .
            ($person->address_city ?? '') .
            ($person->address_country ? ', ' . $person->address_country : ''),
            ', ') }}</strong>.
        (hereinafter referred to as the “<strong>Employee</strong>”).
    </li>
</ol>

<p>
    The Employer and the Employee are hereinafter individually referred to as the <strong>“Party”</strong> and collectively referred to as the <strong>“Parties”</strong>.
</p>

<h2>AGREED TERMS</h2>

<h2>1. Appointment</h2>
<p class="clause">1.1. The Employer employs the Employee as a professional driver and the Employee accepts such employment subject to the terms of this Agreement.</p>
<p class="clause">1.2. The employment shall commence on <strong>{{ $person->contract_start_date?->format('d F Y') ?? now()->format('d F Y') }}</strong> and shall continue for an indefinite duration unless terminated in accordance with this Agreement and applicable law.</p>
<p class="clause">1.3. The first six (6) months of employment shall constitute a probationary period. During probation, either Party may terminate the employment at any time with 7 days. During the probationary period the Employee's performance and suitability for continued employment will be monitored.</p>

<h2>2. Duties and Responsibilities</h2>
<p class="clause">2.1. The Employee is employed as a driver for the transport of goods within Europe, for clients and partners of the Company, including but not limited to WETTER, WTT Sp.z.o.o. and NTT Sp.z.o.o., W.T. Transport Sp.z.o.o</p>
<p class="clause">2.2. The Employee's duties shall include, but not be limited to:</p>
<p class="sub">2.2.1. driving commercial vehicles as instructed by the Employer;</p>
<p class="sub">2.2.2. loading, unloading and delivering goods safely and on time;</p>
<p class="sub">2.2.3. keeping the assigned vehicle clean, secure and roadworthy;</p>
<p class="sub">2.2.4. maintaining all required transport, tachograph, delivery and customs records;</p>
<p class="sub">2.2.5. promptly reporting any accident, damage, delay, loss, defect or legal issue;</p>
<p class="sub">2.2.6. complying with all road transport, traffic and safety laws applicable in the countries of operation; and</p>
<p class="sub">2.2.7. carrying out any other duties reasonably related to the Employee's position.</p>
<p class="clause">2.3. The Employee confirms that he/she holds and shall maintain throughout the employment all licenses, permits, qualifications and certificates required by law, including a valid driving license, Certificate of Professional Competence (hereinafter referred to as “CPC certificate”) and tachograph card where applicable.</p>
<p class="clause">2.4. The Employee confirms that he/she is medically fit to perform the duties of a professional driver and shall inform the Employer immediately of any condition affecting fitness to drive.</p>

<h2>3. Place of Work</h2>
<p class="clause">3.1. The Employee's work is mobile in nature. The primary area of work shall be within the European Union, as directed by the Employer. The Employer's registered office is in Nicosia, Cyprus.</p>
<p class="clause">3.2. The Employee may be required to work in or travel through any country within the operational scope of the Employer's logistics business.</p>

<h2>4. Salary and Remuneration</h2>
<p class="clause">4.1. The Employee's basic gross salary for a standard working week of 38 hours shall be €1,100 (One Thousand One Hundred Euro) per month gross, payable on the last day of each calendar month. The Employee shall be entitled to twelve (12) monthly salaries. This amount is above the national minimum wage applicable in the Republic of Cyprus pursuant to the relevant Ministerial Decree, as increased from time to time. In the event that the applicable national minimum wage at any point exceeds the basic salary set out herein, the salary shall be automatically adjusted to meet the applicable minimum wage.</p>
<p class="clause">4.2. In addition to the basic salary, the Employee may receive variable remuneration based on working hours, production kilometers and operational performance, in accordance with the Employer's remuneration structure as communicated from time to time. The total monthly remuneration may reach up to <strong>€2,300</strong> (Four Thousand Euro) net, subject to the applicable calculation method working 2 weeks on a month. Start salary 1 month €2,000 (2 WEEKS). If working 4 weeks + , salary become €1025/week.</p>
<p class="clause">4.3. Overtime worked beyond the standard 38-hour week shall be compensated as follows:</p>
<p class="sub">4.3.1. 1.5 times the normal hourly rate for overtime worked on normal working days.</p>
<p class="clause">4.4. The Employer shall provide a pay slip showing salary, deductions and any additional payments.</p>

<h2>5. Tax, Social Insurance and Healthcare Contributions</h2>
<p class="clause">5.1. Where salary is earned and taxable in the Republic of Cyprus, the Employer shall apply all deductions required by Cyprus law, including:</p>
<p class="sub">5.1.1. Income tax under the Income Tax Law of 2002 (Law 118(I)/2002) (as amended);</p>
<p class="sub">5.1.2. Social Insurance contributions under the Social Insurance Law, Cap. 59 (as amended), including employee and employer contributions at the applicable rates;</p>
<p class="sub">5.1.3. General Healthcare System (GHS/GESY) contributions under the General Healthcare System Law 89(I)/2001 (as amended), at the applicable employer and employee rates;</p>
<p class="sub">5.1.4. and any other mandatory Cyprus payroll contributions, including Redundancy Fund, Social Cohesion Fund, Human Resources Development Fund and Holiday Fund contributions, where applicable.</p>
<p class="clause">5.2. Where salary is earned and taxable outside Cyprus, tax deductions, social security contributions and similar compulsory payments shall be dealt with in accordance with the laws of the country in which the relevant tax or social security liability arises. In such case, the Employee shall be responsible for registration, declaration and payment in that country, and the Employer shall provide reasonable assistance and supporting payroll documentation.</p>
<p class="clause">5.3. The Parties shall cooperate in good faith in relation to any applicable double taxation agreement and any applicable EU rules on coordination of social security systems, including Regulation (EC) No. 883/2004 and its implementing rules, where relevant.</p>
<p class="clause">5.4. If the Employee's tax residency, social insurance position or habitual place of work changes, the Parties shall review and amend the relevant payroll and compliance arrangements accordingly.</p>

<h2>6. Working Time, Driving Hours and Rest</h2>
<p class="clause">6.1. The Employee's normal contractual working time is 38 hours per week.</p>
<p class="clause">6.2. As a mobile road transport worker, the Employee shall comply at all times with:</p>
<p class="sub">6.2.1. Regulation (EC) No. 561/2006 on driving times and rest periods; and</p>
<p class="sub">6.2.2. the applicable EU and Cyprus rules on the organization of working time for road transport workers.</p>
<p class="clause">6.3. Without prejudice to stricter mandatory rules, the Employee acknowledges that the following key limits apply:</p>
<p class="sub">6.3.1. daily driving time shall not exceed 9 hours, save that it may be extended to 10 hours no more than twice in one week;</p>
<p class="sub">6.3.2. weekly driving time shall not exceed 56 hours;</p>
<p class="sub">6.3.3. total driving time over any two consecutive weeks shall not exceed 90 hours;</p>
<p class="sub">6.3.4. after 4.5 hours of driving, the Employee shall take a break of at least 45 minutes (which may be split in accordance with the law);</p>
<p class="sub">6.3.5. the Employee shall observe all legally required daily and weekly rest periods.</p>
<p class="clause">6.4. The Employee shall maintain proper tachograph records and shall not tamper with, interfere with or misuse tachograph equipment. Any serious breach of driving time, rest period or tachograph rules may constitute grounds for disciplinary action or dismissal.</p>
<p class="clause">6.5. The Employer shall organize work in a manner that permits compliance with the applicable legal limits.</p>

<h2>7. Annual Leave and Public Holidays</h2>
<p class="clause">7.1. The Company's holiday year runs between 1st January and 31st December of each year. If the Employee's employment commences or terminates during the holiday year, their entitlement to paid annual leave shall be calculated on a pro-rata basis, rounded up to the nearest whole day.</p>
<p class="clause">7.2. The Employee shall be entitled to 21 working days of paid annual leave in each holiday year (calculated on a pro rata basis by reference to a full-time entitlement of 21 days of annual leave each year), in addition to the official public holidays within the Republic of Cyprus.</p>
<p class="clause">7.3. The Employee is encouraged to take their full entitlement within the relevant holiday year. Carry-over of unused paid annual leave to the following year may only occur with prior approval from the Company.</p>
<p class="clause">7.4. The Employee shall not be entitled to any payment in lieu of accrued but unused annual leave days, except upon termination of employment, in which case payment will be made in respect of any outstanding statutory leave entitlement accrued up to the termination date.</p>
<p class="clause">7.5. All paid annual leave days must be requested two weeks in advance and approved by the Employee's manager, taking into account the Company's operational requirements.</p>
<p><u>All vacation is included and settled in the monthly payment.</u></p>

<h2>8. Sickness and Medical Fitness</h2>
<p class="clause">8.1. The Employee shall notify the Employer as soon as reasonably possible in the event of sickness or incapacity. Where absence extends beyond three (3) consecutive working days, the Employer may require a medical certificate.</p>
<p class="clause">8.2. Any entitlement to sickness benefit shall be governed by the applicable provisions of the Social Insurance Law, Cap. 59 and any other applicable law.</p>
<p class="clause">8.3. The Employee shall at all times remain medically fit to perform driving duties and shall immediately notify the Employer of any suspension, restriction or issue affecting his/her ability lawfully and safely to drive.</p>

<h2>9. Confidentiality, Loyalty and Conduct</h2>
<p class="clause">9.1. The Employee shall act loyally and in good faith towards the Employer and shall avoid any conflict of interest.</p>
<p class="clause">9.2. The Employee shall keep strictly confidential all non-public information relating to the Employer, its clients, cargo, routes, pricing, business methods, operations and affairs that comes to the Employee's knowledge in the course of employment.</p>
<p class="clause">9.3. The confidentiality obligations under this Agreement shall continue for three (3) years after termination of employment, or for so long as the relevant information remains confidential, whichever is longer.</p>
<p class="clause">9.4. The Employee shall not consume alcohol, drugs or any substance impairing driving ability while on duty or otherwise responsible for a Company vehicle, and shall comply with all lawful instructions, traffic laws and professional conduct requirements.</p>

<h2>10. Intellectual Property</h2>
<p class="clause">10.1. Any work product, operational materials, reports, records, route plans, databases, documents or other materials created, compiled or developed by the Employee in the course of employment shall belong exclusively to the Employer.</p>
<p class="clause">10.2. To the extent necessary, the Employee hereby assigns to the Employer all intellectual property rights in such materials, for the full period of legal protection.</p>

<h2>11. Data Protection</h2>
<p class="clause">11.1. The Employee shall process personal data only as necessary for the performance of duties and strictly in accordance with the Employer's instructions, Regulation (EU) 2016/679 (GDPR) and Law 125(I)/2018 of Cyprus.</p>
<p class="clause">11.2. The Employee shall not disclose personal data to any unauthorized person and shall take appropriate steps to protect such data from loss, misuse or unauthorized access.</p>
<p class="clause">11.3. The Employee acknowledges that the Employer may use lawful GPS, tachograph and operational monitoring systems for fleet management, safety, compliance and business protection purposes, subject to applicable data protection law.</p>

<h2>12. Vehicle, Equipment and Expenses</h2>
<p class="clause">12.1. The Employer shall provide the vehicle and such equipment as are reasonably necessary for the performance of the Employee's duties.</p>
<p class="clause">12.2. The Employee shall use the vehicle and equipment carefully, lawfully and only for authorized purposes, unless otherwise agreed in writing.</p>
<p class="clause">12.3. The Employee shall immediately report any accident, damage, defect, fine, penalty, loss of cargo or legal incident relating to the vehicle or the performance of duties.</p>
<p class="clause">12.4. Reasonable work-related expenses properly incurred in the course of employment shall be reimbursed by the Employer, subject to prior approval where required and submission of valid supporting receipts.</p>

<h2>13. Health, Safety and Harassment</h2>
<p class="clause">13.1. The Employer shall comply with all applicable occupational safety and health legislation, including the Safety and Health at Work Laws of 1996 to 2026 (Law 89(I)/1996, as amended), and applicable EU directives, to ensure a safe working environment. The Employer shall provide the Employee with appropriate health and safety training from the first day of employment, in a language understandable to the Employee, and shall maintain the Employee on full pay and benefits during any such training period, in accordance with the amendments to the Safety and Health at Work Law enacted in 2026.</p>
<p class="clause">13.2. The Employee shall comply with all health and safety procedures and shall take reasonable care of his/her own safety and that of others.</p>
<p class="clause">13.3. The Parties acknowledge that the workplace must be free from violence and harassment, in accordance with the Law on the Prevention and Combating of Violence and Harassment in the Workplace 42(I)/2025.</p>

<h2>14. Termination</h2>
<p class="clause">14.1. Either Party may terminate this Agreement by giving one (1) month's written notice, expiring at the end of a calendar month, subject always to the minimum rights under the Termination of Employment Law, Law 24/1967 (as amended).</p>
<p class="clause">14.2. The Employer may terminate the employment summarily, without notice, in the event of serious misconduct or material breach, including but not limited to:</p>
<p class="sub">14.2.1. driving under the influence of alcohol or drugs;</p>
<p class="sub">14.2.2. serious or repeated breach of driving time or tachograph rules;</p>
<p class="sub">14.2.3. gross negligence;</p>
<p class="sub">14.2.4. unjustified absence from work;</p>
<p class="sub">14.2.5. serious breach of law;</p>
<p class="sub">14.2.6. conduct causing serious damage to the Employer's reputation or business.</p>
<p class="clause">14.3. If the Employee loses or ceases to hold a valid driving license, CPC, tachograph card or other essential authorization, and is thereby unable to perform the role, the Employer may terminate the employment in accordance with the law and the circumstances of the case.</p>
<p class="clause">14.4. Upon termination, the Employee shall immediately return all Company property, documents, equipment, fuel cards, keys and any vehicle in his/her possession.</p>
<p class="clause">14.5. The Employer shall pay all accrued salary and other lawful entitlements due up to the termination date.</p>

<h2>15. Disciplinary Matters and Parallel Employment</h2>
<p class="clause">15.1. In disciplinary matters, the Employee shall be informed of the issue and given a reasonable opportunity to present his/her position before a final decision is taken.</p>
<p class="clause">15.2. In accordance with Transparent and Predictable Working Conditions Law of 2023 (Law 25(I)/2023), the Employee may engage in parallel employment outside the working schedule under this Agreement, provided that this does not create a conflict of interest, breach confidentiality, affect the Employee's fitness for work, or cause breach of driving time and rest rules.</p>

<h2>16. Governing Law and General Provisions</h2>
<p class="clause">16.1. This Agreement shall be governed by and construed in accordance with the laws of the Republic of Cyprus.</p>
<p class="clause">16.2. Any dispute arising out of or in connection with this Agreement shall be subject to the jurisdiction of the Courts of the Republic of Cyprus, without prejudice to any overriding mandatory protections that may apply under the law of the country where the Employee habitually works.</p>
<p class="clause">16.3. Any amendment to this Agreement shall be made in writing and signed by both Parties.</p>
<p class="clause">16.4. The Employer shall register the essential terms of employment in accordance with the applicable Cyprus law and the ERGANI system requirements.</p>
<p class="clause">16.5. If any provision of this Agreement is found invalid or unenforceable, the remaining provisions shall remain in full force and effect.</p>
<p class="clause">16.6. This Agreement is executed in two original copies, one for each Party.</p>

@if(!empty($contractNotes))
    <h2>Additional Notes</h2>
    <div class="notes-box">
        <p style="white-space: pre-wrap; margin: 0;">{{ $contractNotes }}</p>
    </div>
@endif

<p style="margin-top: 26pt;"><strong>IN WITNESS WHEREOF</strong>, the Parties have executed this Agreement as of the date first written above.</p>

<table class="sig">
    <tr>
        <td>
            <p class="label">For and on behalf of the Company:</p>
            <p style="margin-top: 22pt;">Name: {{ $company['name'] ?? 'PWR WORK' }}</p>
            <p style="margin-top: 18pt;">Signature: ____________________________</p>
        </td>
        <td>
            <p class="label">The Employee:</p>
            <p style="margin-top: 22pt;">Name: <strong>{{ $person->full_name }}</strong></p>
            <p style="margin-top: 18pt;">Signature: ____________________________</p>
        </td>
    </tr>
</table>

<div class="employee-data">
    <div class="heading">Employee data:</div>
    <p style="margin: 2pt 0;">
        {{ $person->full_name }}@if($person->address_street || $person->address_city) — {{ trim(
            ($person->address_street ?? '') . ', ' .
            ($person->address_post_code ?? '') . ' ' .
            ($person->address_city ?? '') .
            ($person->address_country ? ', ' . $person->address_country : ''),
            ', ') }}@endif
    </p>
    <p style="margin: 2pt 0;">
        Salary IBAN: <strong>{{ $person->bank_iban ?? '—' }}</strong>@if($person->bank_swift) &nbsp;&nbsp; SWIFT: <strong>{{ $person->bank_swift }}</strong>@endif
    </p>
</div>

</body>
</html>
