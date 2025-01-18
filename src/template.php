<?php

declare(strict_types=1);

namespace atom;

/**
 * Stores template rendering context.
 *
 * @return array<string, mixed>
 */
function &template_context(): array {
  static $context = [
    'sections' => [],
    'current_section' => null,
    'layout' => null,
  ];
  return $context;
}

/**
 * Starts defining a section.
 *
 * @param string $name Name of the section.
 * @return void
 */
function section(string $name): void {
  $context = &template_context();
  $context['current_section'] = $name;
  ob_start();
}

/**
 * Ends the current section.
 *
 * @return void
 */
function end_section(): void {
  $context = &template_context();
  $name = $context['current_section'];
  if (!$name) {
    throw new \Exception("No section started.");
  }
  $context['sections'][$name] = ob_get_clean();
  $context['current_section'] = null;
}

/**
 * Yields the content of a section.
 *
 * @param string $name Name of the section.
 * @param string $default Default content if the section is not defined.
 * @return void
 */
function yields(string $name, string $default = ''): void {
  $context = &template_context();
  echo $context['sections'][$name] ?? $default;
}

/**
 * Sets the layout for the current template.
 *
 * @param string $layout Path to the layout template.
 * @return void
 */
function layout(string $layout): void {
  $context = &template_context();
  $context['layout'] = $layout;
}

/**
 * Renders a template.
 *
 * @param string $template Path to the template file.
 * @param array<string, mixed> $data Data to pass to the template.
 * @return void
 */
function render(string $template, array $data = []): void {
  $config = config('templates');
  $debug = $config['debug'];
  $path = rtrim($config['path'], '/') . '/';
  $cache_path = rtrim($config['cache_path'], '/') . '/';
  $template_path = $path . "$template.php";
  if (!file_exists($template_path)) {
    throw new \Exception("Template $template not found.");
  }
  // Ensure the cache directory exists
  if (!$debug && !is_dir($cache_path)) {
    mkdir($cache_path, 0755, true);
  }
  $cache_file = $cache_path . md5($template_path . serialize($data)) . '.php';
  if (file_exists($cache_file) && filemtime($cache_file) >= filemtime($template_path)) {
    include $cache_file;
    return;
  }

  $context = &template_context();
  extract($data, EXTR_OVERWRITE);

  ob_start();
  include $template_path;
  $content = ob_get_clean();

  if (!$debug) {
    file_put_contents($cache_file, $content);
  }

  if ($context['layout']) {
    $layout = $context['layout'];
    $context['layout'] = null; // Reset layout
    render($layout, $data);
  } else {
    echo $content;
  }
}
