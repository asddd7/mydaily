<?php

header('Content-Type: application/json');

$year = $_GET['year'] ?? date('Y');

$file = __DIR__ . "/holidays_$year.json";

if (!file_exists($file)) {
    echo json_encode([]);
    exit;
}

echo file_get_contents($file);