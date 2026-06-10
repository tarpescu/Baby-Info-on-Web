<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\MediaModel;
use App\Models\MedicalModel;
use App\Services\UploadService;

class MedicalController extends Controller
{
    public function index(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $limit = (int) ($this->request->query['limit'] ?? 50);

        $model = new MedicalModel();
        Response::json($model->getByChild($childId, $limit));
    }

    /**
     * Creeaza un eveniment medical. Accepta application/json sau
     * multipart/form-data cu un camp optional 'document' (DOAR PDF,
     * validat cu finfo) — cerinta de curs: "attach PDF documents per event".
     * PDF-ul e salvat in storage/uploads/documents/ (in afara webroot) si
     * inregistrat si in tabela media (type 'document').
     *
     * @return void Raspuns JSON: { id, document_url|null, message }
     */
    public function store(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        if (empty($body['type']) || empty($body['title']) || empty($body['date_at'])) {
            Response::error('Type, title and date are required', 400);
        }

        // Atasament PDF optional — validat INAINTE de a crea inregistrarea,
        // ca sa nu ramana evenimente fara documentul promis.
        $documentUrl = null;
        $upload = null;
        $file = $this->request->files['document'] ?? null;

        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = (new UploadService())->handleDocument($file, $childId);
            if (!$upload['success']) {
                Response::error($upload['error'], 422);
            }
            $documentUrl = $upload['path'];
        }

        $model = new MedicalModel();
        $id = $model->create([
            'child_id' => $childId,
            'logged_by' => SessionManager::userId(),
            'type' => $body['type'],
            'title' => $body['title'],
            'description' => $body['description'] ?? null,
            'doctor_name' => $body['doctor_name'] ?? null,
            'clinic_name' => $body['clinic_name'] ?? null,
            'date_at' => $body['date_at'],
            'next_date' => $body['next_date'] ?? null,
            'document_url' => $documentUrl,
        ]);

        // Inregistram documentul si in tabela media (evidenta centralizata a fisierelor)
        if ($upload !== null) {
            (new MediaModel())->create([
                'child_id' => $childId,
                'moment_id' => null,
                'uploaded_by' => SessionManager::userId(),
                'type' => 'document',
                'filename' => $upload['filename'],
                'original_name' => $upload['original_name'],
                'size_bytes' => $upload['size_bytes'],
                'mime_type' => $upload['mime_type'],
                'caption' => $body['title'],
                'taken_at' => $body['date_at'],
            ]);
        }

        Response::json([
            'id' => $id,
            'document_url' => $documentUrl,
            'message' => 'Medical record logged',
        ], 201);
    }
}