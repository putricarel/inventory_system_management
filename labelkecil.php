<?php
require 'function.php';
require 'cek.php';

// Assuming you have the user ID stored in a session variable
$userId = $_SESSION['empid'] ?? 'Guest'; // Default to 'Guest' if not set

$assetNo = $_GET['id'] ?? null; // Ambil asset type dari URL jika ada
if ($assetNo) {
    // Query untuk mengambil data detail berdasarkan asset type
    $query = "SELECT * FROM masuk WHERE assetno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $assetNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Jika tidak ada data ditemukan
    if ($result->num_rows === 0) {
        die("Data tidak ditemukan.");
    }
} else {
    die("ID tidak valid.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Inventory System Management</title>
    <link href="css/styles.css" rel="stylesheet" />
 <link href="jscss/bootstrap4.5.2.0min.css" rel="stylesheet" />
    <link href="jscss/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <link href="jscss/buttons.dataTables.min.css" rel="stylesheet" />
    <script src="jscss/jquery.min.js"></script>
    <script src="jscss/popper1.16.0.min.js"></script>
    <script src="jscss/maxcdn.bootstrap4.5.2.min.js"></script>
    <script src="jscss/jquery1.10.19.dataTables.js"></script>
    <script src="jscss/dataTables1.6.5.buttons.min.js"></script>
    <script src="jscss/jszip3.1.3.min.js"></script>
    <script src="jscss/pdfmake0.1.53.min.js"></script>
    <script src="jscss/vfs_fonts0.1.53.js"></script>
    <script src="jscss/buttons1.6.5.html5.min.js"></script>
    <script src="jscss/buttons1.6.5.print.min.js"></script>
    <script src="jscss/all5.15.1.min.js"></script>
    <script src="jscss/JsBarcode3.11.0.all.min.js"></script>
    <style>
        .logo {
            height: 40px; /* Adjust the height as needed */
            margin-left: auto;
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
    <style>
        @media print {
            .print-area {
                width: 3.5cm; /* Set width for 4-inch print */
                height: 3cm; /* Set height for 6-inch print */
                margin: 0 auto; /* Center the print area */
                border: 1px solid black; /* Optional: border for visual clarity */
                padding: 5px; /* Add padding around print area */
            }
            body {
                visibility: hidden; /* Hide the entire body */
            }
            .print-area {
                visibility: visible; /* Show only the print area */
                position: absolute; /* Position it for printing */
                top: 0; /* Align to top */
                left: 0; /* Align to left */
            }
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand" href="index.php">System Inventory</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0" id="sidebarToggle" href="#"><i class="fas fa-bars"></i></button>
        <ul class="navbar-nav ml-auto ml-md-0">
            <li class="nav-item">
                <span class="nav-link text-white">ID User: <?php echo htmlspecialchars($userId); ?></span>
            </li>
        </ul>
        <img src="logo.bmp" alt="Logo" class="logo"> <!-- Add your logo here -->
    </nav>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <a class="nav-link" href="dashboard.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                    <a class="nav-link" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Barang Masuk
                        </a>
                        <a class="nav-link" href="report_barang_stock.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Stock Barang
                        </a>
                        <a class="nav-link" href="keluar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Barang Keluar
                        </a>
                        <a class="nav-link" href="pengembalian.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Barang Kembali
                        </a>
                        <a class="nav-link" href="rusak.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Barang Rusak
                        </a>
                        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#reportDropdown" aria-expanded="false" aria-controls="reportDropdown">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Report
                        </a>
                    <div class="collapse" id="reportDropdown" aria-labelledby="headingOne" data-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link" href="report_barang_masuk.php">Report Barang Masuk</a>
                            <a class="nav-link" href="report_barang_keluar.php">Report Barang Keluar</a>
                            <a class="nav-link" href="report_barang_obsolate.php">Report Barang Obsolate</a>
                            <a class="nav-link" href="report_barang_rusak.php">Report Barang Rusak</a>
                            <a class="nav-link" href="report_barang_pengembalian.php">Report Barang Pengembalian</a>
                        </nav>
                    </div>
                    <div class="container">
                        <?php if (isset($_SESSION['empid'])): ?>
                            <a href="profile.php">Profile</a> | <a href="logout.php">Logout</a>
                        <?php else: ?>
                            <a href="login.php">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid">
                    <h2>Cetak Label</h2>
                    <h4>(Barang Masuk)</h4>
                    <div class="data-tables datatable-dark">
                        <div class="card mb-4">
                            <div class="card-header">
                                <a href="index.php" class="btn btn-secondary">Kembali</a>
                                <button onclick="window.print()" class="btn btn-primary">Print</button>
                            </div>
                            <div class="card-body print-area">
                                <?php if ($detail = $result->fetch_assoc()): ?>
                                    <div class="detail-item">
                                        <svg id="barcode"></svg> <!-- Barcode SVG element -->
                                        <script>
                                        $(document).ready(function() {
                                            JsBarcode("#barcode", "<?php echo htmlspecialchars($detail['assetno']); ?>", {
                                                format: "CODE128",
                                                width: 1, // Adjust the width of the barcode
                                                height: 60, // Adjust the height of the barcode
                                                displayValue: true,
                                                fontSize: 8 // Adjust the font size of the asset number
                                            });
                                        });
                                    </script>
                                    </div>
                                <?php else: ?>
                                    <p>Data tidak ditemukan untuk asset No ini.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Putri Carellilas Fony</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    </script>
   <script src="jscss/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
        <script src="jscss/bootstrap4.5.3.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="jscss/Chart2.8.0.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="jscss/jquery1.10.20.dataTables.min.js" crossorigin="anonymous"></script>
        <script src="jscss/dataTables1.10.20.bootstrap4.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/datatables-demo.js"></script>
</body>
</html>