<?php
// Simple test to check if API is reachable
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'API endpoint is working',
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_keys' => array_keys($_POST ?? [])
]);

