#include <Adafruit_Fingerprint.h>
#include <HardwareSerial.h>

#define RX_PIN 16
#define TX_PIN 17

HardwareSerial mySerial(1);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

uint8_t id;
bool menuPrinted = false;

void setup() {
  Serial.begin(9600);
  mySerial.begin(57600, SERIAL_8N1, 16, 17);
  while (!Serial);
  delay(100);
  Serial.println("\n\nAdafruit Fingerprint sensor enrollment");

  // set the data rate for the sensor serial port
  finger.begin(57600);

  if (finger.verifyPassword()) {
    Serial.println("Found fingerprint sensor!");
  } else {
    Serial.println("Did not find fingerprint sensor :(");
    while (1) { delay(1); }
  }

  Serial.println(F("Reading sensor parameters"));
  finger.getParameters();
  Serial.print(F("Status: 0x")); Serial.println(finger.status_reg, HEX);
  Serial.print(F("Sys ID: 0x")); Serial.println(finger.system_id, HEX);
  Serial.print(F("Capacity: ")); Serial.println(finger.capacity);
  Serial.print(F("Security level: ")); Serial.println(finger.security_level);
  Serial.print(F("Device address: ")); Serial.println(finger.device_addr, HEX);
  Serial.print(F("Packet len: ")); Serial.println(finger.packet_len);
  Serial.print(F("Baud rate: ")); Serial.println(finger.baud_rate);

  finger.getTemplateCount();
  Serial.print(F("Templates in DB: ")); Serial.println(finger.templateCount);
}

uint8_t readnumber(void) {
  uint8_t num = 0;
  while (num == 0) {
    while (!Serial.available());
    num = Serial.parseInt();
  }
  return num;
}

void printMenu() {
  Serial.println();
  Serial.println(F("=== Fingerprint Menu ==="));
  Serial.println(F("[e] Enroll new ID"));
  Serial.println(F("[m] Match/Identify"));
  Serial.println(F("[d] Delete ID"));
  Serial.println(F("[c] Count templates"));
  Serial.println(F("[x] Empty database"));
  Serial.print (F("Enter choice: "));
  menuPrinted = true;
}
void waitFingerGone() {
  // Tunggu sampai jari diangkat supaya serial tidak banjir
  while (finger.getImage() != FINGERPRINT_NOFINGER) {
    delay(50);
  }
  delay(200);
}

void loop() {
  static uint32_t lastScan = 0;
  if (millis() - lastScan < 150) return;   // throttle ringan
  lastScan = millis();

  uint8_t p = finger.getImage();
  if (p == FINGERPRINT_NOFINGER) return;   // tidak ada jari, lewati
  if (p != FINGERPRINT_OK) {               // error ambil gambar
    Serial.println("Imaging error");
    waitFingerGone();
    return;
  }

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) {
    Serial.println("Convert error");
    waitFingerGone();
    return;
  }

  p = finger.fingerFastSearch();
  if (p == FINGERPRINT_OK) {
    Serial.print("Match! ID #");
    Serial.print(finger.fingerID);
    Serial.print("  conf=");
    Serial.println(finger.confidence);
  } else {
    Serial.println("No match");
  }

  waitFingerGone();  // cegah spam saat jari masih nempel
}
