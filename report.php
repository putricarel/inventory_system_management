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
    <title>Barang Report</title>
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
            <span class="nav-link text-white">ID User: <?php echo htmlspecialchars($userId); ?></span>
        </li>
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
                    <!DOCTYPE html>
                            <html lang="id">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <title>Beranda</title>
                                <link rel="stylesheet" href="style.css">
                            </head>
                            <body>
                                <div class="container">
                                    <?php if (isset($_SESSION['empid'])): ?>
                                        <a href="profile.php">Profile</a> | <a href="logout.php">Logout</a>
                                    <?php else: ?>
                                        <a href="login.php">Login</a>
                                    <?php endif; ?>
                                </div>
                            </body>
                            </html>
                </div>
            </div>
        </nav>
    </div>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid">
                <h1 class="mt-4">Report</h1>
                <div class="card mb-4">
                    <div class="card-header">
                        <form method="post" class="form-inline">
                            <input type="date" name="tgl_mulai" class="form-control" required>
                            <input type="date" name="tgl_selesai" class="form-control ml-3" required>
                            <button type="submit" name="filter_tgl" class="btn btn-info ml-3">Filter Tanggal</button>
                        </form>
                        <br>
                        <?php if ($dataAvailable): ?>
                        <a href="export.php?mulai=<?php echo urlencode($mulai); ?>&selesai=<?php echo urlencode($selesai); ?>" class="btn btn-info ml-3">Cetak Laporan</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($dataAvailable): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Asset Type</th>
                                        <th>Asset No</th>
                                        <th>Item Code</th>
                                        <th>Item Detail</th>
                                        <th>PO Price Unit</th>
                                        <th>Vendor Code</th>
                                        <th>Sisa Stock </th>
                                        <th>Stock Yang Berkurang </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Initialize the query
                                    $query = "
                                        SELECT stock.*, 
                                        COALESCE((SELECT COUNT(*) FROM stock WHERE assettype = stock.assettype AND inputdate < '$mulai'), 0) AS stock_awal,
                                        COALESCE((SELECT COUNT(*) FROM barang_keluar WHERE assettype = stock.assettype AND useddate BETWEEN '$mulai' AND '$selesai'), 0) AS stock_keluar,
                                        COALESCE((SELECT COUNT(*) FROM barang_rusak WHERE assettype = stock.assettype AND returnfaileddate BETWEEN '$mulai' AND '$selesai'), 0) AS stock_rusak,
                                        COALESCE((SELECT COUNT(*) FROM barang_obsolate WHERE assettype = stock.assettype AND obsolatedate BETWEEN '$mulai' AND '$selesai'), 0) AS stock_obsolate
                                        FROM stock
                                        WHERE inputdate BETWEEN '$mulai' AND DATE_ADD('$selesai', INTERVAL 1 DAY)
                                    ";

                                    // Execute the query
                                    $ambilsemuadatastock = mysqli_query($conn, $query);

                                    // Check for SQL errors
                                    if (!$ambilsemuadatastock) {
                                        echo "Error: " . mysqli_error($conn);
                                    } else {
                                        while ($data = mysqli_fetch_array($ambilsemuadatastock)) {
                                            $assettype = $data['assettype'];
                                            $assetno = $data['assetno'];
                                            $itemcode = $data['itemcode'];
                                            $itemdetail = $data['itemdetail'];
                                            $popriceunit = $data['popriceunit'];
                                            $vendorcode = $data['vendorcode'];

                                            // Get stock calculations
                                            $stock_awal = $data['stock_awal'];
                                            $stock_keluar = $data['stock_keluar'];
                                            $stock_rusak = $data['stock_rusak'];
                                            $stock_obsolate = $data['stock_obsolate'];

                                            // Calculate final stock
                                            $stock_akhir = $stock_awal - $stock_keluar - $stock_rusak - $stock_obsolate;
                                    ?>
                                    <tr>
                                        <td><?=$assettype;?></td>
                                        <td><?=$assetno;?></td>
                                        <td><?=$itemcode;?></td>
                                        <td><?=$itemdetail;?></td>
                                        <td><?=$popriceunit;?></td>
                                        <td><?=$vendorcode;?></td>
                                        <td><?=$stock_awal;?></td>
                                        <td><?=$stock_akhir;?></td>
                                    </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Silakan filter tanggal untuk melihat data.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; Putri carellilas fony</div>
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
<script src="jscss/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
        <script src="jscss/bootstrap4.5.3.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="jscss/Chart2.8.0.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="jscss/jquery1.10.20.dataTables.min.js" crossorigin="anonymous"></script>
        <script src="jscss/dataTables1.10.20.bootstrap4.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/datatables-demo.js"></script>st>
</body>
</html>