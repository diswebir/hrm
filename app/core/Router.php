<?php
/**
 * مسیریاب (Router) — پشتیبانی از URLهای زیبا و حالت جایگزین ?r=
 */
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{m:string,p:string,c:string,a:string,auth:?bool,perm:?string}> */
    private array $routes = [];

    public function get(string $path, string $handler, array $opts = []): void
    {
        $this->add('GET', $path, $handler, $opts);
    }

    public function post(string $path, string $handler, array $opts = []): void
    {
        $this->add('POST', $path, $handler, $opts);
    }

    private function add(string $method, string $path, string $handler, array $opts): void
    {
        [$controller, $action] = array_pad(explode('@', $handler), 2, 'index');
        $this->routes[] = [
            'm'    => $method,
            'p'    => '/' . ltrim($path, '/'),
            'c'    => $controller,
            'a'    => $action,
            'auth' => $opts['auth'] ?? true,
            'perm' => $opts['perm'] ?? null,
        ];
    }

    /** استخراج مسیر درخواست */
    private function currentPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = hrm_base();
        if ($base !== '' && strncmp($path, $base, strlen($base)) === 0) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . ltrim($path, '/');

        // حالت جایگزین: ?r=employees/1 یا ?page=...
        if ($path === '/index.php' || $path === '/') {
            $r = $_GET['r'] ?? $_GET['page'] ?? null;
            if (is_string($r) && $r !== '') {
                $path = '/' . trim($r, '/');
            }
        }
        $path = rtrim($path, '/') ?: '/';
        // حذف پسوند فایل
        if (preg_match('#^/([^/]+\.php)$#', $path, $m) && $m[1] !== 'index.php') {
            $path = '/' . substr($m[1], 0, -4);
        }
        if ($path === '/index.php') $path = '/';
        return $path;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->currentPath();

        foreach ($this->routes as $route) {
            if ($route['m'] !== $method) continue;
            $pattern = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $route['p']);
            if (!preg_match('#^' . $pattern . '$#u', $path, $m)) continue;

            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

            // احراز هویت
            if ($route['auth'] !== false && !Auth::check()) {
                if ($method === 'POST') {
                    http_response_code(401);
                    echo 'unauthorized';
                    exit;
                }
                Flash::set('warning', 'برای دسترسی به این بخش ابتدا وارد شوید.');
                redirect('/login?back=' . urlencode($path));
            }

            // مجوز خاص
            if ($route['perm'] && !Auth::can($route['perm'])) {
                http_response_code(403);
                View::render('errors/403', ['title' => 'دسترسی غیرمجاز']);
                exit;
            }

            $class = 'App\\Controllers\\' . $route['c'] . 'Controller';
            if (!class_exists($class)) {
                http_response_code(404);
                View::render('errors/404', ['title' => 'یافت نشد']);
                exit;
            }

            $controller = new $class();
            $action = $route['a'];
            if (!method_exists($controller, $action)) {
                http_response_code(404);
                View::render('errors/404', ['title' => 'یافت نشد']);
                exit;
            }

            $out = $controller->$action(...array_values($params));
            if (is_string($out)) {
                echo $out;
            }
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'صفحه یافت نشد']);
    }
}
