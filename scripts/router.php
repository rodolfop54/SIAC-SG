<?php

// Local PHP development router. Public assets are served by PHP itself.
$public = realpath(__DIR__.'/../public');
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = realpath($public.$path);
if ($path !== '/' && $file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}
require $public.'/index.php';
