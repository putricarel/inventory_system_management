<?php
session_start();

if (!isset($_SESSION['empid'])) 


{
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System Management</title>
    <link rel="stylesheet" href="jscss/stackpath.css">
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
        h1 {
            color: #343a40;
        }
        a {
            margin-top: 20px;
            display: inline-block;
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
        <h1>Profil Pengguna</h1>
        <p><strong>Id User:</strong> <?= $_SESSION['empid']; ?></p>
        <p><strong>Nama:</strong> <?= $_SESSION['nm']; ?></p>
        <p><strong>Role:</strong> <?= $_SESSION['rl']; ?></p>
        <a href="change_password.php" class="btn btn-success">Change Password</a>
        <a href="index.php" class="btn btn-secondary">Beranda</a>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <script src="jscss/jquery-3.5.11.slim.min.js"></script>
    <script src="jscss/popper2.9.2.min.js"></script>
    <script src="jscss/bootstrap4.5.2.2min.js"></script>
</body>
</html>