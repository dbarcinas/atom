<?php

use function atom\{config, template_context, render};

beforeEach(function () {
  config('templates', [
    'path' => __DIR__ . '/templates',
    'cache_path' => __DIR__ . '/templates/cache',
  ]);
 
  $this->templatesPath = __DIR__ . '/templates/';
  $this->cachePath = $this->templatesPath . 'cache/';

  // Create templates/cache directories
  if (!is_dir($this->templatesPath)) {
    mkdir($this->templatesPath, 0755, true);
  }
  if (!is_dir($this->cachePath)) {
    mkdir($this->cachePath, 0755, true);
  }

  // Reset the template context before each test
  $context = &template_context();
  $context['sections'] = [];
  $context['current_section'] = null;
  $context['layout'] = null; 
});

afterEach(function () {
  // Clean up cache and views
  array_map('unlink', glob($this->templatesPath . '*.php'));
  array_map('unlink', glob($this->cachePath . '*.php'));
});

it('renders a simple template without a layout', function () {
  $template = 'simple';
  file_put_contents($this->templatesPath . "$template.php", '<?php echo "hello";');
  ob_start();
  render($template);
  $output = ob_get_clean();
  expect($output)->toBe('hello');
});

it('renders a template with a layout', function () {
  $layout = 'layout';
  $child = 'child';
  file_put_contents($this->templatesPath . "$layout.php", <<<PHP
<!doctype html>
<html>
<head><title><?php atom\\yields('title', 'Default Title'); ?></title></head>
<body>
  <main><?php atom\\yields('content'); ?></main>
</body>
</html>
PHP);

  file_put_contents($this->templatesPath . "$child.php", <<<PHP
<?php atom\\layout('layout'); ?>
<?php atom\\section('title'); ?>Test Title<?php atom\\end_section(); ?>
<?php atom\\section('content'); ?>Hello from Child<?php atom\\end_section(); ?>
PHP);

  ob_start();
  render($child);
  $output = ob_get_clean();
  $expected = <<<PHP
<!doctype html>
<html>
<head><title>Test Title</title></head>
<body>
  <main>Hello from Child</main>
</body>
</html>
PHP;
  expect($expected)->toBe($output);
});

it('renders a template with data', function () { 
  $template = 'var';
  file_put_contents($this->templatesPath . "$template.php", '<?php echo $foo;');
  ob_start();
  render($template, ['foo' => 'bar']);
  $output = ob_get_clean();
  expect($output)->toBe('bar');
});

it('caches a rendered template', function () {
  $template = 'cached';
  file_put_contents($this->templatesPath . "$template.php", "<?php echo 'Hello, world!';");
  ob_start();
  render($template);
  $output = ob_get_clean();
  expect($output)->toBe('Hello, world!');
  // check if cache file exists
  $cachedFile = $this->cachePath . md5($this->templatesPath . "$template.php" . serialize([])) . '.php';
  expect($cachedFile)->toBeFile();
});

it('uses cached template if valid', function () {
  // Create a view
  $template = 'cache_test';
  file_put_contents($this->templatesPath . "$template.php", "<?php echo 'Original Content';");

  // Render the template (creates the cache)
  ob_start();
  render($template);
  ob_end_clean();

  // Modify the original template (cache should prevent changes from being used)
  file_put_contents($this->templatesPath . "$template.php", "<?php echo 'Updated Content';");

  // Render again and verify cached content is used
  ob_start();
  render($template);
  $output = ob_get_clean();

  expect($output)->toBe('Original Content');
});

it('regenerates cache if template changes', function () {
  // Create a view
  $template = 'regenerate_cache';
  file_put_contents($this->templatesPath . "$template.php", "<?php echo 'First Version';");

  // Render the template (creates the cache)
  ob_start();
  render($template);
  ob_end_clean();

  // Modify the template
  sleep(1); // Ensure filemtime changes
  file_put_contents($this->templatesPath . "$template.php", "<?php echo 'Second Version';");

  // Render again (should regenerate cache)
  ob_start();
  render($template);
  $output = ob_get_clean();

  expect($output)->toBe('Second Version');
});

it('does not cache if disabled', function () {
  // disable cache
  config('templates.debug', true);
  // Create a view
  $template = 'no_cache';
  file_put_contents($this->templatesPath . "{$template}.php", "<?php echo 'Non-Cached Content';");

  // Render without caching
  ob_start();
  render($template);
  $output = ob_get_clean();

  // Verify output
  expect($output)->toBe('Non-Cached Content');

  // Check if cache file does not exist
  $cacheFile = $this->cachePath . md5($this->templatesPath . "$template.php" . serialize([])) . '.php';
  expect($cacheFile)->not()->toBeFile();
});
