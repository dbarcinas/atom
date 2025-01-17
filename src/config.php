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
function config(?string $path = null, $value = null) {
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
    if ($value) {
      $ref = is_array($value) ? array_merge($ref, $value) : $value;
    }
  }
  return $ref;
}
