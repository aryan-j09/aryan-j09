<?php
$barcodeCode = $_GET['barcode'] ?? '';
$itemName = $_GET['item'] ?? '';
$qrCount = max(1, (int)($_GET['count'] ?? 1));
$mode = $_GET['mode'] ?? 'qty';
$totalQty = (int)($_GET['qty'] ?? 0);
$boxSize = (int)($_GET['box'] ?? 0);
$poId = $_GET['po'] ?? '';

if ($barcodeCode === '') {
    die('Invalid barcode');
}

if ($mode === 'box' && $boxSize <= 0) {
    $boxSize = max(1, $totalQty);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print QR - <?php echo htmlspecialchars($itemName); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; padding: 15px; }
        .qr-container { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; max-width: 1200px; }
        .qr-label { border: 2px solid #000; padding: 10px; text-align: center; page-break-inside: avoid; background: #fff; display: flex; flex-direction: column; justify-content: center; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .item-name { font-size: 9px; font-weight: bold; margin-bottom: 3px; word-wrap: break-word; max-width: 90px; line-height: 1.2; }
        .serial-num { font-size: 11px; font-weight: bold; color: #d9534f; margin: 3px 0; }
        .qr-code { width: 80px; height: 80px; margin: 5px auto; display: flex; align-items: center; justify-content: center; }
        .qr-code > div { width: 100% !important; height: 100% !important; }
        .qr-code > div img { width: 100% !important; height: 100% !important; }
        .barcode-code { font-size: 7px; font-family: 'Courier New', monospace; margin-top: 2px; word-break: break-all; line-height: 1.1; }
        .date { font-size: 7px; color: #666; margin-top: 2px; }
        @media print {
            @page { size: A4; margin: 5mm; }
            body { padding: 5px; }
            .qr-container { gap: 10px; }
            .qr-label { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="qr-container" id="qr-container">
        <?php for ($i = 1; $i <= $qrCount; $i++):
            $serialCode = $barcodeCode . '-' . $i;
            if ($mode === 'qty') {
                $displayLabel = 'S/N: ' . $i . '/' . $qrCount;
                $qrContent = $serialCode . '|' . $itemName;
            } else {
                $itemsInThisBox = max(0, min($boxSize, $totalQty - (($i - 1) * $boxSize)));
                $displayLabel = 'Box ' . $i . '/' . $qrCount . ' (' . $itemsInThisBox . ' items)';
                $qrContent = $serialCode . '|' . $itemName . '|Box ' . $i;
            }
        ?>
        <div class="qr-label">
            <div class="item-name"><?php echo htmlspecialchars($itemName); ?></div>
            <div class="serial-num"><?php echo htmlspecialchars($displayLabel); ?></div>
            <div class="qr-code" id="qr-<?php echo $i; ?>"></div>
            <div class="barcode-code"><?php echo htmlspecialchars($serialCode); ?></div>
            <div class="date"><?php echo date('d-m-Y'); ?></div>
        </div>
        <?php endfor; ?>
    </div>

    <script>
        var qrData = <?php echo json_encode(array_map(function($i) use ($barcodeCode, $itemName, $qrCount, $mode, $totalQty, $boxSize) {
            $serialCode = $barcodeCode . '-' . $i;
            if ($mode === 'qty') {
                $content = $serialCode . '|' . $itemName;
            } else {
                $itemsInThisBox = max(0, min($boxSize, $totalQty - (($i - 1) * $boxSize)));
                $content = $serialCode . '|' . $itemName . '|Box ' . $i;
            }
            return $content;
        }, range(1, $qrCount))); ?>;

        function generateQRCodes() {
            qrData.forEach(function(data, index) {
                var qrId = 'qr-' + (index + 1);
                var element = document.getElementById(qrId);
                if (element) {
                    new QRCode(element, {
                        text: data,
                        width: 80,
                        height: 80,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            });
        }

        window.onload = function() {
            generateQRCodes();
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
