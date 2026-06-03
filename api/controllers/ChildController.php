<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\ChildModel;
use App\Services\UploadService;

class ChildController extends Controller
{
    public function index(array $params): void
    {
        $this->requireAuth();
        $userId = SessionManager::userId();

        $model = new ChildModel();
        $children = $model->getByUser($userId);

        Response::json($children);
    }

    public function show(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $model = new ChildModel();
        $child = $model->findById($childId);

        if (!$child) {
            Response::error('Child not found', 404);
        }

        Response::json($child);
    }

    public function store(array $params): void
    {
        $this->requireAuth();
        $body = $this->request->body;

        $required = ['first_name', 'date_of_birth'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                Response::error("Field {$field} is required", 400);
            }
        }

        $userId = SessionManager::userId();
        $model = new ChildModel();

        $childId = $model->create([
            'first_name' => $body['first_name'],
            'last_name' => $body['last_name'] ?? '',
            'date_of_birth' => $body['date_of_birth'],
            'gender' => $body['gender'] ?? null,
            'blood_type' => $body['blood_type'] ?? null,
            'notes' => $body['notes'] ?? '',
            'created_by' => $userId,
        ]);

        $model->addFamilyMember($childId, $userId, 'owner');

        Response::json(['id' => $childId, 'message' => 'Child profile created'], 201);
    }

    public function update(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        $model = new ChildModel();

        $success = $model->update($childId, $body);

        if (!$success) {
            Response::error('Update failed', 500);
        }

        Response::json(['message' => 'Child profile updated']);
    }

    /**
     * Uploadează poza de profil a copilului.
     * Acceptă multipart/form-data cu câmpul 'photo'.
     * Salvează în public/uploads/photos/ și actualizează photo_url în DB.
     */
    public function uploadPhoto(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        if (empty($this->request->files['photo'])) {
            Response::error('No photo uploaded', 400);
        }

        $file = $this->request->files['photo'];

        // Validare MIME cu finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mime, $allowed, true)) {
            Response::error('Only JPEG, PNG or WebP images are allowed', 422);
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            Response::error('Image must be under 5 MB', 422);
        }

        // Salvare în subdirectorul photos/
        $ext      = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        $filename  = $childId . '_' . uniqid('', true) . '.' . $ext;
        $uploadDir = __DIR__ . '/../../public/uploads/photos/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            Response::error('Failed to save image', 500);
        }

        $photoUrl = '/uploads/photos/' . $filename;

        $model = new ChildModel();
        $model->updatePhoto($childId, $photoUrl);

        Response::json(['photo_url' => $photoUrl]);
    }

    public function destroy(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $model = new ChildModel();
        $model->delete($childId);

        Response::noContent();
    }
}