<?php

require 'function.php'; // Pastikan koneksi database sudah ada di sini

// Cek Login, terdaftar atau tidak
if (isset($_POST['login'])) {
    $iduser = $_POST['iduser'];
    $password = $_POST['password'];

    // Gunakan prepared statements untuk keamanan
    $stmt = $conn->prepare("SELECT * FROM user WHERE iduser = ? AND password = ?");
    $stmt->bind_param("ss", $iduser, $password); // "ss" menunjukkan dua string
    $stmt->execute();
    $result = $stmt->get_result();

    // Cek jika ada hasil
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc(); // Ambil data pengguna

        $_SESSION['log'] = 'True';
        $_SESSION['empid'] = $user['iduser']; // Pastikan ini ada di database
        $_SESSION['nm'] = $user['nama']; // Ambil nama dari database
        $_SESSION['rl'] = $user['role']; // Ambil role dari database
        $_SESSION['pa'] = $user['password']; // Ambil password dari database
        
        header('Location: dashboard.php');
        exit();
    } else {
        header('Location: login.php');
        exit();
    }
}

// Cek jika sudah login, redirect ke index
if (isset($_SESSION['log'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Login</title>
    <link href="css/styles.css" rel="stylesheet" />
    <style>
        body {
            background-color: #d3d3d3;
        }
        .card-header {
            background-color: #FAFAFA; /* Mengubah warna latar belakang header */
            color: #212529; /* Mengubah warna teks menjadi putih */
        }
    </style>
    <script src="jscss/all.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header"><h3 class="text-center font-weight-light my-4">Login</h3></div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group">
                                            <label class="small mb-1" for="inputiduser">ID User</label>
                                            <input class="form-control py-4" name="iduser" id="inputiduser" type="text" placeholder="Enter id user" required />
                                        </div>
                                        <div class="form-group">
                                            <label class="small mb-1" for="inputPassword">Password</label>
                                            <input class="form-control py-4" name="password" id="inputPassword" type="password" placeholder="Enter password" required />
                                        </div>
                                        <div class="form-group d-flex align-items-center justify-content-between mt-4 mb-0">
                                            <button class="btn btn-primary" name="login">Login</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="jscss/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
    <script src="jscss/bootstrap4.5.3.1bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js" crossorigin="anonymous"></script>
</body>
</html>