import math
from collections import Counter

# Fungsi hitung jarak Euclidean
def euclidean_distance(x1, x2):
    return math.sqrt((x1 - x2) ** 2)

# Fungsi KNN manual
def knn_classify(data, labels, sample, k=3):
    distances = []
    for i in range(len(data)):
        dist = euclidean_distance(sample, data[i])
        distances.append((dist, labels[i]))
    # Urutkan berdasarkan jarak terdekat
    distances.sort(key=lambda x: x[0])
    # Ambil k tetangga terdekat
    neighbors = [label for _, label in distances[:k]]
    # Voting mayoritas
    most_common = Counter(neighbors).most_common(1)
    return most_common[0][0]

# Data latih (jumlah hadir dan label disiplin)
X_train = [18, 17, 16, 15, 14, 13, 12, 11, 10, 9, 8, 7, 6, 5]
y_train = [
    "Disiplin", "Disiplin", "Disiplin",
    "Kurang Disiplin", "Kurang Disiplin", "Kurang Disiplin", "Kurang Disiplin", "Kurang Disiplin",
    "Tidak Disiplin", "Tidak Disiplin", "Tidak Disiplin", "Tidak Disiplin", "Tidak Disiplin", "Tidak Disiplin"
]

# Data uji beberapa siswa
data_siswa = {
    "Siswa A": 18,
    "Siswa B": 15,
    "Siswa C": 8,
    "Siswa D": 5,
    "Siswa E": 12
}

# Cetak hasil prediksi
print("\n=== Hasil Klasifikasi KNN Absensi Siswa ===\n")
for nama, hadir in data_siswa.items():
    hasil = knn_classify(X_train, y_train, hadir, k=3)
    print(f"{nama} | Hadir: {hadir} | Status: {hasil}")
