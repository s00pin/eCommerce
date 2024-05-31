import http.client
import json
from encrypttoken import decrypt_token
import pymysql

mysql_host = 'localhost'
mysql_user = 'root'  # Change this to your MySQL username
mysql_password = ''  # Change this to your MySQL password
mysql_db = 'eCommerce'

def connect_to_mysql():
    return pymysql.connect(host=mysql_host,
                           user=mysql_user,
                           password=mysql_password,
                           db=mysql_db,
                           charset='utf8mb4',
                           cursorclass=pymysql.cursors.DictCursor)

def subscribe_all_webhooks(token):
    conn = http.client.HTTPSConnection("api.salla.dev")
    token = decrypt_token(token)
    headers = {
        'Content-Type': "application/json",
        'Authorization': f"Bearer {token}"
    }

    events = ["order.created", "product.created", "product.updated", "customer.created", "customer.updated", "invoice.created"]

    for event_name in events:
        # The payload
        payload = {
            "name": f"{event_name.capitalize()} webhook",
            "event": event_name,
            "version": "2",
            "url": "https://fox-great-cockatoo.ngrok-free.app/webhook",
            "security_strategy": "signature",
            "secret": "cab4095a50300981ca2484bc07701ff2"
        }

        # Sending the request
        conn.request("POST", "/admin/v2/webhooks/subscribe", json.dumps(payload), headers)

        # Getting the response
        res = conn.getresponse()
        data = res.read()

        # Print the response
        print(data.decode("utf-8"))

def save_api_data(sub, access_token):
    access_token = decrypt_token(access_token)
    conn = connect_to_mysql()
    try:
        with conn.cursor() as cursor:
            headers = {
                'Content-Type': "application/json",
                'Authorization': f"Bearer {access_token}",
                'User-Agent': 'Apidog/1.0.0 (https://apidog.com)'
            }
            
            fetch_and_save_data(cursor, "/admin/v2/orders", headers, sub, save_orders)
            fetch_and_save_data(cursor, "/admin/v2/customers", headers, sub, save_customers)
            fetch_and_save_data(cursor, "/admin/v2/orders/invoices?from_date&to_date&order_id", headers, sub, save_invoices)
            fetch_and_save_data(cursor, "/admin/v2/products", headers, sub, save_products)
            print("Data saved successfully")
        
        conn.commit()
    finally:
        conn.close()

def fetch_and_save_data(cursor, endpoint, headers, sub, save_function):
    conn = http.client.HTTPSConnection("api.salla.dev")
    conn.request("GET", endpoint, '', headers)
    res = conn.getresponse()
    data = res.read()
    decoded_data = json.loads(data.decode("utf-8"))
    print(json.dumps(decoded_data, indent=4))
    save_function(cursor, decoded_data['data'], sub)

def save_orders(cursor, data, sub):
    for order in data:
        sql = """INSERT INTO orders 
                 (id, reference_id, total_amount, total_currency, order_date, status_name, 
                 customer_first_name, customer_last_name, customer_email, customer_mobile_code, customer_mobile, sub) 
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        values = (
            order['id'], order['reference_id'], order['total']['amount'], order['total']['currency'], 
            order['date']['date'], order['status']['name'], 
            order['customer'].get('first_name', ''), order['customer'].get('last_name', ''), 
            order['customer'].get('email', ''), order['customer'].get('mobile_code', ''), 
            order['customer'].get('mobile', ''), sub
        )
        cursor.execute(sql, values)

def save_customers(cursor, data, sub):
    for customer in data:
        sql = """INSERT INTO customers 
                 (id, first_name, last_name, mobile, email, country, avatar, sub) 
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s)"""
        values = (
            customer['id'], customer['first_name'], customer['last_name'], customer['mobile'], 
            customer['email'], customer['country'], customer['avatar'], sub
        )
        cursor.execute(sql, values)

def save_invoices(cursor, data, sub):
    for invoice in data:
        sql = """INSERT INTO invoices 
                 (id, invoice_number, order_id, invoice_type, invoice_date, payment_method, 
                 sub_total_amount, sub_total_currency, shipping_cost_amount, shipping_cost_currency, 
                 cod_cost_amount, cod_cost_currency, discount_amount, discount_currency, 
                 tax_percent, total_amount, total_currency, sub) 
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        values = (
            invoice['id'], invoice['invoice_number'], invoice['order_id'], invoice['type'], 
            invoice['date'], invoice['payment_method'], 
            invoice['sub_total']['amount'], invoice['sub_total']['currency'], 
            invoice['shipping_cost']['amount'], invoice['shipping_cost']['currency'], 
            invoice['cod_cost']['amount'], invoice['cod_cost']['currency'], 
            invoice['discount']['amount'], invoice['discount']['currency'], 
            invoice['tax']['percent'], invoice['total']['amount'], invoice['total']['currency'], sub
        )
        cursor.execute(sql, values)

def save_products(cursor, data, sub):
    for product in data:
        sql = """INSERT INTO products 
                 (id, name, sku, price_amount, price_currency, thumbnail, sub) 
                 VALUES (%s, %s, %s, %s, %s, %s, %s)"""
        values = (
            product['id'], product['name'], product['sku'], product['price']['amount'], 
            product['price']['currency'], product['thumbnail'], sub
        )
        cursor.execute(sql, values)



