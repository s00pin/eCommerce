<?php
$session = $sdk->getCredentials();
$authenticated = $session !== null;

if (!$authenticated) {
    header('Location: /login');
    exit;
}

printf('<p>Welcome, %s.</p>', htmlspecialchars($session->user['name']));
printf('<p><pre>%s</pre></p>', htmlspecialchars($session->user['email']));

// Database connection settings
$servername = "localhost";
$username = "root"; // Change this to your MySQL username
$password = ""; // Change this to your MySQL password
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

$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.salla.dev/admin/v2/orders',
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
   // Decode JSON response to an array
   $decodedResponse = json_decode($response, true);

   // Extract important fields and generate HTML
   $html = '<div>';
   foreach ($decodedResponse['data'] as $order) {
      $html .= '<div>';
      $html .= '<p>ID: ' . htmlspecialchars($order['id']) . '</p>';
      $html .= '<p>Reference ID: ' . htmlspecialchars($order['reference_id']) . '</p>';
      $html .= '<p>Total Amount: ' . htmlspecialchars($order['total']['amount']) . ' ' . htmlspecialchars($order['total']['currency']) . '</p>';
      $html .= '<p>Date: ' . htmlspecialchars($order['date']['date']) . '</p>';
      $html .= '<p>Status: ' . htmlspecialchars($order['status']['name']) . '</p>';
      if (isset($order['customer'])) {
         $html .= '<p>Customer: ' . htmlspecialchars($order['customer']['first_name']) . ' ' . htmlspecialchars($order['customer']['last_name']) . '</p>';
         $html .= '<p>Customer Email: ' . htmlspecialchars($order['customer']['email']) . '</p>';
         $html .= '<p>Customer Mobile: ' . htmlspecialchars($order['customer']['mobile_code']) . ' ' . htmlspecialchars($order['customer']['mobile']) . '</p>';
      }
      $html .= '</div><br>';
   }
   $html .= '</div>';

   // Output the HTML
   echo $html;
} else {
   echo "cURL Error: " . curl_error($curl);
}




$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.salla.dev/admin/v2/products',
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
   // Decode JSON response to an array
   $decodedResponse = json_decode($response, true);

   // Extract important fields and generate HTML
   if ($decodedResponse['status'] == 200 && $decodedResponse['success'] == true) {
       $html = '<div>';
       foreach ($decodedResponse['data'] as $product) {
           $html .= '<div>';
           $html .= '<p>ID: ' . htmlspecialchars($product['id']) . '</p>';
           $html .= '<p>Name: ' . htmlspecialchars($product['name']) . '</p>';
           $html .= '<p>SKU: ' . htmlspecialchars($product['sku']) . '</p>';
           $html .= '<p>Price: ' . htmlspecialchars($product['price']['amount']) . ' ' . htmlspecialchars($product['price']['currency']) . '</p>';
           $html .= '<img src="' . htmlspecialchars($product['thumbnail']) . '" alt="Product Image" style="width:100px;">';
           $html .= '</div><br>';
       }
       $html .= '</div>';

       // Output the HTML
       echo $html;
   } else {
       echo '<p>Failed to retrieve data.</p>';
   }
} else {
   echo "cURL Error: " . curl_error($curl);
}



$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.salla.dev/admin/v2/customers',
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
   // Decode JSON response to an array
   $decodedResponse = json_decode($response, true);

   // Extract important fields and generate HTML
   if ($decodedResponse['status'] == 200 && $decodedResponse['success'] == true) {
       $html = '<div>';
       foreach ($decodedResponse['data'] as $customer) {
           $html .= '<div>';
           $html .= '<p>ID: ' . htmlspecialchars($customer['id']) . '</p>';
           $html .= '<p>First Name: ' . htmlspecialchars($customer['first_name']) . '</p>';
           $html .= '<p>Last Name: ' . htmlspecialchars($customer['last_name']) . '</p>';
           $html .= '<p>Mobile: ' . htmlspecialchars($customer['mobile']) . '</p>';
           $html .= '<p>Email: ' . htmlspecialchars($customer['email']) . '</p>';
           $html .= '<p>Country: ' . htmlspecialchars($customer['country']) . '</p>';
           $html .= '<img src="' . htmlspecialchars($customer['avatar']) . '" alt="Customer Avatar" style="width:100px;">';
           $html .= '</div><br>';
       }
       $html .= '</div>';

       // Output the HTML
       echo $html;
   } else {
       echo '<p>Failed to retrieve data.</p>';
   }
} else {
   echo "cURL Error: " . curl_error($curl);
}


$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.salla.dev/admin/v2/orders/invoices?from_date=&to_date=&order_id=',
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
   // Decode JSON response to an array
   $decodedResponse = json_decode($response, true);

   // Extract important fields and generate HTML
   if ($decodedResponse['status'] == 200 && $decodedResponse['success'] == true) {
       $html = '<div>';
       foreach ($decodedResponse['data'] as $invoice) {
           $html .= '<div>';
           $html .= '<p>Invoice ID: ' . htmlspecialchars($invoice['id']) . '</p>';
           $html .= '<p>Invoice Number: ' . htmlspecialchars($invoice['invoice_number']) . '</p>';
           $html .= '<p>Order ID: ' . htmlspecialchars($invoice['order_id']) . '</p>';
           $html .= '<p>Type: ' . htmlspecialchars($invoice['type']) . '</p>';
           $html .= '<p>Date: ' . htmlspecialchars($invoice['date']) . '</p>';
           $html .= '<p>Payment Method: ' . htmlspecialchars($invoice['payment_method']) . '</p>';
           $html .= '<p>Sub Total: ' . htmlspecialchars($invoice['sub_total']['amount']) . ' ' . htmlspecialchars($invoice['sub_total']['currency']) . '</p>';
           $html .= '<p>Shipping Cost: ' . htmlspecialchars($invoice['shipping_cost']['amount']) . ' ' . htmlspecialchars($invoice['shipping_cost']['currency']) . '</p>';
           $html .= '<p>COD Cost: ' . htmlspecialchars($invoice['cod_cost']['amount']) . ' ' . htmlspecialchars($invoice['cod_cost']['currency']) . '</p>';
           $html .= '<p>Discount: ' . htmlspecialchars($invoice['discount']['amount']) . ' ' . htmlspecialchars($invoice['discount']['currency']) . '</p>';
           $html .= '<p>Tax: ' . htmlspecialchars($invoice['tax']['amount']['amount']) . ' ' . htmlspecialchars($invoice['tax']['amount']['currency']) . ' (' . htmlspecialchars($invoice['tax']['percent']) . '%)</p>';
           $html .= '<p>Total: ' . htmlspecialchars($invoice['total']['amount']) . ' ' . htmlspecialchars($invoice['total']['currency']) . '</p>';
           $html .= '</div><br>';
       }
       $html .= '</div>';

       // Output the HTML
       echo $html;
   } else {
       echo '<p>Failed to retrieve data.</p>';
   }
} else {
   echo "cURL Error: " . curl_error($curl);
}



$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.salla.dev/admin/v2/coupons',
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
   // Decode JSON response to an array
   $decodedResponse = json_decode($response, true);

   // Extract important fields and generate HTML
   if ($decodedResponse['status'] == 200 && $decodedResponse['success'] == true) {
       $html = '<div>';
       foreach ($decodedResponse['data'] as $coupon) {
           $html .= '<div>';
           $html .= '<p>Coupon ID: ' . htmlspecialchars($coupon['id']) . '</p>';
           $html .= '<p>Code: ' . htmlspecialchars($coupon['code']) . '</p>';
           $html .= '<p>Type: ' . htmlspecialchars($coupon['type']) . '</p>';
           $html .= '<p>Status: ' . htmlspecialchars($coupon['status']) . '</p>';
           $html .= '<p>Amount: ' . htmlspecialchars($coupon['amount']['amount']) . ' ' . htmlspecialchars($coupon['amount']['currency']) . '</p>';
           $html .= '<p>Maximum Amount: ' . htmlspecialchars($coupon['maximum_amount']['amount']) . ' ' . htmlspecialchars($coupon['maximum_amount']['currency']) . '</p>';
           $html .= '<p>Expiry Date: ' . htmlspecialchars($coupon['expiry_date']) . '</p>';
           $html .= '<p>Start Date: ' . htmlspecialchars($coupon['start_date']) . '</p>';
           $html .= '<p>Free Shipping: ' . ($coupon['free_shipping'] ? 'Yes' : 'No') . '</p>';
           $html .= '<p>Usage Limit: ' . htmlspecialchars($coupon['usage_limit']) . '</p>';
           $html .= '<p>Usage Limit Per User: ' . htmlspecialchars($coupon['usage_limit_per_user']) . '</p>';
           $html .= '<p>Applied In: ' . htmlspecialchars($coupon['applied_in']) . '</p>';
           $html .= '<p>Number of Usages: ' . htmlspecialchars($coupon['statistics']['num_of_usage']) . '</p>';
           $html .= '<p>Number of Customers: ' . htmlspecialchars($coupon['statistics']['num_of_customers']) . '</p>';
           $html .= '<p>Coupon Sales: ' . htmlspecialchars($coupon['statistics']['coupon_sales']['amount']) . ' ' . htmlspecialchars($coupon['statistics']['coupon_sales']['currency']) . '</p>';
           $html .= '</div><br>';
       }
       $html .= '</div>';

       // Output the HTML
       echo $html;
   } else {
       echo '<p>Failed to retrieve data.</p>';
   }
} else {
   echo "cURL Error: " . curl_error($curl);
}


$curl = curl_init();

curl_setopt_array($curl, array(
   CURLOPT_URL => 'https://api.salla.dev/admin/v2/orders/statuses',
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
   // Decode JSON response to an array
   $decodedResponse = json_decode($response, true);

   // Extract important fields and generate HTML
   if ($decodedResponse['status'] == 200 && $decodedResponse['success'] == true) {
       $html = '<div>';
       foreach ($decodedResponse['data'] as $status) {
           $html .= '<div>';
           $html .= '<p>Status ID: ' . htmlspecialchars($status['id']) . '</p>';
           $html .= '<p>Name: ' . htmlspecialchars($status['name']) . '</p>';
           $html .= '<p>Type: ' . htmlspecialchars($status['type']) . '</p>';
           $html .= '<p>Slug: ' . htmlspecialchars($status['slug']) . '</p>';
           $html .= '<p>Sort Order: ' . htmlspecialchars($status['sort']) . '</p>';
           $html .= '<p>Icon: ' . htmlspecialchars($status['icon']) . '</p>';
           $html .= '<p>Is Active: ' . ($status['is_active'] ? 'Yes' : 'No') . '</p>';
           $html .= '</div><br>';
       }
       $html .= '</div>';

       // Output the HTML
       echo $html;
   } else {
       echo '<p>Failed to retrieve data.</p>';
   }
} else {
   echo "cURL Error: " . curl_error($curl);
}

?>



