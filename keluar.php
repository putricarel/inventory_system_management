<?php
require 'function.php';
require 'cek.php';

// Function to get the last document number for today
function getLastDocNoForToday($conn, $date) {
    $query = "SELECT useddocno 
              FROM masuk 
              WHERE (DATE(useddate) = '$date' OR DATE(temporarydate) = '$date') 
              ORDER BY useddocno DESC LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval(substr($row['useddocno'], -3)); // Get the last 3 digits
    }
    return 0; // Return 0 if no data for today
}

// Function to generate document numbers
function generateDocNumbers($qty, $lastNumber, $date) {
    $docNumbers = [];
    $year = date('y', strtotime($date));
    $month = date('m', strtotime($date));
    $day = date('d', strtotime($date));

    for ($i = 1; $i <= $qty; $i++) {
        // Increment last number
        $increment = $lastNumber + $i;
        $useddocno = "{$year}{$month}{$day}" . sprintf('%03d', $increment);
        $docNumbers[] = $useddocno;
    }

    return $docNumbers;
}

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$userId = $_SESSION['empid'] ?? 'Guest'; // Default to 'Guest' if not set
$selectedAssets = $_SESSION['selectedAssets'] ?? []; // Get existing selected assets from session

// Handle asset selection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selectAsset'])) {
    $assetno = mysqli_real_escape_string($conn, $_POST['assetno']);
    if (!in_array($assetno, $selectedAssets)) {
        $selectedAssets[] = $assetno; // Add the new asset to the array
        $_SESSION['selectedAssets'] = $selectedAssets; // Update the session
    }
}

// Process item recording
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addBarangKeluar'])) {
    // Get and sanitize input
    $usedbyempid = mysqli_real_escape_string($conn, $_POST['usedbyempid']);
    $useddate = mysqli_real_escape_string($conn, $_POST['useddate'] ?? null);
    $usedremark = mysqli_real_escape_string($conn, $_POST['usedremark'] ?? null);
    $givenbyempid = mysqli_real_escape_string($conn, $_POST['givenbyempid']);
    $tilldate = mysqli_real_escape_string($conn, $_POST['tilldate'] ?? null);
    $temporarydate = mysqli_real_escape_string($conn, $_POST['temporarydate'] ?? null);
    $status = mysqli_real_escape_string($conn, $_POST['status']); 

    // Get today's date
    $today = date('Y-m-d');
    // Get the last document number for today
    $lastDocNo = getLastDocNoForToday($conn, $today);
    // Generate new document number
    $generatedDocNo = generateDocNumbers(1, $lastDocNo, $today)[0]; 

    // Prepare the insert statement for keluar table
    $stmt = $conn->prepare("INSERT INTO keluar (usedbyempid, useddate, usedremark, givenbyempid, useddocno, usedapproval, assetno, tilldate, temporarydate, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        die("Error preparing statement: " . htmlspecialchars($conn->error));
    }

    // Set status to "Pending"
    $statusApproval = 'Pending';

    // Loop through selected assets
    foreach ($selectedAssets as $assetno) {
        // Bind parameters with 'Pending' status for usedapproval
        $stmt->bind_param("ssssssssss", $usedbyempid, $useddate, $usedremark, $givenbyempid, $generatedDocNo, $statusApproval, $assetno, $tilldate, $temporarydate, $status);
        
        // Execute the insert statement
        if (!$stmt->execute()) {
            echo "Error inserting into keluar: " . $stmt->error;
        }
    }

    // Close the statement after execution
    $stmt->close();

    // Update query for masuk table
    $updateApprovalQuery = "UPDATE masuk
        SET usedbyempid = ?, 
            useddate = ?, 
            usedremark = ?, 
            givenbyempid = ?,
            useddocno = ?, 
            usedapproval = 'Pending',
            tilldate = ?,
            temporarydate = ?,
            status = ? 
        WHERE assetno = ?";

    // Prepare the update statement for masuk table
    $stmtUpdate = $conn->prepare($updateApprovalQuery);

    if ($stmtUpdate === false) {
        die("Error preparing update statement: " . htmlspecialchars($conn->error));
    }

    // Bind the parameters for the update query
    foreach ($selectedAssets as $assetno) {
        // Bind parameters for update
        $stmtUpdate->bind_param("ssssissss", $usedbyempid, $useddate, $usedremark, $givenbyempid, $generatedDocNo, $tilldate, $temporarydate, $status, $assetno);
        
        // Execute the update statement
        if (!$stmtUpdate->execute()) {
            echo "Error updating masuk: " . $stmtUpdate->error;
        }
    }

    // Close the update statement
    $stmtUpdate->close();

    // Clear selected assets after submission
    unset($_SESSION['selectedAssets']);

    $_SESSION['notification'] = 'Data telah diperbarui dan menunggu persetujuan.';

    // Redirect to the main page
    header('Location: keluar.php'); 
    exit();
}

// Ambil data dari tabel emplist
$employeeQuery = "SELECT empid, empname, empdept FROM emplist";
$employeeResult = mysqli_query($conn, $employeeQuery);

// Cek jika query berhasil
if (!$employeeResult) {
    die("Error fetching employees: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Barang Keluar</title>
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
                    <a class="nav-link text-white" href="keluarapproval.php">Approval Requests</a>
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
                            <?php if (isset($_SESSION['notification'])): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($_SESSION['notification']); ?>
                            <?php unset($_SESSION['notification']); ?>
                        </div>
                    <?php endif; ?>
                            <h1 class="mt-4">Barang Keluar</h1>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#myModal">Tambah Item</button>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group">
                                            <label for="givenbyempid">Given By Emp ID:</label>
                                            <input type="text" name="givenbyempid" class="form-control" value="<?php echo htmlspecialchars($userId); ?>" readonly />
                                        </div>
                                       <div class="form-group">
    <label for="usedbyempid">Used By:</label>
    <input type="text" id="filterEmpDept" class="form-control" placeholder="Filter Employee Department" onkeyup="filterEmployees()">
    <select name="usedbyempid" id="employeeSelect" class="form-control" required>
        <option value="">Select Employee</option>
        <?php while ($employee = mysqli_fetch_assoc($employeeResult)): ?>
            <option value="<?php echo htmlspecialchars($employee['empid']); ?>">
                <?php echo htmlspecialchars($employee['empid']) . " - " . htmlspecialchars($employee['empname']) . " - " . " (" . htmlspecialchars($employee['empdept']) . ")"; ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>
                                        <div class="form-group">
                                            <label for="status">Status:</label>
                                            <select name="status" class="form-control" required onchange="toggleFields()">
                                                <option value="">Select Remark</option>
                                                <option value="Temporary">Temporary</option>
                                                <option value="Fixed">Fixed</option>
                                            </select>
                                        </div>
                                        <div id="dateGroup" class="form-group" style="display: none;">
                                            <label for="dateInput">Date:</label>
                                            <input type="date" id="dateInput" name="dateInput" class="form-control">
                                        </div>
                                        <div id="tilldateGroup" class="form-group" style="display: none;">
                                            <label for="tilldate">Till Date:</label>
                                            <input type="date" name="tilldate" class="form-control">
                                        </div>
                                        <div id="remarkGroup" class="form-group" style="display: none;">
                                            <label for="usedremark">Remark:</label>
                                            <input type="text" name="usedremark" class="form-control" placeholder="Remark">
                                        </div>

                                        <script>
                                            function toggleFields() {
                                                const statusSelect = document.querySelector('select[name="status"]');
                                                const dateGroup = document.getElementById('dateGroup');
                                                const tilldateGroup = document.getElementById('tilldateGroup');
                                                const remarkGroup = document.getElementById('remarkGroup');

                                                // Reset all fields
                                                dateGroup.style.display = 'none';
                                                tilldateGroup.style.display = 'none';
                                                remarkGroup.style.display = 'none';

                                                // Show fields based on selected status
                                                if (statusSelect.value === 'Temporary') {
                                                    dateGroup.style.display = 'block';
                                                    tilldateGroup.style.display = 'block';
                                                    remarkGroup.style.display = 'block';

                                                    // Change the input name for temporary status
                                                    document.getElementById('dateInput').name = 'temporarydate';
                                                } else if (statusSelect.value === 'Fixed') {
                                                    dateGroup.style.display = 'block';
                                                    remarkGroup.style.display = 'block';

                                                    // Change the input name for fixed status
                                                    document.getElementById('dateInput').name = 'useddate';
                                                }
                                            }
                                        </script>

                                        <div class="form-group">
                                            <label for="useddocno">Document No:</label>
                                            <input type="text" class="form-control" value="<?php echo generateDocNumbers(1, getLastDocNoForToday($conn, date('Y-m-d')), date('Y-m-d'))[0]; ?>" readonly>
                                        </div>
                                        <button type="submit" name="addBarangKeluar" class="btn btn-primary">Submit</button>
                                    </form>
                                    <br>
                                    <a href="Report_barang_keluar.php" class="btn btn-secondary">Cek Status Approval</a>

                                    <?php if (!empty($selectedAssets)): ?>
                                        <h3>Detail Asset yang Dipilih</h3>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Asset No</th>
                                                    <th>Asset Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($selectedAssets as $assetno): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($assetno); ?></td>
                                                <td>
                                                    <?php
                                                    // Fetch asset type for the selected asset
                                                    $assetQuery = "SELECT assettype FROM masuk WHERE assetno = '$assetno'";
                                                    $assetResult = mysqli_query($conn, $assetQuery);
                                                    if ($assetResult) {
                                                        $assetRow = mysqli_fetch_assoc($assetResult);
                                                        echo htmlspecialchars($assetRow['assettype']);
                                                    }
                                                    ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
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

            <script src="jscss/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
        <script src="jscss/bootstrap4.5.3.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="jscss/Chart2.8.0.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="jscss/jquery1.10.20.dataTables.min.js" crossorigin="anonymous"></script>
        <script src="jscss/dataTables1.10.20.bootstrap4.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/datatables-demo.js"></script>

            <!-- The Modal -->
            <div class="modal fade" id="myModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Tambah Barang</h4>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <form method="post">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="assetno">Pilih Barang:</label>
                                    <input type="text" id="filterAssetNo" class="form-control" placeholder="Filter Asset No" onkeyup="filterAssets()">
                                    <br>
                                    <select id="assetSelect" name="assetno" class="form-control" required>
                                        <option value="">Select Asset No</option>
                                        <?php
                                        // Query for assets from masuk
                                        $excludedAssets = "'" . implode("', '", $selectedAssets) . "'";
                                        $query = "SELECT assetno, assettype FROM masuk WHERE useddate IS NULL AND obsolatedate IS NULL AND returnfaileddate IS NULL AND temporarydate IS NULL AND assetno NOT IN ($excludedAssets)";
                                        $barangMasuk = mysqli_query($conn, $query);
                                        
                                        while ($row = mysqli_fetch_assoc($barangMasuk)): ?>
                                            <option value="<?php echo htmlspecialchars($row['assetno']); ?>">
                                                <?php echo htmlspecialchars($row['assetno']); ?> - <?php echo htmlspecialchars($row['assettype']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                        </select>
                                        <br>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" name="selectAsset" class="btn btn-primary">Select Asset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
                function filterAssets() {
                    const filter = document.getElementById('filterAssetNo').value.toLowerCase();
                    const options = document.getElementById('assetSelect').options;

                    for (let i = 0; i < options.length; i++) {
                        const assetNo = options[i].value.toLowerCase();
                        options[i].style.display = assetNo.includes(filter) ? "" : "none";
                    }
                }
            </script>

            <script>
    function filterEmployees() {
        const filter = document.getElementById('filterEmpDept').value.toLowerCase();
        const options = document.getElementById('employeeSelect').options;

        for (let i = 0; i < options.length; i++) {
            const empDept = options[i].text.toLowerCase();  // Mencari di teks opsi
            options[i].style.display = empDept.includes(filter) ? "" : "none"; // Menampilkan opsi berdasarkan filter
        }
    }
</script>
    </body>
</html>