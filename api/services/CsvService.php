<?php
/**
 * Parseaza CSV-uri si valideaza randurile inainte de import in DB.
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Services;

class CsvService
{
    /**
     * Reguli de validare per tabel: coloane obligatorii + valori permise pentru enum-uri.
     * Coloanele care lipsesc din "enums" sau "required" sunt acceptate ca atare.
     */
    public const RULES = [
        'feedings' => [
            'required' => ['child_id', 'type'],
            'enums'    => [
                'type' => ['breast', 'bottle', 'solids'],
                'side' => ['L', 'R', 'both'],
            ],
        ],
        'sleep' => [
            'required' => ['child_id', 'type', 'started_at'],
            'enums'    => ['type' => ['night', 'nap']],
        ],
        'growth' => [
            'required' => ['child_id'],
            'enums'    => [],
        ],
        'medical' => [
            'required' => ['child_id', 'type', 'title', 'date_at'],
            'enums'    => [
                'type' => ['vaccine', 'visit', 'allergy', 'medication', 'measurement', 'other'],
            ],
        ],
        'moments' => [
            'required' => ['child_id', 'type', 'title'],
            'enums'    => [
                'type' => ['milestone', 'food', 'medical', 'photo', 'friends', 'sleep', 'voice', 'other'],
            ],
        ],
    ];

    /**
     * Transforma textul CSV intr-o lista de randuri asociative (cheie = antet).
     * Sirurile goale devin null. Randurile complet goale sunt ignorate.
     */
    public function parse(string $content): array
    {
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $content);
        rewind($fh);

        $header = fgetcsv($fh, 0, ',', '"', '');
        if ($header === false) {
            fclose($fh);
            return [];
        }
        $colCount = count($header);

        $rows = [];
        while (($data = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            // linie goala
            if ($data === [null] || ($data === [''] )) {
                continue;
            }
            $data = array_slice($data, 0, $colCount);
            $data = array_pad($data, $colCount, null);
            $row = array_combine($header, $data);
            // normalizeaza '' -> null
            foreach ($row as $k => $v) {
                $row[$k] = ($v === '' ? null : $v);
            }
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }

    /**
     * Valideaza un rand pentru un tabel. Intoarce mesajul de eroare sau null daca e ok.
     */
    public function validateRow(string $table, array $row): ?string
    {
        $rules = self::RULES[$table] ?? null;
        if ($rules === null) {
            return "Unknown table '{$table}'";
        }

        foreach ($rules['required'] as $col) {
            if (!isset($row[$col]) || $row[$col] === null || $row[$col] === '') {
                return "Missing required column '{$col}'";
            }
        }

        if (!ctype_digit((string) $row['child_id'])) {
            return "Invalid child_id '{$row['child_id']}'";
        }

        foreach ($rules['enums'] as $col => $allowed) {
            $val = $row[$col] ?? null;
            if ($val !== null && !in_array($val, $allowed, true)) {
                return "Invalid value '{$val}' for column '{$col}'";
            }
        }

        return null;
    }
}
