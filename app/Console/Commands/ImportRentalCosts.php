<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use SimpleXMLElement;
use XMLReader;

class ImportRentalCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rental:import {file : Path to rental.xlsx} {--truncate : Truncate the rental_costs table before import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import rental.xlsx into the rental_costs table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = (string) $this->argument('file');

        if (!is_file($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            $this->error("Unable to open XLSX file: {$file}");
            return self::FAILURE;
        }

        if ($this->option('truncate')) {
            DB::table('rental_costs')->truncate();
            $this->info('Truncated rental_costs table.');
        }

        $sharedStrings = $this->loadSharedStrings($zip);
        $sheetName = $this->firstWorksheetName($zip);

        if ($sheetName === null) {
            $this->error('No worksheet found in XLSX.');
            return self::FAILURE;
        }

        $sheetXml = $zip->getFromName($sheetName);
        if ($sheetXml === false) {
            $this->error("Unable to read worksheet: {$sheetName}");
            return self::FAILURE;
        }

        $reader = new XMLReader();
        $reader->XML($sheetXml);

        $headerColumns = null;
        $rowIndex = 0;
        $batch = [];
        $batchSize = 500;
        $inserted = 0;

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'row') {
                continue;
            }

            $rowXml = $reader->readOuterXml();
            if ($rowXml === '') {
                continue;
            }

            $rowValues = $this->extractRowValues($rowXml, $sharedStrings);

            if ($rowIndex === 0) {
                $headerColumns = $this->mapHeaderColumns($rowValues);
                $rowIndex++;
                continue;
            }

            if (!$headerColumns) {
                $this->error('Header row not found or invalid.');
                return self::FAILURE;
            }

            $record = [];
            $hasData = false;
            foreach ($headerColumns as $index => $column) {
                $value = $rowValues[$index] ?? null;
                $normalized = $this->normalizeValue($value);
                if ($normalized !== null && $normalized !== '') {
                    $hasData = true;
                }
                $record[$column] = $normalized;
            }

            if (!$hasData) {
                $rowIndex++;
                continue;
            }

            $batch[] = $record;
            if (count($batch) >= $batchSize) {
                DB::table('rental_costs')->insert($batch);
                $inserted += count($batch);
                $batch = [];
            }

            $rowIndex++;
        }

        if (!empty($batch)) {
            DB::table('rental_costs')->insert($batch);
            $inserted += count($batch);
        }

        $this->info("Imported {$inserted} row(s) into rental_costs.");
        return self::SUCCESS;
    }

    private function headerMap(): array
    {
        return [
            'Time period' => 'time_period',
            'Area code' => 'area_code',
            'Area name' => 'area_name',
            'Region or country name' => 'region_or_country_name',
            'Index' => 'index',
            'Monthly change' => 'monthly_change',
            'Annual change' => 'annual_change',
            'Rental price' => 'rental_price',
            'Index one bed' => 'index_one_bed',
            'Monthly change one bed' => 'monthly_change_one_bed',
            'Annual change one bed' => 'annual_change_one_bed',
            'Rental price one bed' => 'rental_price_one_bed',
            'Index two bed' => 'index_two_bed',
            'Monthly change two bed' => 'monthly_change_two_bed',
            'Annual change two bed' => 'annual_change_two_bed',
            'Rental price two bed' => 'rental_price_two_bed',
            'Index three bed' => 'index_three_bed',
            'Monthly change three bed' => 'monthly_change_three_bed',
            'Annual change three bed' => 'annual_change_three_bed',
            'Rental price three bed' => 'rental_price_three_bed',
            'Index four or more bed' => 'index_four_or_more_bed',
            'Monthly change four or more bed' => 'monthly_change_four_or_more_bed',
            'Annual change four or more bed' => 'annual_change_four_or_more_bed',
            'Rental price four or more bed' => 'rental_price_four_or_more_bed',
            'Index detached' => 'index_detached',
            'Monthly change detached' => 'monthly_change_detached',
            'Annual change detached' => 'annual_change_detached',
            'Rental price detached' => 'rental_price_detached',
            'Index semidetached' => 'index_semidetached',
            'Monthly change semidetached' => 'monthly_change_semidetached',
            'Annual change semidetached' => 'annual_change_semidetached',
            'Rental price semidetached' => 'rental_price_semidetached',
            'Index terraced' => 'index_terraced',
            'Monthly change terraced' => 'monthly_change_terraced',
            'Annual change terraced' => 'annual_change_terraced',
            'Rental price terraced' => 'rental_price_terraced',
            'Index flat maisonette' => 'index_flat_maisonette',
            'Monthly change flat maisonette' => 'monthly_change_flat_maisonette',
            'Annual change flat maisonette' => 'annual_change_flat_maisonette',
            'Rental price flat maisonette' => 'rental_price_flat_maisonette',
        ];
    }

    private function mapHeaderColumns(array $rowValues): array
    {
        $headers = array_keys($this->headerMap());
        $columns = array_values($this->headerMap());

        foreach ($headers as $index => $header) {
            $value = trim((string) ($rowValues[$index] ?? ''));
            if ($value !== $header) {
                $this->error("Unexpected header at column " . ($index + 1) . ": '{$value}' (expected '{$header}').");
                return [];
            }
        }

        $mapped = [];
        foreach ($columns as $index => $column) {
            $mapped[$index] = $column;
        }

        return $mapped;
    }

    private function extractRowValues(string $rowXml, array $sharedStrings): array
    {
        $row = new SimpleXMLElement($rowXml);
        $row->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $cells = $row->xpath('s:c') ?: [];

        $values = [];
        foreach ($cells as $cell) {
            $ref = (string) ($cell['r'] ?? '');
            if ($ref === '') {
                continue;
            }

            $letters = preg_replace('/\\d+/', '', $ref);
            $index = $this->columnIndex($letters);
            $values[$index] = $this->extractCellValue($cell, $sharedStrings);
        }

        return $values;
    }

    private function extractCellValue(SimpleXMLElement $cell, array $sharedStrings): ?string
    {
        $type = (string) ($cell['t'] ?? '');

        if ($type === 's') {
            $idx = (int) ($cell->v ?? -1);
            return $sharedStrings[$idx] ?? null;
        }

        if ($type === 'inlineStr') {
            return isset($cell->is->t) ? (string) $cell->is->t : null;
        }

        return isset($cell->v) ? (string) $cell->v : null;
    }

    private function loadSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $doc = new SimpleXMLElement($xml);

        $strings = [];
        $entries = $doc->xpath('//*[local-name()="si"]') ?: [];
        foreach ($entries as $entry) {
            $parts = [];
            foreach ($entry->xpath('.//*[local-name()="t"]') as $text) {
                $parts[] = (string) $text;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function firstWorksheetName(ZipArchive $zip): ?string
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name && preg_match('#^xl/worksheets/sheet\\d+\\.xml$#', $name)) {
                return $name;
            }
        }

        return null;
    }

    private function columnIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function normalizeValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^\\[[A-Za-z]\\]$/', $trimmed)) {
            return null;
        }

        if ($trimmed === ':' || $trimmed === '..') {
            return null;
        }

        if (is_numeric($trimmed) && strpbrk($trimmed, 'eE') !== false) {
            $formatted = rtrim(rtrim(sprintf('%.10F', (float) $trimmed), '0'), '.');
            return $formatted === '' ? '0' : $formatted;
        }

        $noCommas = str_replace(',', '', $trimmed);
        if ($noCommas !== $trimmed && is_numeric($noCommas)) {
            return $noCommas;
        }

        return $trimmed;
    }
}
