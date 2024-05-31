<?php
$session = $sdk->getCredentials();
$authenticated = $session !== null;

if (!$authenticated) {
    header('Location: /login');
    exit;
}

$host = 'localhost';
$db = 'eCommerce';
$user = 'root';
$pass = '';

$sub = $session->user['sub']; // Assuming this is how you get the 'sub' value

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query customers table
$customers_sql = "
    SELECT id, first_name, last_name, mobile, email, country, avatar
    FROM customers
    WHERE sub = ?
";
$stmt = $conn->prepare($customers_sql);
$stmt->bind_param("s", $sub);
$stmt->execute();
$customers_result = $stmt->get_result();
$customers = $customers_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Query invoices table
$invoices_sql = "
    SELECT id, invoice_number, order_id, invoice_type, invoice_date, payment_method, sub_total_amount, sub_total_currency, shipping_cost_amount, shipping_cost_currency, cod_cost_amount, cod_cost_currency, discount_amount, discount_currency, tax_percent, total_amount, total_currency
    FROM invoices
    WHERE sub = ?
";
$stmt = $conn->prepare($invoices_sql);
$stmt->bind_param("s", $sub);
$stmt->execute();
$invoices_result = $stmt->get_result();
$invoices = $invoices_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Query orders table
$orders_sql = "
    SELECT id, reference_id, total_amount, total_currency, order_date, status_name, customer_first_name, customer_last_name, customer_email, customer_mobile_code, customer_mobile
    FROM orders
    WHERE sub = ?
";
$stmt = $conn->prepare($orders_sql);
$stmt->bind_param("s", $sub);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Query products table
$products_sql = "
    SELECT id, name, sku, price_amount, price_currency, thumbnail
    FROM products
    WHERE sub = ?
";
$stmt = $conn->prepare($products_sql);
$stmt->bind_param("s", $sub);
$stmt->execute();
$products_result = $stmt->get_result();
$products = $products_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

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
   <div class="container mt-4">
   <div class="row">
      <div class="col">
         <div class="content">
            <div class="section">
               <h2>Orders</h2>
               <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                  <?php foreach ($orders as $order): ?>
                     <div class="col">
                        <div class="card">
                           <div class="card-body">
                              <p class="card-text"><strong>ID:</strong> <?php echo $order['id']; ?></p>
                              <p class="card-text"><strong>Reference ID:</strong> <?php echo $order['reference_id']; ?></p>
                              <p class="card-text"><strong>Total Amount:</strong> <?php echo $order['total_amount'] . ' ' . $order['total_currency']; ?></p>
                              <p class="card-text"><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
                              <p class="card-text"><strong>Status:</strong> <?php echo $order['status_name']; ?></p>
                              <p class="card-text"><strong>Customer:</strong> <?php echo $order['customer_first_name'] . ' ' . $order['customer_last_name']; ?></p>
                              <p class="card-text"><strong>Customer Email:</strong> <?php echo $order['customer_email']; ?></p>
                              <p class="card-text"><strong>Customer Mobile:</strong> <?php echo $order['customer_mobile']; ?></p>
                           </div>
                        </div>
                     </div>
                  <?php endforeach; ?>
               </div>
            </div>
            <div class="section">
               <h2>Products</h2>
               <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                  <?php foreach ($products as $product): ?>
                     <div class="col">
                        <div class="card">
                           <img src="<?php echo $product['thumbnail']; ?>" class="card-img-top" alt="Product Image" loading="lazy">
                           <div class="card-body">
                              <p class="card-text"><strong>ID:</strong> <?php echo $product['id']; ?></p>
                              <p class="card-text"><strong>Name:</strong> <?php echo $product['name']; ?></p>
                              <p class="card-text"><strong>SKU:</strong> <?php echo $product['sku']; ?></p>
                              <p class="card-text"><strong>Price:</strong> <?php echo $product['price_amount'] . ' ' . $product['price_currency']; ?></p>
                           </div>
                        </div>
                     </div>
                  <?php endforeach; ?>
               </div>
            </div>
            <div class="section">
               <h2>Customers</h2>
               <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                  <?php foreach ($customers as $customer): ?>
                     <div class="col">
                        <div class="card">
                           <img src="<?php echo $customer['avatar']; ?>" class="card-img-top" alt="Customer Avatar" loading="lazy">
                           <div class="card-body">
                              <p class="card-text"><strong>ID:</strong> <?php echo $customer['id']; ?></p>
                              <p class="card-text"><strong>First Name:</strong> <?php echo $customer['first_name']; ?></p>
                              <p class="card-text"><strong>Last Name:</strong> <?php echo $customer['last_name']; ?></p>
                              <p class="card-text"><strong>Mobile:</strong> <?php echo $customer['mobile']; ?></p>
                              <p class="card-text"><strong>Email:</strong> <?php echo $customer['email']; ?></p>
                              <p class="card-text"><strong>Country:</strong> <?php echo $customer['country']; ?></p>
                           </div>
                        </div>
                     </div>
                  <?php endforeach; ?>
               </div>
            </div>
            <div class="section">
               <h2>Invoices</h2>
               <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                  <?php foreach ($invoices as $invoice): ?>
                     <div class="col">
                        <div class="card">
                           <div class="card-body">
                              <p class="card-text"><strong>ID:</strong> <?php echo $invoice['id']; ?></p>
                              <p class="card-text"><strong>Invoice Number:</strong> <?php echo $invoice['invoice_number']; ?></p>
                              <p class="card-text"><strong>Order ID:</strong> <?php echo $invoice['order_id']; ?></p>
                              <p class="card-text"><strong>Invoice Type:</strong> <?php echo $invoice['invoice_type']; ?></p>
                              <p class="card-text"><strong>Date:</strong> <?php echo $invoice['invoice_date']; ?></p>
                              <p class="card-text"><strong>Payment Method:</strong> <?php echo $invoice['payment_method']; ?></p>
                              <p class="card-text"><strong>Sub Total:</strong> <?php echo $invoice['sub_total_amount'] . ' ' . $invoice['sub_total_currency']; ?></p>
                              <p class="card-text"><strong>Shipping Cost:</strong> <?php echo $invoice['shipping_cost_amount'] . ' ' . $invoice['shipping_cost_currency']; ?></p>
                              <p class="card-text"><strong>COD Cost:</strong> <?php echo $invoice['cod_cost_amount'] . ' ' . $invoice['cod_cost_currency']; ?></p>
                              <p class="card-text"><strong>Discount:</strong> <?php echo $invoice['discount_amount'] . ' ' . $invoice['discount_currency']; ?></p>
                              <p class="card-text"><strong>Tax Percent:</strong> <?php echo $invoice['tax_percent']; ?></p>
                              <p class="card-text"><strong>Total Amount:</strong> <?php echo $invoice['total_amount'] . ' ' . $invoice['total_currency']; ?></p>
                           </div>
                        </div>
                     </div>
                  <?php endforeach; ?>
               </div>
            </div>
         </div>
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
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-K3wyVATv7e3cuWddVv01PqKCYCEVrjg2sYgPrvcHkS2GsM0tJehgM/gCsKaIA+wz" crossorigin="anonymous"></script>
</body>
</html>
