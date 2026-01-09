<?php
require 'function.php';
require 'cek.php';

// Periksa koneksi
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Cek apakah pengguna adalah admin
if ($_SESSION['rl'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Proses pengesahan dan penolakan
if (isset($_GET['action']) && isset($_GET['assetno'])) {
    $assetno = $_GET['assetno'];

    // Handle approval
    if ($_GET['action'] == 'approve') {
        // Update the status to Approved in the masuk table
        $updateQuery = "UPDATE masuk SET usedapproval = 'Approved' WHERE assetno = '$assetno'";

        if (mysqli_query($conn, $updateQuery)) {
            // Update the keluar table for the specific asset
            $insertTempQuery = "UPDATE keluar SET usedapproval = 'Approved' WHERE assetno = '$assetno' AND usedapproval = 'Pending'";
            mysqli_query($conn, $insertTempQuery);

            // Add the asset number to selected assets
            $_SESSION['notification'] = 'Data berhasil disetujui dan status berubah menjadi Approved.';
        } else {
            $_SESSION['notification'] = 'Error: ' . mysqli_error($conn);
        }

        header('Location: keluarapproval.php'); // Redirect to the approval page
        exit();
    }
}

// Handle rejection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject'])) {
    $assetno = $_POST['assetno'];
    $remark = $_POST['remark'];

    // Prepare the update query for the masuk table
    $updatemasukQuery = "UPDATE masuk SET usedbyempid = NULL, useddate = NULL, usedremark = ?, givenbyempid = NULL, useddocno = NULL, usedapproval = NULL, temporarydate = NULL, tilldate = NULL WHERE assetno = ?";
    $stmtmasuk = $conn->prepare($updatemasukQuery);
    $stmtmasuk->bind_param("ss", $remark, $assetno);

    // Execute the masuk update
    if ($stmtmasuk->execute()) {
        // Prepare the update query for the keluar table
        $updateKeluarQuery = "UPDATE keluar SET usedapproval = 'Reject', remark = ? WHERE assetno = ?";
        $stmtKeluar = $conn->prepare($updateKeluarQuery);
        $stmtKeluar->bind_param("ss", $remark, $assetno);

        // Execute the keluar update
        if ($stmtKeluar->execute()) {
            $_SESSION['notification'] = 'Data berhasil ditolak dengan alasan: ' . htmlspecialchars($remark);
        } else {
            $_SESSION['notification'] = 'Error updating keluar: ' . $stmtKeluar->error;
        }
    } else {
        $_SESSION['notification'] = 'Error updating masuk: ' . $stmtmasuk->error;
    }

    // Redirect to the desired page
    header('Location: keluarapproval.php');
    exit();
}

// Fetch approval requests that are still pending with employee names and department
$requests = mysqli_query($conn, "
    SELECT m.*, e.empname, e.empdept 
    FROM masuk m 
    LEFT JOIN emplist e ON m.usedbyempid = e.empid 
    WHERE m.usedapproval = 'Pending'
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Approval Requests</title>
    <link href="jscss/bootstrap4.5.2.1min.css" rel="stylesheet" />
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Approval Requests</h1>

        <?php if (isset($_SESSION['notification'])): ?>
            <div class="alert alert-info">
                <?php 
                    echo ($_SESSION['notification']);
                    unset($_SESSION['notification']); // Clear the notification after displaying
                ?>
            </div>
        <?php endif; ?>

        <a href="keluar.php" class="btn btn-primary mb-3">Back</a>

        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Asset No</th>
                    <th>Asset Type</th>
                    <th>Used By</th>
                    <th>Status</th>
                    <th>Used Date</th>
                    <th>Temporary Date</th>
                    <th>Remark</th>
                    <th>Given By Emp ID</th>
                    <th>Document No</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($requests)) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['assetno']); ?></td>
                        <td><?php echo htmlspecialchars($row['assettype']); ?></td>
                        <td><?php echo htmlspecialchars($row['usedbyempid']) . ' - ' . htmlspecialchars($row['empname']) . ' - ' . htmlspecialchars($row['empdept']); ?></td> <!-- Format: usedbyempid - empname - empdept -->
                        <td><?php echo htmlspecialchars($row['usedapproval']); ?></td>
                        <td><?php echo htmlspecialchars($row['useddate']); ?></td>
                        <td><?php echo htmlspecialchars($row['temporarydate']); ?></td>
                        <td><?php echo htmlspecialchars($row['usedremark']); ?></td>
                        <td><?php echo htmlspecialchars($row['givenbyempid']); ?></td>
                        <td><?php echo htmlspecialchars($row['useddocno']); ?></td>
                        <td>
                            <a href="?action=approve&assetno=<?php echo $row['assetno']; ?>" class="btn btn-success btn-sm">Approve</a>
                            <br>
                            <br>
                            <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#rejectModal<?php echo $row['assetno']; ?>">Reject</button>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal<?php echo $row['assetno']; ?>" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="rejectModalLabel">Reject Asset No: <?php echo htmlspecialchars($row['assetno']); ?></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="assetno" value="<?php echo htmlspecialchars($row['assetno']); ?>">
                                                <div class="form-group">
                                                    <label for="remark">Alasan Penolakan:</label>
                                                    <textarea class="form-control" name="remark" required></textarea>
                                                </div>
                                                <button type="submit" name="reject" class="btn btn-danger">Tolak</button>
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
    <script src="jscss/jquery-3.5.1.slim.min.js"></script>
    <script src="jscss/poppercore@2.9.2.min.js"></script>
    <script src="jscss/bootstrap4.5.2.min.js"></script>
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
</body>
</html>