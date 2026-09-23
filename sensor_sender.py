""" IoT Temperature & Humidity Monitoring System Python Bridge - Reads serial data from Arduino and posts to PHP API. """


#   implement: cmd
#   cd C:\xampp\htdocs\biodataprojects\
#   python sensor_sender.py


import serial
import requests
import time
import sys

# -- Configuration -----------------------------------------------
COM_PORT    = "COM5"          # Change for your OS / port
BAUD        = 9600
SERVER_URL  = "http://localhost/biodataprojects/API/iot_insert.php"
RETRY_DELAY = 5                # seconds between reconnect attempts

# Fallback only — used to compute the alert flag ourselves if an older
# Arduino sketch (without the 5th "alert" field) is still flashed.
# Keep this equal to HIGH_TEMP_THRESHOLD_C in temperature_sender.ino
# and API/iot_config.php so every layer agrees on the same limit.

HIGH_TEMP_THRESHOLD_C = 35.0

# ------------------------------------------------------------------


def connect_serial():
    """Try to open the serial port; retry on failure."""
    while True:
        try:
            arduino = serial.Serial(COM_PORT, BAUD, timeout=5)
            time.sleep(2)  # Let Arduino reset after serial open
            print(f"[OK] Connected to Arduino on {COM_PORT} at {BAUD} baud.")
            return arduino
        except serial.SerialException as e:
            print(f"[ERROR] Cannot open {COM_PORT}: {e}")
            print(f"[INFO] Retrying in {RETRY_DELAY} seconds...")
            time.sleep(RETRY_DELAY)


def post_data(temperature: str, humidity: str, alert: str) -> bool:
    """Send temperature, humidity, and the alert flag to the PHP API."""
    payload = {
        "temperature": temperature.strip(),
        "humidity":    humidity.strip(),
        "alert":       alert.strip()
    }
    try:
        response = requests.post(SERVER_URL, data=payload, timeout=5)
        if response.status_code == 200 and response.text.strip() == "OK":
            tag = " [ALERT]" if alert.strip() == "1" else ""
            print(f"[SENT] Temp={temperature}C | Humidity={humidity}%{tag}")
            return True
        else:
            print(f"[WARN] Server responded: {response.status_code} - {response.text.strip()}")
            return False
    except requests.exceptions.ConnectionError:
        print(f"[ERROR] Cannot reach server at {SERVER_URL}")
        return False
    except requests.exceptions.Timeout:
        print("[ERROR] Request timed out.")
        return False


def main():
    print("=" * 50)
    print(" IoT Monitoring - Python Bridge")
    print("=" * 50)

    arduino = connect_serial()

    while True:
        try:
            raw = arduino.readline().decode("utf-8", errors="replace").strip()

            if not raw:
                continue

            # Handle Arduino error messages
            if raw.startswith("ERROR"):
                print(f"[ARDUINO ERROR] {raw}")
                continue

            # Handle DATA packets
            if raw.startswith("DATA,"):
                parts = raw.split(",")

                # New format: DATA,temp,humidity,count,alert
                # Old format (pre-alert-feature firmware): DATA,temp,humidity,count
                if len(parts) == 5:
                    _, temperature, humidity, count, alert = parts
                elif len(parts) == 4:
                    _, temperature, humidity, count = parts
                    alert = None  # not sent by this firmware — computed below
                else:
                    print(f"[SKIP] Malformed DATA packet: {raw}")
                    continue

                try:
                    t = float(temperature)
                    h = float(humidity)

                    # validate again (extra safety)
                    if -40 <= t <= 80 and 0 <= h <= 100:
                        if alert is None:
                            alert = "1" if t >= HIGH_TEMP_THRESHOLD_C else "0"
                        post_data(temperature, humidity, alert)
                        tag = " [ALERT]" if alert == "1" else ""
                        print(f"[DATA] Temp={t}°C | Humidity={h}% | Count={count}{tag}")
                    else:
                        print(f"[SKIP] Out-of-range values: T={t}, H={h}")

                except ValueError:
                    print(f"[SKIP] Non-numeric DATA: {raw}")

                continue

            # Any other serial output (startup logs, etc.)
            print(f"[ARDUINO] {raw}")

        except serial.SerialException as e:
            print(f"[ERROR] Serial connection lost: {e}")
            print("[INFO] Reconnecting...")
            try:
                arduino.close()
            except Exception:
                pass
            arduino = connect_serial()

        except KeyboardInterrupt:
            print("\n[INFO] Monitoring stopped by user.")
            arduino.close()
            sys.exit(0)


if __name__ == "__main__":
    main()