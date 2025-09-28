import pandas as pd
from sklearn.neighbors import KNeighborsClassifier
from datetime import datetime

# Data latih (dummy)
data_latih = [
    [20], [18], [17],  # Disiplin
    [14], [13], [12],  # Kurang Disiplin
    [9], [8], [5]      # Tidak Disiplin
]
label_latih = [
    "Disiplin", "Disiplin", "Disiplin",
    "Kurang Disiplin", "Kurang Disiplin", "Kurang Disiplin",
    "Tidak Disiplin", "Tidak Disiplin", "Tidak Disiplin"
]

# Data uji (dummy absensi siswa)
data_uji = [
    [18],  # Siswa A
    [15],  # Siswa B
    [8],   # Siswa C
    [5],   # Siswa D
    [12]   # Siswa E
]
nama_siswa = ["Siswa A", "Siswa B", "Siswa C", "Siswa D", "Siswa E"]

bulan = datetime.now().month
semester = "Ganjil" if bulan in [7, 8, 9, 10, 11, 12] else "Genap"
tanggal_proses = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

knn = KNeighborsClassifier(n_neighbors=3)
knn.fit(data_latih, label_latih)
hasil_prediksi = knn.predict(data_uji)

df = pd.DataFrame({
    "ID": range(1, len(nama_siswa) + 1),
    "Nama Siswa": nama_siswa,
    "Jumlah Hadir": [d[0] for d in data_uji],
    "Status": hasil_prediksi,
    "Semester": semester,
    "Tanggal Proses": tanggal_proses
})

pd.set_option("display.colheader_justify", "center") 
pd.set_option("display.width", 1000) 
pd.set_option("display.max_columns", None)  

print("\n=== Hasil Klasifikasi KNN Absensi Siswa ===\n")
print(df.to_string(index=False))


