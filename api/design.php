<?php
require __DIR__ . '/bootstrap.php';
$user = api_require_user();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405);
}

$designKey = $_GET['id'] ?? $_GET['key'] ?? null;
if (!$designKey) {
    json_response(['success' => false, 'error' => 'MISSING_ID'], 422);
}

$stmt = db()->prepare('SELECT * FROM designs WHERE (design_key = ? OR id = ?) AND user_id = ? LIMIT 1');
$stmt->execute([$designKey, (int) $designKey, $user['id']]);
$design = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$design) {
    json_response(['success' => false, 'error' => 'NOT_FOUND'], 404);
}

$design['meta_json'] = json_decode($design['meta_json'] ?? '[]', true);
$design['file_url'] = app_config('app_url') . '/' . str_replace('\\', '/', $design['file_path']);
$design['thumbnail_url'] = app_config('app_url') . '/' . str_replace('\\', '/', $design['thumbnail_path']);

json_response([
    'success' => true,
    'design' => $design,
]);





