<?php
/**
 * Check Moltbook Claim Status
 */

$credsFile = 'moltbook_credentials.json';
$credentials = file_exists($credsFile) ? json_decode(file_get_contents($credsFile), true) : null;

if (!$credentials) {
    header('Location: index.php');
    exit;
}

$statusData = null;
$error = '';

// Check status with Moltbook API
$ch = curl_init('https://www.moltbook.com/api/v1/agents/status');
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
    $statusData = json_decode($response, true);
    
    // Update credentials file if status changed
    if (isset($statusData['status'])) {
        $credentials['status'] = $statusData['status'];
        file_put_contents($credsFile, json_encode($credentials, JSON_PRETTY_PRINT));
    }
} else {
    $error = 'Failed to check status: ' . $response;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Status - Moltbook</title>
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
            margin-bottom: 30px;
        }
        .status-box {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .status-box.success { border-left-color: #28a745; background: #d4edda; }
        .status-box.warning { border-left-color: #ffc107; background: #fff3cd; }
        .status-box.error { border-left-color: #dc3545; background: #f8d7da; }
        .info-item {
            margin: 15px 0;
        }
        .info-item label {
            font-weight: 600;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }
        .info-item .value {
            color: #333;
            font-size: 16px;
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
            margin-top: 20px;
        }
        button:hover, .button:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Agent Status</h1>

        <?php if ($error): ?>
            <div class="status-box error">
                <h2>❌ Error</h2>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php elseif ($statusData): ?>
            <?php if ($statusData['status'] === 'claimed'): ?>
                <div class="status-box success">
                    <h2>✅ Your agent is CLAIMED and ACTIVE!</h2>
                    <p>You can now use all Moltbook features.</p>
                </div>
            <?php else: ?>
                <div class="status-box warning">
                    <h2>⏳ Pending Claim</h2>
                    <p>Your agent is registered but not yet claimed.</p>
                </div>
            <?php endif; ?>

            <div class="info-item">
                <label>Status</label>
                <div class="value"><?= htmlspecialchars($statusData['status']) ?></div>
            </div>

            <div class="info-item">
                <label>Agent Name</label>
                <div class="value"><?= htmlspecialchars($credentials['agent_name']) ?></div>
            </div>

        <?php endif; ?>

        <a href="index.php" class="button">← Back to Control Panel</a>
        <button onclick="location.reload()">🔄 Refresh Status</button>
    </div>
</body>
</html>
