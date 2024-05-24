import http.client
import json

conn = http.client.HTTPSConnection("api.salla.dev")

# Make sure to replace <access-token-obtained-at-callback-url> with your actual access token
headers = {
    'Content-Type': "application/json",
    'Authorization': "Bearer ory_at_l2CsemuzpCoswXpPBhkwL_tScoaS9vn3WMSiYowB_YY.l_NeBn9kI14zepaNRX5qxYYj8LC2n7h8iapp4HuT1lU"
}

# The payload
payload = {
    "name": "Order updated",
    "event": "order.updated",
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
