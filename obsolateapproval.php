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

// Initialize the session variable for selected assets if not set
if (!isset($_SESSION['selected_assets_obsolate'])) {
    $_SESSION['selected_assets_obsolate'] = [];
}

// Proses pengesahan dan penolakan
if (isset($_GET['action']) && isset($_GET['assetno'])) {
    $assetno = $_GET['assetno'];

    if ($_GET['action'] == 'approve') {
        // Update the status to Approved in the masuk table
        $updateQuery = "UPDATE masuk SET obsolateapproval = 'Approved' WHERE assetno = '$assetno'";
        
        if (mysqli_query($conn, $updateQuery)) {
            // Update the rusak table for the specific asset
            $insertTempQuery = "UPDATE rusak SET statusobsolate = 'Obsolate' WHERE assetno = '$assetno' AND statusobsolate = 'Pending'";
            mysqli_query($conn, $insertTempQuery);

            // Add the asset number to selected assets
            $_SESSION['selected_assets_obsolate'][] = $assetno;
            $_SESSION['notification'] = 'Data berhasil disetujui dan status obsolate berubah menjadi Approved.';
        } else {
            $_SESSION['notification'] = 'Error: ' . mysqli_error($conn);
        }

        header('Location: obsolateapproval.php'); // Redirect to the approval page
        exit();
    } elseif ($_GET['action'] == 'reject' && isset($_POST['reject_reason'])) {
        // Get the reason from the POST data
        $rejectReason = mysqli_real_escape_string($conn, $_POST['reject_reason']);

        // Reject the request by updating the status
        $deleteQuery = "UPDATE masuk SET obsolatedate = NULL, obsolateby = NULL, obsolatedocno = NULL, obsolateapproval = NULL, remarkapprovalreject = '$rejectReason' WHERE assetno = '$assetno'";
        
        if (mysqli_query($conn, $deleteQuery)) {
            // Update the rusak table for the specific asset
            $updateRusakQuery = "UPDATE rusak SET statusobsolate = 'Rejected', remarkapprovalreject = '$rejectReason' WHERE assetno = '$assetno'";
            mysqli_query($conn, $updateRusakQuery);

            $_SESSION['notification'] = 'Data berhasil ditolak dan status obsolate menjadi Rejected.';
        } else {
            $_SESSION['notification'] = 'Error: ' . mysqli_error($conn);
        }

        header('Location: obsolateapproval.php'); // Redirect to the approval page
        exit();
    }
}

// Handle asset removal from selected assets
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_asset'])) {
    $assetToRemove = $_POST['remove_asset'];
    if (($key = array_search($assetToRemove, $_SESSION['selected_assets_obsolate'])) !== false) {
        unset($_SESSION['selected_assets_obsolate'][$key]); // Remove the asset from the session variable
    }
}

// Handle obsolate action
if (isset($_POST['obsolate']) && !empty($_SESSION['selected_assets_obsolate'])) {
    $currentDate = date('Y-m-d');
    $docNoPrefix = date('Ymd'); // Format: YYYYMMDD

    // Fetch the latest docno for today
    $latestDocNoQuery = "SELECT obsolatedocno FROM masuk WHERE obsolatedocno LIKE '$docNoPrefix%' ORDER BY obsolatedocno DESC LIMIT 1";
    $latestDocNoResult = mysqli_query($conn, $latestDocNoQuery);
    $latestDocNo = mysqli_fetch_assoc($latestDocNoResult);

    // Generate new docno
    if ($latestDocNo) {
        $latestDocNum = intval(substr($latestDocNo['obsolatedocno'], -2)); // Get the last two digits
        $newDocNo = $docNoPrefix . sprintf('%02d', $latestDocNum + 1); // Increment and format as two digits
    } else {
        $newDocNo = $docNoPrefix . '01'; // Start with 01 if no previous docno exists
    }

    // Update each selected asset with the new obsolatedocno
    foreach ($_SESSION['selected_assets_obsolate'] as $assetno) {
        $updateDocNoQuery = "UPDATE masuk SET obsolatedocno = '$newDocNo' WHERE assetno = '$assetno'";
        mysqli_query($conn, $updateDocNoQuery);
    }

    // Clear selected assets after obsolating
    $_SESSION['selected_assets_obsolate'] = [];

    $_SESSION['notification'] = 'Obsolate action completed with Doc No: ' . $newDocNo;
    $_SESSION['new_doc_no'] = $newDocNo; // Simpan nomor dokumen baru di sesi
    header('Location: obsolateapproval.php'); // Redirect to the approval page
    exit();
}

// Fetch approval requests that are still pending
$requests = mysqli_query($conn, "SELECT * FROM masuk WHERE obsolateapproval = 'Pending'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Inventory System Management</title>
    <link href="jscss/bootstrap4.5.2.min.css" rel="stylesheet" />
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

        <?php if (isset($_SESSION['new_doc_no'])): ?>
            <div class="alert alert-success">
                Nomor Dokumen Baru: <?php echo htmlspecialchars($_SESSION['new_doc_no']); ?>
                <?php unset($_SESSION['new_doc_no']); // Clear the new doc no after displaying ?>
            </div>
        <?php endif; ?>

        <a href="report_barang_obsolate.php" class="btn btn-primary mb-3">Back</a>

        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Asset No</th>
                    <th>Asset Type</th>
                    <th>Obsolate Date</th>
                    <th>Obsolate By</th>
                    <th>Remark</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($requests)) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['assetno']); ?></td>
                        <td><?php echo htmlspecialchars($row['assettype']); ?></td>
                        <td><?php echo htmlspecialchars($row['obsolatedate']); ?></td>
                        <td><?php echo htmlspecialchars($row['obsolateby']); ?></td>
                        <td><?php echo htmlspecialchars($row['failedremark']); ?></td>
                        <td>
                            <a href="?action=approve&assetno=<?php echo $row['assetno']; ?>" class="btn btn-success btn-sm">Approve</a>
                            <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#rejectModal<?php echo $row['assetno']; ?>">Reject</button>
                            
                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal<?php echo $row['assetno']; ?>" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="rejectModalLabel">Reason for Rejection</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="?action=reject&assetno=<?php echo $row['assetno']; ?>">
                                                <div class="form-group">
                                                    <label for="reject_reason">Alasan Penolakan:</label>
                                                    <textarea class="form-control" name="reject_reason" required></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-danger">Submit</button>
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

        <h3>Detail Asset yang Dipilih</h3>
        <?php if (!empty($_SESSION['selected_assets_obsolate'])): ?>
            <form method="post" action="">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Asset No</th>
                            <th>Obsolate Date</th>
                            <th>Obsolate By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['selected_assets_obsolate'] as $assetno): ?>
                            <?php
                            // Fetch asset details for the selected asset
                            $assetQuery = "SELECT * FROM masuk WHERE assetno = '$assetno'";
                            $assetResult = mysqli_query($conn, $assetQuery);
                            $assetRow = mysqli_fetch_assoc($assetResult);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assetRow['assetno']); ?></td>
                                <td><?php echo htmlspecialchars($assetRow['obsolatedate']); ?></td>
                                <td><?php echo htmlspecialchars($assetRow['obsolateby']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="obsolate" class="btn btn-warning">Obsolate</button>
            </form>
        <?php else: ?>
            <p>Tidak ada aset yang dipilih untuk di-obsolate.</p>
        <?php endif; ?>
    </div>

    <script src="jscss/jquery-3.5.1.slim.min.js"></script>
    <script src="jscss/bootstrap4.5.3.bundle.min.js"></script>
</body>
</html>