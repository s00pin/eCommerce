from flask import Flask, request, redirect, jsonify
import requests
import random
import hmac
import hashlib
from dotenv import load_dotenv
from os import getenv

load_dotenv()

app = Flask(__name__)

WEBHOOK_SECRET = getenv('WEBHOOK_SECRET').encode()
CLIENT_ID = getenv('CLIENT_ID')
CLIENT_SECRET = getenv('CLIENT_SECRET')
REDIRECT_URI = getenv('REDIRECT_URI')
SCOPE = getenv('SCOPE')
SALLA_STORE_INFO_URL = getenv('SALLA_STORE_INFO_URL')


@app.route('/authStore')
def storeauth():
    state = random.randint(1000000000, 9999999999)
    auth_url = (
        f"https://accounts.salla.sa/oauth2/auth?client_id={CLIENT_ID}&response_type=code"
        f"&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={state}"
    )
    return redirect(auth_url)

@app.route('/authStore/callback')
def callback():
    code = request.args.get('code')
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
        if store_info_response.status_code == 200:
            print("accessToken--- "+access_token+" ---accessToken")
            return jsonify(store_info_response.json())
        else:
            return jsonify({'error': 'Failed to fetch store info', 'details': store_info_response.text})
    else:
        return jsonify({'error': 'Failed to receive token', 'details': token_response.text})
    
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

if __name__ == '__main__':
    app.run(debug=True)

    