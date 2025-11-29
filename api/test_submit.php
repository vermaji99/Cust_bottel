<?php
// Minimal test to see if API endpoint is reachable
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Test endpoint is working',
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST
]);

