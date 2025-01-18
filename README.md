
# Atom

The procedural PHP micro framework.

## Features

- Routing
- Templates
- [Inertia](https://inertiajs.com/) (WIP)
## Installation

Atom requires [PHP](https://php.net/) >= 8.2

```sh
composer require dbarcinas/atom
```
    
## Configuration

```php
use function atom\config;

// Set config
config('app.name', 'Atom');
config('foo', 'bar');

// Get config
$name = config('app.name');
echo $name; // 'Atom'

// Retrieve all config
$config = config();
print_r($config);

// Prints out
[
    'app' => [
        'name' => 'Atom',
    ],
    'foo' => 'bar'
]

// Configuration can be retrieved using dot notation
echo config('foo'); // 'bar'
echo config('app.name') // 'Atom'
```

## Routing

**Basic Usage**

```php
use function atom\{dispatch, route, url_for};

// Basic route
route('GET', '/foo', function () {
    // ...
});

// Multiple methods
route(['GET', 'POST'], '/bar', function () {
    // ...
});

// Route names
// Method verbs can be lowercased
route(['put'], '/baz', function () {
    // ...
}, '@baz');

route('get', '/qux', function () {
    echo url_for('@baz'); // prints '/baz'
});

// Dispatch
dispatch();
```

**Route Parameters**

```php
route('GET', '/foo/{id}', function ($id) {
    echo "ID: $id";
});

// Parameter types
route('POST', '/foo/bar/{id:int}, function ($id) {
    echo "POST ID: $id";
});
```

**Middlewares**

```php
// Auth middleware
function auth(string $request, string $method, array $params): bool {
    if ($params['id'] > 1) {
        return false;
    }
    // Must return boolean
    return true;
}

route('get', '/foo/{id:int}', function ($id) {
    // ...
}, '@foo', 'auth');

// Multiple middlewares
route('get', '/bar', function () {
    // ...
}, '@bar', ['auth', 'logger']);
```

**Route Groups**

```php
use function atom\{dispatch, stack};

// Grouping routes in atom are called stacks
// $route is delegated to 'route(...)'
stack('/api/user', function ($route) {
    $route('GET', '/{id:int}' function ($id) {
        // Maps to '/api/user/1'
        // ...
    });

    // Middleware example
    $route(['GET', 'POST'], '/feed', function () {
        // Maps to '/api/user/feed'
        // ...
    }, '@api.user.feed', ['auth']);
});

// Middleware
stack('/dashboard', function ($route) {
    // ...
}, ['auth', 'logger']);

dispatch();
```

**Custom 404 Page**

```php
use function atom\{route, dispatch};

// Routes
// route(...);
// route(...);

// Dispatch with a custom error handler
dispatch(function ($request, $method) {
    echo '404 Not Found.';
});
```

**Note:** Routes are automatically cached for faster response and improved performance speed.

## Templates

**Basic Usage**

```php
use function atom\{config, dispatch, route, render}

// Set templates path
config('templates.path', __DIR__ . '/../templates');

route('get', '/foo', function () {
    // '.php' extension is optional
    render('foo', [
        'bar' => 'baz',
    ]);
});

dispatch();

// /templates/foo.php
<div>
  <h1>foo <?= $bar ?></h1>
</div>
```

**Layouts**

```php
// /templates/layout.php
<html>
  <head>
    <title><?php atom\yields('title', 'Default'); ?></title>
  </head>
  <main>
    <?php atom\yields('content'); ?>
  </main>
</html>

// /templates/foo.php
<?php atom\layout('layout'); ?>

<?php atom\section('title') ?>
  Foo
<?php atom\end_section(); ?>

<?php atom\section('content') ?>
  <p><?= $punch_line; ?></p>
<?php atom\end_section(); ?>

// App
route('get', '/foo', function () {
    render('foo', [
        'punch_line' => 'Atom against the mainstream!',
    ]);
});

dispatch();
```

**Caching**

By default, templates are cached. If your in development mode, it's best to disable this.

```php
// Setting template cache path
config('templates.cache_path', __DIR__ . '/../cache');

// Disable cache
config('templates.debug', true);
```

## Inertia

[Inertia](https://inertiajs.com/) support is currently a work in progress. Check out the development branch!


## Running Tests

To run tests, run the following command

```sh
composer test
```


## License

[MIT](https://choosealicense.com/licenses/mit/)
