<?php
require 'function.php';
require 'cek.php';

$obsolatedocno = $_GET['id'] ?? '';

$sql = "SELECT assetno, assettype, itemserialno, obsolateby, obsolatedate, failedremark 
        FROM masuk 
        WHERE obsolatedocno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $obsolatedocno);
$stmt->execute();
$result = $stmt->get_result();

// Ambil nomor dokumen untuk ditampilkan
$documentNumber = $obsolatedocno; // Menggunakan obsolatedocno sebagai nomor dokumen
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>PT. INDO BHARAT RAYON - Asset Disposal Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            border: 1px solid #000; /* Border will be applied in print */
        }

        .text-center {
            text-align: center;
        }

        .text-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .left-side, .right-side {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .right-side {
            align-items: flex-end; /* Align right-side items to the right */
        }

        .text-container h4 {
            margin: 0;
            font-size: 16px;
            font-weight: normal;
        }

        .main-heading {
            font-size: 24px;
        }

        .document-number {
            font-size: 18px; /* Smaller font size for document number */
            font-weight: normal; /* Not bold */
        }

        .table-responsive {
            margin-top: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="text-center">
        <h2 class="main-heading">PT. INDO BHARAT RAYON</h2>
        <h4 class="main-heading">ASSET DISPOSAL APPLICATION</h4>
        <h4 class="document-number">DOCUMENT NO: <?php echo htmlspecialchars($documentNumber); ?></h4> <!-- Menampilkan nomor dokumen -->
    </div>

    <div class="text-container">
        <div class="left-side">
            <h4>DEPARTMENT: IT</h4>
            <h4>LOCATION: IT Office / PURWAKARTA</h4>
        </div>
        <div class="right-side">
            <h4 id="current-date">DATE: <?php echo date('d M Y'); ?></h4>
        </div>
    </div>

    <div class="table-responsive">
        <table id="mauexport">
            <thead>
                <tr>
                    <th>Asset No</th>
                    <th>Asset Type</th>
                    <th>Item Serial No</th>
                    <th>Obsolate By</th>
                    <th>Obsolate Date</th>
                    <th>Reasons for Disposal</th>
                </tr>
            </thead>
            <tbody id="data-body">
                <?php
                if ($result) {
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['assetno']}</td>
                                    <td>{$row['assettype']}</td>
                                    <td>{$row['itemserialno']}</td>
                                    <td>{$row['obsolateby']}</td>
                                    <td>{$row['obsolatedate']}</td>
                                    <td>{$row['failedremark']}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No data available</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='error'>Error fetching data: " . $conn->error . "</td></tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>

    <div class="text-container">
        <div class="left-side">
            <h4>MODE OF DISPOSAL</h4>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <tbody>
                <tr>
                    <td>SOLD AS SCRAP</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>WRITE OFF AT ZERO VALUE REALIZATION</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>OTHERS</td>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>

    <br />
    <div class="text-container">
        <div class="left-side">
            <h4 style="margin-bottom: 100px">PREPARED BY</h4>
            <h4 style="margin-bottom: 100px">RECEIVED BY</h4>
        </div>
        <div class="right-side">
            <h4 style="margin-bottom: 100px">AUTHORIZED BY</h4>
            <h4 style="margin-bottom: 100px">APPROVED BY</h4>
        </div>
    </div>
    
    <div class="button-container">
        <button class="btn" id="print-report">Print</button>
    </div>
    <script>
    document.getElementById('print-report').addEventListener('click', function() {
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write('<html><head><title>Print Report Obsolate</title>');
        printWindow.document.write('<style>body { font-family: Arial, sans-serif; margin: 0; padding: 20px; border: 1px solid #000; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #000; padding: 8px; text-align: left; } th { background-color: #f2f2f2; } .text-center { text-align: center; } .text-container { display: flex; justify-content: space-between; }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<div class="text-center"><h2 class="main-heading">PT. INDO BHARAT RAYON</h2><h4 class="main-heading">ASSET DISPOSAL APPLICATION</h4><h4 class="document-number">DOCUMENT NO: <span style="font-weight: normal;">' + <?php echo json_encode($documentNumber); ?> + '</span></h4></div>');
        printWindow.document.write('<div class="text-container"><div class="left-side"><h4>DEPARTMENT: IT</h4><h4>LOCATION: IT Office / PURWAKARTA</h4></div>');
        printWindow.document.write('<div class="right-side"><h4>DATE: ' + new Date().toLocaleDateString() + '</h4></div></div>');
        printWindow.document.write(document.getElementById('mauexport').outerHTML);
        printWindow.document.write('<h4>MODE OF DISPOSAL</h4>');
        printWindow.document.write('<table><tbody><tr><td>SOLD AS SCRAP</td><td>&nbsp;</td></tr><tr><td>WRITE OFF AT ZERO VALUE REALIZATION</td><td>&nbsp;</td></tr><tr><td>OTHERS</td><td>&nbsp;</td></tr></tbody></table>');
        printWindow.document.write('<div class="text-container"><div class="left-side"><h4 style="margin-bottom: 100px">PREPARED BY</h4><h4 style="margin-bottom: 100px">RECEIVED BY</h4></div><div class="right-side" style="text-align: right;"><h4 style="margin-bottom: 100px">AUTHORIZED BY</h4><h4 style="margin-bottom: 100px">APPROVED BY</h4></div></div>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    });
    </script>
    <script>
        const timeoutDuration = 300; // 5 menit
        const timeoutInMilliseconds = timeoutDuration * 1000;

        let timeout;

        function resetTimer() {
            clearTimeout(timeout);
            timeout = setTimeout(logout, timeoutInMilliseconds);
        }

        function logout() {
            window.location.href = 'logout.php';
        }

        window.onload = resetTimer;
        window.onmousemove = resetTimer;
        window.onkeypress = resetTimer;
        window.ontouchstart = resetTimer;
    </script>
</body>
</html>