<?php
session_start();

// Create a connection to the database
$conn = mysqli_connect("localhost", "root", "", "inventory");

function getLastAssetNo($conn) {
    $query = "SELECT assetno FROM masuk ORDER BY assetno DESC LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval(substr($row['assetno'], -3)); // Get the last 3 digits
    }
    return 0; // Return 0 if no data
}

function generateAssetNumbers($qty, $lastNumber) {
    $assetNumbers = [];
    $year = date('y');
    $month = date('m');
    $day = date('d');

    for ($i = 1; $i <= $qty; $i++) {
        // Increment last number
        $increment = $lastNumber + $i;
        $assetno = "{$year}{$month}{$day}" . sprintf('%03d', $increment);
        $assetNumbers[] = $assetno;
    }

    return $assetNumbers;
}

// Adding new items
if (isset($_POST['addnewbarang'])) {
    $assettype = $_POST['assettype'];
    $itemcode = $_POST['itemcode'];
    $itemdetail = $_POST['itemdetail'];
    $itemserialno = $_POST['itemserialno'];
    $pono = $_POST['pono'];
    $poitemno = $_POST['poitemno'];
    $podate = $_POST['podate'];
    $popriceunit = $_POST['popriceunit'];
    $vendorcode = $_POST['vendorcode'];
    $goodreceiptdate = $_POST['goodreceiptdate'];
    $warantyyear = $_POST['warantyyear'];
    $qty = $_POST['qty'];

    // Get the last asset number from the entire database
    $lastNumber = getLastAssetNo($conn);

    // Generate asset numbers based on quantity
    $generatedAssetNumbers = generateAssetNumbers($qty, $lastNumber);

    // Insert each asset number into the database
    foreach ($generatedAssetNumbers as $assetno) {
        $query = "INSERT INTO masuk (assettype, itemcode, itemdetail, itemserialno, pono, poitemno, podate, popriceunit, vendorcode, goodreceiptdate, warantyyear, assetno, inputdate, inputbyempid)
                  VALUES ('$assettype', '$itemcode', '$itemdetail', '$itemserialno', '$pono', '$poitemno', '$podate', '$popriceunit', '$vendorcode', '$goodreceiptdate', '$warantyyear', '$assetno', NOW(), '".$_SESSION['empid']."')";
        
        if (!mysqli_query($conn, $query)) {
            echo "Error: " . mysqli_error($conn);
        }
    }

    header("Location: index.php");
    exit();
}

// Update item
if (isset($_POST['updatebarangmasuk'])) {
    $idbarang = $_POST['idb'];
    $assettype = $_POST['assettype'];
    $itemcode = $_POST['itemcode'];
    $itemdetail = $_POST['itemdetail'];
    $itemserialno = $_POST['itemserialno'];
    $pono = $_POST['pono'];
    $poitemno = $_POST['poitemno'];
    $podate = $_POST['podate'];
    $popriceunit = $_POST['popriceunit'];
    $vendorcode = $_POST['vendorcode'];
    $goodreceiptdate = $_POST['goodreceiptdate'];
    $warantyyear = $_POST['warantyyear'];

    $update = mysqli_query($conn, "UPDATE masuk SET assettype='$assettype', itemcode='$itemcode', itemdetail='$itemdetail', itemserialno='$itemserialno', pono='$pono', poitemno='$poitemno', podate='$podate', popriceunit='$popriceunit', vendorcode='$vendorcode', goodreceiptdate='$goodreceiptdate', warantyyear='$warantyyear' WHERE idbarang ='$idbarang'");
    if ($update) {
        header('location:index.php');
    } else {   
        echo 'Gagal';
        header('location:index.php');
    }
}
?>