<?php
/**
 * Serializare JSON structurata pentru export: invelis cu metadate, conversie
 * a tipurilor brute din PDO (string) catre tipuri reale (int/float/bool) si
 * encodare cu sau fara pretty-print.
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Constants;

class JsonService
{
    /** Versiunea schemei de export (independenta de versiunea aplicatiei). */
    public const EXPORT_VERSION = '1.0';

    /** Coloane castate dupa nume (numele sunt consistente intre tabele). */
    private const INT_FIELDS = [
        'id', 'child_id', 'logged_by', 'created_by', 'uploaded_by', 'moment_id',
        'user_id', 'duration_min', 'amount_ml', 'quality', 'reactions',
        'size_bytes', 'age_years',
    ];
    private const FLOAT_FIELDS = ['weight_kg', 'height_cm', 'head_cm'];
    private const BOOL_FIELDS  = ['is_pinned', 'is_shared', 'is_superadmin'];

    /**
     * Construieste structura de export (invelis + date + counts), cu tipuri normalizate.
     *
     * @param array $child    Randul copilului.
     * @param array $sections name => lista de randuri (feedings, sleep, ...).
     */
    public function buildExport(array $child, array $sections): array
    {
        $data = [];
        $counts = [];
        foreach ($sections as $name => $rows) {
            $data[$name] = array_map([$this, 'normalizeRow'], $rows);
            $counts[$name] = count($rows);
        }

        return [
            'export' => [
                'version'      => self::EXPORT_VERSION,
                'generated_at' => date(DATE_ATOM),
                'app'          => Constants::APP_NAME,
                'app_version'  => Constants::APP_VERSION,
            ],
            'child'  => $this->normalizeRow($child),
            'data'   => $data,
            'counts' => $counts,
        ];
    }

    /**
     * Encodeaza un array in JSON. $pretty=true pentru export descarcabil (indentat),
     * $pretty=false pentru comunicare API (compact).
     */
    public function encode(array $data, bool $pretty = false): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        return json_encode($data, $flags);
    }

    /**
     * Converteste valorile string brute din PDO la tipuri JSON reale, dupa numele
     * coloanei. Valorile null raman null; coloanele necunoscute raman neschimbate.
     */
    public function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (in_array($key, self::INT_FIELDS, true)) {
                $row[$key] = (int) $value;
            } elseif (in_array($key, self::FLOAT_FIELDS, true)) {
                $row[$key] = (float) $value;
            } elseif (in_array($key, self::BOOL_FIELDS, true)) {
                $row[$key] = (bool) (int) $value;
            }
        }
        return $row;
    }
}
