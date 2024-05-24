<?php
declare(strict_types=1);

$session = $sdk->getCredentials();
$authenticated = $session !== null;

if (!$authenticated) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <?php 
    printf('<p>Welcome, %s.</p>', htmlspecialchars($session->user['name']));
    printf('<p><pre>%s</pre></p>', htmlspecialchars($session->user['email']));
    printf('<p><a href="/%s">Log %s</a></p>', 'logout', 'out');

    $json_data = json_encode($session->user);

    $json_file = 'user_data.json';
    if (file_put_contents($json_file, $json_data) === false) {
        echo '<p>Error saving user data to file.</p>';
    }

    $api_data = [
        'sub' => $session->user['sub'],
        'nickname' => $session->user['nickname'],
        'email' => $session->user['email']
    ];

    $api_json_data = json_encode($api_data);

    $api_url = 'http://127.0.0.1:5000/add_user';

    $headers = [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($api_json_data)
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $api_json_data,
            'timeout' => 5 // Optional: to avoid long wait if server is unreachable
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($api_url, false, $context);

    if ($result === false) {
        $error = error_get_last();
        echo '<p>Error making API request: ' . htmlspecialchars($error['message']) . '</p>';
    } else {
        $response = json_decode($result, true);
        if ($response && isset($response['message'])) {
            echo '<p>API Response: ' . htmlspecialchars($response['message']) . '</p>';
        } else {
            echo '<p>Invalid API response.</p>';
        }
    }

    $_SESSION['message'] = 'User added successfully';
    ?>
</body>
</html>
