/**
 * @file RFID_Mapel_Reader_LCD.ino
 * @brief Membaca satu data Mata Pelajaran dari kartu MIFARE 1K dan menampilkannya di LCD I2C 16x2.
 * @details Kode ini adalah pasangan dari RFID_Mapel_Writer_LCD.ino.
 *
 * Hardware:
 * - ESP32 38-pin Dev Kit
 * - MFRC522 RFID Reader/Writer
 * - Active Buzzer
 * - I2C LCD 16x2
 *
 * Koneksi Pin ESP32 (SAMA SEPERTI PENULIS):
 * - MFRC522: SS(5), SCK(18), MISO(19), MOSI(23), RST(25)
 * - Buzzer:  GPIO 26
 * - I2C LCD: SDA(21), SCL(22)
 */

#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// --- Definisi Pin (Sama seperti program penulis) ---
const uint8_t RST_PIN = 25;
const uint8_t SS_PIN = 5;
const uint8_t BUZZER_PIN = 26;

// --- Inisialisasi Perangkat ---
MFRC522 mfrc522(SS_PIN, RST_PIN);
MFRC522::MIFARE_Key key;
LiquidCrystal_I2C lcd(0x27, 16, 2);

// --- Fungsi Buzzer ---
void beep(int note, int duration) {
  tone(BUZZER_PIN, note, duration);
  delay(duration * 1.3);
  noTone(BUZZER_PIN);
}
void beepSuccess() { beep(1000, 100); }
void beepError() { beep(500, 500); }
void beepCardDetect() { beep(800, 50); }

void setup() {
  Serial.begin(115200);
  SPI.begin();
  mfrc522.PCD_Init();
  pinMode(BUZZER_PIN, OUTPUT);

  // Inisialisasi LCD
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("Sistem ABSENSI");
  lcd.setCursor(0, 1);
  
  
  // Atur kunci default (harus sama dengan kunci saat menulis)
  for (byte i = 0; i < 6; i++) { key.keyByte[i] = 0xFF; }
  
  delay(2000);
  Serial.println("==========================================");
  Serial.println("Sistem ABSENSI");
  Serial.println("Letakkan kartu yang sudah terdaftar...");
  Serial.println("==========================================");
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Tempelkan Kartu");
  lcd.setCursor(0, 1);
  lcd.print("HALLO!!");
  beep(600, 100);
}

void loop() {
  // 1. Cek kartu baru
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    delay(50);
    return;
  }

  // --- Kartu Terdeteksi ---
  beepCardDetect();
  lcd.clear();
  lcd.print("Membaca Kartu...");
  Serial.println("\n** Kartu Terdeteksi, mencoba membaca... **");
  delay(500); // Beri jeda singkat agar pembacaan lebih stabil

  // Buffer untuk menampung data yang dibaca
  byte buffer[18];
  
  // Baca data dari blok 4 dan simpan statusnya
  MFRC522::StatusCode readStatus = ReadDataFromBlock(4, buffer);
  bool dataValid = false;

  if (readStatus == MFRC522::STATUS_OK) {
    // Cek apakah data yang dibaca valid (bukan kosong)
    for (int i = 0; i < 16; i++) {
      if (buffer[i] != ' ' && buffer[i] != 0x00) {
        dataValid = true;
        break;
      }
    }
  }

  lcd.clear();
  // KASUS 1: Berhasil membaca dan data valid
  if (readStatus == MFRC522::STATUS_OK && dataValid) {
    beepSuccess();
    Serial.print("Data terbaca dari Blok 4: ");
    
    char dataString[17];
    for (int i = 0; i < 16; i++) { dataString[i] = buffer[i]; }
    dataString[16] = '\0';

    Serial.println(dataString);

    lcd.setCursor(0, 0);
    lcd.print("Mapel Terdaftar:");
    lcd.setCursor(0, 1);
    lcd.print(dataString);

  // KASUS 2: Gagal membaca karena timeout (kartu ditarik terlalu cepat)
  } else if (readStatus == MFRC522::STATUS_TIMEOUT) {
    beepError();
    Serial.println("!!! GAGAL BACA: TIMEOUT. Tempel kartu lebih lama.");
    lcd.setCursor(0, 0);
    lcd.print("Tempel yg Lama");
    lcd.setCursor(0, 1);
    lcd.print(" SABAR WOI -_-");
  
  // KASUS 3: Gagal karena alasan lain atau data kosong
  } else {
    beepError();
    Serial.println("!!! KARTU TIDAK TERDAFTAR !!!");
    lcd.setCursor(0, 0);
    lcd.print("Kartu Tidak");
    lcd.setCursor(0, 1);
    lcd.print("Terdaftar!");
  }

  // Hentikan komunikasi
  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();

  delay(4000); // Tahan tampilan di LCD selama 4 detik
  Serial.println("\nSistem siap untuk kartu berikutnya.");
  lcd.clear();
  lcd.print("Tempelkan Kartu");
}

/**
 * @brief Membaca data dari sebuah blok di kartu MIFARE.
 * @param blockNum Nomor blok yang akan dibaca.
 * @param resultBuffer Array byte untuk menyimpan hasil baca.
 * @return Kode status dari MFRC522 (STATUS_OK jika berhasil).
 */
MFRC522::StatusCode ReadDataFromBlock(int blockNum, byte resultBuffer[]) {
  MFRC522::StatusCode status;
  byte bufferSize = 18;

  // 1. Autentikasi
  status = mfrc522.PCD_Authenticate(MFRC522::PICC_CMD_MF_AUTH_KEY_A, blockNum, &key, &(mfrc522.uid));
  if (status != MFRC522::STATUS_OK) {
    Serial.print("Gagal autentikasi: ");
    Serial.println(mfrc522.GetStatusCodeName(status));
    return status; // Kembalikan kode error
  }

  // 2. Baca data
  status = mfrc522.MIFARE_Read(blockNum, resultBuffer, &bufferSize);
  if (status != MFRC522::STATUS_OK) {
    Serial.print("Gagal membaca: ");
    Serial.println(mfrc522.GetStatusCodeName(status));
    return status; // Kembalikan kode error
  }
  
  return MFRC522::STATUS_OK; // Kembalikan status OK jika semua berhasil
}

