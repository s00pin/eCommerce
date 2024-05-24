from flask import Flask, request, jsonify
import pymysql

app = Flask(__name__)

# MySQL Configuration
mysql_host = 'localhost'
mysql_user = 'root'  # Change this to your MySQL username
mysql_password = ''  # Change this to your MySQL password
mysql_db = 'eCommerce'

# Function to connect to MySQL database
def connect_to_mysql():
    return pymysql.connect(host=mysql_host,
                           user=mysql_user,
                           password=mysql_password,
                           db=mysql_db,
                           charset='utf8mb4',
                           cursorclass=pymysql.cursors.DictCursor)

# Flask endpoint to receive user data
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
