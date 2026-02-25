<?php
/**
 * Moltbook Control Panel
 * Manage your Moltbook agent from web hosting
 */

session_start();

// Load credentials if they exist
$credsFile = 'moltbook_credentials.json';
$credentials = file_exists($credsFile) ? json_decode(file_get_contents($credsFile), true) : null;

// Handle actions
$action = $_GET['action'] ?? 'home';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'register') {
        // Register with Moltbook
        $name = $_POST['name'] ?? 'WebDevStudio';
        $description = $_POST['description'] ?? 'Full-stack web developer agent';
        
        $ch = curl_init('https://www.moltbook.com/api/v1/agents/register');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'name' => $name,
                'description' => $description
            ])
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 201) {
            $data = json_decode($response, true);
            if (isset($data['agent'])) {
                $credentials = [
                    'api_key' => $data['agent']['api_key'],
                    'agent_name' => $name,
                    'claim_url' => $data['agent']['claim_url'],
                    'verification_code' => $data['agent']['verification_code'],
                    'registered_at' => date('Y-m-d H:i:s'),
                    'status' => 'pending_claim'
                ];
                file_put_contents($credsFile, json_encode($credentials, JSON_PRETTY_PRINT));
                $message = 'Successfully registered! Check the claim status below.';
            }
        } else {
            $error = 'Registration failed: ' . $response;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moltbook Control Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .status-box {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .status-box.success { border-left-color: #28a745; }
        .status-box.warning { border-left-color: #ffc107; }
        .status-box.error { border-left-color: #dc3545; }
        .credential-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        .credential-item label {
            display: block;
            font-weight: 600;
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .credential-item .value {
            font-family: 'Courier New', monospace;
            color: #333;
            word-break: break-all;
        }
        form {
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5568d3;
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
        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .action-card {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        .action-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }
        .action-card p {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }
        .action-card a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🦞 Moltbook Control Panel</h1>
        <p class="subtitle">Manage your AI agent's social network presence</p>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!$credentials): ?>
            <!-- Registration Form -->
            <div class="status-box warning">
                <h2 style="margin-bottom: 10px;">⚠️ Not Registered</h2>
                <p>You need to register your agent with Moltbook first.</p>
            </div>

            <form method="POST" action="?action=register">
                <div class="form-group">
                    <label>Agent Name</label>
                    <input type="text" name="name" value="WebDevStudio" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required>Senior full-stack web developer specializing in vanilla HTML, CSS, JavaScript, PHP, and MySQL. Creating clean, maintainable code for personal web projects.</textarea>
                </div>
                <button type="submit">Register with Moltbook</button>
            </form>

        <?php else: ?>
            <!-- Registered Status -->
            <div class="status-box <?= $credentials['status'] === 'claimed' ? 'success' : 'warning' ?>">
                <h2 style="margin-bottom: 10px;">
                    <?= $credentials['status'] === 'claimed' ? '✅ Claimed & Active' : '⏳ Pending Claim' ?>
                </h2>
                <p>Registered: <?= htmlspecialchars($credentials['registered_at']) ?></p>
            </div>

            <div class="credential-item">
                <label>Agent Name</label>
                <div class="value"><?= htmlspecialchars($credentials['agent_name']) ?></div>
            </div>

            <div class="credential-item">
                <label>API Key</label>
                <div class="value"><?= htmlspecialchars($credentials['api_key']) ?></div>
            </div>

            <?php if (isset($credentials['claim_url'])): ?>
                <div class="credential-item">
                    <label>Claim URL</label>
                    <div class="value">
                        <a href="<?= htmlspecialchars($credentials['claim_url']) ?>" target="_blank">
                            <?= htmlspecialchars($credentials['claim_url']) ?>
                        </a>
                    </div>
                </div>

                <div class="credential-item">
                    <label>Verification Code</label>
                    <div class="value"><?= htmlspecialchars($credentials['verification_code']) ?></div>
                </div>

                <div class="status-box">
                    <h3 style="margin-bottom: 10px;">📋 To Claim Your Agent:</h3>
                    <ol style="margin-left: 20px; line-height: 1.6;">
                        <li>Visit the claim URL above</li>
                        <li>Follow the instructions on that page</li>
                        <li>You'll need to verify via Twitter (or check if there's an alternative method)</li>
                    </ol>
                </div>
            <?php endif; ?>

            <div class="actions">
                <div class="action-card">
                    <h3>📊 Check Status</h3>
                    <p>See if your agent has been claimed</p>
                    <a href="check_status.php"><button>Check Status</button></a>
                </div>
                <div class="action-card">
                    <h3>📝 Make Post</h3>
                    <p>Create a new post on Moltbook</p>
                    <a href="post.php"><button>Create Post</button></a>
                </div>
                <div class="action-card">
                    <h3>📰 View Feed</h3>
                    <p>See what's happening</p>
                    <a href="feed.php"><button>View Feed</button></a>
                </div>
                <div class="action-card">
                    <h3>📋 Your Posts</h3>
                    <p>View and manage your posts</p>
                    <a href="your_posts.php"><button>Your Posts</button></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
