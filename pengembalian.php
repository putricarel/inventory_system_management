<?php
require 'function.php';
require 'cek.php';

$userId = $_SESSION['empid'] ?? 'Guest'; // Default to 'Guest' if not set

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize notification variable
$notification = '';

// Handle the action for 'running'
if (isset($_GET['action'], $_GET['useddate'], $_GET['assetno']) && $_GET['action'] == 'running') {
    $useddate = $_GET['useddate'];
    $assetno = $_GET['assetno'];

    // Fetch data from the masuk table based on useddate and assetno
    $query = "SELECT * FROM masuk WHERE useddate = ? AND assetno = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $useddate, $assetno);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        // Insert into pengembalian with status Running
        $status = 'Running'; // Status
        $insertQuery = "INSERT INTO pengembalian (assetno, assettype, useddocno, usedbyempid, useddate, usedremark, givenbyempid, temporarydate, tilldate, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        if (!$insertStmt) {
            die("Prepare failed: " . $conn->error);
        }

        $values = [
            $data['assetno'],
            $data['assettype'],
            $data['useddocno'],
            $data['usedbyempid'],
            $data['useddate'],
            $data['usedremark'],
            $data['givenbyempid'],
            $data['temporarydate'],
            $data['tilldate'],
            $status
        ];

        // Handle NULL values
        foreach ($values as $key => $value) {
            if (is_null($value)) {
                $values[$key] = ''; // Replace NULL with an empty string or handle as needed
            }
        }

        // Bind parameters
        $insertStmt->bind_param("ssssssssss", ...$values);

        if ($insertStmt->execute()) {
            $notification = 'Barang telah dikembalikan.';
        } else {
            $notification = 'Error: ' . $conn->error;
        }

        // Update masuk to mark the request as processed but keep the asset available
        $updateQuery = "UPDATE masuk SET usedbyempid = NULL, useddate = NULL, usedremark = NULL, givenbyempid = NULL, useddocno = NULL, usedapproval = NULL, temporarydate = NULL, tilldate = NULL, status = NULL WHERE assetno = ?";
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            die("Prepare failed: " . $conn->error);
        }
        $updateStmt->bind_param("s", $data['assetno']);
        $updateStmt->execute();
    } else {
        $notification = 'Data tidak ditemukan.';
    }
}

// Handle the action for 'failed' with reason from POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fail'])) {
    $assetno = $_POST['assetno'];
    $reason = $_POST['reason'];

    // Insert into pengembalian with status Failed
    $status = 'Failed'; 
    $insertQuery = "INSERT INTO pengembalian (assetno, assettype, useddocno, usedbyempid, useddate, usedremark, givenbyempid, temporarydate, tilldate, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    if (!$insertStmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Fetch data for the specific asset
    $query = "SELECT * FROM masuk WHERE assetno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $assetno);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        $values = [
            $data['assetno'],
            $data['assettype'],
            $data['useddocno'],
            $data['usedbyempid'],
            $data['useddate'],
            $data['usedremark'],
            $data['givenbyempid'],
            $data['temporarydate'],
            $data['tilldate'],
            $status
        ];

        // Handle NULL values
        foreach ($values as $key => $value) {
            if (is_null($value)) {
                $values[$key] = ''; // Replace NULL with an empty string or handle as needed
            }
        }

        // Bind parameters
        $insertStmt->bind_param("ssssssssss", ...$values);

        if ($insertStmt->execute()) {
            $notification = 'Data berhasil masuk ke barang rusak dengan alasan: ' . htmlspecialchars($reason);
        } else {
            $notification = 'Error: ' . $conn->error;
        }

        // Update returnfaileddate and receiveby with the logged-in user ID
        $receiveBy = $_SESSION['empid']; 
        $currentDate = date('Y-m-d H:i:s'); 

        // Update the failedremark in masuk, pengembalian, and rusak tables
        $updateQuery = "UPDATE masuk SET returnfaileddate = ?, returnreceiveby = ?, failedremark = ?, tilldate = NULL WHERE assetno = ?";
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            die("Prepare failed: " . $conn->error);
        }
        $updateStmt->bind_param("ssss", $currentDate, $receiveBy, $reason, $data['assetno']);
        $updateStmt->execute();

        $updateQuery = "UPDATE pengembalian SET failedremark = ? WHERE assetno = ?";
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            die("Prepare failed: " . $conn->error);
        }
        $updateStmt->bind_param("ss", $reason, $data['assetno']);
        $updateStmt->execute();

        $updateQuery = "INSERT INTO rusak (assetno, failedremark) VALUES (?, ?) ON DUPLICATE KEY UPDATE failedremark = ?";
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            die("Prepare failed: " . $conn->error);
        }
        $updateStmt->bind_param("sss", $data['assetno'], $reason, $reason);
        $updateStmt->execute();
    }
}

// Fetch approval requests excluding Running and Failed statuses
$query = "SELECT m.*, e.empname, e.empdept 
          FROM masuk m 
          LEFT JOIN emplist e ON m.usedbyempid = e.empid
          WHERE ((m.useddate <> '0000-00-00' AND m.useddate IS NOT NULL AND m.usedapproval = 'approved') 
          OR (m.temporarydate <> '0000-00-00' AND m.temporarydate IS NOT NULL AND m.usedapproval = 'approved'))
          AND NOT(m.returnfaileddate <> '0000-00-00' AND m.returnfaileddate IS NOT NULL)
          AND m.assetno NOT IN (SELECT assetno FROM masuk WHERE status IN ('Running', 'Failed'))";

// Execute the query and check for errors
$requests = mysqli_query($conn, $query);
if (!$requests) {
    die("Query failed: " . mysqli_error($conn)); // Log the error
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
        const timeoutDuration = 300; // 5 minutes
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

        $(document).ready(function() {
            // Initialize DataTable
            $('#dataTable').DataTable({
                "searching": true, // Enable searching
                "ordering": true, // Enable ordering
                "lengthChange": false, // Disable changing the number of records per page
                "pageLength": 10 // Set default page length
            });
        });

        function confirmAction(action, useddate, assetno) {
            const url = `?action=${action}&useddate=${useddate}&assetno=${assetno}`;
            window.location.href = url; // Redirect to the same page with parameters
        }
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
                    <h1 class="mt-4">Barang Kembali</h1>
                    <br>

                    <?php if ($notification): ?>
                        <div class="alert alert-info">
                            <?php echo htmlspecialchars($notification); ?>
                        </div>
                    <?php endif; ?>

                    <table class="table table-bordered" id="dataTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>Asset No</th>
                                <th>Asset Type</th>
                                <th>Used By</th>
                                <th>Status</th>
                                <th>Till Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
    <?php while ($row = mysqli_fetch_assoc($requests)) : ?>
        <tr>
            <td><?php echo htmlspecialchars($row['assetno']); ?></td>
            <td><?php echo htmlspecialchars($row['assettype']); ?></td>
            <td><?php echo htmlspecialchars($row['usedbyempid'] . ' - ' . $row['empname'] . ' - ' . $row['empdept']); ?></td>
            <td><?php echo htmlspecialchars($row['status']); ?></td>
            <td><?php echo htmlspecialchars($row['tilldate']); ?></td>
            <td>
                <button class="btn btn-success btn-sm" onclick="confirmAction('running', '<?php echo urlencode($row['useddate'] ?: $row['temporarydate']); ?>', '<?php echo urlencode($row['assetno']); ?>')">Running</button>
                <!-- Button to trigger modal for Failed -->
                <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#failModal<?php echo $row['assetno']; ?>">Failed</button>

                <!-- Modal for entering failure reason -->
                <div class="modal fade" id="failModal<?php echo $row['assetno']; ?>" tabindex="-1" role="dialog" aria-labelledby="failModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="failModalLabel">Reason for Failure</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="assetno" value="<?php echo htmlspecialchars($row['assetno']); ?>">
                                    <div class="form-group">
                                        <label for="reason">Alasan Rusak:</label>
                                        <textarea class="form-control" name="reason" required></textarea>
                                    </div>
                                    <button type="submit" name="fail" class="btn btn-danger">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    <?php endwhile; ?>
</tbody>
                    </table>
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

    <script src="jscss/jquery-3.5.11.slim.min.js" crossorigin="anonymous"></script>
    <script src="jscss/bootstrap@4.5.3.bundle.min.js" crossorigin="anonymous"></script>
    <script src="jscss/jquery.dataTables1.10.20.min.js" crossorigin="anonymous"></script>
    <script src="jscss/dataTables1.10.2.0.bootstrap4.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="jscss/Chartt2.8.0.min.js" crossorigin="anonymous"></script>
    <script src="assets/demo/chart-area-demo.js"></script>
    <script src="assets/demo/chart-bar-demo.js"></script>
    <script src="assets/demo/datatables-demo.js"></script>
</body>
</html>