<?php

use function atom\{route, dispatch, url_for};

it('can register a route and dispatch it', function () {
  route('GET', '/test', function () {
    echo 'test route';
  });

  ob_start();
  $_SERVER['REQUEST_METHOD'] = 'GET';
  $_SERVER['REQUEST_URI'] = '/test';
  dispatch();
  $output = ob_get_clean();

  expect($output)->toBe('test route');
});

it('returns 404 for an unknown route', function () {
  ob_start();
  $_SERVER['REQUEST_METHOD'] = 'GET';
  $_SERVER['REQUEST_URI'] = '/unknown';
  dispatch();
  $output = ob_get_clean();

  expect($output)->toBe('404 Not Found');
});

it('should capture route params', function () {
  route('GET', '/test/{id:int}/{name:slug}', function ($id, $name) {
    echo "id: $id name: $name";
  });

  ob_start();
  $_SERVER['REQUEST_METHOD'] = 'GET';
  $_SERVER['REQUEST_URI'] = '/test/1/foo';
  dispatch();
  $output = ob_get_clean();
  expect($output)->toBe('id: 1 name: foo');
});

it('should call middleware', function () {
  $mw = function ($request, $method, $params) {
    if ($params['id'] !== '2') {
      echo 'Unauthorized';
      return false;
    }
    return true;
  };

  route(['get'], '/user/{id:int}', function ($id) {
    echo "user id: $id";
  }, '@user', [$mw]);

  ob_start();
  $_SERVER['REQUEST_METHOD'] = 'GET';
  $_SERVER['REQUEST_URI'] = '/user/1';
  dispatch();
  $output = ob_get_clean();
  expect($output)->toBe('Unauthorized');
});

it('should generate route url', function () {
  route('get', '/foo', function () {}, 'foo');
  route('get', '/bar', function () {
    echo 'foo route: ' . url_for('foo');
  });
  ob_start();
  $_SERVER['REQUEST_METHOD'] = 'GET';
  $_SERVER['REQUEST_URI'] = '/bar';
  dispatch();
  $output = ob_get_clean();
  expect($output)->toBe('foo route: /foo');
});
