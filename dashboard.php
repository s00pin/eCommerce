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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        body {
            margin: 1%;
            font-family: Arial, Helvetica, sans-serif;
        }
        .topnav {
        overflow: hidden;
        background-color: #333;
        }
        .topnav a {
        float: left;
        color: #f2f2f2;
        text-align: center;
        padding: 14px 16px;
        text-decoration: none;
        font-size: 17px;
        }
        .topnav a:hover {
        background-color: #ddd;
        color: black;
        }
        .topnav a.active {
        background-color: #04AA6D;
        color: white;
        }
        .content {
            margin: 20px;
        }
        .section {
            margin-bottom: 40px;
        }
        .card {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            margin: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: inline-block; /* Add this line to make cards inline */
            vertical-align: top; /* Align cards to the top of the container */
            width: calc(33.33% - 20px); /* Adjust the width of each card, considering margin */
            box-sizing: border-box; /* Include padding and border in the width calculation */
        }
        .card p {
            margin: 5px 0;
        }
        .card img {
            display: block;
            margin: 10px 0;
            max-width: 100px;
        }
        
        p {
            font-size: 16px;
            color: #555;
        }
        a {
            color: #3498db;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="http://localhost:3000">Ecommerce</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="http://localhost:3000">Main Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="http://localhost:3000/apis">APIs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="http://localhost:3000/webhooks">Webhooks</a>
                </li>
                <li class="nav-item">
                    <p class="nav-link">Welcome, <?php echo htmlspecialchars($session->user['name']); ?></p>
                </li>
                <li class="nav-item">
                    <p class="nav-link"><pre><?php echo htmlspecialchars($session->user['email']); ?></pre></p>
                </li>
            </ul>
        </div>
    </div>
</nav>
   <h1>User info</h1>
    <?php 
   
    printf('<p>Name:<pre>%s</pre></p>', htmlspecialchars($session->user['name']));
    printf('<p>Email:<pre>%s</pre></p>', htmlspecialchars($session->user['email']));
    
    printf('<p><a href="/%s" class="btn btn-primary">Log %s</a></p>', 'logout', 'out');

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
<p> Don't forget to link you account to see your orders and much more.</p>
<p><a href="https://fox-great-cockatoo.ngrok-free.app/authStore?sub=<?php echo htmlspecialchars($session->user['sub']); ?>" class="btn btn-primary">Salla Connect</a></p>
<footer>
      <div class="container">
         
         <div class="container mt-5">
    <h2>Contact Us</h2>
    <form action="mailto:someone@example.com" method="post" enctype="text/plain">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="subject">Subject:</label>
            <input type="text" class="form-control" id="subject" name="subject" required>
        </div>
        <div class="form-group">
            <label for="message">Message:</label>
            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send Email</button>
    </form>
</div>
      </div>
      <p>&copy; 2024 Ecommerce Website. All rights reserved.</p>
   </footer>
   <script src="https://kit.fontawesome.com/2363f97efc.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

</body>
</html>
