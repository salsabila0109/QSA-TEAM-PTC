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
FirebaseData fbdo;
FirebaseAuth auth;
FirebaseConfig config;
#define FIREBASE_API_KEY   "AIzaSyDgrAuCUiiX5qod51uQT686b-CS12ub6KQ"
#define FIREBASE_DB_URL    "https://rfid-eb0c0-default-rtdb.asia-southeast1.firebasedatabase.app/"

// ====================== TIME ======================
const long GMT_OFFSET_SEC = 8 * 3600;
const int  DST_OFFSET_SEC = 0;

// ====================== PIN ======================
const uint8_t SS_PIN      = 5;   // MFRC522 SS
const uint8_t RST_PIN     = 25;  // MFRC522 RST
const uint8_t BUZZER_PIN  = 26;  // Buzzer
const uint8_t LCD_SDA_PIN = 21;
const uint8_t LCD_SCL_PIN = 22;
const uint8_t LED_GREEN   = 27;
const uint8_t LED_RED     = 14;

// ====================== RFID ======================
MFRC522 mfrc522(SS_PIN, RST_PIN);
MFRC522::MIFARE_Key key;

// ====================== LCD ======================
LiquidCrystal_I2C lcd(0x27, 16, 2);

// ====================== Fingerprint ======================
HardwareSerial fingerSerial(2);
Adafruit_Fingerprint finger(&fingerSerial);

// ====================== Variabel Global ======================
bool sessionActive = false;
String currentMapel = "";
String currentUID = "";

// ====================== HELPER ======================
void beepRaw(int note, int duration){
  tone(BUZZER_PIN, note, duration);
  delay(duration*12/10);
  noTone(BUZZER_PIN);
}

void beepSuccess() { beepRaw(1000,100); }
void beepError()   { beepRaw(500,500); }
void beepCard()    { beepRaw(800,50); }

void lcdMsg(String l1, String l2=""){
  lcd.clear();
  lcd.setCursor(0,0); lcd.print(l1.substring(0,16));
  lcd.setCursor(0,1); lcd.print(l2.substring(0,16));
}

String uidToFlat(const byte *uid, byte uidSize){
  String s="";
  for(byte i=0;i<uidSize;i++){
    if(uid[i]<0x10) s+="0";
    s+=String(uid[i],HEX);
  }
  s.toUpperCase();
  return s;
}

String currentDateStr(){
  struct tm t;
  if(!getLocalTime(&t)) return "1970-01-01";
  char buf[11]; strftime(buf,sizeof(buf),"%Y-%m-%d",&t);
  return String(buf);
}

String currentTimeStr(){
  struct tm t;
  if(!getLocalTime(&t)) return "00:00:00";
  char buf[9]; strftime(buf,sizeof(buf),"%H:%M:%S",&t);
  return String(buf);
}

uint32_t currentEpoch(){ return (uint32_t)time(nullptr); }

bool pushRFIDToFirebase(String uid, String mapel){
  FirebaseJson json;
  json.set("uid", uid);
  json.set("mapel", mapel);
  json.set("time", currentTimeStr());
  json.set("timestamp", (int)currentEpoch());

  String path = "/attendance/" + currentDateStr() + "/rfid/" + uid;
  return Firebase.RTDB.setJSON(&fbdo,path.c_str(),&json);
}

bool pushFingerprintToFirebase(int fingerID, String siswa){
  FirebaseJson json;
  json.set("finger_id", fingerID);
  json.set("siswa", siswa);
  json.set("mapel", currentMapel);
  json.set("time", currentTimeStr());
  json.set("timestamp",(int)currentEpoch());

  String path = "/attendance/" + currentDateStr() + "/fingerprint/" + String(fingerID);
  return Firebase.RTDB.setJSON(&fbdo,path.c_str(),&json);
}

MFRC522::StatusCode ReadDataFromBlock(int blockNum, byte buffer[]){
  MFRC522::StatusCode status;
  byte size=18;
  status = mfrc522.PCD_Authenticate(MFRC522::PICC_CMD_MF_AUTH_KEY_A,blockNum,&key,&(mfrc522.uid));
  if(status!=MFRC522::STATUS_OK) return status;
  status = mfrc522.MIFARE_Read(blockNum,buffer,&size);
  return status;
}

void connectWiFi(){
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID,WIFI_PASS);
  lcdMsg("WiFi connect...");
  int tries=0;
  while(WiFi.status()!=WL_CONNECTED && tries<120){delay(250); tries++;}
  if(WiFi.status()==WL_CONNECTED) lcdMsg("WiFi OK",WiFi.localIP().toString());
  else lcdMsg("WiFi FAIL","");
  delay(600);
}

void setupTime(){
  lcdMsg("Sinkron NTP...");
  configTime(GMT_OFFSET_SEC,DST_OFFSET_SEC,"pool.ntp.org","time.nist.gov");
  int tries=0; struct tm t;
  while(!getLocalTime(&t) && tries<60){delay(250);tries++;}
}

void setupFirebase(){
  config.api_key = FIREBASE_API_KEY;
  config.database_url = FIREBASE_DB_URL;
  Firebase.signUp(&config,&auth,"","");
  config.token_status_callback = tokenStatusCallback;
  Firebase.begin(&config,&auth);
  Firebase.reconnectNetwork(true);
}

// ====================== SETUP ======================
void setup(){
  Serial.begin(115200);

  // Pin
  pinMode(BUZZER_PIN,OUTPUT);
  pinMode(LED_GREEN,OUTPUT);
  pinMode(LED_RED,OUTPUT);

  // LCD
  Wire.begin(LCD_SDA_PIN,LCD_SCL_PIN);
  lcd.init();
  lcd.backlight();
  lcdMsg("Sistem ABSENSI","Init...");

  // RFID
  SPI.begin();
  mfrc522.PCD_Init();
  for(byte i=0;i<6;i++) key.keyByte[i]=0xFF;

  // Fingerprint
  fingerSerial.begin(57600,SERIAL_8N1,16,17);
  finger.begin(57600);
  if(finger.verifyPassword()) Serial.println("Fingerprint OK");
  else { Serial.println("Fingerprint ERROR"); while(1) delay(1); }

  // WiFi & Firebase
  connectWiFi();
  setupTime();
  setupFirebase();

  delay(500);
  lcdMsg("Tempel Kartu","Ready");
}

// ====================== LOOP ======================
void loop(){
  // ====================== RFID GURU ======================
  if(mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()){
    beepCard();
    lcdMsg("Membaca Kartu...","");
    String uidFlat = uidToFlat(mfrc522.uid.uidByte,mfrc522.uid.size);

    byte buffer[18]; bool dataValid=false; char mapelStr[17];
    MFRC522::StatusCode status = ReadDataFromBlock(4,buffer);
    if(status==MFRC522::STATUS_OK){
      for(int i=0;i<16;i++) mapelStr[i]=buffer[i];
      mapelStr[16]='\0';
      for(int i=0;i<16;i++) if(buffer[i]!=0x00 && buffer[i]!=' ') {dataValid=true; break;}
    }

    if(status==MFRC522::STATUS_OK && dataValid){
      currentUID=uidFlat;
      currentMapel=String(mapelStr); currentMapel.trim();
      sessionActive=true;

      digitalWrite(LED_GREEN,HIGH); digitalWrite(LED_RED,LOW);
      beepSuccess();
      lcdMsg("Selamat Datang! - Mapel:",currentMapel);

      if(pushRFIDToFirebase(currentUID,currentMapel)){
        Serial.println("RFID Terkirim Firebase");
      } else { Serial.println(fbdo.errorReason()); }

    } else {
      digitalWrite(LED_GREEN,LOW); digitalWrite(LED_RED,HIGH);
      beepError();
      lcdMsg("Kartu Tidak","Terdaftar!");
      sessionActive=false;
    }

    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
    delay(2000);
    lcdMsg("Hai! Silakan Verifikasi","Sidik Jari Anda");
  }

  // ====================== FINGERPRINT SISWA ======================
  if(sessionActive){
    int fingerID=-1;
    uint8_t p=finger.getImage();
    if(p==FINGERPRINT_OK){
      p=finger.image2Tz();
      if(p==FINGERPRINT_OK){
        p=finger.fingerFastSearch();
        if(p==FINGERPRINT_OK){
          fingerID=finger.fingerID;
          digitalWrite(LED_GREEN,HIGH); digitalWrite(LED_RED,LOW);
          beepSuccess();
          lcdMsg("Yes! Terverifikasi", "ID: " + String(fingerID));

          // Kirim ke Firebase
          if(pushFingerprintToFirebase(fingerID,"Siswa_"+String(fingerID))){
            Serial.println("FP Terkirim Firebase");
          } else { Serial.println(fbdo.errorReason()); }

        } else { digitalWrite(LED_GREEN,LOW); digitalWrite(LED_RED,HIGH); beepError(); }
      }
    }
  }

  delay(100);
}
