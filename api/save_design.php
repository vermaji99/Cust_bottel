<?php
require __DIR__ . '/bootstrap.php';
ensure_json_request();
$user = api_require_user();
$body = read_json_body();

if (empty($body['image'])) {
    json_response(['success' => false, 'error' => 'IMAGE_REQUIRED', 'message' => 'Missing design image.'], 422);
}

$format = strtolower($body['format'] ?? 'png');
if (!in_array($format, ['png', 'jpg', 'jpeg'], true)) {
    json_response(['success' => false, 'error' => 'UNSUPPORTED_FORMAT'], 422);
}
$format = $format === 'jpeg' ? 'jpg' : $format;

$imageData = $body['image'];
if (str_starts_with($imageData, 'data:image')) {
    $imageData = substr($imageData, strpos($imageData, ',') + 1);
}
$binary = base64_decode($imageData);
if ($binary === false) {
    json_response(['success' => false, 'error' => 'INVALID_IMAGE'], 422);
}

$meta = $body['meta'] ?? [];
if (!is_array($meta)) {
    json_response(['success' => false, 'error' => 'INVALID_META'], 422);
}

$designDir = app_config('paths.designs');
$thumbDir = app_config('paths.thumbnails');
if (!is_dir($designDir)) {
    mkdir($designDir, 0755, true);
}
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

$designKey = bin2hex(random_bytes(8));
$filename = sprintf('design_%s.%s', $designKey, $format);
$filepath = $designDir . '/' . $filename;

if (file_put_contents($filepath, $binary) === false) {
    json_response(['success' => false, 'error' => 'WRITE_FAILED'], 500);
}

$thumbPath = $thumbDir . '/thumb_' . $designKey . '.jpg';
$imageResource = imagecreatefromstring($binary);
if ($imageResource) {
    $width = imagesx($imageResource);
    $height = imagesy($imageResource);
    $target = 320;
    $ratio = $width / $height;
    if ($ratio > 1) {
        $newWidth = $target;
        $newHeight = (int) ($target / $ratio);
    } else {
        $newHeight = $target;
        $newWidth = (int) ($target * $ratio);
    }
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($thumb, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagejpeg($thumb, $thumbPath, 85);
    imagedestroy($thumb);
    imagedestroy($imageResource);
}

$normalizePath = static function (string $path): string {
    $root = str_replace('\\', '/', app_config('paths.root'));
    $clean = str_replace('\\', '/', $path);
    $relative = ltrim(str_replace($root, '', $clean), '/');
    return $relative;
};

$stmt = db()->prepare('
    INSERT INTO designs (user_id, design_key, file_path, thumbnail_path, meta_json, bottle_shape, bottle_color)
    VALUES (?, ?, ?, ?, ?, ?, ?)
');
$bottleShape = $meta['bottleShape'] ?? null;
$bottleColor = $meta['bottleColor'] ?? null;
$metaJson = json_encode($meta);
$stmt->execute([
    $user['id'],
    $designKey,
    $normalizePath($filepath),
    $normalizePath($thumbPath),
    $metaJson,
    $bottleShape,
    $bottleColor,
]);
$designId = (int) db()->lastInsertId();

$designPayload = [
    'design_key' => $designKey,
    'design_id' => $designId,
    'image_url' => app_config('app_url') . '/' . $normalizePath($filepath),
    'thumbnail_url' => app_config('app_url') . '/' . $normalizePath($thumbPath),
];

send_design_upload_email($user, $designPayload);

json_response([
    'success' => true,
    'design' => $designPayload,
]);
