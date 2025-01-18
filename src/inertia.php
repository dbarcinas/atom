<?php

declare(strict_types=1);

namespace atom;

/**
 * Renders component
 *
 * @param string $component
 * @param array $props
 */
function inertia_render(string $component, array $props = []): void {
  $page = [
    'component' => $component,
    'props' => $props, // TODO: Add/Merge shared data
    'url' => $_SERVER['REQUEST_URI'],
  ];
  if (isset($_SERVER['HTTP_X_INERTIA'])) {
    header('Content-Type: application/json');
    header('X-Inertia: true');
    echo json_encode($page);
  } else {
    // TODO: Include default template
  }
  exit;
}

/**
 * Inertia destination URL
 *
 * @param string $url
 */
function location(string $url) {
  header("X-Inertia-Location: $url");
  exit;
}
