from flask import Flask, request, redirect, jsonify
import requests
import random
import hmac
import hashlib
from dotenv import load_dotenv
from os import getenv
import pymysql
import urllib.parse
import json
from encrypttoken import encrypt_token, decrypt_token

load_dotenv()


app = Flask(__name__)

WEBHOOK_SECRET = getenv('WEBHOOK_SECRET').encode()
CLIENT_ID = getenv('CLIENT_ID')
CLIENT_SECRET = getenv('CLIENT_SECRET')
REDIRECT_URI = getenv('REDIRECT_URI')
SCOPE = getenv('SCOPE')
SALLA_STORE_INFO_URL = getenv('SALLA_STORE_INFO_URL')

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

@app.route('/authStore')
def storeauth():
    state_data = {
        'state': random.randint(1000000000, 9999999999),
        'sub': request.args.get('sub')
    }
    state = urllib.parse.quote(json.dumps(state_data))
    auth_url = (
        f"https://accounts.salla.sa/oauth2/auth?client_id={CLIENT_ID}&response_type=code"
        f"&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={state}"
    )
    return redirect(auth_url)

@app.route('/authStore/callback')
def callback():
    code = request.args.get('code')
    state = request.args.get('state')
    
    if not state:
        return jsonify({'error': 'Missing state parameter'}), 400
    
    try:
        state_data = json.loads(urllib.parse.unquote(state))
        sub = state_data['sub']
    except (json.JSONDecodeError, KeyError):
        return jsonify({'error': 'Invalid state parameter'}), 400

    if not sub:
        return jsonify({'error': 'Missing sub parameter'}), 400
    
    token_data = {
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET,
        'grant_type': 'authorization_code',
        'code': code,
        'redirect_uri': REDIRECT_URI,
        'scope': SCOPE
    }
    
    token_response = requests.post('https://accounts.salla.sa/oauth2/token', data=token_data)
    
    if token_response.status_code == 200:
        access_token = token_response.json().get('access_token')
        headers = {'Authorization': f'Bearer {access_token}'}
        store_info_response = requests.get(SALLA_STORE_INFO_URL, headers=headers)
        access_token = encrypt_token(access_token)
        if store_info_response.status_code == 200:
            # Save the access token to the user's record in the database
            connection = connect_to_mysql()
            try:
                with connection.cursor() as cursor:
                    cursor.execute("UPDATE users SET token = %s WHERE sub = %s", (access_token, sub))
                    if cursor.rowcount == 0:
                        return jsonify({'error': 'User not found'}), 404
                    connection.commit()
                return jsonify(store_info_response.json())
            except Exception as e:
                connection.rollback()
                return jsonify({'error': 'Database error', 'details': str(e)}), 500
            finally:
                connection.close()
        else:
            return jsonify({'error': 'Failed to fetch store info', 'details': store_info_response.text}), store_info_response.status_code
    else:
        return jsonify({'error': 'Failed to receive token', 'details': token_response.text}), token_response.status_code

    
@app.route('/webhook', methods=['POST'])
def webhook_receiver():
    received_signature = request.headers.get('X-Salla-Signature')

    if received_signature is None:
        return jsonify({'status': 'failed', 'message': 'No signature header received'}), 401

    signature = hmac.new(WEBHOOK_SECRET, request.data, hashlib.sha256).hexdigest()

    if hmac.compare_digest(received_signature, signature):
        data = request.json
        print(data)

        return jsonify({'status': 'success', 'message': 'Webhook validated and processed'}), 200
    else:
        print("Invalid signature. Rejecting request.")
        return jsonify({'status': 'failed', 'message': 'Invalid signature'}), 401
    
@app.route('/add_user', methods=['POST'])
def add_user():
    user_data = request.json
    if user_data is None:
        return jsonify({'error': 'No data received'}), 400

    connection = connect_to_mysql()

    try:
        with connection.cursor() as cursor:
            cursor.execute("SELECT * FROM users WHERE sub = %s", (user_data['sub'],))
            existing_user = cursor.fetchone()

            if existing_user:
                return jsonify({'message': 'User already exists'}), 200
            else:
                cursor.execute("INSERT INTO users (sub, name, email) VALUES (%s, %s, %s)",
                               (user_data['sub'], user_data['nickname'], user_data['email']))
                connection.commit()
                return jsonify({'message': 'User added successfully'}), 200

    except Exception as e:
        return jsonify({'error': str(e)}), 500

    finally:
        connection.close()


if __name__ == '__main__':
    app.run(debug=True)

    