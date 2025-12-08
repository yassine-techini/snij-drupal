<?php

/**
 * @file
 * Drupal settings for SNIJ - Production on Render.
 */

// Database configuration from environment variable
$databases = [];
if (getenv('DATABASE_URL')) {
  $db_url = parse_url(getenv('DATABASE_URL'));
  $databases['default']['default'] = [
    'database' => ltrim($db_url['path'], '/'),
    'username' => $db_url['user'],
    'password' => $db_url['pass'],
    'host' => $db_url['host'],
    'port' => $db_url['port'] ?? 5432,
    'driver' => 'pgsql',
    'prefix' => '',
  ];
}

// Hash salt from environment
$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT') ?: 'snij-default-hash-salt-change-me';

// Trusted host patterns
$settings['trusted_host_patterns'] = array_filter(
  explode('|', getenv('TRUSTED_HOST_PATTERNS') ?: '^localhost$')
);

// File paths
$settings['file_private_path'] = '/var/www/html/private';
$settings['file_temp_path'] = '/tmp';

// Config sync directory
$settings['config_sync_directory'] = '../config/sync';

// Error handling
$config['system.logging']['error_level'] = 'hide';

// Performance settings
$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;
$config['system.performance']['cache']['page']['max_age'] = 3600;

// JSON:API settings
$config['jsonapi.settings']['read_only'] = FALSE;

// Simple OAuth settings
$settings['simple_oauth.public_key'] = '/var/www/html/keys/public.key';
$settings['simple_oauth.private_key'] = '/var/www/html/keys/private.key';

// Default language
$config['system.site']['default_langcode'] = 'ar';

// Disable update module in production
$config['update.settings']['check']['disabled_extensions'] = TRUE;

// Redis cache (if available)
if (getenv('REDIS_URL')) {
  $redis_url = parse_url(getenv('REDIS_URL'));
  $settings['redis.connection']['host'] = $redis_url['host'];
  $settings['redis.connection']['port'] = $redis_url['port'] ?? 6379;
  if (!empty($redis_url['pass'])) {
    $settings['redis.connection']['password'] = $redis_url['pass'];
  }
  $settings['cache']['default'] = 'cache.backend.redis';
}

// Reverse proxy settings for Render
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = ['127.0.0.1'];
$settings['reverse_proxy_trusted_headers'] = \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO;

// Local settings override
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
