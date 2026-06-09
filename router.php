<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$mimeTypes = ['css'=>'text/css; charset=utf-8','js'=>'application/javascript; charset=utf-8','webp'=>'image/webp','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','svg'=>'image/svg+xml','ico'=>'image/x-icon','gif'=>'image/gif'];
$f = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($f) && is_file($f)) {
    $e = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (isset($mimeTypes[$e])) header('Content-Type: '.$mimeTypes[$e]);
    header('Content-Length: '.filesize($f));
    readfile($f); return true;
}
$a = __DIR__ . $uri;
if (str_starts_with($uri, '/assets/') && file_exists($a) && is_file($a)) {
    $e = strtolower(pathinfo($a, PATHINFO_EXTENSION));
    if (isset($mimeTypes[$e])) header('Content-Type: '.$mimeTypes[$e]);
    header('Content-Length: '.filesize($a));
    readfile($a); return true;
}
require __DIR__ . '/public/index.php';
