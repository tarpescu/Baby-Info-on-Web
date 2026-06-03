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
    }

    private function registerRoutes(): void
    {
        $this->add('POST', '/api/auth/login', 'AuthController', 'login');
        $this->add('POST', '/api/auth/logout', 'AuthController', 'logout');
        $this->add('GET', '/api/auth/me', 'AuthController', 'me');
        $this->add('POST', '/api/auth/register', 'AuthController', 'register');

        $this->add('GET', '/api/children', 'ChildController', 'index');
        $this->add('POST', '/api/children', 'ChildController', 'store');
        $this->add('GET', '/api/children/{id}', 'ChildController', 'show');
        $this->add('PUT', '/api/children/{id}', 'ChildController', 'update');
        $this->add('DELETE', '/api/children/{id}', 'ChildController', 'destroy');

        $this->add('GET', '/api/children/{id}/family', 'FamilyController', 'index');
        $this->add('PUT', '/api/children/{id}/family/permission', 'FamilyController', 'updatePermission');
        $this->add('DELETE', '/api/children/{id}/family/member', 'FamilyController', 'removeMember');

        $this->add('GET', '/api/children/{id}/timeline', 'TimelineController', 'index');
        $this->add('GET', '/api/children/{id}/feed', 'TimelineController', 'feed');
        $this->add('POST', '/api/children/{id}/moments', 'MomentController', 'store');
        $this->add('DELETE', '/api/moments/{id}', 'MomentController', 'destroy');

        $this->add('POST', '/api/moments/{id}/comments', 'CommentController', 'store');
        $this->add('POST', '/api/moments/{id}/reactions', 'ReactionController', 'store');
        $this->add('DELETE', '/api/moments/{id}/reactions', 'ReactionController', 'destroy');

        $this->add('POST', '/api/children/{id}/feedings', 'FeedingController', 'store');
        $this->add('POST', '/api/children/{id}/sleep', 'SleepController', 'store');
        $this->add('POST', '/api/children/{id}/growth', 'GrowthController', 'store');
        $this->add('POST', '/api/children/{id}/medical', 'MedicalController', 'store');

        $this->add('POST', '/api/children/{id}/invites', 'InviteController', 'store');
        $this->add('GET', '/api/invite', 'InviteController', 'validate');

        $this->add('GET', '/api/children/{id}/export/json', 'ExportController', 'json');
        $this->add('GET', '/api/children/{id}/export/csv', 'ExportController', 'csv');
        $this->add('POST', '/api/import/csv', 'ImportController', 'csv');

        $this->add('GET', '/api/rss/{child_id}', 'RssController', 'feed');

        $this->add('GET', '/api/admin/stats', 'AdminController', 'stats');
        $this->add('GET', '/api/admin/users', 'AdminController', 'users');
        $this->add('POST', '/api/admin/users/{id}/ban', 'AdminController', 'ban');
        $this->add('POST', '/api/admin/users/{id}/unban', 'AdminController', 'unban');
        $this->add('GET', '/api/admin/storage', 'AdminController', 'storage');
    }

    private function add(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[$method][$path] = ['controller' => $controller, 'action' => $action];
    }

    public function dispatch(): void
    {
        $request = new Request();
        $method = $request->method;
        $uri = $request->uri;

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>\d+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->execute($handler['controller'], $handler['action'], $request, $params);
                return;
            }
        }

        Response::error('Route not found', 404);
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