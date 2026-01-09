<?php
require 'function.php';
require 'cek.php';

// Fungsi untuk mendapatkan jumlah barang keluar sementara yang terkelompok berdasarkan tilldate
function getTemporaryOutgoingCountsByTillDate($conn) {
    $query = "SELECT DATE(tilldate) AS till_date, COUNT(*) AS total 
          FROM masuk 
          WHERE usedapproval = 'Approved' 
            AND tilldate IS NOT NULL 
            AND tilldate <> '0000-00-00' 
          GROUP BY DATE(tilldate) 
          ORDER BY DATE(tilldate)";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        die("Query gagal: " . mysqli_error($conn));
    }

    $temporaryOutgoingCounts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $temporaryOutgoingCounts[$row['till_date']] = (int)$row['total'];
    }
    
    return $temporaryOutgoingCounts;
}

// Fungsi untuk mendapatkan jumlah barang rusak berdasarkan assettype
function getDamagedCountsByAssetType($conn) {
    $query = "SELECT assettype, COUNT(*) AS total 
              FROM masuk 
              WHERE returnfaileddate IS NOT NULL 
              AND returnfaileddate <> '0000-00-00' 
              GROUP BY assettype";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        die("Query gagal: " . mysqli_error($conn));
    }

    $damagedCounts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $damagedCounts[$row['assettype']] = (int)$row['total'];
    }
    
    return $damagedCounts;
}

// Mengambil jumlah barang rusak berdasarkan assettype
$damagedCountsByAssetType = getDamagedCountsByAssetType($conn);

// Fungsi untuk mendapatkan jumlah barang per hari untuk bulan tertentu
function getDailyCountsByMonth($conn, $dateField, $tableName, $month, $year) {
    $query = "SELECT DAY($dateField) AS day, COUNT(*) AS total 
              FROM $tableName 
              WHERE MONTH($dateField) = $month AND YEAR($dateField) = $year 
              GROUP BY DAY($dateField) 
              ORDER BY DAY($dateField)";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        die("Query gagal: " . mysqli_error($conn));
    }

    $dailyCounts = array_fill(1, 31, 0); // Initialize array for 31 days
    while ($row = mysqli_fetch_assoc($result)) {
        $dailyCounts[$row['day']] = $row['total'];
    }
    
    return $dailyCounts;
}

// Fungsi untuk mendapatkan jumlah barang yang perlu disetujui
function getApprovalCount($conn) {
     $query = "SELECT COUNT(*) AS total FROM masuk 
              WHERE usedapproval = 'Pending' OR obsolateapproval = 'Pending'";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        die("Query gagal: " . mysqli_error($conn));
    }

    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Fungsi untuk mendapatkan total stok barang berdasarkan assettype
function getStockCountsByAssetType($conn) {
    $query = "SELECT assettype, COUNT(*) AS total 
              FROM masuk 
              WHERE useddate IS NULL 
              AND returnfaileddate IS NULL 
              AND obsolatedate IS NULL 
              AND inputdate IS NOT NULL 
              GROUP BY assettype";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        die("Query gagal: " . mysqli_error($conn));
    }

    $stockCounts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stockCounts[$row['assettype']] = (int)$row['total'];
    }
    
    return $stockCounts;
}

// Mengambil bulan dan tahun saat ini
$currentMonth = date('n'); // Bulan saat ini (1-12)
$currentYear = date('Y'); // Tahun saat ini

// Mengambil total berdasarkan kriteria yang sesuai untuk bulan ini
$dailyBarangMasuk = getDailyCountsByMonth($conn, 'inputdate', 'masuk', $currentMonth, $currentYear);
$dailyBarangKeluar = getDailyCountsByMonth($conn, 
    !empty($useddate) ? 'useddate' : 'temporarydate', 
    'keluar', 
    $currentMonth, 
    $currentYear
);
$stockCountsByAssetType = getStockCountsByAssetType($conn);
$approvalCount = getApprovalCount($conn);
$temporaryOutgoingCounts = getTemporaryOutgoingCountsByTillDate($conn); // Hitung jumlah barang keluar sementara berdasarkan tilldate

$userId = $_SESSION['empid'] ?? 'Guest'; // Default to 'Guest' if not set
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
    <link href="jscss/bootstrap.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="jscss/all.min.js" crossorigin="anonymous"></script>
    <script src="jscss/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        nav {
            background-color: #343a40;
            color: white;
            padding: 15px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        h2 {
            margin: 0;
        }
        main {
            margin-top: 20px;
        }
        h3 {
            color: #343a40;
        }
        .approval-count {
            font-weight: bold;
            color: #dc3545;
            white-space: nowrap;
            position: absolute;
            left: 45%;
            width: 10%;
            transform: translate(20%, 0);
            animation: scroll 5s linear infinite;
            padding: 10 50px;
        }
        @keyframes scroll {
            0% {
                transform: translateX(100%);
            }
            50% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 35vh;
            width: 100%;
            margin-bottom: -40px;
        }
        .chart-wrapper {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 40px;
        }
        ..chart-column {
            lex: 1 1 45%; /* Grafik akan menyesuaikan ukuran hingga 45% dari lebar layar */
        max-width: 45%; /* Pastikan maksimal 45% */
        min-width: 300px; /* Grafik memiliki ukuran minimum */
        margin: 0 auto;
        }
        .chart-column.full-width {
        flex: 1 1 100%; /* Grafik akan memakan seluruh lebar saat diberi kelas ini */
        max-width: 100%;
    }
        .chart-column:last-child {
            margin-right: 0;
        }
        .logo {
            height: 40px;
            margin-left: auto;
        }
        
    </style>
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
        <span class="approval-count">Menunggu Approval: <?php echo $approvalCount; ?>, Barang Keluar(Temporary): <?php echo array_sum($temporaryOutgoingCounts); ?></span>
        <img src="logo.bmp" alt="Logo" class="logo">
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
        <!-- Grafik Barang Masuk, Keluar, dan Rusak -->
        <div class="chart-wrapper">
            <div class="chart-column">
                <h3>Total Barang Masuk per Hari</h3>
                <div class="chart-container">
                    <canvas id="barangMasukChart"></canvas>
                </div>
            </div>
            <div class="chart-column">
                <h3>Total Barang Keluar per Hari</h3>
                <div class="chart-container">
                    <canvas id="barangKeluarChart"></canvas>
                </div>
            </div>
            <div class="chart-column">
                <h3>
                Total Barang Rusak</h3>
                <div class="chart-container">
                    <canvas id="barangRusakChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Grafik Total Stok dan Barang Keluar Temporary -->
        <div class="chart-wrapper">
            <div class="chart-column">
                <h3>Total Stok Barang berdasarkan Tipe Aset</h3>
                <div class="chart-container">
                    <canvas id="stockBarangChart"></canvas>
                </div>
            </div>
            <div class="chart-column">
                <h3>Total Barang Keluar Temporary per Tanggal</h3>
                <div class="chart-container">
                    <canvas id="temporaryOutgoingChart"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>


                <script>
                    const daysInMonth = new Date(<?php echo $currentYear; ?>, <?php echo $currentMonth; ?>, 0).getDate();
                    const days = Array.from({length: daysInMonth}, (_, i) => i + 1);
                    
                    // Data untuk Barang Masuk
                    const barangMasukCounts = <?php echo json_encode(array_values($dailyBarangMasuk)); ?>;
                    const barangMasukCtx = document.getElementById('barangMasukChart').getContext('2d');
                    const barangMasukChart = new Chart(barangMasukCtx, {
                        type: 'bar',
                        data: {
                            labels: days,
                            datasets: [{
                                label: 'Barang Masuk',
                                data: barangMasukCounts,
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Total Barang Masuk per Hari Bulan Ini'
                                }
                            }
                        }
                    });

                    // Data untuk Barang Keluar
                    const barangKeluarCounts = <?php echo json_encode(array_values($dailyBarangKeluar)); ?>;
                    const barangKeluarCtx = document.getElementById('barangKeluarChart').getContext('2d');
                    const barangKeluarChart = new Chart(barangKeluarCtx, {
                        type: 'bar',
                        data: {
                            labels: days,
                            datasets: [{
                                label: 'Barang Keluar',
                                data: barangKeluarCounts,
                                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Total Barang Keluar per Hari Bulan Ini'
                                }
                            }
                        }
                    });

                   // Data untuk Barang Rusak berdasarkan Asset Type
                    const damagedCounts = <?php echo json_encode($damagedCountsByAssetType); ?>;
                    const damagedLabels = Object.keys(damagedCounts);
                    const damagedValues = Object.values(damagedCounts);
                    const barangRusakCtx = document.getElementById('barangRusakChart').getContext('2d');

                    const barangRusakChart = new Chart(barangRusakCtx, {
                        type: 'bar',
                        data: {
                            labels: damagedLabels,
                            datasets: [{
                                label: 'Barang Rusak',
                                data: damagedValues,
                                backgroundColor: 'rgba(255, 206, 86, 0.6)',
                                borderColor: 'rgba(255, 206, 86, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Total Barang Rusak berdasarkan Tipe Aset'
                                }
                            }
                        }
                    });

                    // Data untuk Stok Barang berdasarkan Asset Type
                    const stockCounts = <?php echo json_encode($stockCountsByAssetType); ?>;
                    const stockLabels = Object.keys(stockCounts);
                    const stockValues = Object.values(stockCounts);
                    const stockBarangCtx = document.getElementById('stockBarangChart').getContext('2d');
                    const stockBarangChart = new Chart(stockBarangCtx, {
                        type: 'bar',
                        data: {
                            labels: stockLabels,
                            datasets: [{
                                label: 'Stok Barang',
                                data: stockValues,
                                backgroundColor: 'rgba(153, 102, 255, 0.6)',
                                borderColor: 'rgba(153, 102, 255, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Total Stok Barang berdasarkan Tipe Aset'
                                }
                            }
                        }
                    });

   // Data untuk Barang Keluar Sementara berdasarkan Till Date
const temporaryOutgoingCounts = <?php echo json_encode($temporaryOutgoingCounts); ?>;
const temporaryOutgoingLabels = Object.keys(temporaryOutgoingCounts);
const temporaryOutgoingValues = Object.values(temporaryOutgoingCounts);

const today = new Date();
const todayString = today.toISOString().split('T')[0]; // Format YYYY-MM-DD

const temporaryOutgoingCtx = document.getElementById('temporaryOutgoingChart').getContext('2d');

// Tentukan warna berdasarkan tanggal untuk background
const temporaryOutgoingColors = temporaryOutgoingLabels.map(date => {
    if (date < todayString) {
        return 'rgba(255, 0, 0, 0.6)'; // Merah jika sudah lewat
    } else if (date === todayString) {
        return 'rgba(255, 255, 0, 0.6)'; // Kuning jika hari ini
    } else {
        return 'rgba(0, 255, 0, 0.6)'; // Hijau jika masih di depan
    }
});

// Tentukan warna border berdasarkan tanggal
const temporaryOutgoingBorderColors = temporaryOutgoingLabels.map(date => {
    if (date < todayString) {
        return 'rgba(255, 0, 0, 1)'; // Merah jika sudah lewat
    } else if (date === todayString) {
        return 'rgba(255, 255, 0, 1)'; // Kuning jika hari ini
    } else {
        return 'rgba(0, 255, 0, 1)'; // Hijau jika masih di depan
    }
});

const temporaryOutgoingChart = new Chart(temporaryOutgoingCtx, {
    type: 'bar',
    data: {
        labels: temporaryOutgoingLabels,
        datasets: [{
            label: 'Barang Keluar Temporary',
            data: temporaryOutgoingValues,
            backgroundColor: temporaryOutgoingColors, // Menggunakan warna background yang ditentukan
            borderColor: temporaryOutgoingBorderColors, // Menggunakan warna border yang ditentukan
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Total Barang Keluar Temporary per Tanggal'
            }
        }
    }
});
                </script>
            </main>
        </div>
    </div>

    <!-- Tambahkan ini untuk mengimpor Bootstrap JS -->
    <script src="jscss/jquery-3.3.1.slim.min.js"></script>
    <script src="jscss/popper.min.js"></script>
    <script src="jscss/bootstrap.min.js"></script>
</body>
</html>