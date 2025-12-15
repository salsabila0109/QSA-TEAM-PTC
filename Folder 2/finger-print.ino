#include <WiFi.h>
#include <HardwareSerial.h>
#include <Adafruit_Fingerprint.h>

#include <Firebase_ESP_Client.h>
#include "addons/TokenHelper.h"
#include "addons/RTDBHelper.h"

// ====================== WIFI ======================
const char* WIFI_SSID = "aa";
const char* WIFI_PASS = "abcdefgh";

// ====================== FIREBASE ======================
FirebaseData fbdo;
FirebaseAuth auth;
FirebaseConfig config;

#define FIREBASE_API_KEY "AIzaSyCuayRKFrLjWaVcrWBeWZjmA4V_CWPtPcU"
#define FIREBASE_DB_URL  "https://presentech-4c4c0-default-rtdb.firebaseio.com/"

// ====================== FINGERPRINT ======================
// ESP32: HardwareSerial(2) pakai pins RX2=16, TX2=17
HardwareSerial fingerSerial(2);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&fingerSerial);

// ====================== HELPER SERIAL ======================
String readLine() {
  String s = "";
  unsigned long start = millis();
  while (true) {
    while (Serial.available()) {
      char c = Serial.read();
      if (c == '\r') continue;
      if (c == '\n') return s;
      s += c;
    }
    if (millis() - start > 30000) break; // timeout 30s
    delay(10);
  }
  s.trim();
  return s;
}

// Validasi key / NIS (kalau mau fleksibel bisa dilonggarkan)
bool isValidKey(const String &k) {
  if (k.length() < 3) return false;
  return true;
}

bool isValidNIS(const String &nis) {
  if (nis.length() < 6) return false;
  return true;
}

// ====================== SLOT OTOMATIS ======================
// Cari slot berikutnya di sensor berdasarkan templateCount
int getNextSlot() {
  int rc = finger.getTemplateCount();
  if (rc != FINGERPRINT_OK) {
    Serial.print("getTemplateCount() error: ");
    Serial.println(rc);
  }

  uint16_t count = finger.templateCount; // jumlah template sekarang
  uint16_t cap   = finger.capacity;      // kapasitas sensor (kalau lib mendukung)

  Serial.print("Sensor: templates=");
  Serial.print(count);
  Serial.print(" capacity=");
  Serial.println(cap);

  if (cap == 0) cap = 127;   // fallback

  if (count >= cap) {
    Serial.println("Slot penuh");
    return -1;
  }

  // anggapan: slot dipakai berurutan tanpa lubang
  return (int)count + 1;
}

// ====================== HAPUS DATABASE FINGERPRINT (SENSOR) ======================
void clearFingerprintDatabase() {
  Serial.println("\n=== HAPUS SEMUA SIDIK JARI DI SENSOR ===");
  Serial.println("PERINGATAN: Ini hanya menghapus di sensor, BUKAN di Firebase.");
  Serial.println("Jika setuju, ketik: YA lalu tekan Enter.");
  Serial.print("Konfirmasi: ");

  String ans = readLine();
  ans.trim();
  ans.toUpperCase();

  if (ans != "YA") {
    Serial.println("Dibatalkan ❌");
    return;
  }

  uint8_t p = finger.emptyDatabase();
  if (p == FINGERPRINT_OK) {
    Serial.println("Database fingerprint di sensor DIKOSONGKAN ✅");
    // Refresh jumlah template
    finger.getTemplateCount();
    Serial.print("Jumlah template sekarang: ");
    Serial.println(finger.templateCount);
  } else {
    Serial.print("Gagal kosongkan database, kode error: ");
    Serial.println(p);
  }
}

// ====================== CEK INFO SENSOR ======================
void showSensorInfo() {
  int rc = finger.getTemplateCount();
  if (rc != FINGERPRINT_OK) {
    Serial.print("getTemplateCount() error: ");
    Serial.println(rc);
  }

  Serial.println("\n=== INFO SENSOR FINGERPRINT ===");
  Serial.print("Jumlah template tersimpan: ");
  Serial.println(finger.templateCount);
  Serial.print("Kapasitas (capacity)     : ");
  Serial.println(finger.capacity);
  Serial.println("==============================");
}

// ====================== ENROLL / PENDAFTARAN ======================
void enrollFingerprint(bool manualSlot) {
  while (Serial.available()) Serial.read();

  Serial.println("\n=== PENDAFTARAN SIDIK JARI ===");

  // 1) Input KODE SISWA (KEY untuk node Firebase)
  Serial.print("Masukkan Kode Siswa (key, contoh: 202543): ");
  String key = readLine();
  key.trim();
  Serial.println("Key diterima: " + key);
  if (!isValidKey(key)) {
    Serial.println("Key tidak valid ❌ (panjang minimal 3)");
    return;
  }

  // 2) Input NIS
  Serial.print("Masukkan NIS lengkap (misal: 2023123417): ");
  String nis = readLine();
  nis.trim();
  Serial.println("NIS diterima: " + nis);
  if (!isValidNIS(nis)) {
    Serial.println("NIS tidak valid ❌ (panjang minimal 6)");
    return;
  }

  // 3) Input Nama siswa
  Serial.print("Masukkan nama siswa: ");
  String nama = readLine();
  nama.trim();
  Serial.println("Nama diterima: " + nama);
  if (nama.length() == 0) {
    Serial.println("Nama tidak boleh kosong ❌");
    return;
  }

  // 4) Tentukan slot fingerprint
  int slot = -1;
  if (manualSlot) {
    Serial.print("Masukkan nomor slot fingerprint (1..127): ");
    String sSlot = readLine();
    sSlot.trim();
    slot = sSlot.toInt();
    if (slot <= 0 || slot > 127) {
      Serial.println("Slot tidak valid ❌");
      return;
    }
  } else {
    slot = getNextSlot();
    if (slot <= 0) {
      Serial.println("Slot sensor tidak tersedia ❌");
      return;
    }
  }

  Serial.print("Fingerprint akan disimpan di slot: ");
  Serial.println(slot);

  // ====== PROSES SCAN TANPA TIMEOUT ======
  uint8_t p = 0;

  // --- Scan 1 ---
  Serial.println();
  Serial.println("Tempel sidik jari 1...");
  while (true) {
    p = finger.getImage();
    if (p == FINGERPRINT_OK) {
      Serial.println("SidikJari 1 terbaca ✅");
      break;
    }
    // kalau mau lihat error bisa buka komentar ini:
    // else if (p != FINGERPRINT_NOFINGER) {
    //   Serial.print("getImage(1) error: "); Serial.println(p);
    // }
    delay(50);
  }

  p = finger.image2Tz(1);
  if (p != FINGERPRINT_OK) {
    Serial.print("image2Tz(1) error: ");
    Serial.println(p);
    return;
  }

  Serial.println("Angkat jari...");
  // tunggu sampai jari bener-bener diangkat
  while (true) {
    p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER) {
      Serial.println("Jari sudah diangkat.");
      break;
    }
    delay(50);
  }

  // --- Scan 2 ---
  Serial.println("Tempel sidik jari 2 (jari yang sama)...");
  while (true) {
    p = finger.getImage();
    if (p == FINGERPRINT_OK) {
      Serial.println("SidikJari 2 terbaca ✅");
      break;
    }
    delay(50);
  }

  p = finger.image2Tz(2);
  if (p != FINGERPRINT_OK) {
    Serial.print("image2Tz(2) error: ");
    Serial.println(p);
    return;
  }

  // Buat model & simpan ke slot
  p = finger.createModel();
  if (p != FINGERPRINT_OK) {
    Serial.print("createModel error: ");
    Serial.println(p);
    return;
  }

  p = finger.storeModel(slot);
  if (p != FINGERPRINT_OK) {
    Serial.print("storeModel error: ");
    Serial.println(p);
    return;
  }

  Serial.println("Sidik jari tersimpan di sensor ✅ (slot " + String(slot) + ")");

  // 6) Simpan ke Firebase
  String path = "/students/" + key; // key contoh: 202543

  FirebaseJson json;
  json.set("nis", nis);
  json.set("nama", nama);
  json.set("slot", slot);
  json.set("finger_id", slot);   // finger_id = slot sensor

  Serial.println("Menyimpan data siswa ke Firebase di path: " + path);
  if (Firebase.RTDB.setJSON(&fbdo, path.c_str(), &json)) {
    Serial.println("Data siswa tersimpan ke Firebase ✅");
  } else {
    Serial.print("Gagal simpan Firebase ❌: ");
    Serial.println(fbdo.errorReason());
  }
}


// ====================== VIEW DATA / CEK ======================
void viewStudents() {
  Serial.println("\n=== DATA STUDENTS DI FIREBASE ===");
  if (!Firebase.RTDB.getJSON(&fbdo, "/students")) {
    Serial.print("Gagal ambil data: ");
    Serial.println(fbdo.errorReason());
    return;
  }

  FirebaseJson &json = fbdo.jsonObject();
  size_t count = json.iteratorBegin();
  if (count == 0) {
    Serial.println("Belum ada data siswa.");
    json.iteratorEnd();
    return;
  }

  for (size_t i = 0; i < count; i++) {
    String key, value;
    int type;
    json.iteratorGet(i, type, key, value); // key = child key (contoh "202543")

    FirebaseJson child;
    child.setJsonData(value);

    FirebaseJsonData vNis, vNama, vSlot, vFinger;
    child.get(vNis, "nis");
    child.get(vNama, "nama");
    child.get(vSlot, "slot");
    child.get(vFinger, "finger_id");

    Serial.println("------------------------");
    Serial.print("Key      : "); Serial.println(key);
    Serial.print("NIS      : "); Serial.println(vNis.stringValue);
    Serial.print("Nama     : "); Serial.println(vNama.stringValue);
    Serial.print("slot     : "); Serial.println(vSlot.stringValue);
    Serial.print("finger_id: "); Serial.println(vFinger.stringValue);
  }

  json.iteratorEnd();
  Serial.println("------------------------");
}

// ====================== SETUP ======================
void setup() {
  Serial.begin(115200);
  delay(800);
  Serial.println();
  Serial.println("=== SETUP ESP32 FINGERPRINT + FIREBASE ===");

  // WiFi
  Serial.print("Menghubungkan WiFi: ");
  Serial.println(WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 120) {
    delay(500);
    Serial.print(".");
    tries++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi terhubung ✅");
    Serial.print("IP: "); Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nWiFi gagal terhubung ❌");
  }

  // Firebase
  config.api_key = "AIzaSyCuayRKFrLjWaVcrWBeWZjmA4V_CWPtPcU";
  config.database_url = "https://presentech-4c4c0-default-rtdb.firebaseio.com/";

  if (!Firebase.signUp(&config, &auth, "", "")) {
    Serial.print("Signup info: ");
    Serial.println(fbdo.errorReason());
  }
  config.token_status_callback = tokenStatusCallback;
  Firebase.begin(&config, &auth);
  Firebase.reconnectNetwork(true);

  // Fingerprint sensor
  fingerSerial.begin(57600, SERIAL_8N1, 16, 17); // RX=16, TX=17
  finger.begin(57600);

  Serial.println("Mencari sensor sidik jari...");
  if (finger.verifyPassword()) {
    Serial.println("Sensor sidik jari terdeteksi ✅");
    finger.getTemplateCount();
    Serial.print("Jumlah template terdaftar: ");
    Serial.println(finger.templateCount);
  } else {
    Serial.println("Sensor sidik jari TIDAK terdeteksi ❌");
    Serial.println("Periksa wiring / baudrate.");
    while (1) { delay(1); }
  }
}

// ====================== MENU LOOP ======================
void printMenu() {
  Serial.println();
  Serial.println("=== MENU SIDIK JARI ===");
  Serial.println("1. Daftar sidik jari (slot otomatis) + simpan ke Firebase");
  Serial.println("2. Daftar sidik jari (pilih slot manual) + simpan ke Firebase");
  Serial.println("3. Lihat data students di Firebase");
  Serial.println("4. Kosongkan database fingerprint di sensor");
  Serial.println("5. Lihat info sensor (templateCount & capacity)");
  Serial.println("========================");
  Serial.print("Masukkan pilihan (1/2/3/4/5): ");
}

void loop() {
  printMenu();
  unsigned long start = millis();
  while (!Serial.available()) {
    if (millis() - start > 60000) { // 60s tidak ada input, ulang menu
      return;
    }
    delay(10);
  }

  char pilih = Serial.read();
  Serial.println(pilih);
  while (Serial.available()) Serial.read(); // bersihkan buffer

  if (pilih == '1') {
    enrollFingerprint(false);  // slot otomatis
  } else if (pilih == '2') {
    enrollFingerprint(true);   // slot manual
  } else if (pilih == '3') {
    viewStudents();
  } else if (pilih == '4') {
    clearFingerprintDatabase();
  } else if (pilih == '5') {
    showSensorInfo();
  } else {
    Serial.println("Pilihan tidak dikenal.");
  }

  delay(500);
}
