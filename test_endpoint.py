import requests
import sys

# Set encoding to handle Arabic characters
sys.stdout.reconfigure(encoding='utf-8')

# Test the Flask endpoint
data = {'phone': '967772006329'}
response = requests.post('http://127.0.0.1:5000/send-schedule', data=data)

print(f"Status Code: {response.status_code}")
try:
    print(f"Response: {response.text}")
except UnicodeEncodeError:
    print(f"Response (bytes): {response.content}")
