<?php
/**
 * Export complet al datelor unui copil in format JSON sau CSV (ZIP cu un CSV per tabel).
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\ChildModel;
use App\Models\FeedingModel;
use App\Models\SleepModel;
use App\Models\GrowthModel;
use App\Models\MedicalModel;
use App\Models\MomentModel;
use App\Services\JsonService;
use ZipArchive;

class ExportController extends Controller
{
    private const EXPORT_LIMIT = 100000;

    /**
     * GET /api/export/children — exporta lista copiilor in CSV.
     * Super-admin primeste toti copiii din platforma (cu owner);
     * un user obisnuit primeste copiii familiei sale (cu permisiunea lui).
     *
     * @return void Descarca un fisier children.csv
     */
    public function children(array $params): void
    {
        $this->requireAuth();

        $model = new ChildModel();
        $rows = SessionManager::isSuperAdmin()
            ? $model->getAll()
            : $model->getByUser((int) SessionManager::userId());

        $body = $this->toCsv($rows);

        http_response_code(200);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="children.csv"');
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }

    public function json(array $params): void
    {
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $data = $this->gatherData($childId);
        if ($data === null) {
            Response::error('Child not found', 404);
        }

        $child = $data['child'];
        unset($data['child']);
        $json = new JsonService();
        $payload = $json->buildExport($child, $data);
        $body = $json->encode($payload, true); // pretty pentru export

        $filename = $this->fileSlug($child) . '.json';
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }

    public function csv(array $params): void
    {
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $data = $this->gatherData($childId);
        if ($data === null) {
            Response::error('Child not found', 404);
        }

        // Pentru CSV "child" devine o lista cu un singur rand.
        $sections = [
            'child'    => [$data['child']],
            'feedings' => $data['feedings'],
            'sleep'    => $data['sleep'],
            'growth'   => $data['growth'],
            'medical'  => $data['medical'],
            'moments'  => $data['moments'],
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'export_');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            Response::error('Could not create archive', 500);
        }

        foreach ($sections as $name => $rows) {
            $zip->addFromString($name . '.csv', $this->toCsv($rows));
        }
        $zip->close();

        $filename = $this->fileSlug($data['child']) . '.zip';
        $contents = file_get_contents($tmp);
        unlink($tmp);

        http_response_code(200);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($contents));
        echo $contents;
        exit;
    }

    /**
     * Aduna toate datele copilului. Intoarce null daca nu exista copilul.
     */
    private function gatherData(int $childId): ?array
    {
        $child = (new ChildModel())->findById($childId);
        if (!$child) {
            return null;
        }

        return [
            'child'    => $child,
            'feedings' => (new FeedingModel())->getByChild($childId, self::EXPORT_LIMIT),
            'sleep'    => (new SleepModel())->getByChild($childId, self::EXPORT_LIMIT),
            'growth'   => (new GrowthModel())->getByChild($childId),
            'medical'  => (new MedicalModel())->getByChild($childId, self::EXPORT_LIMIT),
            'moments'  => (new MomentModel())->getByChild($childId, null, self::EXPORT_LIMIT, 0),
        ];
    }

    /**
     * Converteste o lista de randuri asociative in text CSV.
     * Antetul provine din cheile primului rand; sectiunile goale dau fisier gol.
     */
    private function toCsv(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }

        $out = fopen('php://temp', 'r+');
        // escape="" => CSV standard RFC 4180 (si evita deprecation-ul)
        fputcsv($out, array_keys($rows[0]), ',', '"', '');
        foreach ($rows as $row) {
            $values = array_map(
                static fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v,
                array_values($row)
            );
            fputcsv($out, $values, ',', '"', '');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    private function fileSlug(array $child): string
    {
        $name = trim(($child['first_name'] ?? 'child') . '_' . ($child['last_name'] ?? ''));
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '_', $name);
        return 'export_' . trim($slug, '_');
    }
}
