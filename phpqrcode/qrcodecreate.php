<?php
require_once('qrlib.php');

// The data to be encoded in the QR code
$data = "2023-06-14 17:58:114uTib,Y8JFH35UZN";
$string = str_replace(':', '_', $data); // Replaces all spaces with hyphens.


// The file path to save the generated QR code image
$filePath = 'image/'.$string.'.png';

// Generate the QR code
QRcode::png($data, $filePath, QR_ECLEVEL_L, 10);
?>

<!DOCTYPE html>
<html>
<head>
    <title>QR Code Generator</title>
</head>
<body>
    <h1>QR Code Generator</h1>

    <div>
        <img src="<?php echo $filePath; ?>" alt="QR Code">

        <br>

        <button onclick="downloadQRCode()">Save Image</button>
    </div>

    <script>
        function downloadQRCode() {
            var link = document.createElement('a');
            link.href = '<?php echo $filePath; ?>';
            link.download = 'qrcode.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
