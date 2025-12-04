#include <Adafruit_Fingerprint.h>
#include <HardwareSerial.h>
#include <WiFi.h>
#include <Firebase_ESP_Client.h>
#include "addons/TokenHelper.h"
#include "addons/RTDBHelper.h"

// ====================== WIFI ======================
const char* WIFI_SSID = "PTC";
const char* WIFI_PASS = "surya1234";

// ====================== FIREBASE ======================
FirebaseData fbdo;
FirebaseAuth auth;
FirebaseConfig config;
#define FIREBASE_API_KEY   "AIzaSyDgrAuCUiiX5qod51uQT686b-CS12ub6KQ"
#define FIREBASE_DB_URL    "https://rfid-eb0c0-default-rtdb.asia-southeast1.firebasedatabase.app/"

// ====================== FINGERPRINT ======================
HardwareSerial fingerSerial(2);  // RX2=16, TX2=17
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&fingerSerial);

// ====================== SETUP ======================
void setup() {
  Serial.begin(115200);
  delay(1000);

  // WiFi
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("Menghubungkan WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(300);
    Serial.print(".");
  }
  Serial.println("\nWiFi Terhubung ✅");
  Serial.println(WiFi.localIP());

  // Firebase
  config.api_key = FIREBASE_API_KEY;
  config.database_url = FIREBASE_DB_URL;
  Firebase.signUp(&config, &auth, "", "");
  config.token_status_callback = tokenStatusCallback;
  Firebase.begin(&config, &auth);
  Firebase.reconnectNetwork(true);

  // Fingerprint
  fingerSerial.begin(57600, SERIAL_8N1, 16, 17);
  finger.begin(57600);

  Serial.println("=== SISTEM DAFTAR SIDIK JARI ESP32 ===");
  if (finger.verifyPassword()) {
    Serial.println("Sensor sidik jari terdeteksi ✅");
  } else {
    Serial.println("Gagal mendeteksi sensor ❌");
    while (1) delay(1);
  }
}

// =========================
// === MENU UTAMA ========
// =========================
void loop() {
  Serial.println("\n=== MENU SIDIK JARI ===");
  Serial.println("1. Daftarkan sidik jari + nama siswa");
  Serial.println("2. Lihat data siswa tersimpan");
  Serial.println("========================");
  Serial.print("Masukkan pilihan: ");

  while (!Serial.available());
  char pilihan = Serial.read();
  Serial.println(pilihan);

  if (pilihan == '1') {
    enrollFingerprintWithName();
  } else if (pilihan == '2') {
    viewStoredData();
  } else {
    Serial.println("Pilihan tidak valid.");
  }
}

// =========================
// === FUNGSI ENROLL ======
// =========================
void enrollFingerprintWithName() {
  int id;
  while (Serial.available()) Serial.read();

  Serial.print("Masukkan ID (1-127): ");
  while (!Serial.available());
  id = Serial.parseInt();
  Serial.println(id);

  if (id <= 0 || id > 127) {
    Serial.println("ID tidak valid ❌");
    return;
  }

  while (Serial.available()) Serial.read();

  Serial.print("Masukkan nama siswa: ");
  while (!Serial.available());
  String nama = Serial.readStringUntil('\n');
  nama.trim();
  Serial.println(nama);

  Serial.println("Letakkan jari di sensor...");
  while (finger.getImage() != FINGERPRINT_OK);
  if (finger.image2Tz(1) != FINGERPRINT_OK) {
    Serial.println("Gagal membaca sidik jari pertama ❌");
    return;
  }

  Serial.println("Angkat jari dan letakkan kembali...");
  delay(2000);

  while (finger.getImage() != FINGERPRINT_OK);
  if (finger.image2Tz(2) != FINGERPRINT_OK) {
    Serial.println("Gagal membaca sidik jari kedua ❌");
    return;
  }

  if (finger.createModel() != FINGERPRINT_OK) {
    Serial.println("Gagal membuat model sidik jari ❌");
    return;
  }

  if (finger.storeModel(id) == FINGERPRINT_OK) {
    Serial.println("Sidik jari berhasil disimpan ✅");

    // Simpan data ke Firebase
    FirebaseJson json;
    json.set("nama", nama);
    json.set("finger_id", id);

    String path = "/students/" + String(id);
    if (Firebase.RTDB.setJSON(&fbdo, path.c_str(), &json)) {
      Serial.println("Data siswa tersimpan di Firebase ✅");
    } else {
      Serial.println("Gagal simpan Firebase ❌: " + fbdo.errorReason());
    }

  } else {
    Serial.println("Gagal menyimpan sidik jari ❌");
  }
}

// =========================
// === FUNGSI VIEW DATA ===
// =========================
void viewStoredData() {
  Serial.println("Mengambil data siswa dari Firebase...");

  if (Firebase.RTDB.getJSON(&fbdo, "/students")) {
    FirebaseJson &json = fbdo.jsonObject();

    size_t count = json.iteratorBegin();
    if (count == 0) {
      Serial.println("Belum ada data siswa ❌");
      return;
    }

    Serial.println("=== DATA SISWA ===");
    for (size_t i = 0; i < count; i++) {
      String key, value;
      int type;
      json.iteratorGet(i, type, key, value);

      FirebaseJsonData val;
      FirebaseJson childJson;
      childJson.setJsonData(value);
      if (childJson.get(val, "nama")) {
        Serial.print("ID: "); Serial.println(key);
        Serial.print("Nama: "); Serial.println(val.stringValue);
        Serial.println("-----------------");
      }
    }
    json.iteratorEnd();
  } else {
    Serial.println("Gagal mengambil data ❌: " + fbdo.errorReason());
  }
}
