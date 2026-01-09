<?php
require 'function.php';
require 'cek.php';

$userId = $_SESSION['empid'] ?? 'Guest'; // Default to 'Guest' if not set

// Initialize filter variable
$filterAssetType = isset($_POST['filterAssetType']) ? $_POST['filterAssetType'] : '';

// Initialize notification variable
$notification = '';

// Initialize selected assets in session
if (!isset($_SESSION['selected_assets'])) {
    $_SESSION['selected_assets'] = [];
}

// Handle action for 'obsolate'
if (isset($_POST['obsolate'])) {
    $selectedAssets = $_SESSION['selected_assets'];

    if (!empty($selectedAssets)) {
        $currentDate = date('Y-m-d H:i:s');
        foreach ($selectedAssets as $assetno) {
            $obsolateBy = $_SESSION['empid'];

            // Update the masuk table
            $updateQuery = "UPDATE masuk SET obsolatedate = ?, obsolateby = ?, obsolateapproval = 'Pending' WHERE assetno = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("sss", $currentDate, $obsolateBy, $assetno); // Bind the parameters
            if (!$updateStmt->execute()) {
                $notification .= ' Error updating asset ' . htmlspecialchars($assetno) . ': ' . $conn->error;
            } else {
                // Insert into the rusak table
                $assetQuery = "SELECT assettype, returnreceiveby, returnfaileddate FROM masuk WHERE assetno = ?";
                $assetStmt = $conn->prepare($assetQuery);
                $assetStmt->bind_param("s", $assetno);
                $assetStmt->execute();
                $assetResult = $assetStmt->get_result();
                
                if ($assetRow = $assetResult->fetch_assoc()) {
                    $assetType = $assetRow['assettype'];
                    $returnReceiveBy = $assetRow['returnreceiveby'];
                    $returnFailedDate = $assetRow['returnfaileddate'];
                    $statusObsolate = 'Pending';

                    $insertQuery = "INSERT INTO rusak (assetno, assettype, returnreceiveby, returnfaileddate, statusobsolate) VALUES (?, ?, ?, ?, ?)";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->bind_param("sssss", $assetno, $assetType, $returnReceiveBy, $returnFailedDate, $statusObsolate);
                    if (!$insertStmt->execute()) {
                        $notification .= ' Error inserting into rusak for asset ' . htmlspecialchars($assetno) . ': ' . $conn->error;
                    }
                }
            }
        }

        $notification .= ' Data berhasil terkirim dan menunggu approval.';
        // Clear selected assets after obsolate
        $_SESSION['selected_assets'] = [];
    }
}

// Handle remark edit
if (isset($_POST['edit_remark'])) {
    $assetno = $_POST['assetno'] ?? '';
    $newRemark = $_POST['remark'] ?? '';

    if ($assetno && $newRemark) {
        $updateRemarkQuery = "UPDATE masuk SET failedremark = ? WHERE assetno = ?";
        $stmt = $conn->prepare($updateRemarkQuery);
        $stmt->bind_param("ss", $newRemark, $assetno);

        if ($stmt->execute()) {
            $notification .= 'Remark updated successfully for asset ' . htmlspecialchars($assetno) . '.';
        } else {
            $notification .= 'Error updating remark for asset ' . htmlspecialchars($assetno) . ': ' . $conn->error;
        }
    }
}

// Fetch total number of records for pagination
$totalQuery = "SELECT COUNT(*) as total FROM masuk WHERE returnfaileddate <> '0000-00-00' AND (obsolatedate IS NULL OR obsolatedate = '')";
if ($filterAssetType) {
    $totalQuery .= " AND assettype LIKE '%" . mysqli_real_escape_string($conn, $filterAssetType) . "%'";
}
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalRecords = $totalRow['total'];

// Pagination logic
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch records for the current page
$query = "SELECT * FROM masuk WHERE returnfaileddate <> '0000-00-00' AND (obsolatedate IS NULL OR obsolatedate = '')";
if ($filterAssetType) {
    $query .= " AND assettype LIKE '%" . mysqli_real_escape_string($conn, $filterAssetType) . "%'";
}
$query .= " LIMIT $limit OFFSET $offset";

$requests = mysqli_query($conn, $query);

// Calculate total pages
$totalPages = ceil($totalRecords / $limit);

// Handle asset selection
if (isset($_POST['select_asset'])) {
    $assetno = $_POST['select_asset'] ?? '';
    if ($assetno && !in_array($assetno, $_SESSION['selected_assets'])) {
        $_SESSION['selected_assets'][] = $assetno;
    }
}

// Handle asset removal from selected
if (isset($_POST['remove_asset'])) {
    $assetno = $_POST['remove_asset'] ?? '';
    if (($key = array_search($assetno, $_SESSION['selected_assets'])) !== false) {
        unset($_SESSION['selected_assets'][$key]);
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
    <link href="jscss/dataTables1.10.20.bootstrap4.min.css" rel="stylesheet" crossorigin="anonymous" />
        <script src="jscss/all.min.js" crossorigin="anonymous"></script>
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
            <?php if ($_SESSION['rl'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="obsolateapproval.php">Approval Requests</a>
                </li>
           <?php endif; ?>
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
                    <h1 class="mt-4">Barang Rusak</h1>

                    <!-- Input Filter -->
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="filterAssetType">Filter Asset Type:</label>
                            <input type="text" class="form-control" id="filterAssetType" name="filterAssetType" value="<?php echo htmlspecialchars($filterAssetType); ?>" placeholder="Filter Asset Type">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                    <br>

                    <?php if ($notification): ?>
                        <div class="alert alert-info">
                            <?php echo htmlspecialchars($notification); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <table class="table table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Action</th>
                                    <th>Asset No</th>
                                    <th>Asset Type</th>
                                    <th>Return Received By</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch records for the current page
                                $requests = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($requests)) : ?>
                                    <?php if (!in_array($row['assetno'], $_SESSION['selected_assets'])): ?>
                                        <tr>
                                            <td>
                                                <button type="submit" name="select_asset" class="btn btn-success" value="<?php echo htmlspecialchars($row['assetno']); ?>">Select</button>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['assetno']); ?></td>
                                            <td><?php echo htmlspecialchars($row['assettype']); ?></td>
                                            <td><?php echo htmlspecialchars($row['returnreceiveby']); ?></td>
                                            <td><?php echo htmlspecialchars($row['failedremark']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </form>
                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&filterAssetType=<?php echo urlencode($filterAssetType); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>

                    <h3>Detail Asset yang Dipilih</h3>
                    <form method="post" action="">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Asset No</th>
                                    <th>Asset Type</th>
                                    <th>Remark</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['selected_assets'] as $assetno): ?>
                                    <?php
                                    // Fetch asset details for the selected asset
                                    $assetQuery = "SELECT assettype, failedremark FROM masuk WHERE assetno = '$assetno'";
                                    $assetResult = mysqli_query($conn, $assetQuery);
                                    $assetRow = mysqli_fetch_assoc($assetResult);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assetno); ?></td>
                                        <td><?php echo htmlspecialchars($assetRow['assettype']); ?></td>
                                        <td>
                                            <form method="post" action="">
                                                <input type="hidden" name="assetno" value="<?php echo htmlspecialchars($assetno); ?>">
                                                <input type="text" name="remark" value="<?php echo htmlspecialchars($assetRow['failedremark']); ?>" required>
                                                <button type="submit" name="edit_remark" class="btn btn-primary">Edit</button>
                                            </form>
                                        </td>
                                        <td>
                                            <button type="submit" name="remove_asset" class="btn btn-danger" value="<?php echo htmlspecialchars($assetno); ?>">Cancel</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                    <br>

                    <form method="post" action="">
                        <button type="submit" name="obsolate" class="btn btn-warning">Obsolate</button>
                    </form>
                    <br>
                    <a href="Report_barang_rusak.php" class="btn btn-secondary">Cek Status Obsolate</a>

                    <a href="summary_obsolate.php" class="btn btn-secondary">Print</a>
                </div>
            </main>
            <br>
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

    <script src="jscss/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
        <script src="jscss/bootstrap4.5.3.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <style>
        /* CSS to make the filter input the same width as the search box */
        #filterAssetType {
            width: 200px; /* Adjust this value as needed */
        }
    </style>
</body>
</html>