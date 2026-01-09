<?php

require 'function.php';
require 'cek.php';

// Assuming you have the user ID stored in a session variable
$userId = $_SESSION['empid'] ?? 'Guest'; // Default to 'Guest' if not set

// Initialize variables for filtering
$mulai = '';
$selesai = '';
$dataAvailable = false; // Flag to check if data is available

// Check if the filter form has been submitted
if (isset($_POST['filter_tgl'])) {
    $mulai = $_POST['tgl_mulai'];
    $selesai = $_POST['tgl_selesai'];

    // Validate dates
    if ($mulai && $selesai) {
        $dataAvailable = true; // Set flag to true if dates are valid
    }
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
                </div>
            </nav>
        </div>
        
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid">
                    <h2>Cetak Laporan</h2>
                    <h4>(Barang Masuk)</h4>
                    <div class="data-tables datatable-dark">
                        <div class="card mb-4">
                            <div class="card-header">
                                <form method="post" class="form-inline">
                                    <input type="date" name="tgl_mulai" class="form-control" required>
                                    <input type="date" name="tgl_selesai" class="form-control ml-3" required>
                                    <button type="submit" name="filter_tgl" class="btn btn-info ml-3">Filter Tanggal</button>
                                </form>
                                <br>
                                <a href="summary_masuk.php" class="btn btn-secondary">Summary</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="mauexport" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Asset Type</th>
                                                <th>Item Code</th>
                                                <th>Item Detail</th>
                                                <th>Item Serial No</th>
                                                <th>PO No</th>
                                                <th>PO Item No</th>
                                                <th>PO Date</th>
                                                <th>PO Price Unit</th>
                                                <th>Vendor Code</th>
                                                <th>Good Receipt Date</th>
                                                <th>Warranty Year</th>
                                                <th>Asset No</th>
                                                <th>Input Date</th>
                                                <th>Input by Emp ID</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($dataAvailable) {
                                                $query = "
                                                SELECT * FROM masuk
                                                WHERE inputdate BETWEEN '$mulai' AND DATE_ADD('$selesai', INTERVAL 1 DAY)
                                                ";
                                                $ambilsemuadatamasuk = mysqli_query($conn, $query);
                                                while ($data = mysqli_fetch_array($ambilsemuadatamasuk)) {
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $data['assettype']; ?></td>
                                                        <td><?php echo $data['itemcode']; ?></td>
                                                        <td><?php echo $data['itemdetail']; ?></td>
                                                        <td><?php echo $data['itemserialno']; ?></td>
                                                        <td><?php echo $data['pono']; ?></td>
                                                        <td><?php echo $data['poitemno']; ?></td>
                                                        <td><?php echo $data['podate']; ?></td>
                                                        <td><?php echo $data['popriceunit']; ?></td>
                                                        <td><?php echo $data['vendorcode']; ?></td>
                                                        <td><?php echo $data['goodreceiptdate']; ?></td>
                                                        <td><?php echo $data['warantyyear']; ?></td>
                                                        <td><?php echo $data['assetno']; ?></td>
                                                        <td><?php echo $data['inputdate']; ?></td>
                                                        <td><?php echo $data['inputbyempid']; ?></td>
                                                    </tr>
                                                    <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='14'>Silakan filter tanggal untuk melihat data.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
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
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#mauexport').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    title: 'Laporan Barang Masuk',
                    className: 'btn-excel'
                },
                {
                    extend: 'pdf',
                    title: 'Laporan Barang Masuk',
                    orientation: 'landscape', // Set PDF orientation to landscape
                    pageSize: 'A4', // Set page size
                    className: 'btn-pdf'
                },
                {
                    extend: 'print',
                    title: 'Laporan Barang Masuk',
                    className: 'btn-print'
                }
            ]
        });
    });
    </script>
</body>
</html>