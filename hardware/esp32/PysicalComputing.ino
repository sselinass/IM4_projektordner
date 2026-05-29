/******************************************************************************************
 *
 * READY SET DINNER
 * MULTI BUZZER DINNER SYSTEM
 *
 * ----------------------------------------------------------------------------
 * PROJEKTÜBERSICHT
 * ----------------------------------------------------------------------------
 *
 * Dieses System steuert ein interaktives Dinner-/Buzzer-Spiel mit:
 *
 * - 1 Start-Button mit integrierter LED
 * - 4 End-Buttons
 * - 3 Status-LEDs
 * - 1 Piepser
 * - WLAN-Verbindung
 * - HTTP API Kommunikation
 * - NTP Zeitsynchronisation
 *
 * Das Ziel:
 *
 * 1. Eine Person startet das Spiel über den Start-Button.
 * 2. Alle LEDs gehen an.
 * 3. Der Piepser wird aktiviert.
 * 4. Die Teilnehmenden drücken ihren jeweiligen End-Button.
 * 5. Jeder Tastendruck wird live an die API gesendet.
 * 6. Nach allen gedrückten Buttons endet das Spiel automatisch.
 *
 * ----------------------------------------------------------------------------
 * SYSTEMVERHALTEN
 * ----------------------------------------------------------------------------
 *
 * START BUTTON
 * ----------------------------------------------------------------------------
 * GPIO 7  -> Start-Button
 * GPIO 8  -> Integrierte LED des Start-Buttons
 *
 * Beim Drücken:
 * - Spiel startet
 * - Start-LED leuchtet
 * - Alle End-LEDs leuchten
 * - Piepser aktiviert sich
 * - Start-Event wird an die API gesendet
 *
 * ----------------------------------------------------------------------------
 * END BUTTONS
 * ----------------------------------------------------------------------------
 *
 * Button 1
 * GPIO 10 -> End-Button 1
 * GPIO 2  -> Eigene LED
 *
 * Button 2
 * GPIO 6  -> End-Button 2
 * GPIO 5  -> Eigene LED
 *
 * Button 3
 * GPIO 0  -> End-Button 3
 *
 * Button 4
 * GPIO 1  -> End-Button 4
 *
 * Gemeinsame LED
 * GPIO 3 -> LED für Button 3 + 4
 *
 * Verhalten der gemeinsamen LED
 * - Die LED bleibt aktiv,
 *   bis BOTH Button 3 UND Button 4 gedrückt wurden.
 * - Erst danach wird die LED ausgeschaltet.
 *
 * ----------------------------------------------------------------------------
 * PIEPSER
 * ----------------------------------------------------------------------------
 *
 * GPIO 11 -> Piepser
 *
 * Verhalten:
 * - Aktiviert sich beim Spielstart
 * - Wird nach PIEPSER_TIMEOUT deaktiviert (20sec)
 * - Oder direkt beim Spielende
 *
 * ----------------------------------------------------------------------------
 * SPIELENDE
 * ----------------------------------------------------------------------------
 *
 * Das Spiel endet automatisch wenn:
 *
 * - Alle 4 End-Buttons gedrückt wurden
 * ODER
 * - Das GAME_TIMEOUT erreicht wurde (5min)
 *
 * Beim Spielende:
 * - Alle LEDs werden ausgeschaltet
 * - Piepser wird ausgeschaltet
 * - End-Event wird an die API gesendet
 *
 * ----------------------------------------------------------------------------
 * WLAN
 * ----------------------------------------------------------------------------
 *
 * Das Gerät verbindet sich automatisch mit dem WLAN.
 * Bei Verbindungsverlust wird automatisch versucht,
 * die Verbindung erneut aufzubauen.
 *
 * ----------------------------------------------------------------------------
 * ZEITSYNCHRONISATION
 * ----------------------------------------------------------------------------
 *
 * Über NTP wird die aktuelle Uhrzeit geladen,
 * damit alle API Events einen korrekten Timestamp erhalten.
 *
 * ----------------------------------------------------------------------------
 * API KOMMUNIKATION
 * ----------------------------------------------------------------------------
 *
 * Jeder Event wird sofort per HTTP POST an die API gesendet.
 *
 * Gesendete Events:
 * - Start
 * - Buzzer_1
 * - Buzzer_2
 * - Buzzer_3
 * - Buzzer_4
 * - End
 *
 * JSON Beispiel:
 *
 * {
 *   "buzzer_events":"Buzzer_1",
 *   "timestamp":"2026-05-21 12:30:55",
 *   "id_users":12
 * }
 *
 * ----------------------------------------------------------------------------
 * TECHNISCHE DETAILS
 * ----------------------------------------------------------------------------
 *
 * Plattform:
 * - ESP32 / ESP32-C6
 *
 * Verwendete Libraries:
 * - WiFi.h
 * - HTTPClient.h
 * - time.h
 *
 * Kommunikation:
 * - WLAN
 * - HTTP POST
 * - JSON
 * - NTP
 *
 ******************************************************************************************/

 #include <WiFi.h>
 #include <HTTPClient.h>
 #include <time.h>
 
 //////////////////////////////////////////////////////////////////
 // WLAN
 //////////////////////////////////////////////////////////////////
 
 const char* ssid = "tinkergarden";
 const char* pass = "strenggeheim";
 
 //////////////////////////////////////////////////////////////////
 // API
 //////////////////////////////////////////////////////////////////
 
 const char* serverURL = "https://im4.selina-schoepfer.ch/api/load.php";
 
 //////////////////////////////////////////////////////////////////
 // ZEIT / NTP
 //////////////////////////////////////////////////////////////////
 
 const char* ntpServer = "pool.ntp.org";
 
 const long gmtOffset_sec = 3600;
 const int daylightOffset_sec = 3600;
 
 //////////////////////////////////////////////////////////////////
 // GPIO
 //////////////////////////////////////////////////////////////////
 
 // START BUTTON MIT INTEGRIERTER LED
 const int startButtonPin = 7;
 const int startButtonLedPin = 8;
 
 // END BUTTONS
 const int endButton1Pin = 10;
 const int endButton2Pin = 6;
 const int endButton3Pin = 0;
 const int endButton4Pin = 1;
 
 // LEDS
 const int endButton1LedPin = 2;
 const int endButton2LedPin = 5;
 
 // Button 3 + 4 teilen dieselbe LED
 const int endButton3LedPin = 3;
 
 // PIEPSER
 const int piepserPin = 11;
 
 //////////////////////////////////////////////////////////////////
 // BUTTON STATUS
 //////////////////////////////////////////////////////////////////
 
 int startButtonState = LOW;
 int lastStartButtonState = LOW;
 
 int endButton1State = LOW;
 int lastEndButton1State = LOW;
 
 int endButton2State = LOW;
 int lastEndButton2State = LOW;
 
 int endButton3State = LOW;
 int lastEndButton3State = LOW;
 
 int endButton4State = LOW;
 int lastEndButton4State = LOW;
 
 //////////////////////////////////////////////////////////////////
 // SPIELSTATUS
 //////////////////////////////////////////////////////////////////
 
 bool gameRunning = false;
 
 bool end1Pressed = false;
 bool end2Pressed = false;
 bool end3Pressed = false;
 bool end4Pressed = false;
 
 bool piepserActive = false;
 
 unsigned long gameStartMillis = 0;
 
 const unsigned long GAME_TIMEOUT = 5UL * 60UL * 1000UL;
 const unsigned long PIEPSER_TIMEOUT = 20UL * 1000UL;
 
 //////////////////////////////////////////////////////////////////
 // WLAN STATUS
 //////////////////////////////////////////////////////////////////
 
 bool isWlanConnected = false;
 
 //////////////////////////////////////////////////////////////////
 // SETUP
 //////////////////////////////////////////////////////////////////
 
 void setup() {
 
   Serial.begin(115200);
 
   delay(1000);
 
   pinMode(startButtonPin, INPUT_PULLDOWN);
 
   pinMode(endButton1Pin, INPUT_PULLDOWN);
   pinMode(endButton2Pin, INPUT_PULLDOWN);
   pinMode(endButton3Pin, INPUT_PULLDOWN);
   pinMode(endButton4Pin, INPUT_PULLDOWN);
 
   pinMode(startButtonLedPin, OUTPUT);
 
   pinMode(endButton1LedPin, OUTPUT);
   pinMode(endButton2LedPin, OUTPUT);
   pinMode(endButton3LedPin, OUTPUT);
 
   pinMode(piepserPin, OUTPUT);
 
   allOutputsOff();
 
   connectWiFi();
 
   if (isWlanConnected) {
 
     configTime(gmtOffset_sec, daylightOffset_sec, ntpServer);
 
     Serial.println("NTP Zeit wird synchronisiert...");
 
     delay(2000);
   }
 }
 
 //////////////////////////////////////////////////////////////////
 // LOOP
 //////////////////////////////////////////////////////////////////
 
 void loop() {
 
   is_wlan_connected();
 
   startButtonState = digitalRead(startButtonPin);
 
   endButton1State = digitalRead(endButton1Pin);
   endButton2State = digitalRead(endButton2Pin);
   endButton3State = digitalRead(endButton3Pin);
   endButton4State = digitalRead(endButton4Pin);
 
   ////////////////////////////////////////////////////////////////
   // GAME START
   ////////////////////////////////////////////////////////////////
 
   if (startButtonState == HIGH &&
       lastStartButtonState == LOW &&
       !gameRunning) {
 
     Serial.println("GAME START");
 
     gameRunning = true;
 
     end1Pressed = false;
     end2Pressed = false;
     end3Pressed = false;
     end4Pressed = false;
 
     piepserActive = true;
 
     gameStartMillis = millis();
 
     digitalWrite(startButtonLedPin, HIGH);
 
     digitalWrite(endButton1LedPin, HIGH);
     digitalWrite(endButton2LedPin, HIGH);
     digitalWrite(endButton3LedPin, HIGH);
 
     digitalWrite(piepserPin, HIGH);
 
     sendEvent("Start");
   }
 
   ////////////////////////////////////////////////////////////////
   // PIEPSER TIMEOUT
   ////////////////////////////////////////////////////////////////
 
   if (gameRunning &&
       piepserActive &&
       millis() - gameStartMillis >= PIEPSER_TIMEOUT) {
 
     Serial.println("PIEPSER OFF");
 
     digitalWrite(piepserPin, LOW);
 
     piepserActive = false;
   }
 
   ////////////////////////////////////////////////////////////////
   // END BUTTON 1
   ////////////////////////////////////////////////////////////////
 
   if (gameRunning &&
       endButton1State == HIGH &&
       lastEndButton1State == LOW &&
       !end1Pressed) {
 
     Serial.println("END BUTTON 1");
 
     end1Pressed = true;
 
     digitalWrite(endButton1LedPin, LOW);
 
     sendEvent("Buzzer_1");
   }
 
   ////////////////////////////////////////////////////////////////
   // END BUTTON 2
   ////////////////////////////////////////////////////////////////
 
   if (gameRunning &&
       endButton2State == HIGH &&
       lastEndButton2State == LOW &&
       !end2Pressed) {
 
     Serial.println("END BUTTON 2");
 
     end2Pressed = true;
 
     digitalWrite(endButton2LedPin, LOW);
 
     sendEvent("Buzzer_2");
   }
 
   ////////////////////////////////////////////////////////////////
   // END BUTTON 3
   ////////////////////////////////////////////////////////////////
 
   if (gameRunning &&
       endButton3State == HIGH &&
       lastEndButton3State == LOW &&
       !end3Pressed) {
 
     Serial.println("END BUTTON 3");
 
     end3Pressed = true;
 
     // LED erst löschen wenn beide gedrückt wurden
     if (end4Pressed) {
       digitalWrite(endButton3LedPin, LOW);
     }
 
     sendEvent("Buzzer_3");
   }
 
   ////////////////////////////////////////////////////////////////
   // END BUTTON 4
   ////////////////////////////////////////////////////////////////
 
   if (gameRunning &&
       endButton4State == HIGH &&
       lastEndButton4State == LOW &&
       !end4Pressed) {
 
     Serial.println("END BUTTON 4");
 
     end4Pressed = true;
 
     // LED erst löschen wenn beide gedrückt wurden
     if (end3Pressed) {
       digitalWrite(endButton3LedPin, LOW);
     }
 
     sendEvent("Buzzer_4");
   }
 
   ////////////////////////////////////////////////////////////////
   // GAME ENDE
   ////////////////////////////////////////////////////////////////
 
   if (gameRunning &&
       end1Pressed &&
       end2Pressed &&
       end3Pressed &&
       end4Pressed) {
 
     endGame();
   }
 
   ////////////////////////////////////////////////////////////////
   // GAME TIMEOUT
   ////////////////////////////////////////////////////////////////
 
   if (gameRunning &&
       millis() - gameStartMillis >= GAME_TIMEOUT) {
 
     endGame();
   }
 
   ////////////////////////////////////////////////////////////////
   // LAST STATES SPEICHERN
   ////////////////////////////////////////////////////////////////
 
   lastStartButtonState = startButtonState;
 
   lastEndButton1State = endButton1State;
   lastEndButton2State = endButton2State;
   lastEndButton3State = endButton3State;
   lastEndButton4State = endButton4State;
 }
 
 //////////////////////////////////////////////////////////////////
 // SPIEL BEENDEN
 //////////////////////////////////////////////////////////////////
 
 void endGame() {
 
   Serial.println("GAME END");
 
   gameRunning = false;
 
   piepserActive = false;
 
   allOutputsOff();
 
   sendEvent("End");
 }
 
 //////////////////////////////////////////////////////////////////
 // ALLE OUTPUTS AUS
 //////////////////////////////////////////////////////////////////
 
 void allOutputsOff() {
 
   digitalWrite(startButtonLedPin, LOW);
 
   digitalWrite(endButton1LedPin, LOW);
   digitalWrite(endButton2LedPin, LOW);
   digitalWrite(endButton3LedPin, LOW);
 
   digitalWrite(piepserPin, LOW);
 }
 
 //////////////////////////////////////////////////////////////////
 // ZEIT HOLEN
 //////////////////////////////////////////////////////////////////
 
 String getCurrentTimestamp() {
 
   struct tm timeinfo;
 
   if (!getLocalTime(&timeinfo)) {
 
     Serial.println("Zeit konnte nicht gelesen werden");
 
     return "1970-01-01 00:00:00";
   }
 
   char timestamp[20];
 
   strftime(timestamp,
            sizeof(timestamp),
            "%Y-%m-%d %H:%M:%S",
            &timeinfo);
 
   return String(timestamp);
 }
 
 //////////////////////////////////////////////////////////////////
 // EVENT SENDEN
 //////////////////////////////////////////////////////////////////
 
 void sendEvent(const char* eventName) {
 
   if (!isWlanConnected) {
 
     Serial.println("Kein WLAN");
 
     return;
   }
 
   String timestamp = getCurrentTimestamp();
 
   String jsonString = "{";
 
   jsonString += "\"buzzer_events\":\"";
   jsonString += eventName;
   jsonString += "\",";
 
   jsonString += "\"timestamp\":\"";
   jsonString += timestamp;
   jsonString += "\",";
 
   jsonString += "\"id_users\":12";
 
   jsonString += "}";
 
   Serial.println("Sende Event:");
   Serial.println(jsonString);
 
   HTTPClient http;
 
   http.begin(serverURL);
 
   http.addHeader("Content-Type", "application/json");
 
   int httpResponseCode = http.POST(jsonString);
 
   if (httpResponseCode > 0) {
 
     Serial.printf("HTTP Response: %d\n", httpResponseCode);
 
     String response = http.getString();
 
     Serial.println(response);
 
   } else {
 
     Serial.printf("HTTP Fehler: %d\n", httpResponseCode);
   }
 
   http.end();
 }
 
 //////////////////////////////////////////////////////////////////
 // WLAN VERBINDUNG
 //////////////////////////////////////////////////////////////////
 
 void connectWiFi() {
 
   Serial.printf("Verbinde mit WLAN %s\n", ssid);
 
   WiFi.begin(ssid, pass);
 
   int attempts = 0;
 
   while (WiFi.status() != WL_CONNECTED &&
          attempts < 40) {
 
     delay(500);
 
     Serial.print(".");
 
     attempts++;
   }
 
   if (WiFi.status() == WL_CONNECTED) {
 
     Serial.println("\nWLAN verbunden");
 
     Serial.println(WiFi.localIP());
 
     isWlanConnected = true;
 
   } else {
 
     Serial.println("\nWLAN fehlgeschlagen");
 
     isWlanConnected = false;
   }
 }
 
 //////////////////////////////////////////////////////////////////
 // WLAN STATUS
 //////////////////////////////////////////////////////////////////
 
 bool is_wlan_connected() {
 
   if (WiFi.status() != WL_CONNECTED) {
 
     if (isWlanConnected) {
 
       Serial.println("WLAN verloren");
 
       isWlanConnected = false;
     }
 
     connectWiFi();
 
     return false;
   }
 
   isWlanConnected = true;
 
   return true;
 }