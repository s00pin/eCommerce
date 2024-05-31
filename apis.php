<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Ecommerce</title>
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
h2 {
    color: #333;
}
</style>
</head>
<body>


<?php
$session = $sdk->getCredentials();
$authenticated = $session !== null;

if (!$authenticated) {
    header('Location: /login');
    exit;
}
?>
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
   <div class="container mt-4">
      <div class="row">
         <div class="col">


<?php
// Database connection settings
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "eCommerce";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get token by user sub
function getTokenByUserSub($conn, $userSub) {
    $stmt = $conn->prepare("SELECT token FROM users WHERE sub = ?");
    $stmt->bind_param("s", $userSub);
    $stmt->execute();
    $result = $stmt->get_result();
    $token = null;
    if ($row = $result->fetch_assoc()) {
        $token = $row['token'];
    }
    $stmt->close();
    return $token;
    
}

// Get the user's sub
$userSub = $session->user['sub'];
$encryptedToken = getTokenByUserSub($conn, $userSub);

if (!$encryptedToken) {
    die("Error: Token not found for user.");
}

// Close the connection after fetching the token
$conn->close();


// Function to decrypt token via Flask endpoint
function decryptToken($encryptedToken) {
   $curl = curl_init();

   $payload = json_encode(array("token" => $encryptedToken));

   curl_setopt_array($curl, array(
       CURLOPT_URL => 'http://localhost:5000/decrypt-token',
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_POST => true,
       CURLOPT_HTTPHEADER => array(
           'Content-Type: application/json',
           'Content-Length: ' . strlen($payload)
       ),
       CURLOPT_POSTFIELDS => $payload,
   ));

   $response = curl_exec($curl);
   curl_close($curl);

   if ($response === false) {
       return null;
   }

   $decodedResponse = json_decode($response, true);
   return $decodedResponse['decrypted_token'] ?? null;
}

$token = decryptToken($encryptedToken);
if (!$token) {
    die("Error: Failed to decrypt token.");
}

// Inline CSS for styling


echo '<div class="content">';

// Fetch and display orders
$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.salla.dev/admin/v2/orders',
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_HTTPHEADER => array(
      'User-Agent: Apidog/1.0.0 (https://apidog.com)',
      'Authorization: Bearer ' . $token
   ),
));

$response = curl_exec($curl);
curl_close($curl);

if ($response) {
   $decodedResponse = json_decode($response, true);

   echo '<div class="section"><h2>Orders</h2>';
   foreach ($decodedResponse['data'] as $order) {
      echo '<div class="card">';
      echo '<p><strong>ID:</strong> ' . htmlspecialchars($order['id']) . '</p>';
      echo '<p><strong>Reference ID:</strong> ' . htmlspecialchars($order['reference_id']) . '</p>';
      echo '<p><strong>Total Amount:</strong> ' . htmlspecialchars($order['total']['amount']) . ' ' . htmlspecialchars($order['total']['currency']) . '</p>';
      echo '<p><strong>Date:</strong> ' . htmlspecialchars($order['date']['date']) . '</p>';
      echo '<p><strong>Status:</strong> ' . htmlspecialchars($order['status']['name']) . '</p>';
      if (isset($order['customer'])) {
         echo '<p><strong>Customer:</strong> ' . htmlspecialchars($order['customer']['first_name']) . ' ' . htmlspecialchars($order['customer']['last_name']) . '</p>';
         echo '<p><strong>Customer Email:</strong> ' . htmlspecialchars($order['customer']['email']) . '</p>';
         echo '<p><strong>Customer Mobile:</strong> ' . htmlspecialchars($order['customer']['mobile_code']) . ' ' . htmlspecialchars($order['customer']['mobile']) . '</p>';
      }
      echo '</div>';
   }
   echo '</div>';
} else {
   echo "cURL Error: " . curl_error($curl);
}

// Fetch and display products
$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.salla.dev/admin/v2/products',
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_HTTPHEADER => array(
      'User-Agent: Apidog/1.0.0 (https://apidog.com)',
      'Authorization: Bearer ' . $token
   ),
));

$response = curl_exec($curl);
curl_close($curl);

if ($response) {
   $decodedResponse = json_decode($response, true);

   if ($decodedResponse['status'] == 200 && $decodedResponse['success'] == true) {
       echo '<div class="section"><h2>Products</h2>';
       foreach ($decodedResponse['data'] as $product) {
           echo '<div class="card">';
           echo '<p><strong>ID:</strong> ' . htmlspecialchars($product['id']) . '</p>';
           echo '<p><strong>Name:</strong> ' . htmlspecialchars($product['name']) . '</p>';
           echo '<p><strong>SKU:</strong> ' . htmlspecialchars($product['sku']) . '</p>';
           echo '<p><strong>Price:</strong> ' . htmlspecialchars($product['price']['amount']) . ' ' . htmlspecialchars($product['price']['currency']) . '</p>';
           echo '<img src="' . htmlspecialchars($product['thumbnail']) . '" alt="Product Image">';
           echo '</div>';
       }
       echo '</div>';
   } else {
       echo '<p>Failed to retrieve data.</p>';
   }
} else {
   echo "cURL Error: " . curl_error($curl);
}

// Fetch and display customers
$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.salla.dev/admin/v2/customers',
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_HTTPHEADER => array(
      'User-Agent: Apidog/1.0.0 (https://apidog.com)',
      'Authorization: Bearer ' . $token
   ),
));

$response = curl_exec($curl);
curl_close($curl);

if ($response) {
   $decodedResponse = json_decode($response, true);

   if ($decodedResponse['status'] == 200 && $decodedResponse['success'] == true) {
       echo '<div class="section"><h2>Customers</h2>';
       foreach ($decodedResponse['data'] as $customer) {
           echo '<div class="card">';
           echo '<p><strong>ID:</strong> ' . htmlspecialchars($customer['id']) . '</p>';
           echo '<p><strong>First Name:</strong> ' . htmlspecialchars($customer['first_name']) . '</p>';
           echo '<p><strong>Last Name:</strong> ' . htmlspecialchars($customer['last_name']) . '</p>';
           echo '<p><strong>Mobile:</strong> ' . htmlspecialchars($customer['mobile']) . '</p>';
           echo '<p><strong>Email:</strong> ' . htmlspecialchars($customer['email']) . '</p>';
           echo '<p><strong>Country:</strong> ' . htmlspecialchars($customer['country']) . '</p>';
           echo '<img src="' . htmlspecialchars($customer['avatar']) . '" alt="Customer Avatar">';
           echo '</div>';
       }
       echo '</div>';
   } else {
       echo '<p>Failed to retrieve data.</p>';
   }
} else {
   echo "cURL Error: " . curl_error($curl);
}

// Fetch and display invoices
$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.salla.dev/admin/v2/orders/invoices?from_date&to_date&order_id',
   CURLOPT_RETURNTRANSFER => true,
   CURLOPT_ENCODING => '',
   CURLOPT_MAXREDIRS => 10,
   CURLOPT_TIMEOUT => 0,
   CURLOPT_FOLLOWLOCATION => true,
   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
   CURLOPT_CUSTOMREQUEST => 'GET',
   CURLOPT_HTTPHEADER => array(
      'User-Agent: Apidog/1.0.0 (https://apidog.com)',
      'Authorization: Bearer ' . $token
   ),
));

$response = curl_exec($curl);

curl_close($curl);

if ($response) {
    $decodedResponse = json_decode($response, true);

    if ($decodedResponse['status'] == 200 && $decodedResponse['success'] == true) {
        echo '<div class="section"><h2>Invoices</h2>';
        foreach ($decodedResponse['data'] as $invoice) {
            echo '<div class="card">';
            echo '<p><strong>ID:</strong> ' . htmlspecialchars($invoice['id']) . '</p>';
            echo '<p><strong>Invoice Number:</strong> ' . htmlspecialchars($invoice['invoice_number']) . '</p>';
            echo '<p><strong>Order ID:</strong> ' . htmlspecialchars($invoice['order_id']) . '</p>';
            echo '<p><strong>Invoice Type:</strong> ' . htmlspecialchars($invoice['type']) . '</p>';
            echo '<p><strong>Date:</strong> ' . htmlspecialchars($invoice['date']) . '</p>';
            echo '<p><strong>Payment Method:</strong> ' . htmlspecialchars($invoice['payment_method']) . '</p>';
            echo '<p><strong>Sub Total:</strong> ' . htmlspecialchars($invoice['sub_total']['amount']) . ' ' . htmlspecialchars($invoice['sub_total']['currency']) . '</p>';
            echo '<p><strong>Shipping Cost:</strong> ' . htmlspecialchars($invoice['shipping_cost']['amount']) . ' ' . htmlspecialchars($invoice['shipping_cost']['currency']) . '</p>';
            echo '<p><strong>COD Cost:</strong> ' . htmlspecialchars($invoice['cod_cost']['amount']) . ' ' . htmlspecialchars($invoice['cod_cost']['currency']) . '</p>';
            echo '<p><strong>Discount:</strong> ' . htmlspecialchars($invoice['discount']['amount']) . ' ' . htmlspecialchars($invoice['discount']['currency']) . '</p>';
            echo '<p><strong>Tax Percent:</strong> ' . htmlspecialchars($invoice['tax']['percent']) . '</p>';
            echo '<p><strong>Total Amount:</strong> ' . htmlspecialchars($invoice['total']['amount']) . ' ' . htmlspecialchars($invoice['total']['currency']) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>Failed to retrieve data.</p>';
    }
} else {
    echo "cURL Error: " . curl_error($curl);
}


echo '</div>';
?>
 </div>
      </div>
   </div>



   <footer>
      <div class="container">
         <p>&copy; 2024 Ecommerce Website. All rights reserved.</p>
      </div>
   </footer>
   <script src="https://kit.fontawesome.com/2363f97efc.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

</body>
</html>
