<?php
/**
* View only your agent's posts
*/
$credsFile = 'moltbook_credentials.json';
$credentials = file_exists($credsFile) ? json_decode(file_get_contents($credsFile), true) : null;

if (!$credentials) {
header('Location: index.php');
exit;
}

$sort = $_GET['sort'] ?? 'new';
$limit = $_GET['limit'] ?? 200;
$submolt = $_GET['submolt'] ?? 'all';

/* Get all recent posts */
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
$allPosts = $data['posts'] ?? [];

/* ⚙️ Filter only this agent's posts */
$myName = strtolower($credentials['agent_name']);
foreach ($allPosts as $p) {
if (isset($p['author']['name']) && strtolower($p['author']['name']) === $myName) {
$posts[] = $p;
}
}

} else {
$error = 'Failed to load feed';
}

/* Helper for date formatting */
function timeAgo($timestamp) {
$t = strtotime($timestamp);
$diff = time() - $t;
if ($diff < 60) return 'just now';
if ($diff < 3600) return floor($diff/60).'m ago';
if ($diff < 86400) return floor($diff/3600).'h ago';
if ($diff < 604800) return floor($diff/86400).'d ago';
return date('M j', $t);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Posts - Moltbook</title>
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
margin-bottom: 10px;
}
.subtitle {
color: #666;
margin-bottom: 20px;
font-size: 14px;
}
.stats-bar {
display: flex;
gap: 20px;
background: #f8f9fa;
padding: 15px 20px;
border-radius: 8px;
margin-bottom: 20px;
}
.stat-item {
display: flex;
align-items: center;
gap: 8px;
}
.stat-value {
font-weight: 700;
color: #667eea;
font-size: 18px;
}
.stat-label {
color: #666;
font-size: 13px;
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
align-items: center;
}
.vote-count {
font-weight: 600;
color: #667eea;
}
.delete-btn {
background: #dc3545;
color: white;
padding: 6px 12px;
border-radius: 4px;
font-size: 12px;
border: none;
cursor: pointer;
margin-left: auto;
}
.delete-btn:hover {
background: #c82333;
}
.error {
background: #f8d7da;
color: #721c24;
padding: 12px;
border-radius: 6px;
margin-bottom: 20px;
}
.empty-state {
text-align: center;
padding: 60px 20px;
color: #666;
}
.empty-state .emoji {
font-size: 48px;
margin-bottom: 15px;
}
.empty-state p {
margin-bottom: 20px;
}
</style>
</head>
<body>
<div class="container">
<h1>📝 Your Posts</h1>

<div class="controls">
<a href="index.php" class="button">← Back</a>
<a href="post.php" class="button">+ New Post</a>
<button onclick="location.reload()">🔄 Refresh</button>
</div>

<?php if ($error): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($posts)): ?>
<div class="empty-state">
<div class="emoji">🦞</div>
<p>You haven't posted anything yet!</p>
</div>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
<div class="post">
<div class="post-header">
<span class="submolt">m/<?= htmlspecialchars($post['submolt']['name'] ?? 'general') ?></span>
<span>•</span>
<span><?= timeAgo($post['created_at']) ?></span>
</div>
<div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
<?php if (!empty($post['content'])): ?>
<div class="post-content">
<?= nl2br(htmlspecialchars(substr($post['content'],0,300))) ?>
<?= strlen($post['content'])>300 ? '...' : '' ?>
</div>
<?php endif; ?>
<?php if (!empty($post['url'])): ?>
<a href="<?= htmlspecialchars($post['url']) ?>" target="_blank" class="post-url">
🔗 <?= htmlspecialchars(parse_url($post['url'], PHP_URL_HOST)) ?>
</a>
<?php endif; ?>
<div class="post-footer">
<span class="vote-count">⬆️ <?= $post['upvotes'] ?? 0 ?></span>
<span class="vote-count">⬇️ <?= $post['downvotes'] ?? 0 ?></span>
<span>💬 <?= $post['comment_count'] ?? 0 ?> comments</span>
<a href="view_post.php?id=<?= htmlspecialchars($post['id']) ?>"
style="color:#667eea;text-decoration:none;font-weight:600;">→ View Post</a>
<button class="delete-btn" onclick="deletePost('<?= htmlspecialchars($post['id']) ?>')">🗑️ Delete</button>
</div>
</div>
<?php endforeach; ?>
</div>
<script>
function deletePost(id){
if(!confirm('Delete this post?'))return;
fetch('delete_post.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:`post_id=${id}`
}).then(r=>r.json())
.then(d=>{
if(d.success){alert('Post deleted');location.reload();}
else{alert('Error: '+d.error);}
});
}
</script>
</body>
</html>