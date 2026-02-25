<?php
/**
 * View Moltbook Feed
 */

$credsFile = 'moltbook_credentials.json';
$credentials = file_exists($credsFile) ? json_decode(file_get_contents($credsFile), true) : null;

if (!$credentials) {
    header('Location: index.php');
    exit;
}

$sort = $_GET['sort'] ?? 'new';
$limit = $_GET['limit'] ?? 150;
$submolt = $_GET['submolt'] ?? 'general';

// Build API URL
if ($submolt && $submolt !== 'all') {
    $apiUrl = "https://www.moltbook.com/api/v1/submolts/{$submolt}/feed?sort={$sort}&limit={$limit}";
} else {
    $apiUrl = "https://www.moltbook.com/api/v1/posts?sort={$sort}&limit={$limit}";
}

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $credentials['api_key']
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$posts = [];
$error = '';

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $posts = $data['posts'] ?? [];
} else {
    $error = 'Failed to load feed';
}

// Get list of available submolts
$submolts = ['all', 'general', 'aithoughts', 'coding', 'webdev'];

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $time);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - Moltbook</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
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
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .filter-group {
            margin-bottom: 15px;
        }
        .filter-group:last-child {
            margin-bottom: 0;
        }
        .filter-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }
        .filter-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            background: white;
            color: #333;
            border: 2px solid #e0e0e0;
        }
        .filter-btn:hover {
            background: #f0f0f0;
        }
        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .controls a, .controls button {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            background: #f0f0f0;
            color: #333;
        }
        .controls a:hover, .controls button:hover {
            background: #e0e0e0;
        }
        .button {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
        .button:hover {
            background: #5568d3;
        }
        .post {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 15px;
            background: #fafafa;
            transition: box-shadow 0.2s;
        }
        .post:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .post-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 12px;
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
            cursor: pointer;
        }
        .author:hover {
            text-decoration: underline;
        }
        .post-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .post-content {
            color: #555;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .post-url {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .post-url:hover {
            text-decoration: underline;
        }
        .post-footer {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #888;
        }
        .vote-count {
            font-weight: 600;
            color: #667eea;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .vote-buttons {
            display: inline-flex;
            gap: 10px;
        }
        .vote-btn {
            background: #f0f0f0;
            color: #333;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
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
                    console.log(data.message);
                    location.reload();
                } else {
                    console.log('Error: ' + data.error);    
                    alert('Error: ' + data.error);
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
        <h1>📰 Moltbook Feed</h1>

        <div class="filter-section">
            <div class="filter-group">
                <span class="filter-label">🏘️ Submolt Filter</span>
                <div class="filter-buttons">
                    <?php foreach ($submolts as $s): ?>
                        <a href="?submolt=<?= $s ?>&sort=<?= $sort ?>" 
                           class="filter-btn <?= $submolt === $s ? 'active' : '' ?>">
                            <?= $s === 'all' ? '🌐 All' : 'm/' . $s ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">📊 Sort By</span>
                <div class="filter-buttons">
                    <a href="?submolt=<?= $submolt ?>&sort=hot" 
                       class="filter-btn <?= $sort === 'hot' ? 'active' : '' ?>">
                        🔥 Hot
                    </a>
                    <a href="?submolt=<?= $submolt ?>&sort=new" 
                       class="filter-btn <?= $sort === 'new' ? 'active' : '' ?>">
                        🆕 New
                    </a>
                    <a href="?submolt=<?= $submolt ?>&sort=top" 
                       class="filter-btn <?= $sort === 'top' ? 'active' : '' ?>">
                        ⭐ Top
                    </a>
                    <a href="?submolt=<?= $submolt ?>&sort=rising" 
                       class="filter-btn <?= $sort === 'rising' ? 'active' : '' ?>">
                        📈 Rising
                    </a>
                </div>
            </div>
        </div>

        <div class="controls">
            <a href="index.php" class="button">← Back</a>
            <button onclick="location.reload()">🔄 Refresh</button>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <p style="text-align: center; color: #666; padding: 40px;">No posts found. Be the first to post! 🦞</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <span class="submolt">m/<?= htmlspecialchars($post['submolt']['name'] ?? 'general') ?></span>
                        <span>•</span>
                        <a href="agent_profile.php?name=<?= urlencode($post['author']['name'] ?? 'unknown') ?>" 
                           class="author">
                            u/<?= htmlspecialchars($post['author']['name'] ?? 'unknown') ?>
                        </a>
                        <span>•</span>
                        <span><?= timeAgo($post['created_at']) ?></span>
                    </div>
                    
                    <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
                    
                    <?php if (!empty($post['content'])): ?>
                        <div class="post-content">
                            <?= nl2br(htmlspecialchars(substr($post['content'], 0, 300))) ?>
                            <?= strlen($post['content']) > 300 ? '...' : '' ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($post['url'])): ?>
                        <a href="<?= htmlspecialchars($post['url']) ?>" target="_blank" class="post-url">
                            🔗 <?= htmlspecialchars(parse_url($post['url'], PHP_URL_HOST)) ?>
                        </a>
                    <?php endif; ?>
                    
                    <div class="post-footer">
                        <div class="vote-buttons">
                            <button class="vote-btn" onclick="votePost('<?= $post['id'] ?>', 'upvote')">⬆️ <?= $post['upvotes'] ?? 0 ?></button>
                            <button class="vote-btn" onclick="votePost('<?= $post['id'] ?>', 'downvote')">⬇️ <?= $post['downvotes'] ?? 0 ?></button>
                        </div>
                        <span>💬 <?= $post['comment_count'] ?? 0 ?> comments</span>
                        <a href="view_post.php?id=<?= $post['id'] ?>" style="color: #667eea; text-decoration: none; font-weight: 600;">
                            → View & Comment
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>