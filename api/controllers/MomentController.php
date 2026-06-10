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
     * in storage/uploads/photos/ (in afara webroot) si legat de moment in tabela media.
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

        // Normalizare tags: "Primul zambet, vara , PLAJA" -> "primul zambet,vara,plaja"
        $tags = $this->normalizeTags($body['tags'] ?? '');
        if ($tags === null) {
            Response::error('Maximum 10 tags, each up to 30 characters', 400);
        }

        $model = new MomentModel();
        $momentId = $model->create([
            'child_id' => $childId,
            'logged_by' => SessionManager::userId(),
            'type' => $body['type'],
            'title' => $body['title'],
            'body' => $body['body'] ?? '',
            'is_pinned' => $this->toBool($body['is_pinned'] ?? false),
            'is_shared' => $this->toBool($body['is_shared'] ?? false),
            'tags' => $tags,
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

    /**
     * Normalizeaza un sir de tag-uri separate prin virgula:
     * trim, lowercase, fara HTML, fara duplicate, fara goluri.
     * Returneaza null daca limitele sunt depasite (max 10 tag-uri, max 30 caractere fiecare).
     *
     * @param  string $raw Tag-urile brute din formular
     * @return string|null Sir normalizat "tag1,tag2" sau null la eroare de validare
     */
    private function normalizeTags(string $raw): ?string
    {
        if (trim($raw) === '') {
            return '';
        }

        $tags = [];
        foreach (explode(',', $raw) as $tag) {
            $tag = mb_strtolower(trim(strip_tags($tag)));
            if ($tag === '') {
                continue;
            }
            if (mb_strlen($tag) > 30) {
                return null;
            }
            if (!in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        if (count($tags) > 10) {
            return null;
        }

        return implode(',', $tags);
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
        if (str_starts_with($mime, 'text/')) {
            return 'document';
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