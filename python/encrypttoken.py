from cryptography.fernet import Fernet
import base64
import json
import urllib.parse

# Use an existing key (this should be securely stored)
key = b'vYcY-ucD2ptzkZD9dfMmlmw37ee3olLs93HhsOCPuaI='  # Replace with your actual key
cipher_suite = Fernet(key)

def encrypt_token(token):
    encrypted_token = cipher_suite.encrypt(token.encode())
    return base64.urlsafe_b64encode(encrypted_token).decode()

def decrypt_token(encrypted_token):
    decoded_token = base64.urlsafe_b64decode(encrypted_token)
    decrypted_token = cipher_suite.decrypt(decoded_token)
    return decrypted_token.decode()
