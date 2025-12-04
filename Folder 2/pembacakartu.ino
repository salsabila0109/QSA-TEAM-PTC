#include <SPI.h>
#include <MFRC522.h>

// Definisikan pin yang sama seperti proyek Anda
#define SS_PIN    5   // D5
#define RST_PIN   4   // D4

MFRC522 rfid(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);
  SPI.begin();
  rfid.PCD_Init();
  Serial.println("Tempelkan e-KTP Anda untuk membaca UID Chip...");
}

void loop() {
  if ( ! rfid.PICC_IsNewCardPresent() || ! rfid.PICC_ReadCardSerial()) {
    return;
  }

  Serial.print("UID Chip KTP Terdeteksi: ");
  String uidContent = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    uidContent.concat(String(rfid.uid.uidByte[i] < 0x10 ? " 0" : " "));
    uidContent.concat(String(rfid.uid.uidByte[i], HEX));
  }
  uidContent.toUpperCase();
  uidContent.trim();
  
  Serial.println(uidContent);
  delay(2000);
}
