<?php

declare(strict_types=1);

namespace atom;

/**
 * Handles configuration
 * 
 * @param string|null $path The configuration path/key to get or set
 * @param mixed|null $data The data to set for the specified key
 * @return mixed
 */
function config(?string $path = null, $data = null) {
  // defaults
  static $config = [
    'template' => [
      'debug' => false,
      'path' => __DIR__ . '/../templates',
      'cache_path' => __DIR__ . '/../cache',
    ],
  ]; 
  $ref = &$config;
  if ($path) {
    $token = strtok($path, '.');
    while (false !== $token) {
      $ref = &$ref[$token];
      $token = strtok('.');
    }
    if ($data) {
      $ref = is_array($data) && is_array($ref) ? array_merge($ref, $data) : $data;
    }
  }
  return $ref;
}
