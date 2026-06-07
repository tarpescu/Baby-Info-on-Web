<?php
/**
 * Import atomic dintr-un ZIP cu CSV-uri (oglinda exportului). child_id este citit
 * din fiecare rand, accesul este verificat per copil, iar totul ruleaza intr-o
 * singura tranzactie (tot-sau-nimic).
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Config\Database;
use App\Services\CsvService;
use App\Models\FeedingModel;
use App\Models\SleepModel;
use App\Models\GrowthModel;
use App\Models\MedicalModel;
use App\Models\MomentModel;
use ZipArchive;
use RuntimeException;

class ImportController extends Controller
{
    /** Tabel -> clasa de model. Determina si ce fisiere .csv citim din zip. */
    private const TABLE_MODELS = [
        'feedings' => FeedingModel::class,
        'sleep'    => SleepModel::class,
        'growth'   => GrowthModel::class,
        'medical'  => MedicalModel::class,
        'moments'  => MomentModel::class,
    ];

    private const WRITE_ROLES = ['owner', 'coparent', 'caregiver'];

    public function csv(array $params): void
    {
        $this->requireAuth();
        $userId = SessionManager::userId();

        if (empty($this->request->files['file'])) {
            Response::error('No file uploaded (expected field "file")', 400);
        }
        $file = $this->request->files['file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::error('Upload failed', 400);
        }

        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            Response::error('Invalid ZIP archive', 422);
        }

        // Citeste si parseaza fiecare CSV cunoscut din arhiva.
        $service = new CsvService();
        $datasets = [];
        foreach (array_keys(self::TABLE_MODELS) as $table) {
            $content = $zip->getFromName($table . '.csv');
            if ($content !== false && trim($content) !== '') {
                $datasets[$table] = $service->parse($content);
            }
        }
        $zip->close();

        if (empty($datasets)) {
            Response::error('Archive contains no importable CSV files', 422);
        }

        $db = Database::getConnection();
        $accessCache = [];
        $counts = [];

        $db->beginTransaction();
        try {
            foreach ($datasets as $table => $rows) {
                $model = new (self::TABLE_MODELS[$table])();
                $counts[$table] = 0;

                foreach ($rows as $i => $row) {
                    $error = $service->validateRow($table, $row);
                    if ($error !== null) {
                        throw new RuntimeException("[{$table}.csv rand " . ($i + 1) . "] {$error}");
                    }

                    $childId = (int) $row['child_id'];
                    if (!$this->canWrite($db, $childId, $userId, $accessCache)) {
                        throw new RuntimeException(
                            "[{$table}.csv rand " . ($i + 1) . "] Fara permisiune de scriere pentru copilul {$childId}"
                        );
                    }

                    $data = $this->prepareRow($row, $childId, $userId);
                    $model->create($data);
                    $counts[$table]++;
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('Import esuat (rollback): ' . $e->getMessage(), 422);
        }

        Response::json([
            'success'  => true,
            'imported' => $counts,
            'total'    => array_sum($counts),
        ]);
    }

    /**
     * Pregateste randul pentru create(): forteaza child_id si logged_by pe valori
     * sigure (logged_by = utilizatorul curent, ca sa nu pice cheia straina catre users).
     * Coloanele in plus (id, first_name, media_url...) sunt ignorate de create().
     */
    private function prepareRow(array $row, int $childId, int $userId): array
    {
        $row['child_id']  = $childId;
        $row['logged_by'] = $userId;
        return $row;
    }

    /**
     * Verifica daca utilizatorul are rol de scriere pentru copil. Rezultatele
     * sunt memorate in cache per copil pentru a evita interogari repetate.
     */
    private function canWrite(\PDO $db, int $childId, int $userId, array &$cache): bool
    {
        if (array_key_exists($childId, $cache)) {
            return $cache[$childId];
        }

        $stmt = $db->prepare("
            SELECT permission FROM family_members
            WHERE child_id = :child_id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':child_id' => $childId, ':user_id' => $userId]);
        $row = $stmt->fetch();

        $allowed = $row && in_array($row['permission'], self::WRITE_ROLES, true);
        $cache[$childId] = $allowed;
        return $allowed;
    }
}
