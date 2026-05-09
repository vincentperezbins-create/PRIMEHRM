<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the QR code data from the POST request
    $qrCodeData = $_POST['qrCodeData'];

    // Redirect to another PHP file with the captured data
    header("Location: qr.php?data=" . urlencode($qrCodeData));
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Code Data Capture</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.1.0/html5-qrcode.min.js"></script>
</head>
<body>
    <h1>QR Code Data Capture</h1>
    <div id="reader" style="height: 400px; width: 400px;"></div>

    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="text" name="qrCodeData" placeholder="Enter QR Code Data">
        <button type="submit">Submit</button>
    </form>

    <script>
        function onScanSuccess(qrCodeMessage) {
            // Redirect or process the QR code message as needed
            console.log('Scanned QR code:', qrCodeMessage);

            // Fill the input field with the QR code data
            const qrCodeInput = document.querySelector('input[name="qrCodeData"]');
            qrCodeInput.value = qrCodeMessage;

            // You can perform further processing here, such as sending the QR code message to the server
            // using AJAX or navigating to a new page with the scanned data.
        }

        const html5QrCode = new Html5Qrcode("reader");

        html5QrCode.start(
            { facingMode: "environment" }, // camera facing mode, "environment" for back camera
            { qrbox: 250 }, // size of QR code scanning area
            onScanSuccess
        );
    </script>
</body>
</html>