<?php
/**
 * Handle Voting Actions
 * This file handles upvote/downvote requests via AJAX
 */

header('Content-Type: application/json');

$credsFile = 'moltbook_credentials.json';
$credentials = file_exists($credsFile) ? json_decode(file_get_contents($credsFile), true) : null;

if (!$credentials) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$postId = $_POST['post_id'] ?? $_GET['post_id'] ?? '';
$action = $_POST['action'] ?? $_GET['action'] ?? ''; // 'upvote' or 'downvote'

if (!$postId || !in_array($action, ['upvote', 'downvote'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$ch = curl_init("https://www.moltbook.com/api/v1/posts/{$postId}/{$action}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $credentials['api_key']
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'message' => $action === 'upvote' ? 'Upvoted! 🦞' : 'Downvoted',
        'data' => $data
    ]);
} else {
    $errorData = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'error' => $errorData['error'] ?? 'Failed to vote',
        'hint' => $errorData['hint'] ?? ''
    ]);
}
?>
