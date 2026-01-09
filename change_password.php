<?php
require 'function.php';

if (!isset($_SESSION['empid'])) {
    header('Location: login.php');
    exit();
}

// Tempat untuk menangani pengubahan password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $udid = $_SESSION['empid'];
    $opass = $_SESSION['pa'];


    // Validasi (contoh sederhana)
    //if ($old_password === $_SESSION['pa'] && $new_password === $confirm_password) {
    if ($opass === $old_password && $new_password === $confirm_password) {
        // Update password di database
        // Pastikan Anda sudah terhubung dengan database sebelum menjalankan query ini
        //$query ="UPDATE user SET password = '$new_password' WHERE password = '$old_password' AND iduser = '$udid' ";

        $stmt = $conn->prepare("UPDATE user SET password = ? WHERE password = ? AND iduser = ?");
        $stmt->bind_param("sss", $new_password, $old_password, $udid); // "ss" menunjukkan dua string
        $stmt->execute();


        // Simulasi: Mengubah password di session (jika menggunakan session)
        $_SESSION['pw'] = $new_password;

        // Beri umpan balik ke pengguna
        echo "<div class='alert alert-success'>Password berhasil diubah.</div>";
    } else {
        echo "<div class='alert alert-danger'>Password lama salah atau password baru tidak cocok.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password</title>
    <link rel="stylesheet" href="jscss/bootstrap4.5.2.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
    <script>
        const timeoutDuration = 300; // 5 menit
        const timeoutInMilliseconds = timeoutDuration * 1000; // Convert to milliseconds

        let timeout; // Variable to hold the timeout

        function resetTimer() {
            clearTimeout(timeout);
            timeout = setTimeout(logout, timeoutInMilliseconds);
        }

        function logout() {
            window.location.href = 'logout.php'; // Redirect to logout page
        }

        // Event listeners for user activity
        window.onload = resetTimer;
        window.onmousemove = resetTimer;
        window.onkeypress = resetTimer;
        window.ontouchstart = resetTimer; // For mobile touch events
    </script>
</head>
<body>
    <div class="container">
        <h1>Ubah Password</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="old_password">Password Lama</label>
                <input type="password" class="form-control" name="old_password" id="old_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">Password Baru</label>
                <input type="password" class="form-control" name="new_password" id="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password Baru</label>
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Ubah Password</button>
            <a href="profile.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>

    <script src="jscss/jquery-3.5.1.slim.min.js"></script>
    <script src="jscss/poppercore@2.9.2.min.js"></script>
    <script src="jscss/stackpath.bootstrap4.5.2.min.js"></script>
</body>
</html>