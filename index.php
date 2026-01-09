<?php
require 'function.php';
require 'cek.php';


// Assuming you have the user ID stored in a session variable
// For example: $_SESSION['empid']
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

            <!-- Navbar-->
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
                        <h1 class="mt-4">Barang Masuk</h1>
               
                       
                        <div class="card mb-4">
                            <div class="card-header">
                                <!-- Button to Open the Modal -->
                                 <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#myModal">
                                 Tambah Barang
                                </button>

                    <a href="report_barang_stock.php" class="btn btn-secondary">Stock</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
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
                                                <th>Waranty Year</th>
                                                <th>Asset No</th>
                                                <th>Input Date</th>
                                                <th>Input by Emp ID</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <?php
                                            $ambilsemuadatamasuk = mysqli_query($conn,"select * from masuk");
                                            $i = 1;
                                            while($data=mysqli_fetch_array($ambilsemuadatamasuk)){
                                                $assettype = $data['assettype'];
                                                $itemcode = $data['itemcode'];
                                                $itemdetail = $data['itemdetail'];
                                                $itemserialno = $data['itemserialno'];
                                                $pono = $data['pono'];
                                                $poitemno = $data['poitemno'];
                                                $podate = $data['podate'];
                                                $popriceunit = $data['popriceunit'];
                                                $vendorcode = $data['vendorcode'];
                                                $goodreceiptdate = $data['goodreceiptdate'];
                                                $warantyyear = $data['warantyyear'];
                                                $assetno = $data['assetno'];
                                                $inputdate = $data['inputdate'];
                                                $inputbyempid = $data['inputbyempid'];
                                                $idb = $data['idbarang'];
                                            
                                            ?>

                                            <tr>
                                                <td><?=$assettype;?></td>
                                                <td><?=$itemcode;?></td>
                                                <td><?=$itemdetail;?></td>
                                                <td><?=$itemserialno;?></td>
                                                <td><?=$pono;?></td>
                                                <td><?=$poitemno;?></td>
                                                <td><?=$podate;?></td>
                                                <td><?=$popriceunit;?></td>
                                                <td><?=$vendorcode;?></td>
                                                <td><?=$goodreceiptdate;?></td>
                                                <td><?=$warantyyear;?></td>
                                                <td><?=$assetno;?></td>
                                                <td><?=$inputdate;?></td>
                                                <td><?=$inputbyempid;?></td>
                                                <td>
                                                <div class="btn-group" role="group" aria-label="Button group">
                                                <div class="btn-group" role="group" aria-label="Button group">
                                                <br>
    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#edit<?=$idb;?>">
    Edit
</button>

<?php if (in_array($assettype, ['MOUSE'])): ?>
    <button type="button" class="btn btn-danger" onclick="window.location.href='labelkecil.php?id=<?php echo htmlspecialchars($data['assetno']); ?>'">
        Label
    </button>
<?php elseif (in_array($assettype, ['KEYBOARD', 'CCTV', 'FAN', 'THINCLIENT', 'MONITOR', 'PRINTER', 'STORAGE', 'BATTERY', 'SWITCH', 'DESKTOP', 
'MOTHERBOARD', 'TV DISPLAY', 'AUDIO DEVICE', 'CABLE NETWORK', 'LAPTOP', 'NIC', 'PLOTTER', 'POWER SUPPLY', 'PROCESSOR', 'PROJECTOR', 'RAM', 'SCANNER', 'SERVER', 'WIFI', 'OTHERS'])): ?>
    <button type="button" class="btn btn-danger" onclick="window.location.href='label.php?id=<?php echo htmlspecialchars($data['assetno']); ?>'">
        Label
    </button>
<?php endif; ?>


</div>

<style>
.btn-group .btn {
    margin-right: 5px; /* Atur jarak yang diinginkan */
}
</style>
                                            </div>
                                            </td>

                                            </tr>
                                            

                                                        <!-- Edit Modal -->
                                                        <div class="modal fade" id="edit<?=$idb;?>">
                                                        <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            
                                                            <!-- Modal Header -->
                                                            <div class="modal-header">
                                                            <h4 class="modal-title">Edit Barang Masuk</h4>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                            </div>
                                                                
                                                            <!-- Modal body -->
                                                            <form method="post">
                                                            <div class="modal-body">
                                                            </select>
                                                                <br>
                                                                <label for="assettype">Asset Type:</label>
                                                            <select name="assettype" class="form-control" required>
                                                                <option value="">Select Asset Type</option>
                                                                <option value="KEYBOARD">KEYBOARD</option>
                                                                <option value="MOUSE">MOUSE</option>
                                                                <option value="CCTV">CCTV</option>
                                                                <option value="FAN">FAN</option>
                                                                <option value="THINCLIENT">THINCLIENT</option>
                                                                <option value="MONITOR">MONITOR</option>
                                                                <option value="PRINTER">PRINTER</option>
                                                                <option value="STORAGE">STORAGE</option>
                                                                <option value="BATTERY">BATTERY</option>
                                                                <option value="SWITCH">SWITCH</option>
                                                                <option value="DESKTOP">DESKTOP</option>
                                                                <option value="MOTHERBOARD">MOTHERBOARD</option>
                                                                <option value="TV DISPLAY">TV DISPLAY</option>
                                                                <option value="AUDIO DEVICE">AUDIO DEVICE</option>
                                                                <option value="CABLE NETWORK">CABLE NETWORK</option>
                                                                <option value="LAPTOP">LAPTOP</option>
                                                                <option value="NIC">NIC</option>
                                                                <option value="PLOTTER">PLOTTER</option>
                                                                <option value="POWER SUPPLY">POWER SUPPLY</option>
                                                                <option value="PROCESSOR">PROCESSOR</option>
                                                                <option value="PROJECTOR">PROJECTOR</option>
                                                                <option value="RAM">RAM</option>
                                                                <option value="SCANNER">SCANNER</option>
                                                                <option value="SERVER">SERVER</option>
                                                                <option value="WIFI">WIFI</option>
                                                                <option value="OTHERS">OTHERS</option>
                                                            </select>
                                                            <br>

                                                            <label for="Item Code">Item Code:</label>
                                                            <input type="char" name="itemcode" value="<?=$itemcode;?>" class="form-control" required>
                                                            <br>

                                                            <label for="Item Detail">Item Detail:</label>
                                                            <input type="char" name="itemdetail" value="<?=$itemdetail;?>" class="form-control" required>
                                                            <br>

                                                            <label for="Item Serial No">Item Serial No:</label>
                                                            <input type="char" name="itemserialno" value="<?=$itemserialno;?>" class="form-control" required>
                                                            <br>

                                                            <label for="PO No">PO No:</label>
                                                            <input type="number" name="pono" value="<?=$pono;?>" class="form-control" required>
                                                            <br>

                                                            <label for="PO Item No">PO Item No:</label>
                                                            <input type="number" name="poitemno" value="<?=$poitemno;?>" class="form-control" required>
                                                            <br>

                                                            <label for="PO Date">PO Date:</label>
                                                            <input type="date" name="podate" value="<?=$podate;?>" class="form-control" required>
                                                            <br>

                                                            <label for="PO Price Unit">PO Price Unit:</label>
                                                            <input type="char" name="popriceunit" value="<?=$popriceunit;?>" class="form-control" required>
                                                            <br>

                                                            <label for="Vendor Code">Vendor Code:</label>
                                                            <select name="vendorcode" class="form-control" required>
                                                                <option value="Vendor1">Vendor 1</option>
                                                                <option value="Vendor2">Vendor 2</option>
                                                                <option value="Vendor3">Vendor 3</option>
                                                            </select>
                                                            <br>

                                                            <label for="Good Receipt Date">Good Receipt Date:</label>
                                                            <input type="date" name="goodreceiptdate" value="<?=$goodreceiptdate;?>" class="form-control" required>
                                                            <br>

                                                            <label for="Waranty Year">Warranty Year:</label>
                                                            <input type="char" name="warantyyear" value="<?=$warantyyear;?>" class="form-control" required>
                                                            <br>

                                                            <input type="hidden" name="inputbyempid" value="<?php echo $_SESSION['empid']; ?>" class="form-control" required>
                                                            <br>
                                                            <input type="hidden" name="idb" value="<?=$idb;?>">
                                                            <button type="submit" class="btn btn-primary" name="updatebarangmasuk">Submit</button>
                                                            </div>
                                                            </form>
                                                        
                                                    </div>
                                                    </div>
                                                </div>

                                            </div>

                                            </div>





                                            <?php
                                            };

                                            ?>
         
                                        </tbody>
                                    </table>
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
        <script src="jscss/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
        <script src="jscss/bootstrap4.5.3.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="jscss/Chart2.8.0.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="jscss/jquery1.10.20.dataTables.min.js" crossorigin="anonymous"></script>
        <script src="jscss/dataTables1.10.20.bootstrap4.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/datatables-demo.js"></script>
    </body>


            <!-- The Modal -->
        <div class="modal fade" id="myModal">
        <div class="modal-dialog">
        <div class="modal-content">
            
            <!-- Modal Header -->
            <div class="modal-header">
            <h4 class="modal-title">Tambah Barang</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            
                
            <!-- Modal body -->
            <form method="post">
            <div class="modal-body">
            <?php
                    $ambilsemuadatanya = mysqli_query($conn,"select * from masuk");
                    while($fetcharray = mysqli_fetch_array($ambilsemuadatanya)){
                        $assettype = $fetcharray['assettype'];
                        $itemcode = $fetcharray['itemcode'];
                        $itemdetail = $fetcharray['itemdetail'];
                        $itemserialno = $fetcharray['itemserialno'];
                        $pono = $fetcharray['pono'];
                        $poitemno = $fetcharray['poitemno'];
                        $podate = $fetcharray['podate'];
                        $popriceunit = $fetcharray['popriceunit'];
                        $vendorcode = $fetcharray['vendorcode'];
                        $goodreceiptdate = $fetcharray['goodreceiptdate'];
                        $warantyyear = $fetcharray['warantyyear'];
                        $assetno = $fetcharray['assetno'];
                        

                    }
                ?>
            </select>
            <br>
            <label for="assettype">Asset Type:</label>
        <select name="assettype" class="form-control" required>
            <option value="">Select Asset Type</option>
            <option value="KEYBOARD">KEYBOARD</option>
            <option value="MOUSE">MOUSE</option>
            <option value="CCTV">CCTV</option>
            <option value="FAN">FAN</option>
            <option value="THINCLIENT">THINCLIENT</option>
            <option value="MONITOR">MONITOR</option>
            <option value="PRINTER">PRINTER</option>
            <option value="STORAGE">STORAGE</option>
            <option value="BATTERY">BATTERY</option>
            <option value="SWITCH">SWITCH</option>
            <option value="DESKTOP">DESKTOP</option>
            <option value="MOTHERBOARD">MOTHERBOARD</option>
            <option value="TV DISPLAY">TV DISPLAY</option>
            <option value="AUDIO DEVICE">AUDIO DEVICE</option>
            <option value="CABLE NETWORK">CABLE NETWORK</option>
            <option value="LAPTOP">LAPTOP</option>
            <option value="NIC">NIC</option>
            <option value="PLOTTER">PLOTTER</option>
            <option value="POWER SUPPLY">POWER SUPPLY</option>
            <option value="PROCESSOR">PROCESSOR</option>
            <option value="PROJECTOR">PROJECTOR</option>
            <option value="RAM">RAM</option>
            <option value="SCANNER">SCANNER</option>
            <option value="SERVER">SERVER</option>
            <option value="WIFI">WIFI</option>
            <option value="OTHERS">OTHERS</option>
        </select>
        <br>

        <label for="Item Code">Item Code:</label>
        <input type="char" name="itemcode" class="form-control" placeholder="Item Code" required>
        <br>

        <label for="Item Detail">Item Detail:</label>
        <input type="char" name="itemdetail" class="form-control" placeholder="Item Detail" required>
        <br>

        <label for="Item Serial No">Item Serial No:</label>
        <input type="char" name="itemserialno" class="form-control" placeholder="Item Serial No" required>
        <br>

        <label for="pono">PO No:</label>
        <input type="number" name="pono" class="form-control" placeholder="PO No" required>
        <br>

        <label for="poitemno">PO Item No:</label>
        <input type="number" name="poitemno" class="form-control" placeholder="PO Item No" required>
        <br>

        <label for="podate">PO Date:</label>
        <input type="date" name="podate" class="form-control" placeholder="PO Date" required>
        <br>

        <label for="popriceunit">PO Price Unit:</label>
        <input type="char" name="popriceunit" class="form-control" placeholder="PO Price Unit" required>
        <br>

        <label for="vendorcode">Vendor Code:</label>
        <select name="vendorcode" class="form-control" required>
            <option value="">Select Vendor Code</option>
            <option value="Vendor1">Vendor 1</option>
            <option value="Vendor2">Vendor 2</option>
            <option value="Vendor3">Vendor 3</option>
        </select>
        <br>

        <label for="goodreceiptdate">Good Receipt Date:</label>
        <input type="date" name="goodreceiptdate" class="form-control" placeholder="Good Receipt Date" required>
        <br>

        <label for="warantyyear">Warranty Year:</label>
        <input type="char" name="warantyyear" class="form-control" placeholder="Waranty Year" required>
        <br>

        <label for="qty">Quantity:</label>
        <input type="number" name="qty" class="form-control" placeholder="Quantity" required>
        <br>

        <input type="hidden" name="inputbyempid" value="<?php echo $_SESSION['empid']; ?>" class="form-control" required>
        <br>

        <button type="submit" class="btn btn-primary" name="addnewbarang">Submit</button>
    </div>
</form>
      </div>
    </div>
  </div>
</html>