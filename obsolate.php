<?php
require 'function.php';  // Ensure this file includes database connection logic
require 'cek.php';       // Check user session or permissions

// Periksa koneksi
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Ambil data barang dari tabel stock yang belum dicatat di barang_keluar dan belum obsolate
$barangMasuk = mysqli_query($conn, "
    SELECT s.*
    FROM stock s
    LEFT JOIN barang_keluar b ON s.assetno = b.assetno
    LEFT JOIN barang_rusak r ON s.assetno = r.assetno
    LEFT JOIN barang_obsolate a ON s.assetno = a.assetno
    WHERE b.assetno IS NULL AND r.assetno IS NULL AND a.assetno IS NULL
");

// Proses pencatatan barang obsolate
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addBarangObsolate'])) {
    $selectedAsset = $_POST['assetno'] ?? null;
    $obsolateby = $_SESSION['empid'] ?? null;
    $obsolatedate = $_POST['obsolatedate'] ?? null;

    if (!empty($selectedAsset) && !empty($obsolatedate)) {
        // Insert into approval_requests instead of barang_obsolate
        $insertApprovalQuery = "INSERT INTO approval_requests (assetno, obsolatedate, obsolateby) VALUES ('$selectedAsset', '$obsolatedate', '$obsolateby')";
        if (!mysqli_query($conn, $insertApprovalQuery)) {
            echo "Error inserting into approval_requests: " . mysqli_error($conn);
        }

        // Set notification
        $_SESSION['notification'] = 'Data sedang menunggu persetujuan dan masih dalam status pending.';
        header('Location: obsolate.php'); // Redirect to approval requests page
        exit();
    } else {
        echo "Semua field harus diisi.";
    }
}

$userId = $_SESSION['empid'] ?? 'Guest'; // Default to 'Guest' if not set
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Barang Obsolate</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="jscss/dataTables1.10.20.bootstrap4.min.css" rel="stylesheet" crossorigin="anonymous" />
        <script src="jscss/all.min.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">

    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand" href="index.php">System Inventory</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0" id="sidebarToggle" href="#"><i class="fas fa-bars"></i></button>
        <ul class="navbar-nav ml-auto ml-md-0">
            <li class="nav-item">
                <span class="nav-link text-white">ID User: <?php echo ($userId); ?></span>
            </li>
            <li class="nav-item">
             <?php if ($_SESSION['rl'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="approv.php">Approval Requests</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <a class="nav-link" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Barang Masuk
                        </a>
                        <a class="nav-link" href="rusak.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Barang Rusak
                        </a>
                        <a class="nav-link" href="keluar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Barang Keluar
                        </a>
                        <a class="nav-link" href="obsolate.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Data Obsolate
                        </a>
                        <a class="nav-link" href="report.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Report
                            </a>
                        <div class="container">
                            <?php if (isset($_SESSION['empid'])): ?>
                                <a href="profile.php">Profile</a> | <a href="logout.php">Logout</a>
                            <?php else: ?>
                                <a href="login.php">Login</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </nav>
        </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid">
                    <h1 class="mt-4">Barang Obsolate</h1>

                    <?php if (isset($_SESSION['notification'])): ?>
                        <div class="alert alert-info">
                            <?php 
                                echo ($_SESSION['notification']);
                                unset($_SESSION['notification']); // Clear the notification after displaying
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form untuk mencatat barang obsolate -->
                    <form method="post">
                        <div class="form-group">
                            <label for="assetno">Pilih Barang:</label>
                            <select name="assetno" class="form-control" required>
                                <option value="">Select Asset No</option>
                                <?php while ($row = mysqli_fetch_assoc($barangMasuk)): ?>
                                    <option value="<?php echo ($row['assetno']); ?>">
                                        <?php echo ($row['assetno']); ?> - <?php echo ($row['assettype']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="obsolatedate">Tanggal Digunakan:</label>
                            <input type="date" name="obsolatedate" class="form-control" required>
                        </div>
                        <input type="hidden" name="obsolateby" value="<?php echo ($_SESSION['empid']); ?>" class="form-control" required>
                        <button type="submit" name="addBarangObsolate" class="btn btn-primary">Simpan</button>
                    </form>

                    <!-- Tampilkan daftar barang obsolate -->
                    <div class="card mb-4 mt-4">
                        <div class="card-body">
                            <h5>Daftar Barang Obsolate</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Asset No</th>
                                        <th>Obsolate By</th>
                                        <th>Obsolate Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Ambil data barang Obsolate berdasarkan ID pengguna
                                    $obsolateData = mysqli_query($conn, "SELECT * FROM barang_obsolate");
                                    if ($obsolateData) {
                                        while ($data = mysqli_fetch_array($obsolateData)): ?>
                                            <tr>
                                                <td><?php echo ($data['assetno']); ?></td>
                                                <td><?php echo ($data['obsolatedate']); ?></td>
                                                <td><?php echo ($data['obsolateby']); ?></td>
                                                <td>
                                                    <?php
                                                    if (isset($data['is_approved'])) {
                                                        echo ($data['is_approved'] == 0 ? 'Approved' : 'Pending');
                                                    } else {
                                                        echo 'Pending';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; 
                                    } else {
                                        echo '<tr><td colspan="4">Tidak ada data barang obsolate.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>