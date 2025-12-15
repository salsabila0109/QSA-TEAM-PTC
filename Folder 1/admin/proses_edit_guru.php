<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_guru = $_POST['id_guru'];
    $nip = $_POST['nip'];
    $nama_guru = $_POST['nama_guru'];
    $no_telepon = $_POST['no_telepon'];

    // 🔹 Tanggal & jam update otomatis sesuai waktu server
    date_default_timezone_set('Asia/Makassar');
    $tanggal_diperbarui = date('Y-m-d H:i:s');

    $sql = $conn->prepare("UPDATE guru SET nip=?, nama_guru=?, no_telepon=?, tanggal_diperbarui=? WHERE id_guru=?");
    $sql->bind_param("ssssi", $nip, $nama_guru, $no_telepon, $tanggal_diperbarui, $id_guru);

    if($sql->execute()){
        echo json_encode([
            'success' => true,
            'tanggal_diperbarui' => date('d-m-Y H:i:s', strtotime($tanggal_diperbarui))
        ]);
    } else {
        echo json_encode(['success'=>false, 'error'=>$conn->error]);
    }

    $sql->close();
}
?>
