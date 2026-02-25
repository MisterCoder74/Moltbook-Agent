<?php
/**
 * View Single Post with Comments
 */

$credsFile = 'moltbook_credentials.json';
$credentials = file_exists($credsFile) ? json_decode(file_get_contents($credsFile), true) : null;

if (!$credentials) {
    header('Location: index.php');
    exit;
}

$postId = $_GET['id'] ?? '';
$sort = $_GET['sort'] ?? 'top';
$message = '';
$error = '';

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $content = $_POST['comment'] ?? '';
    $parentId = $_POST['parent_id'] ?? null;
    
    $commentData = ['content' => $content];
    if ($parentId) {
        $commentData['parent_id'] = $parentId;
    }
    
    $ch = curl_init("https://www.moltbook.com/api/v1/posts/{$postId}/comments");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $credentials['api_key'],
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($commentData)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 || $httpCode === 201) {
        $message = 'Comment posted successfully! 🦞';
        // Redirect per evitare re-submit
        header("Location: view_post.php?id={$postId}&sort={$sort}&success=1");
        exit;
    } else {
        $responseData = json_decode($response, true);
        $error = $responseData['error'] ?? 'Failed to post comment';
        if (isset($responseData['hint'])) {
            $error .= ' - ' . $responseData['hint'];
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $message = 'Comment posted successfully! 🦞';
}

// Get post details
$ch = curl_init("https://www.moltbook.com/api/v1/posts/{$postId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $credentials['api_key']
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$post = null;
if ($httpCode === 200) {
    $data = json_decode($response, true);
    $post = $data['post'] ?? null;
} else {
    $error = 'Failed to load post';
}

// Get comments
$comments = [];
if ($post) {
    $ch = curl_init("https://www.moltbook.com/api/v1/posts/{$postId}/comments?sort={$sort}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $credentials['api_key']
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $hierarchicalComments = $data['comments'] ?? [];
        
        // IMPORTANTE: Appiattire la struttura gerarchica
        $comments = flattenComments($hierarchicalComments);
    }
}

/**
 * Appiattisce i commenti da struttura gerarchica (con replies) a lista piatta
 */
function flattenComments($hierarchicalComments) {
    $flat = [];
    
    foreach ($hierarchicalComments as $comment) {
        // Aggiungi il commento corrente
        $replies = $comment['replies'] ?? [];
        unset($comment['replies']); // Rimuovi replies dal commento
        $flat[] = $comment;
        
        // Ricorsivamente aggiungi tutte le risposte
        if (!empty($replies)) {
            $flattenedReplies = flattenComments($replies);
            $flat = array_merge($flat, $flattenedReplies);
        }
    }
    
    return $flat;
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $time);
}

function renderComments($comments, $parentId = null, $level = 0) {
    foreach ($comments as $comment) {
        // Normalizza parent_id per il confronto
        $commentParentId = $comment['parent_id'] ?? null;
        
        // Confronto migliorato
        $isMatch = false;
        if ($parentId === null) {
            // Cerchiamo commenti top-level (senza parent)
            $isMatch = ($commentParentId === null || $commentParentId === '');
        } else {
            // Cerchiamo risposte a un commento specifico
            $isMatch = ($commentParentId !== null && $commentParentId == $parentId);
        }
        
        if ($isMatch) {
            $indent = $level * 30;
            ?>
            <div class="comment" style="margin-left: <?= $indent ?>px" id="comment-<?= $comment['id'] ?>">
                <div class="comment-header">
                    <a href="agent_profile.php?name=<?= urlencode($comment['author']['name'] ?? 'unknown') ?>" class="author">
                        u/<?= htmlspecialchars($comment['author']['name'] ?? 'unknown') ?>
                    </a>
                    <span>•</span>
                    <span><?= timeAgo($comment['created_at']) ?></span>
                    <span class="vote-count">
                        ⬆️ <?= $comment['upvotes'] ?? 0 ?>
                        ⬇️ <?= $comment['downvotes'] ?? 0 ?>
                    </span>
                    <?php if ($level > 0): ?>
                        <span style="color: #999; font-size: 11px;">↳ reply</span>
                    <?php endif; ?>
                </div>
                <div class="comment-content">
                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                </div>
                <div class="comment-actions">
                    <button onclick="showReplyForm('<?= $comment['id'] ?>')">💬 Reply</button>
                    <button onclick="voteComment('<?= $comment['id'] ?>', 'upvote')">⬆️ Upvote</button>
                </div>
                <div class="reply-form" id="reply-form-<?= $comment['id'] ?>" style="display: none;">
                    <form method="POST">
                        <input type="hidden" name="parent_id" value="<?= htmlspecialchars($comment['id']) ?>">
                        <textarea name="comment" placeholder="Write your reply..." required></textarea>
                        <button type="submit">Post Reply</button>
                        <button type="button" onclick="hideReplyForm('<?= $comment['id'] ?>')">Cancel</button>
                    </form>
                </div>
            </div>
            <?php
            // Ricorsione per renderizzare le risposte di questo commento
            renderComments($comments, $comment['id'], $level + 1);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post ? htmlspecialchars($post['title']) : 'View Post' ?> - Moltbook</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .message {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
        }
        .post-detail {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            background: #fafafa;
        }
        .post-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #666;
        }
        .submolt {
            font-weight: 600;
            color: #667eea;
        }
        .author {
            font-weight: 500;
            color: #667eea;
            text-decoration: none;
        }
        .author:hover {
            text-decoration: underline;
        }
        .post-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }
        .post-content {
            color: #444;
            line-height: 1.7;
            margin-bottom: 15px;
        }
        .post-url {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            padding: 8px 12px;
            background: #e7f3ff;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .post-footer {
            display: flex;
            gap: 20px;
            font-size: 14px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .vote-count {
            font-weight: 600;
            color: #667eea;
        }
        .comment-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .comment-form h3 {
            margin-bottom: 15px;
            color: #333;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
        }
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            margin-right: 10px;
        }
        button:hover {
            background: #5568d3;
        }
        .comment {
            padding: 15px;
            border-left: 2px solid #e0e0e0;
            margin-bottom: 15px;
            background: #fafafa;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .comment:hover {
            background: #f5f5f5;
        }
        .comment-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 12px;
            color: #666;
            flex-wrap: wrap;
        }
        .comment-content {
            color: #444;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .comment-actions button {
            background: #f0f0f0;
            color: #333;
            padding: 6px 12px;
            font-size: 12px;
        }
        .comment-actions button:hover {
            background: #e0e0e0;
        }
        .reply-form {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        .reply-form textarea {
            min-height: 80px;
        }
        .sort-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .sort-controls a {
            padding: 8px 16px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
        .sort-controls a.active {
            background: #667eea;
            color: white;
        }
        .back-button {
            background: #6c757d;
        }
        .back-button:hover {
            background: #5a6268;
        }
        .vote-buttons {
            display: inline-flex;
            gap: 10px;
        }
        .vote-btn {
            background: #f0f0f0;
            color: #333;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 500;
        }
        .vote-btn:hover {
            background: #e0e0e0;
        }
    </style>
    <script>
        function votePost(postId, action) {
            fetch('vote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
        
        function showReplyForm(commentId) {
            document.getElementById('reply-form-' + commentId).style.display = 'block';
        }
        function hideReplyForm(commentId) {
            document.getElementById('reply-form-' + commentId).style.display = 'none';
        }
        function voteComment(commentId, voteType) {
            fetch(`https://www.moltbook.com/api/v1/comments/${commentId}/${voteType}`, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer <?= $credentials['api_key'] ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Vote registered! 🦞');
                    location.reload();
                } else {
                    alert('Failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <a href="feed.php"><button class="back-button">← Back to Feed</button></a>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error && !$post): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($post): ?>
            <div class="post-detail">
                <div class="post-header">
                    <span class="submolt">m/<?= htmlspecialchars($post['submolt']['name'] ?? 'general') ?></span>
                    <span>•</span>
                    <span>Posted by 
                        <a href="agent_profile.php?name=<?= urlencode($post['author']['name'] ?? 'unknown') ?>" class="author">
                            u/<?= htmlspecialchars($post['author']['name'] ?? 'unknown') ?>
                        </a>
                    </span>
                    <span>•</span>
                    <span><?= timeAgo($post['created_at']) ?></span>
                </div>
                
                <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
                
                <?php if (!empty($post['content'])): ?>
                    <div class="post-content">
                        <?= nl2br(htmlspecialchars($post['content'])) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($post['url'])): ?>
                    <a href="<?= htmlspecialchars($post['url']) ?>" target="_blank" class="post-url">
                        🔗 <?= htmlspecialchars($post['url']) ?>
                    </a>
                <?php endif; ?>
                
                <div class="post-footer">
                    <div class="vote-buttons">
                        <button class="vote-btn" onclick="votePost('<?= $post['id'] ?>', 'upvote')">⬆️ Upvote (<?= $post['upvotes'] ?? 0 ?>)</button>
                        <button class="vote-btn" onclick="votePost('<?= $post['id'] ?>', 'downvote')">⬇️ Downvote (<?= $post['downvotes'] ?? 0 ?>)</button>
                    </div>
                    <span>💬 <?= $post['comment_count'] ?? 0 ?> comments</span>
                </div>
            </div>

            <div class="comment-form">
                <h3>💬 Add a Comment</h3>
                <?php if ($error && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <div class="message error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <textarea name="comment" placeholder="Share your thoughts..." required></textarea>
                    <button type="submit">Post Comment 🦞</button>
                </form>
            </div>

            <h2 style="margin-bottom: 15px;">Comments (<?= count($comments) ?>)</h2>

            <div class="sort-controls">
                <a href="?id=<?= $postId ?>&sort=top" class="<?= $sort === 'top' ? 'active' : '' ?>">⭐ Top</a>
                <a href="?id=<?= $postId ?>&sort=new" class="<?= $sort === 'new' ? 'active' : '' ?>">🆕 New</a>
                <a href="?id=<?= $postId ?>&sort=controversial" class="<?= $sort === 'controversial' ? 'active' : '' ?>">🔥 Controversial</a>
            </div>

            <?php if (empty($comments)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">No comments yet. Be the first! 🦞</p>
            <?php else: ?>
                <?php renderComments($comments, null, 0); ?>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>