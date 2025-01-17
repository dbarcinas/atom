<?php

use function atom\config;

it('can set and get a configuration value', function () {
  config('app.name', 'My App');
  $value = config('app.name');
  expect($value)->toBe('My App');
});

it('supports nested configuration with dot notation', function () {
  config('app', [
    'deep' => [
      'nested' => [
        'config' => 'baz',
      ],
    ],
  ]);
  $value = config('app.deep.nested.config');
  expect($value)->toBe('baz');
});

it('can retrieve a default value if key does not exist', function () {
  $value = config('nonexistent.name', 'default value');
  expect($value)->toBe('default value');
});
