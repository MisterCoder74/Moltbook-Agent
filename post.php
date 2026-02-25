<?php
/**
 * Create Moltbook Post
 */

$credsFile = 'moltbook_credentials.json';
$credentials = file_exists($credsFile) ? json_decode(file_get_contents($credsFile), true) : null;

if (!$credentials) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submolt = $_POST['submolt'] ?? 'general';
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $url = $_POST['url'] ?? '';
    
    $postData = [
'submolt_name' => $submolt,
'title' => $title
];

    
    if ($url) {
        $postData['url'] = $url;
    } else {
        $postData['content'] = $content;
    }
    
$ch = curl_init('https://www.moltbook.com/api/v1/posts');

curl_setopt_array($ch, [
CURLOPT_RETURNTRANSFER => true,
CURLOPT_POST => true,
CURLOPT_HTTPHEADER => [
'Authorization: Bearer ' . $credentials['api_key'], // ← CHANGED
'Content-Type: application/json'
],
CURLOPT_POSTFIELDS => json_encode($postData) // ← This line was missing before
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
    
    if ($httpCode === 200 || $httpCode === 201) {
        $message = 'Post created successfully! 🦞';
    } else {
        $responseData = json_decode($response, true);
        $error = $responseData['error'] ?? 'Failed to create post';
        if (isset($responseData['hint'])) {
            $error .= ' - ' . $responseData['hint'];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Moltbook</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 700px;
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
            margin-bottom: 30px;
            font-size: 14px;
        }
        .message {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            resize: vertical;
            min-height: 150px;
        }
        button, .button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        button:hover, .button:hover {
            background: #5568d3;
        }
        .button.secondary {
            background: #6c757d;
        }
        .button.secondary:hover {
            background: #5a6268;
        }
        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
            color: #0c5460;
        }
        .post-type {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .post-type button {
            flex: 1;
            margin: 0;
        }
        .post-type button.active {
            background: #28a745;
        }
    </style>
    <script>
        function switchPostType(type) {
            if (type === 'text') {
                document.getElementById('content-group').style.display = 'block';
                document.getElementById('url-group').style.display = 'none';
                document.getElementById('content').required = true;
                document.getElementById('url').required = false;
                document.getElementById('url').value = '';
            } else {
                document.getElementById('content-group').style.display = 'none';
                document.getElementById('url-group').style.display = 'block';
                document.getElementById('content').required = false;
                document.getElementById('url').required = true;
                document.getElementById('content').value = '';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>📝 Create Post</h1>
        <p class="subtitle">Share your thoughts with the Moltbook community</p>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="info-box">
            <p><strong>⏱️ Rate Limit:</strong> 1 post per 30 minutes</p>
            <p><strong>💡 Tip:</strong> Post quality content that adds value to the community</p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Submolt (Community)</label>
                <select name="submolt" required>
                    <option value="general">m/general</option>
                    <option value="aithoughts">m/aithoughts</option>
                    <option value="coding">m/coding</option>
                    <option value="webdev">m/webdev</option>
                </select>
            </div>

            <div class="form-group">
                <label>Post Title</label>
                <input type="text" name="title" placeholder="Enter an engaging title..." required maxlength="300">
            </div>

            <div class="post-type">
                <button type="button" class="active" onclick="switchPostType('text'); this.classList.add('active'); this.nextElementSibling.classList.remove('active');">Text Post</button>
                <button type="button" onclick="switchPostType('link'); this.classList.add('active'); this.previousElementSibling.classList.remove('active');">Link Post</button>
            </div>

            <div class="form-group" id="content-group">
                <label>Content</label>
                <textarea name="content" id="content" placeholder="Share your thoughts..." required></textarea>
            </div>

            <div class="form-group" id="url-group" style="display: none;">
                <label>URL</label>
                <input type="url" name="url" id="url" placeholder="https://example.com">
            </div>

            <button type="submit">Post to Moltbook 🦞</button>
            <a href="index.php" class="button secondary">Cancel</a>
                <a href="index.php" class="button">← Back to Control Panel</a>
        </form>
    </div>
</body>
</html>
