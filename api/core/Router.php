<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function __construct()
    {
        $this->registerRoutes();
        $this->registerV1Routes();
    }

    private function registerRoutes(): void
    {
        $this->add('GET',  '/api/auth/csrf',     'AuthController', 'csrf');
        $this->add('POST', '/api/auth/login',    'AuthController', 'login');
        $this->add('POST', '/api/auth/logout',   'AuthController', 'logout');
        $this->add('GET',  '/api/auth/me',       'AuthController', 'me');
        $this->add('POST', '/api/auth/register', 'AuthController', 'register');
        $this->add('POST', '/api/auth/reset',    'AuthController', 'reset');

        $this->add('GET', '/api/children', 'ChildController', 'index');
        $this->add('POST', '/api/children', 'ChildController', 'store');
        $this->add('GET',  '/api/children/{id}',       'ChildController', 'show');
        $this->add('PUT',  '/api/children/{id}',       'ChildController', 'update');
        $this->add('POST', '/api/children/{id}/photo', 'ChildController', 'uploadPhoto');
        $this->add('DELETE', '/api/children/{id}',     'ChildController', 'destroy');

        $this->add('GET', '/api/children/{id}/family', 'FamilyController', 'index');
        $this->add('PUT', '/api/children/{id}/family/permission', 'FamilyController', 'updatePermission');
        $this->add('DELETE', '/api/children/{id}/family/member', 'FamilyController', 'removeMember');

        $this->add('GET', '/api/children/{id}/timeline', 'TimelineController', 'index');
        $this->add('GET', '/api/children/{id}/feed', 'TimelineController', 'feed');
        $this->add('POST', '/api/children/{id}/moments', 'MomentController', 'store');
        $this->add('DELETE', '/api/moments/{id}', 'MomentController', 'destroy');

        $this->add('POST', '/api/moments/{id}/comments', 'CommentController', 'store');
        $this->add('GET', '/api/moments/{id}/comments', 'CommentController', 'index');
        $this->add('POST', '/api/moments/{id}/reactions', 'ReactionController', 'store');
        $this->add('DELETE', '/api/moments/{id}/reactions', 'ReactionController', 'destroy');

        $this->add('GET',  '/api/children/{id}/feedings', 'FeedingController', 'index');
        $this->add('POST', '/api/children/{id}/feedings', 'FeedingController', 'store');
        $this->add('GET',  '/api/children/{id}/sleep',    'SleepController',   'index');
        $this->add('POST', '/api/children/{id}/sleep',    'SleepController',   'store');
        $this->add('GET',  '/api/children/{id}/growth',   'GrowthController',  'index');
        $this->add('POST', '/api/children/{id}/growth',   'GrowthController',  'store');
        $this->add('GET',  '/api/children/{id}/medical', 'MedicalController', 'index');
        $this->add('POST', '/api/children/{id}/medical', 'MedicalController', 'store');

        $this->add('GET',    '/api/children/{id}/relationships',     'RelationshipController', 'index');
        $this->add('POST',   '/api/children/{id}/relationships',     'RelationshipController', 'store');
        $this->add('PUT',    '/api/relationships/{id}',              'RelationshipController', 'update');
        $this->add('DELETE', '/api/relationships/{id}',              'RelationshipController', 'destroy');

        $this->add('GET',    '/api/children/{id}/interactions',      'InteractionController', 'index');
        $this->add('GET',    '/api/relationships/{id}/interactions', 'InteractionController', 'byRelationship');
        $this->add('POST',   '/api/relationships/{id}/interactions', 'InteractionController', 'store');
        $this->add('DELETE', '/api/interactions/{id}',               'InteractionController', 'destroy');

        $this->add('POST', '/api/children/{id}/invites', 'InviteController', 'store');
        $this->add('GET', '/api/invite', 'InviteController', 'validate');

        $this->add('GET', '/api/export/children', 'ExportController', 'children');
        $this->add('GET', '/api/children/{id}/export/json', 'ExportController', 'json');
        $this->add('GET', '/api/children/{id}/export/csv', 'ExportController', 'csv');
        $this->add('POST', '/api/import/csv', 'ImportController', 'csv');
        $this->add('POST', '/api/import/json', 'ImportController', 'json');

        $this->add('GET', '/feed/{child_id}.rss', 'RssController', 'feed');
        $this->add('GET', '/share/{token}', 'ShareController', 'show');

        $this->add('GET', '/api/admin/stats', 'AdminController', 'stats');
        $this->add('GET', '/api/admin/users', 'AdminController', 'users');
        $this->add('POST', '/api/admin/users/{id}/ban', 'AdminController', 'ban');
        $this->add('POST', '/api/admin/users/{id}/unban', 'AdminController', 'unban');
        $this->add('GET', '/api/admin/storage', 'AdminController', 'storage');
    }

    /**
     * Inregistreaza rutele REST API v1 cu autentificare Bearer token.
     * Aceleasi controllere ca la /api/ — autentificarea e rezolvata transparent
     * de AuthMiddleware::resolveBearer() apelata in dispatch().
     */
    private function registerV1Routes(): void
    {
        // Token management
        $this->add('POST',   '/api/v1/auth/token',  'ApiTokenController', 'issue');
        $this->add('DELETE', '/api/v1/auth/token',  'ApiTokenController', 'revoke');
        $this->add('GET',    '/api/v1/auth/tokens', 'ApiTokenController', 'list');

        // Children
        $this->add('GET',    '/api/v1/children',            'ChildController', 'index');
        $this->add('POST',   '/api/v1/children',            'ChildController', 'store');
        $this->add('GET',    '/api/v1/children/{id}',       'ChildController', 'show');
        $this->add('PUT',    '/api/v1/children/{id}',       'ChildController', 'update');
        $this->add('DELETE', '/api/v1/children/{id}',       'ChildController', 'destroy');

        // Feeding, Sleep, Growth
        $this->add('GET',  '/api/v1/children/{id}/feedings', 'FeedingController', 'index');
        $this->add('POST', '/api/v1/children/{id}/feedings', 'FeedingController', 'store');
        $this->add('GET',  '/api/v1/children/{id}/sleep',    'SleepController',   'index');
        $this->add('POST', '/api/v1/children/{id}/sleep',    'SleepController',   'store');
        $this->add('GET',  '/api/v1/children/{id}/growth',   'GrowthController',  'index');
        $this->add('POST', '/api/v1/children/{id}/growth',   'GrowthController',  'store');

        // Medical
        $this->add('GET',  '/api/v1/children/{id}/medical', 'MedicalController', 'index');
        $this->add('POST', '/api/v1/children/{id}/medical', 'MedicalController', 'store');

        // Moments, Timeline, Comments, Reactions
        $this->add('GET',    '/api/v1/children/{id}/timeline',   'TimelineController', 'index');
        $this->add('POST',   '/api/v1/children/{id}/moments',    'MomentController',   'store');
        $this->add('DELETE', '/api/v1/moments/{id}',             'MomentController',   'destroy');
        $this->add('GET',    '/api/v1/moments/{id}/comments',    'CommentController',  'index');
        $this->add('POST',   '/api/v1/moments/{id}/comments',    'CommentController',  'store');
        $this->add('POST',   '/api/v1/moments/{id}/reactions',   'ReactionController', 'store');
        $this->add('DELETE', '/api/v1/moments/{id}/reactions',   'ReactionController', 'destroy');

        // Relationships & Interactions
        $this->add('GET',    '/api/v1/children/{id}/relationships',     'RelationshipController', 'index');
        $this->add('POST',   '/api/v1/children/{id}/relationships',     'RelationshipController', 'store');
        $this->add('PUT',    '/api/v1/relationships/{id}',              'RelationshipController', 'update');
        $this->add('DELETE', '/api/v1/relationships/{id}',              'RelationshipController', 'destroy');
        $this->add('GET',    '/api/v1/children/{id}/interactions',      'InteractionController',  'index');
        $this->add('POST',   '/api/v1/relationships/{id}/interactions', 'InteractionController',  'store');

        // Export / Import
        $this->add('GET',  '/api/v1/export/children',           'ExportController', 'children');
        $this->add('GET',  '/api/v1/children/{id}/export/json', 'ExportController', 'json');
        $this->add('GET',  '/api/v1/children/{id}/export/csv',  'ExportController', 'csv');
        $this->add('POST', '/api/v1/import/json',               'ImportController', 'json');
        $this->add('POST', '/api/v1/import/csv',                'ImportController', 'csv');

        // Family
        $this->add('GET', '/api/v1/children/{id}/family', 'FamilyController', 'index');

        // Admin
        $this->add('GET', '/api/v1/admin/stats',              'AdminController', 'stats');
        $this->add('GET', '/api/v1/admin/users',              'AdminController', 'users');
        $this->add('POST', '/api/v1/admin/users/{id}/ban',    'AdminController', 'ban');
        $this->add('POST', '/api/v1/admin/users/{id}/unban',  'AdminController', 'unban');
    }

    private function add(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[$method][$path] = ['controller' => $controller, 'action' => $action];
    }

    public function dispatch(): void
    {
        // Daca body-ul depaseste post_max_size, PHP goleste $_POST si $_FILES
        // in liniste — fara verificarea asta, userul ar primi erori derutante.
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength > 0 && $contentLength > self::iniBytes((string) ini_get('post_max_size'))) {
            Response::error(
                'Request body too large — maximum allowed is ' . ini_get('post_max_size') .
                ' (post_max_size). Start the dev server with: php -S localhost:8000 -d upload_max_filesize=50M -d post_max_size=52M router.php',
                413
            );
        }

        // Rezolva Bearer token inainte de orice controller (REST API v1)
        AuthMiddleware::resolveBearer();

        $request = new Request();
        $method = $request->method;
        $uri = $request->uri;

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            // Parametrii care contin "token" sunt alfanumerici (ex. share token hex);
            // restul sunt numerici (id-uri).
            $pattern = preg_replace_callback('/\{(\w+)\}/', static function (array $m): string {
                $class = str_contains($m[1], 'token') ? '[A-Za-z0-9]+' : '\d+';
                return '(?P<' . $m[1] . '>' . $class . ')';
            }, $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->execute($handler['controller'], $handler['action'], $request, $params);
                return;
            }
        }

        Response::error('Route not found', 404);
    }

    /**
     * Converteste o valoare php.ini cu sufix (ex. "8M", "2G") in bytes.
     *
     * @return int Numarul de bytes
     */
    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX; // nelimitat
        }
        $unit = strtolower($value[strlen($value) - 1]);
        $num  = (int) $value;
        return match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }

    private function execute(string $controllerName, string $action, Request $request, array $params): void
    {
        $class = "App\\Controllers\\{$controllerName}";

        if (!class_exists($class)) {
            Response::error('Controller not found', 500);
        }

        $controller = new $class($request);

        if (!method_exists($controller, $action)) {
            Response::error('Action not found', 500);
        }

        $controller->$action($params);
    }
}