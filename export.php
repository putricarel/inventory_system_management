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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System Management</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="jscss/jquery1.10.19.dataTables.css" rel="stylesheet" />
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
</head>
<body>

<div class="container">
    <h2>Cetak Laporan</h2>
    <h4>(Inventory)</h4>
    <div class="data-tables datatable-dark">
        <div class="card mb-4">
            <div class="card-header">
                <form method="post" class="form-inline">
                    <input type="date" name="tgl_mulai" class="form-control" required>
                    <input type="date" name="tgl_selesai" class="form-control ml-3" required>
                    <button type="submit" name="filter_tgl" class="btn btn-info ml-3">Filter Tanggal</button>
                </form>
                <br>
                <a href="report.php" class="btn btn-secondary">Kembali</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="mauexport" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Asset Type</th>
                                <th>Asset No</th>
                                <th>Item Code</th>
                                <th>Item Detail</th>
                                <th>PO Price Unit</th>
                                <th>Vendor Code</th>
                                <th>Stock Awal</th>
                                <th>Stock Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($dataAvailable) {
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
                                        $assettype = htmlspecialchars($data['assettype']);
                                        $assetno = htmlspecialchars($data['assetno']);
                                        $itemcode = htmlspecialchars($data['itemcode']);
                                        $itemdetail = htmlspecialchars($data['itemdetail']);
                                        $popriceunit = htmlspecialchars($data['popriceunit']);
                                        $vendorcode = htmlspecialchars($data['vendorcode']);

                                        // Get stock calculations
                                        $stock_awal = $data['stock_awal'];
                                        $stock_keluar = $data['stock_keluar'];
                                        $stock_rusak = $data['stock_rusak'];
                                        $stock_obsolate = $data['stock_obsolate'];

                                        // Calculate final stock
                                        $stock_akhir = $stock_awal - $stock_keluar - $stock_rusak - $stock_obsolate;
                            ?>
                            <tr>
                                <td><?php echo $assettype; ?></td>
                                <td><?php echo $assetno; ?></td>
                                <td><?php echo $itemcode; ?></td>
                                <td><?php echo $itemdetail; ?></td>
                                <td><?php echo $popriceunit; ?></td>
                                <td><?php echo $vendorcode; ?></td>
                                <td><?php echo $stock_awal; ?></td>
                                <td><?php echo $stock_akhir; ?></td>
                            </tr>
                            <?php
                                    }
                                }
                            } else {
                                echo "<tr><td colspan='8'>Silakan filter tanggal untuk melihat data.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#mauexport').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    title: 'Laporan Inventory Barang',
                    className: 'btn-excel'
                },
                {
                    extend: 'pdf',
                    title: 'Laporan Inventory Barang',
                    orientation: 'landscape', // Set PDF orientation to landscape
                    pageSize: 'A4', // Set page size
                    className: 'btn-pdf'
                },
                {
                    extend: 'print',
                    title: 'Laporan Inventory Barang',
                    className: 'btn-print'
                }
            ]
        });
    });
    </script>

    <style>
    .btn-excel {
        background-color: #4CAF50; /* Green */
        color: white;
    }

    .btn-pdf {
        background-color: #f44336; /* Red */
        color: white;
    }

    .btn-print {
        background-color: #008CBA; /* Blue */
        color: white;
    }

    .btn-excel:hover, .btn-pdf:hover, .btn-print:hover {
        opacity: 0.8;
    }
    </style>

    <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between small">
                <div class="text-muted">Copyright &copy; sarahagustin</div>
                <div>
                    <a href="#">Privacy Policy</a>
                    &middot;
                    <a href="#">Terms &amp; Conditions</a>
                </div>
            </div>
        </div>
    </footer>
</div>

</body>
</html>