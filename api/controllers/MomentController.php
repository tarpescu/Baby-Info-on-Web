<?php
/**
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\MediaModel;
use App\Models\MomentModel;
use App\Services\UploadService;

class MomentController extends Controller
{
    /**
     * Accepta atat application/json cat si multipart/form-data.
     * Pentru multipart, fisierul (camp 'photo' / 'media' / 'file') este salvat
     * in public/uploads/photos/ si legat de moment in tabela media.
     */
    public function store(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        if (empty($body['title']) || empty($body['type'])) {
            Response::error('Title and type are required', 400);
        }

        $happenedAt = !empty($body['happened_at']) ? $body['happened_at'] : null;

        $model = new MomentModel();
        $momentId = $model->create([
            'child_id' => $childId,
            'logged_by' => SessionManager::userId(),
            'type' => $body['type'],
            'title' => $body['title'],
            'body' => $body['body'] ?? '',
            'is_pinned' => $this->toBool($body['is_pinned'] ?? false),
            'is_shared' => $this->toBool($body['is_shared'] ?? false),
            'happened_at' => $happenedAt,
        ]);

        $mediaUrl = null;
        $file = $this->request->files['photo']
            ?? $this->request->files['media']
            ?? $this->request->files['file']
            ?? null;

        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = (new UploadService())->handlePhoto($file, $childId);
            if (!$upload['success']) {
                Response::error($upload['error'], 422);
            }

            (new MediaModel())->create([
                'child_id' => $childId,
                'moment_id' => $momentId,
                'uploaded_by' => SessionManager::userId(),
                'type' => $this->mediaType($upload['mime_type']),
                'filename' => $upload['filename'],
                'original_name' => $upload['original_name'],
                'size_bytes' => $upload['size_bytes'],
                'mime_type' => $upload['mime_type'],
                'caption' => $body['caption'] ?? null,
                'taken_at' => $happenedAt,
            ]);

            $mediaUrl = $upload['path'];
        }

        Response::json([
            'id' => $momentId,
            'media_url' => $mediaUrl,
            'message' => 'Moment logged',
        ], 201);
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    private function mediaType(string $mime): string
    {
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }
        return 'photo';
    }

    public function destroy(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();
        $momentId = (int) ($params['id'] ?? 0);

        $model = new MomentModel();
        $moment = $model->findById($momentId);

        if (!$moment) {
            Response::error('Moment not found', 404);
        }

        $this->requireWritePermission((int) $moment['child_id']);

        $model->delete($momentId);
        Response::noContent();
    }
}