<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parses a bank-statement file (xlsx / xls / csv) into normalised rows.
 *
 * Expected headers (case-insensitive, in any order):
 *   Account Number | Period | Currency | Date | Description | Debit | Credit | Value Date | Balance
 *
 * Output row shape:
 *   [
 *     'row_index'         => int,
 *     'account_number'    => ?string,
 *     'currency'          => ?string,
 *     'date'              => ?'Y-m-d',
 *     'value_date'        => ?'Y-m-d',
 *     'description'       => ?string,
 *     'debit'             => float,
 *     'credit'            => float,
 *     'balance'           => ?float,
 *     'reference'         => ?string,   // e.g. HB260505070267
 *     'parsed_name'       => ?string,
 *     'looks_like_payroll'=> bool,
 *   ]
 */
class BankFileParser
{
    /**
     * Phrases that indicate a row is NOT payroll. Matched case-insensitively
     * against the description after the reference token.
     */
    private const NON_PAYROLL_KEYWORDS = [
        'TOTAL CHARGES', 'BANK CHARGES', 'COMMISSION', 'INTEREST',
        'AUDIT', 'TAX', 'VAT', 'INSURANCE', 'CONSULTANCY',
        'TRANSFER FEE', 'FEE', 'FX ',
    ];

    public function parse(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, false);
        if (empty($rows)) {
            return ['meta' => [], 'rows' => []];
        }

        // Find header row (first row with "Description" or "Debit" or "Credit")
        $headerRowIndex = null;
        $headers = null;
        foreach ($rows as $i => $row) {
            $lowered = array_map(fn ($c) => is_string($c) ? strtolower(trim($c)) : '', $row);
            if (in_array('description', $lowered, true) && in_array('debit', $lowered, true)) {
                $headerRowIndex = $i;
                $headers = $lowered;
                break;
            }
        }

        if ($headerRowIndex === null) {
            throw new \RuntimeException('Could not find a header row with "Description" and "Debit" columns.');
        }

        $colIdx = [];
        foreach ($headers as $i => $h) {
            $key = match (true) {
                str_contains($h, 'account') => 'account_number',
                $h === 'period' => 'period',
                $h === 'currency' => 'currency',
                $h === 'date' => 'date',
                $h === 'value date' => 'value_date',
                $h === 'description' => 'description',
                $h === 'debit' => 'debit',
                $h === 'credit' => 'credit',
                $h === 'balance' => 'balance',
                default => null,
            };
            if ($key) $colIdx[$key] = $i;
        }

        $out = [];
        $meta = ['account_number' => null, 'currency' => null];

        $rowIndex = 0;
        foreach ($rows as $i => $row) {
            if ($i <= $headerRowIndex) continue;

            $get = fn ($k) => isset($colIdx[$k]) ? ($row[$colIdx[$k]] ?? null) : null;

            $description = $get('description');
            $debit = (float) ($this->normalizeNumber($get('debit')) ?? 0);
            $credit = (float) ($this->normalizeNumber($get('credit')) ?? 0);

            // Skip blank rows
            if (empty($description) && $debit == 0 && $credit == 0) continue;

            // Capture top-level meta from first non-empty row
            if (!$meta['account_number'] && $get('account_number')) $meta['account_number'] = trim((string) $get('account_number'));
            if (!$meta['currency'] && $get('currency')) $meta['currency'] = trim((string) $get('currency'));

            [$reference, $parsedName] = $this->extractReferenceAndName($description);

            $looksLikePayroll = $this->looksLikePayroll($description, $debit, $credit, $parsedName);

            $out[] = [
                'row_index' => $rowIndex++,
                'account_number' => $get('account_number') ? trim((string) $get('account_number')) : null,
                'currency' => $get('currency') ? trim((string) $get('currency')) : null,
                'date' => $this->normalizeDate($get('date')),
                'value_date' => $this->normalizeDate($get('value_date')),
                'description' => $description ? trim((string) $description) : null,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $this->normalizeNumber($get('balance')),
                'reference' => $reference,
                'parsed_name' => $parsedName,
                'looks_like_payroll' => $looksLikePayroll,
            ];
        }

        return ['meta' => $meta, 'rows' => $out];
    }

    /**
     * Extract the transaction reference (e.g. HB260505070267) and the candidate
     * name from a description like "HB260505070267 VAKARIS KRETAVICIUS".
     */
    private function extractReferenceAndName(?string $description): array
    {
        if (!$description) return [null, null];

        $description = trim($description);
        $reference = null;
        $rest = $description;

        // Match leading token like HB.. / SK.. / a long alphanumeric reference
        if (preg_match('/^([A-Z]{2,}\d+)\s+(.*)$/i', $description, $m)) {
            $reference = strtoupper($m[1]);
            $rest = trim($m[2]);
        }

        // If the rest looks like a person name (letters + spaces, optionally hyphen/apostrophe), use it.
        // Filter out obvious vendor-y / fee-y descriptions.
        $candidate = $rest;
        if ($this->descriptionLooksNonPayroll($candidate)) {
            $candidate = null;
        }

        // Normalise spacing
        if ($candidate) {
            $candidate = preg_replace('/\s+/', ' ', $candidate);
            // Strip trailing punctuation
            $candidate = rtrim($candidate, " .,;:-");
        }

        return [$reference, $candidate ?: null];
    }

    private function descriptionLooksNonPayroll(string $description): bool
    {
        $upper = strtoupper($description);
        foreach (self::NON_PAYROLL_KEYWORDS as $kw) {
            if (str_contains($upper, $kw)) return true;
        }
        return false;
    }

    private function looksLikePayroll(?string $description, float $debit, float $credit, ?string $parsedName): bool
    {
        if ($debit <= 0) return false;
        if ($credit > 0) return false;
        if ($description && $this->descriptionLooksNonPayroll($description)) return false;
        if (!$parsedName) return false;

        // Person names usually have at least two tokens (first + last name)
        $tokens = preg_split('/\s+/', $parsedName);
        return count($tokens) >= 2;
    }

    private function normalizeNumber($value): ?float
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (float) $value;

        // Strip currency symbols, commas
        $clean = preg_replace('/[^\d\.\-]/', '', (string) $value);
        return $clean === '' ? null : (float) $clean;
    }

    private function normalizeDate($value): ?string
    {
        if (!$value) return null;

        if (is_numeric($value)) {
            // Excel serial date
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $dt->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);
        // Common formats: dd/mm/yyyy, yyyy-mm-dd, dd-mm-yyyy, mm/dd/yyyy
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y', 'm/d/Y'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $value);
            if ($dt && $dt->format($fmt) === $value) {
                return $dt->format('Y-m-d');
            }
        }
        try {
            return (new \DateTime($value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
