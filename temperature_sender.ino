#include <DHT.h>

#define DHTPIN 7
#define DHTTYPE DHT22

DHT dht(DHTPIN, DHTTYPE);

#define RED_LED_PIN     5
#define YELLOW_LED_PIN  6
#define BUZZER_PIN      8
#define HIGH_TEMP_THRESHOLD_C 35.0

// Tone frequency in Hertz (1000Hz is a clear beep)
#define BUZZER_FREQ 1000 

unsigned long readingCount = 0;
unsigned int errorCount = 0;

void setIndicators(bool alertOn)
{
    digitalWrite(RED_LED_PIN, alertOn ? HIGH : LOW);
    digitalWrite(YELLOW_LED_PIN, alertOn ? LOW : HIGH);
    
    if (alertOn) {
        tone(BUZZER_PIN, BUZZER_FREQ); // Correct way to drive a passive buzzer
    } else {
        noTone(BUZZER_PIN); // Stop the sound
    }
}

void setup()
{
    Serial.begin(9600);

    pinMode(RED_LED_PIN, OUTPUT);
    pinMode(YELLOW_LED_PIN, OUTPUT);
    pinMode(BUZZER_PIN, OUTPUT);

    // --- STARTUP TEST BEEP ---
    // This confirms your wiring works immediately on power-up
    tone(BUZZER_PIN, 2000); 
    delay(200);
    noTone(BUZZER_PIN);
    delay(100);
    tone(BUZZER_PIN, 2000);
    delay(200);
    noTone(BUZZER_PIN);
    // -------------------------

    setIndicators(false);
    dht.begin();
    delay(3000); 
    Serial.println("SYSTEM_READY");
}

void loop()
{
    float temperature = dht.readTemperature();
    float humidity = dht.readHumidity();

    if (isnan(temperature) || isnan(humidity))
    {
        errorCount++;
        setIndicators(false);
        Serial.println("ERROR,READ_FAILED");
        delay(5000);
        return;
    }

    readingCount++;
    bool isAlert = temperature >= HIGH_TEMP_THRESHOLD_C;
    setIndicators(isAlert);

    Serial.print("DATA,");
    Serial.print(temperature, 1);
    Serial.print(",");
    Serial.print(humidity, 1);
    Serial.print(",");
    Serial.print(readingCount);
    Serial.print(",");
    Serial.println(isAlert ? 1 : 0);

    delay(5000);
}
