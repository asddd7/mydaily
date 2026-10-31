<?php

$viewPath = sys_get_temp_dir() . '/laravel-views';

if (!is_dir($viewPath)) {
    mkdir($viewPath, 0755, true);
}

// Arahkan hasil kompilasi Blade ke folder sementara Vercel.
putenv('VIEW_COMPILED_PATH=' . $viewPath);
$_ENV['VIEW_COMPILED_PATH'] = $viewPath;
$_SERVER['VIEW_COMPILED_PATH'] = $viewPath;

require __DIR__ . '/../public/index.php';