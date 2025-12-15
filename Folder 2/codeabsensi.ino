#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <WiFi.h>
#include <Firebase_ESP_Client.h>
#include "addons/TokenHelper.h"
#include "addons/RTDBHelper.h"
#include <time.h>
#include <Adafruit_Fingerprint.h>
#include <HardwareSerial.h>

// ====================== WIFI ======================
const char* WIFI_SSID = "aa";
const char* WIFI_PASS = "abcdefgh";

// ====================== FIREBASE ======================
// Proyek: presentech-4c4c0
FirebaseData fbdo;
FirebaseAuth auth;
FirebaseConfig config;

#define FIREBASE_API_KEY   "AIzaSyCuayRKFrLjWaVcrWBeWZjmA4V_CWPtPcU"
#define FIREBASE_DB_URL    "https://presentech-4c4c0-default-rtdb.firebaseio.com/"

// ====================== TIME (WITA) ======================
const long GMT_OFFSET_SEC = 8 * 3600;  // UTC+8
const int  DST_OFFSET_SEC = 0;

// ====================== PIN ESP32 ======================
const uint8_t SS_PIN      = 5;   // RFID SS/SDA
const uint8_t RST_PIN     = 25;  // RFID RST
const uint8_t BUZZER_PIN  = 26;
const uint8_t LCD_SDA_PIN = 21;
const uint8_t LCD_SCL_PIN = 22;
const uint8_t LED_GREEN   = 27;
const uint8_t LED_RED     = 14;

// ====================== UI TIMING (biar tidak lama) ======================
const uint16_t DELAY_OK_MS        = 1200;
const uint16_t DELAY_ERR_MS       = 1400;
const uint16_t DELAY_AFTER_CARD   = 700;
const uint16_t SCROLL_DELAY_MS    = 160;

// ====================== RFID ======================
MFRC522 mfrc522(SS_PIN, RST_PIN);
MFRC522::MIFARE_Key key;

// ====================== LCD ======================
LiquidCrystal_I2C lcd(0x27, 16, 2);

// ====================== Fingerprint ======================
HardwareSerial fingerSerial(2);             // UART2: RX2=16, TX2=17
Adafruit_Fingerprint finger(&fingerSerial);

// ====================== Variabel Global ======================
bool   sessionActive = false;   // true setelah guru tap kartu mapel
String currentMapel  = "";
String currentUID    = "";

// ====================== HELPER BUNYI & LCD ======================
void beepRaw(int note, int duration) {
  tone(BUZZER_PIN, note, duration);
  delay(duration * 12 / 10);
  noTone(BUZZER_PIN);
}
void beepSuccess() { beepRaw(1000, 80); }
void beepError()   { beepRaw(500,  250); }
void beepCard()    { beepRaw(800,  50); }

void scrollLine(int row, String text, int delayTime) {
  int textLen = text.length();
  if (textLen <= 16) {
    lcd.setCursor(0, row);
    lcd.print("                ");
    lcd.setCursor(0, row);
    lcd.print(text);
    return;
  }
  for (int i = 0; i <= textLen - 16; i++) {
    lcd.setCursor(0, row);
    lcd.print("                ");
    lcd.setCursor(0, row);
    lcd.print(text.substring(i, i + 16));
    delay(delayTime);
  }
}

void lcdMsg(String l1, String l2 = "") {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("                ");
  lcd.setCursor(0, 0);
  lcd.print(l1.substring(0, min(16, (int)l1.length())));

  lcd.setCursor(0, 1);
  lcd.print("                ");
  lcd.setCursor(0, 1);
  lcd.print(l2.substring(0, min(16, (int)l2.length())));
}

// ========= Tampilkan pesan "selamat datang {nama lengkap}" =========
void showWelcomeMessage(const String &namaSiswa) {
  lcd.clear();

  String nama = namaSiswa;
  nama.trim();

  // kalau nama kosong atau placeholder "siswa" => fallback
  String low = nama; low.toLowerCase();
  if (nama.length() == 0 || low == "siswa") nama = "Siswa";

  String msg = "selamat datang " + nama;
  scrollLine(0, msg, SCROLL_DELAY_MS);

  lcd.setCursor(0, 1);
  lcd.print("                ");
  lcd.setCursor(0, 1);
  if (currentMapel.length() > 0) {
    lcd.print("Mapel: ");
    String m = currentMapel;
    m.trim();
    lcd.print(m.substring(0, min(10, (int)m.length())));
  }
}

// ====================== WAKTU & UID ======================
String uidToFlat(const byte *uid, byte uidSize) {
  String s = "";
  for (byte i = 0; i < uidSize; i++) {
    if (uid[i] < 0x10) s += "0";
    s += String(uid[i], HEX);
  }
  s.toUpperCase(); // modifies in place
  return s;
}

String currentDateStr() {
  struct tm t;
  if (!getLocalTime(&t)) return "0000-00-00";
  char buf[11];
  strftime(buf, sizeof(buf), "%Y-%m-%d", &t);
  return String(buf);
}

String currentTimeStr() {
  struct tm t;
  if (!getLocalTime(&t)) return "00:00:00";
  char buf[9];
  strftime(buf, sizeof(buf), "%H:%M:%S", &t);
  return String(buf);
}

uint32_t currentEpoch() { return (uint32_t)time(nullptr); }

// ====================== PUSH KE FIREBASE ======================
bool pushRFIDToFirebase(String uid, String mapel) {
  FirebaseJson json;
  json.set("uid", uid);
  json.set("mapel", mapel);
  json.set("time", currentTimeStr());
  json.set("timestamp", (int)currentEpoch());

  String uniqueKey = String(currentEpoch()) + "_" + uid;
  String path = "/attendance/" + currentDateStr() + "/rfid/" + uniqueKey;

  bool ok = Firebase.RTDB.setJSON(&fbdo, path.c_str(), &json);
  Serial.print("Push RFID ke "); Serial.print(path);
  Serial.println(ok ? " [OK]" : " [GAGAL]");
  if (!ok) Serial.println(fbdo.errorReason());
  return ok;
}

// ✅ simpan juga id_siswa biar web tidak bingung
bool pushFingerprintToFirebase(int fingerID, const String &idSiswa, const String &siswa) {
  FirebaseJson json;
  json.set("finger_id", fingerID);
  if (idSiswa.length() > 0) json.set("id_siswa", idSiswa);
  json.set("siswa", siswa);
  json.set("mapel", currentMapel);
  json.set("time", currentTimeStr());
  json.set("timestamp", (int)currentEpoch());

  String uniqueKey = String(currentEpoch()) + "_" + String(fingerID) + "_" + String(millis() % 1000);
  String path = "/attendance/" + currentDateStr() + "/fingerprint/" + uniqueKey;

  bool ok = Firebase.RTDB.setJSON(&fbdo, path.c_str(), &json);
  Serial.print("Push Finger ke "); Serial.print(path);
  Serial.println(ok ? " [OK]" : " [GAGAL]");
  if (!ok) Serial.println(fbdo.errorReason());
  return ok;
}

// ====================== RESOLVE (INDEX RINGAN) ======================
// /students_by_finger/<fingerID> -> { id_siswa:"202543", nama:"Izza Irena", ... }
bool resolveStudentFromIndex(int fingerID, String &outIdSiswa, String &outNama) {
  outIdSiswa = "";
  outNama    = "";

  String path = "/students_by_finger/" + String(fingerID);
  if (!Firebase.RTDB.getJSON(&fbdo, path.c_str())) {
    Serial.print("Index GET fail: ");
    Serial.println(fbdo.errorReason());
    return false;
  }

  FirebaseJson &j = fbdo.jsonObject();
  FirebaseJsonData d;

  if (j.get(d, "id_siswa")) { outIdSiswa = d.stringValue; outIdSiswa.trim(); }
  if (j.get(d, "nama"))     { outNama   = d.stringValue; outNama.trim(); }

  return (outIdSiswa.length() > 0 || outNama.length() > 0);
}

// ====================== HELPER JSON (lebih toleran field) ======================
bool jsonGetAnyString(FirebaseJson &j, const char* const keys[], int n, String &out) {
  FirebaseJsonData d;
  for (int i = 0; i < n; i++) {
    if (j.get(d, keys[i])) {
      String s = d.stringValue;
      s.trim();
      if (s.length() > 0) { out = s; return true; }
      if (d.intValue != 0) { out = String(d.intValue); return true; }
    }
  }
  return false;
}

bool jsonGetAnyInt(FirebaseJson &j, const char* const keys[], int n, int &out) {
  FirebaseJsonData d;
  for (int i = 0; i < n; i++) {
    if (j.get(d, keys[i])) {
      if (d.stringValue.length() > 0) {
        out = d.stringValue.toInt();
        return true;
      }
      out = d.intValue;
      return true;
    }
  }
  return false;
}

bool parseJsonValueToChild(String v, FirebaseJson &child) {
  v.trim();
  if (v.startsWith("\"") && v.endsWith("\"") && v.length() >= 2) {
    v = v.substring(1, v.length() - 1);
    v.replace("\\\"", "\"");
    v.replace("\\\\", "\\");
    v.trim();
  }
  if (!v.startsWith("{")) return false;
  child.setJsonData(v);
  return true;
}

// ====================== Ambil siswa dari Firebase (ID + Nama) ======================
// (ini fungsi lama kamu — TETAP ADA sebagai fallback)
bool resolveStudentFromFirebase(int fingerID, String &outIdSiswa, String &outNama) {
  outIdSiswa = "";
  outNama = "";

  Serial.print("Resolving fingerID = "); Serial.println(fingerID);

  const char* const NAME_KEYS[] = {"nama", "nama_siswa", "name", "siswa"};
  const char* const ID_KEYS[]   = {"id_siswa", "idSiswa", "nis", "student_id", "studentId"};
  const char* const FID_KEYS[]  = {
    "finger_id", "fingerID", "fingerId", "slot",
    "finger", "fid", "id_finger", "fingerprint_id", "fingerprintId"
  };

  {
    String directPath = "/students/" + String(fingerID);
    if (Firebase.RTDB.getJSON(&fbdo, directPath.c_str())) {
      FirebaseJson &dj = fbdo.jsonObject();
      String namaDirect = "";
      String idDirect = "";
      int fidDirect = -999;

      bool gotNamaD = jsonGetAnyString(dj, NAME_KEYS, 4, namaDirect);
      bool gotIdD   = jsonGetAnyString(dj, ID_KEYS,   5, idDirect);
      bool gotFidD  = jsonGetAnyInt(dj,  FID_KEYS,    9, fidDirect);

      if (gotNamaD) {
        outNama = namaDirect;
        outIdSiswa = gotIdD ? idDirect : String(fingerID);
        Serial.print("Direct hit "); Serial.print(directPath);
        Serial.print(" | id="); Serial.print(outIdSiswa);
        Serial.print(" | nama="); Serial.println(outNama);
        return true;
      }
    }
  }

  if (!Firebase.RTDB.getJSON(&fbdo, "/students")) {
    Serial.print("Gagal get /students: ");
    Serial.println(fbdo.errorReason());
    return false;
  }

  FirebaseJson &root = fbdo.jsonObject();

  String namaRoot = "";
  String idRoot   = "";
  int fidRoot = -999;

  bool gotNama = jsonGetAnyString(root, NAME_KEYS, 4, namaRoot);
  bool gotId   = jsonGetAnyString(root, ID_KEYS,   5, idRoot);
  bool gotFid  = jsonGetAnyInt(root,  FID_KEYS,    9, fidRoot);

  if (gotNama && gotFid) {
    Serial.print("Root students single | fid="); Serial.print(fidRoot);
    Serial.print(" | nama="); Serial.println(namaRoot);

    if (fidRoot == fingerID) {
      outNama = namaRoot;
      outIdSiswa = (gotId && idRoot.length() > 0) ? idRoot : String(fingerID);
      return true;
    }
  }

  size_t count = root.iteratorBegin();
  if (count == 0) {
    Serial.println("Tidak ada data di /students (iterator kosong).");
    root.iteratorEnd();
    return false;
  }

  bool found = false;
  for (size_t i = 0; i < count; i++) {
    int type;
    String keyStr, value;
    root.iteratorGet(i, type, keyStr, value);

    FirebaseJson child;
    bool parsed = parseJsonValueToChild(value, child);

    if (!parsed) continue;

    String nama = "";
    String idFromField = "";
    int fid = -999;

    bool okNama = jsonGetAnyString(child, NAME_KEYS, 4, nama);
    bool okId   = jsonGetAnyString(child, ID_KEYS,   5, idFromField);
    bool okFid  = jsonGetAnyInt(child,  FID_KEYS,    9, fid);

    Serial.print("Cek student id="); Serial.print(keyStr);
    Serial.print(" | fid="); Serial.print(okFid ? String(fid) : String("-"));
    Serial.print(" | nama="); Serial.println(okNama ? nama : "(kosong)");

    if (okFid && fid == fingerID) {
      outIdSiswa = (keyStr.length() > 0) ? keyStr : (okId ? idFromField : String(fingerID));
      outNama = okNama ? nama : "";
      found = true;
      break;
    }
  }

  root.iteratorEnd();
  return found;
}

// ====================== RFID BLOCK READ ======================
MFRC522::StatusCode ReadDataFromBlock(int blockNum, byte buffer[]) {
  MFRC522::StatusCode status;
  byte size = 18;

  status = mfrc522.PCD_Authenticate(MFRC522::PICC_CMD_MF_AUTH_KEY_A,
                                    blockNum, &key, &(mfrc522.uid));
  if (status != MFRC522::STATUS_OK) return status;

  status = mfrc522.MIFARE_Read(blockNum, buffer, &size);
  return status;
}

// ====================== SETUP FUNGSI ======================
void connectWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  lcdMsg("WiFi connect...", "");
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 120) {
    delay(250);
    tries++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    lcdMsg("WiFi OK", WiFi.localIP().toString());
    Serial.print("WiFi connected, IP: ");
    Serial.println(WiFi.localIP());
  } else {
    lcdMsg("WiFi FAIL", "");
    Serial.println("WiFi gagal tersambung");
  }
  delay(400);
}

void setupTime() {
  lcdMsg("Sinkron NTP...", "");
  configTime(GMT_OFFSET_SEC, DST_OFFSET_SEC, "pool.ntp.org", "time.nist.gov");
  int tries = 0;
  struct tm t;
  while (!getLocalTime(&t) && tries < 60) {
    delay(250);
    tries++;
  }
  Serial.println(getLocalTime(&t) ? "Waktu NTP tersinkron." : "Gagal sinkron NTP.");
}

void setupFirebase() {
  config.api_key      = FIREBASE_API_KEY;
  config.database_url = FIREBASE_DB_URL;

  // ---- tambahan kecil untuk stabilitas SSL/RTDB ----
  fbdo.setBSSLBufferSize(4096, 1024);
  fbdo.setResponseSize(2048);

  if (!Firebase.signUp(&config, &auth, "", "")) {
    Serial.print("Firebase signUp error: ");
    Serial.println(fbdo.errorReason());
  } else {
    Serial.println("Firebase signUp OK.");
  }

  config.token_status_callback = tokenStatusCallback;
  Firebase.begin(&config, &auth);
  Firebase.reconnectNetwork(true);
}

// ====================== SETUP ======================
void setup() {
  Serial.begin(115200);

  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(LED_GREEN, OUTPUT);
  pinMode(LED_RED, OUTPUT);

  Wire.begin(LCD_SDA_PIN, LCD_SCL_PIN);
  lcd.init();
  lcd.backlight();
  lcdMsg("Sistem ABSENSI", "Init...");

  SPI.begin();
  mfrc522.PCD_Init();
  for (byte i = 0; i < 6; i++) key.keyByte[i] = 0xFF;

  fingerSerial.begin(57600, SERIAL_8N1, 16, 17);
  finger.begin(57600);
  if (finger.verifyPassword()) {
    Serial.println("Fingerprint OK");
  } else {
    Serial.println("Fingerprint ERROR");
    while (1) delay(1);
  }

  connectWiFi();
  setupTime();
  setupFirebase();

  delay(300);
  lcdMsg("Tempel Kartu", "Ready");
}

// ====================== LOOP ======================
void loop() {
  // ---------- RFID Guru ----------
  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    beepCard();
    lcdMsg("Membaca Kartu...", "");

    String uidFlat = uidToFlat(mfrc522.uid.uidByte, mfrc522.uid.size);

    byte buffer[18];
    bool dataValid = false;
    char mapelStr[17];
    MFRC522::StatusCode status = ReadDataFromBlock(4, buffer);

    if (status == MFRC522::STATUS_OK) {
      for (int i = 0; i < 16; i++) mapelStr[i] = buffer[i];
      mapelStr[16] = '\0';

      for (int i = 0; i < 16; i++) {
        if (buffer[i] != 0x00 && buffer[i] != ' ') {
          dataValid = true;
          break;
        }
      }
    }

    if (status == MFRC522::STATUS_OK && dataValid) {
      currentUID   = uidFlat;
      currentMapel = String(mapelStr);
      currentMapel.trim();
      sessionActive = true;

      digitalWrite(LED_GREEN, HIGH);
      digitalWrite(LED_RED,   LOW);
      beepSuccess();

      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Selamat Datang!");
      scrollLine(1, "Mapel: " + currentMapel, SCROLL_DELAY_MS);

      pushRFIDToFirebase(currentUID, currentMapel);

      delay(DELAY_OK_MS);
      digitalWrite(LED_GREEN, LOW);
      digitalWrite(LED_RED,   LOW);

    } else {
      digitalWrite(LED_GREEN, LOW);
      digitalWrite(LED_RED,   HIGH);
      beepError();
      lcdMsg("Kartu Tidak", "Terdaftar!");
      sessionActive = false;

      delay(DELAY_ERR_MS);
      digitalWrite(LED_RED, LOW);
    }

    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();

    delay(DELAY_AFTER_CARD);
    lcdMsg("Silahkan Tempel", "Sidik Jari");
  }

  // ---------- Fingerprint Siswa ----------
  if (sessionActive) {
    uint8_t p = finger.getImage();

    if (p == FINGERPRINT_OK) {
      p = finger.image2Tz();
      if (p == FINGERPRINT_OK) {
        p = finger.fingerFastSearch();
        if (p == FINGERPRINT_OK) {
          int fingerID = finger.fingerID;
          Serial.print("Finger terdeteksi, ID: ");
          Serial.println(fingerID);

          String idSiswa, studentName;

          // 1) Coba index ringan dulu (paling stabil untuk LCD)
          bool found = resolveStudentFromIndex(fingerID, idSiswa, studentName);

          // 2) Kalau index belum ada / gagal, fallback ke resolver lama kamu
          if (!found || studentName.length() == 0) {
            found = resolveStudentFromFirebase(fingerID, idSiswa, studentName);
          }

          // retry cepat 1x (kadang jaringan)
          if (!found || studentName.length() == 0) {
            delay(150);
            bool found2 = resolveStudentFromIndex(fingerID, idSiswa, studentName);
            if (!found2) found2 = resolveStudentFromFirebase(fingerID, idSiswa, studentName);
            found = found2;
          }

          studentName.trim();
          String low = studentName; low.toLowerCase();
          if (studentName.length() == 0 || low == "siswa") {
            if (idSiswa.length() > 0) studentName = String("ID: ") + idSiswa;
            else studentName = "Siswa";
          }

          digitalWrite(LED_GREEN, HIGH);
          digitalWrite(LED_RED,   LOW);
          beepSuccess();

          showWelcomeMessage(studentName);

          pushFingerprintToFirebase(fingerID, idSiswa, studentName);

          delay(DELAY_OK_MS);
          digitalWrite(LED_GREEN, LOW);
          digitalWrite(LED_RED,   LOW);

          lcdMsg("Silahkan Tempel", "Sidik Jari");

        } else {
          digitalWrite(LED_GREEN, LOW);
          digitalWrite(LED_RED,   HIGH);
          beepError();

          lcdMsg("Sidik Jari", "Tidak Terdaftar!");
          delay(DELAY_ERR_MS);

          digitalWrite(LED_RED, LOW);
          lcdMsg("Silahkan Tempel", "Sidik Jari");
        }
      }
    } else if (p != FINGERPRINT_NOFINGER) {
      Serial.print("Error fingerprint: ");
      Serial.println(p);
    }
  }

  delay(30);
}
