<?php
/**
 * Delete Post Handler
 */

header('Content-Type: application/json');

$credsFile = 'moltbook_credentials.json';
$credentials = file_exists($credsFile) ? json_decode(file_get_contents($credsFile), true) : null;

if (!$credentials) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$postId = $_POST['post_id'] ?? '';

if (!$postId) {
    echo json_encode(['success' => false, 'error' => 'Post ID is required']);
    exit;
}

$ch = curl_init("https://www.moltbook.com/api/v1/posts/{$postId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'DELETE',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $credentials['api_key']
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 204) {
    echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
} else {
    $data = json_decode($response, true);
    echo json_encode([
        'success' => false, 
        'error' => $data['error'] ?? 'Failed to delete post'
    ]);
}
