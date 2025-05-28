from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import logging
from uptime_kuma_api import UptimeKumaApi, MonitorStatus
from uptime_kuma_api.exceptions import UptimeKumaException
import time
import socketio

# Initialize the Flask application
app = Flask(__name__)

# Enable CORS (Cross-Origin Resource Sharing) for the specified origins
# This allows the application to handle requests from these domains
CORS(app, resources={r"/*": {"origins": ["https://dev.birthday.gold", "https://birthday.gold"]}})

# Configure logging for the application to log information and errors
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Uptime Kuma server details
UPTIME_KUMA_URL = 'https://uptime.birthdaygold.cloud'
USERNAME = 'ddgconsultant'

# Fetch Uptime Kuma password from the environment variables
PASSWORD = os.getenv('UPTIME_KUMA_PASSWORD')

# Check if the Uptime Kuma password is set; if not, raise an error
if not PASSWORD:
    logger.error("UPTIME_KUMA_PASSWORD environment variable is not set.")
    raise ValueError("Please set the UPTIME_KUMA_PASSWORD environment variable.")

# Global variable to hold the Uptime Kuma API instance
api_instance = None

def get_api_instance():
    """
    Initialize and return a UptimeKumaApi instance.
    The instance is created only once and reused for subsequent requests.
    This helps in maintaining a single session across multiple API calls.
    """
    global api_instance
    if api_instance is None:
        api_instance = UptimeKumaApi(UPTIME_KUMA_URL, timeout=30)
        api_instance.login(USERNAME, PASSWORD)
    return api_instance

@app.route('/create_monitor', methods=['POST'])
def create_monitor():
    """
    Create a new monitor in Uptime Kuma based on the provided JSON data.
    The request must include 'type', 'name', and 'hostname'. Optional fields
    can be included as well, and they will be passed to the API call.

    Returns:
        JSON response with the result of the monitor creation.
    """
    data = request.json
    if not data:
        logger.error("No data provided in the request.")
        return jsonify({'error': 'No data provided'}), 400

    monitor_type = data.get('type')
    name = data.get('name')
    hostname = data.get('hostname')
    
    if not name or not hostname or not monitor_type:
        logger.error("Name, hostname, and monitor type are required. Provided data: %s", data)
        return jsonify({'error': 'Name, hostname, and monitor type are required'}), 400

    # List of optional fields that can be included in the monitor payload
    optional_fields = [
        'interval', 'retryInterval', 'resendInterval', 'maxretries', 'packetSize', 'parent', 'description',
        'notificationIDList', 'url', 'expiryNotification', 'ignoreTls', 'maxredirects', 'accepted_statuscodes',
        'proxyId', 'method', 'httpBodyEncoding', 'body', 'headers', 'authMethod', 'tlsCert', 'tlsKey', 'tlsCa',
        'basic_auth_user', 'basic_auth_pass', 'authDomain', 'authWorkstation', 'oauth_auth_method', 'oauth_token_url',
        'oauth_client_id', 'oauth_client_secret', 'oauth_scopes', 'timeout', 'keyword', 'invertKeyword', 'port',
        'dns_resolve_server', 'dns_resolve_type', 'mqttUsername', 'mqttPassword', 'mqttTopic', 'mqttSuccessMessage',
        'databaseConnectionString', 'databaseQuery', 'docker_container', 'docker_host', 'radiusUsername', 'radiusPassword',
        'radiusSecret', 'radiusCalledStationId', 'radiusCallingStationId', 'game', 'gamedigGivenPortOnly', 'jsonPath',
        'expectedValue', 'kafkaProducerBrokers', 'kafkaProducerTopic', 'kafkaProducerMessage', 'kafkaProducerSsl',
        'kafkaProducerAllowAutoTopicCreation', 'kafkaProducerSaslOptions'
    ]

    # Construct the payload for the API request
    payload = {
        'type': monitor_type,
        'name': name,
        'hostname': hostname,
    }

    # Add optional fields to the payload if they exist in the request data
    for field in optional_fields:
        if field in data:
            payload[field] = data[field]

    logger.info("Creating monitor with payload: %s", payload)

    try:
        # Get the API instance and attempt to create the monitor
        api = get_api_instance()
        result = api.add_monitor(**payload)
        logger.info("Monitor created successfully: %s", result)
        return jsonify(result)
    except Exception as e:
        logger.error("Error creating monitor: %s", str(e))
        return jsonify({'error': str(e)}), 500

@app.route('/get_monitor_status', methods=['POST'])
def get_monitor_status():
    """
    Retrieve the status of an existing monitor by its ID.
    The request must include 'id' in the JSON body. The method will attempt to
    retrieve the monitor's status and details, retrying up to 3 times in case
    of failures such as timeouts or session expiration.

    Returns:
        JSON response with the monitor's status and details if successful,
        or an error message if not.
    """
    try:
        logger.info("Request headers: %s", request.headers)
        logger.info("Request data: %s", request.json)

        data = request.json

        if not data or 'id' not in data:
            logger.error("No monitor ID provided in the request.")
            return jsonify({'error': 'Monitor ID is required'}), 400

        monitor_id = int(data['id'])  # Ensure ID is treated as an integer

        # Set retry parameters
        max_retries = 3
        delay = 2  # Initial delay for exponential backoff

        for attempt in range(max_retries):
            try:
                # Get the API instance and fetch the monitor details
                api = get_api_instance()
                monitor_details = api.get_monitor(monitor_id)
                # logger.info("Monitor details for ID %s: %s", monitor_id, monitor_details)

                # Fetch the monitor status
                status = api.get_monitor_status(monitor_id)
                logger.info("Monitor status for ID %s: %s", monitor_id, status.name)

                return jsonify({'id': monitor_id, 'status': status.name, 'details': monitor_details}), 200

            except UptimeKumaException as e:
                logger.warning("Attempt %s: Monitor ID %s does not exist: %s", attempt + 1, monitor_id, str(e))
                if "You are not logged in" in str(e):
                    logger.info("Re-authenticating due to session expiration.")
                    global api_instance
                    api_instance = None  # Force re-login
                elif attempt < max_retries - 1:
                    time.sleep(delay)
                    delay *= 2  # Exponential backoff for retries
                else:
                    return jsonify({'error': str(e)}), 404

            except socketio.exceptions.TimeoutError:
                logger.warning("Attempt %s: Timeout error while fetching monitor ID %s", attempt + 1, monitor_id)
                if attempt < max_retries - 1:
                    time.sleep(delay)
                    delay *= 2  # Exponential backoff for retries
                else:
                    return jsonify({'error': 'Timeout error'}), 504

        # If all attempts fail, return an error response
        return jsonify({'error': 'Failed to retrieve monitor status after retries'}), 500

    except Exception as e:
        logger.exception("Error fetching monitor status")
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    # Define paths to the SSL certificate and key files for HTTPS
    cert_file = '/var/web_certs/BIRTHDAY_SERVER/birthday.gold/STAR_birthday_gold_combined.pem'
    key_file = '/var/web_certs/BIRTHDAY_SERVER/birthday.gold/star.birthday.gold.key'
    
    # Start the Flask application, using HTTPS on the specified host and port
    app.run(host='0.0.0.0', port=5443, ssl_context=(cert_file, key_file))
