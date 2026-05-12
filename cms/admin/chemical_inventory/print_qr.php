<?php
/**
 * Chemical Inventory - Print Barcodes
 * Generates CODE128 barcodes for chemical batches
 */

$barcodeCode = $_GET['barcode'] ?? '';
$chemicalName = $_GET['chemical'] ?? '';
$qrCount = max(1, (int)($_GET['count'] ?? 1));
$shortCode = $_GET['short_code'] ?? '';

if ($barcodeCode === '') {
    die('Invalid barcode');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Barcodes - <?php echo htmlspecialchars($chemicalName); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; padding: 10px; }
        .barcode-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 3px; max-width: 1200px; }
        .barcode-label { border: 1px solid #000; padding: 2px; text-align: center; page-break-inside: avoid; background: #fff; display: flex; flex-direction: column; justify-content: center; align-items: center; width: 1.75in; height: 0.5in; }
        .serial-num { font-size: 7px; font-weight: bold; color: #000; margin-bottom: 1px; line-height: 1; }
        .barcode-wrapper { width: 100%; height: 0.35in; display: flex; align-items: center; justify-content: center; }
        .barcode-wrapper svg { max-width: 100%; max-height: 100%; }
        .date { display: none; }
        @media print {
            @page { size: A4; margin: 5mm; }
            body { padding: 5px; }
            .barcode-container { gap: 3px; }
            .barcode-label { padding: 2px; width: 1.75in; height: 0.5in; border: 1px solid #000; }
        }
    </style>
</head>
<body>
    <div class="barcode-container" id="barcode-container">
        <?php for ($i = 1; $i <= $qrCount; $i++):
            $serialCode = $barcodeCode . '-' . $i;
            $displayLabel = 'S/N: ' . $i . '/' . $qrCount;
        ?>
        <div class="barcode-label">
            <div class="serial-num"><?php echo htmlspecialchars($displayLabel); ?></div>
            <div class="barcode-wrapper">
                <svg id="barcode-<?php echo $i; ?>"></svg>
            </div>
            <div class="date"><?php echo date('d-m-Y'); ?></div>
        </div>
        <?php endfor; ?>
    </div>

    <script>
        var shortCode = '<?php echo htmlspecialchars($shortCode); ?>';
        
        function generateBarcodes() {
            for(var i = 1; i <= <?php echo $qrCount; ?>; i++) {
                var barcodeId = '#barcode-' + i;
                var element = document.querySelector(barcodeId);
                if (element && shortCode) {
                    try {
                        JsBarcode(element, shortCode, {
                            format: "CODE128",
                            width: 2,
                            height: 25,
                            displayValue: false,
                            margin: 2
                        });
                    } catch (e) {
                        console.error('Barcode generation error:', e);
                    }
                }
            }
        }

        window.onload = function() {
            generateBarcodes();
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
