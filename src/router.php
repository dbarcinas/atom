<?php

declare(strict_types=1);

namespace atom;

/**
 * Registers a route with the specified method, path, callback, and optional middleware.
 *
 * @param array<string>|string $method HTTP method(s) for the route (e.g., 'GET', ['POST', 'PUT']).
 * @param string $path URL path with optional placeholders (e.g., '/user/{id}').
 * @param callable $callback Function to handle the route.
 * @param string|null $name Optional name for the route, used for reverse routing.
 * @param callable|array<callable> $middleware Middleware(s) to execute before the callback.
 * @return void
 */
function route(array|string $method, string $path, callable $callback, ?string $name = null, callable|array $middleware = []): void {
  $store = routes()['store'];
  $method = is_array($method) ? $method : [$method];
  $middleware = is_array($middleware) ? $middleware : [$middleware];
  $store([
    'method' => array_map('strtoupper', $method),
    'path' => $path,
    'callback' => $callback,
    'name' => $name,
    'middleware' => $middleware,
  ]);
}

/**
 * Provides access to route storage and caching.
 *
 * @return array<string, callable> An associative array with 'store', 'fetch', 'cache', and 'set_cache' handlers.
 */
function routes(): array {
  static $routes = [];
  static $cache = [];
  return [
    'store' => function (array $route) use (&$routes): void {
      $routes[] = $route;
    },
    'fetch' => fn(): array => $routes,
    'cache' => fn(): array => $cache,
    'set_cache' => function ($pattern) use (&$cache): void {
      $cache[] = $pattern;
    },
  ];
}

/**
 * Groups routes under a common path and applies middleware.
 *
 * @param string $path Base path for the route group.
 * @param callable $callback Function to define child routes.
 * @param callable|array<callable> $middleware Middleware(s) to apply to all child routes.
 * @return void
 */
function stack(string $path, callable $callback, callable|array $middleware = []): void {
  $route = function (
    array|string $method,
    string $child_path,
    callable $callback,
    ?string $name = null,
    callable|array $child_middleware = [],
  ) use ($path, $middleware) {
    $full_path = '/' . trim($path, '/') . '/' . trim($child_path, '/');
    $child_middleware = is_array($child_middleware) ? $child_middleware : [$child_middleware];
    $middleware = is_array($middleware) ? $middleware : [$middleware];
    route(
      $method,
      $full_path,
      $callback,
      $name,
      array_merge($middleware, $child_middleware)
    );
  };
  $callback($route);
}

/**
 * Dispatches the current request to the appropriate route.
 *
 * @param callable|null $error_handler Optional error handler for unmatched routes.
 * @return void
 */
function dispatch(?callable $error_handler = null): void {
  $request = parse_url('/' . trim($_SERVER['REQUEST_URI'], '/'), PHP_URL_PATH);
  $method = $_SERVER['REQUEST_METHOD'];
  $routes = routes();
  $fetch_routes = $routes['fetch']();
  $cached_patterns = $routes['cache']();

  foreach ($fetch_routes as $index => $route) {
    // Check if the pattern is already cached
    $pattern = $cached_patterns[$index] ?? null;
    if (!$pattern) {
      $pattern = preg_replace_callback('/\{([a-zA-Z_]+)(?::([a-zA-Z0-9_]+))?\}/', function ($matches) {
        $name = $matches[1];
        $type = $matches[2] ?? 'default';
        return match ($type) {
          'int' => '(?<'.$name.'>\\d+)',
          'slug' => '(?<'.$name.'>[a-zA-Z0-9_-]+)',
          default => '(?<'.$name.'>[^/]+)',
        };
      }, $route['path']);
      $pattern = '@^' . $pattern . '$@';
      $routes['set_cache']($pattern);
    }

    if (is_string($request) && preg_match($pattern, $request, $matches) && in_array($method, $route['method'])) {
      $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
      foreach ($route['middleware'] as $middleware) {
        if (is_callable($middleware)) {
          if ($middleware($request, $method, $params) === false) {
            return;
          }
        }
      }
      call_user_func_array($route['callback'], $params);
      return;
    }
  }

  if ($error_handler) {
    $error_handler($request, $method);
  } else {
    http_response_code(404);
    echo '404 Not Found';
  }
}

/**
 * Generates a URL for a named route with optional parameters.
 *
 * @param string $name Name of the route.
 * @param array<string, mixed> $params Associative array of parameters to replace in the route.
 * @return string The generated URL.
 * @throws \Exception If the route name is not found.
 */
function url_for(string $name, array $params = []): string {
  $routes = routes()['fetch']();
  foreach ($routes as $route) {
    if ($route['name'] === $name) {
      $url = $route['path'];
      foreach ($params as $key => $value) {
        $url = str_replace('{' . $key . '}', $value, $url);
      }
      return $url;
    }
  }
  throw new \Exception("Route with name '{$name}' not found.");
}
