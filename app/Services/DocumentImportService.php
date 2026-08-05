<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use Illuminate\Support\Str;
use Exception;

class DocumentImportService
{
    public function import(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $fields = match ($extension) {
            'xlsx' => $this->importExcel($path),
            'csv' => $this->importCsv($path),
            default => throw new Exception('Unsupported file type.')
        };

        // Remove duplicate fields
        $unique = [];

        foreach ($fields as $field) {
            $unique[$field['name']] = $field;
        }

        return array_values($unique);
    }
    
    private function importExcel(string $path): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($path);

        $rows = $spreadsheet
            ->getActiveSheet()
            ->toArray();

        $fields = [];

        foreach ($rows as $row) {

            $label = trim((string) ($row[0] ?? ''));

            if ($this->shouldSkip($label)) {
                continue;
            }

            $fields[] = $this->detectField($label);
        }

        return $fields;
    }

    private function importCsv(string $path): array
    {
        $rows = array_map('str_getcsv', file($path));

        $fields = [];

        foreach ($rows as $row) {

            $label = trim((string) ($row[0] ?? ''));

            if ($this->shouldSkip($label)) {
                continue;
            }

            $fields[] = $this->detectField($label);
        }

        return $fields;
    }

    private function shouldSkip(string $text): bool
    {
        if ($text === '') {
            return true;
        }

        $lower = strtolower(trim($text));

        $skip = [
            'field',
            'field name',
            'fieldname',
            'label',
            'labels',
            'customer feedback form',
            'feedback form',
            'survey',
            'form',
        ];

        return in_array($lower, $skip);
    }

    private function detectField(string $label): array
    {
        $lower = strtolower($label);

        $type = 'text';
        $options = [];

        if (str_contains($lower, 'email')) {

            $type = 'email';
        } elseif (
            str_contains($lower, 'phone') ||
            str_contains($lower, 'mobile')
        ) {

            $type = 'phone';
        } elseif (
            str_contains($lower, 'date') ||
            str_contains($lower, 'dob')
        ) {

            $type = 'date';
        } elseif (
            str_contains($lower, 'age') ||
            str_contains($lower, 'number')
        ) {

            $type = 'number';
        } elseif (str_contains($lower, 'gender')) {

            $type = 'select';

            $options = [
                'Male',
                'Female',
                'Other',
            ];
        } elseif (
            str_contains($lower, 'rating') ||
            str_contains($lower, 'score')
        ) {

            $type = 'select';

            $options = [
                '1',
                '2',
                '3',
                '4',
                '5',
            ];
        } elseif (
            str_contains($lower, 'comment') ||
            str_contains($lower, 'feedback') ||
            str_contains($lower, 'description')
        ) {

            $type = 'textarea';
        }

        return [
            'label' => $label,
            'name' => Str::snake($label),
            'type' => $type,
            'placeholder' => '',
            'description' => '',
            'required' => true,
            'options' => $options,
        ];
    }
}
