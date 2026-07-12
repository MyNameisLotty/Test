<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

// 1. Fetch the invoice and the primary order row linked to it
$stmt = $conn->prepare("
    SELECT i.*, o.order_number, c.client_name, c.phone, c.email, c.address, c.id AS client_id
    FROM invoices i
    LEFT JOIN orders o ON i.order_id = o.id
    LEFT JOIN clients c ON i.client_id = c.id
    WHERE i.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();

if (!$invoice) {
    die('Invoice not found.');
}

// ==========================================
// START OF STEP 2 & 3 ADJUSTMENTS
// ==========================================

// 2. Extract base batch identifier component safely
$fullOrderNumber = $invoice['order_number'] ?? '';
$baseOrderParts = explode('-', $fullOrderNumber);

// If order format is '19-20260706-205104-1', this captures '20260706-205104'
$batchTimestamp = (isset($baseOrderParts[1], $baseOrderParts[2])) 
    ? $baseOrderParts[1] . '-' . $baseOrderParts[2] 
    : $fullOrderNumber;

// 3. Fetch ALL items sharing this exact batch timestamp run for this client
$orderSearchPattern = '%' . $batchTimestamp . '%';
$itemsStmt = $conn->prepare("
    SELECT description, quantity, selling_price, total 
    FROM orders 
    WHERE order_number LIKE ? AND client_id = ?
");
$itemsStmt->bind_param('si', $orderSearchPattern, $invoice['client_id']);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

$items = [];
$calculatedGrandTotal = 0;

while ($row = $itemsResult->fetch_assoc()) {
    $items[] = $row;
    $calculatedGrandTotal += (float)$row['total'];
}

// Fallback logic protection layer if batch query returns nothing
if (empty($items)) {
    $fallbackStmt = $conn->prepare("SELECT description, quantity, selling_price, total FROM orders WHERE id = ?");
    $fallbackStmt->bind_param('i', $invoice['order_id']);
    $fallbackStmt->execute();
    if ($f = $fallbackStmt->get_result()->fetch_assoc()) {
        $items[] = $f;
        $calculatedGrandTotal = (float)$f['total'];
    }
}

// 4. Generate the dynamic clean Customer Code Reference (e.g., Hi5KDP001)
$clientName = $invoice['client_name'] ?? '';
$clientId = (int)($invoice['client_id'] ?? 0);

if (stripos($clientName, 'high5') !== false) {
    $code = 'Hi5';
    $words = explode(' ', $clientName);
    $lastWord = strtoupper(end($words));
    
    if ($lastWord !== 'CANNA' && $lastWord !== 'HIGH5') {
        $consonants = preg_replace('/[AEIOU\s]/', '', $lastWord);
        $code .= substr($consonants, 0, 3); // KDP
    } else {
        $code .= 'GEN';
    }
    $customerCode = $code;
} else {
    $words = explode(' ', preg_replace('/[^A-Za-z0-9 ]/', '', $clientName));
    $code = '';
    foreach ($words as $w) {
        if (!empty($w) && strtoupper($w) !== 'CANNA') {
            $code .= ucfirst(substr($w, 0, 2));
        }
    }
    $customerCode = !empty($code) ? substr($code, 0, 6) : "CLN";
}

$seqStmt = $conn->prepare("
    SELECT COUNT(DISTINCT SUBSTRING_INDEX(order_number, '-', 3)) as order_sequence 
    FROM orders 
    WHERE client_id = ? AND id <= ?
");
$seqStmt->bind_param('ii', $clientId, $invoice['order_id']);
$seqStmt->execute();
$seqResult = $seqStmt->get_result()->fetch_assoc();
$sequenceNumber = (int)($seqResult['order_sequence'] ?? 1);
$paddedSequence = str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT);

$customCustomerReference = $customerCode . $paddedSequence;

// ==========================================
// END OF ADJUSTMENTS - HTML STARTS BELOW
// ==========================================

ob_start();
?>

<style>
    /* ---------------------------------------------------
        LIVE WEBPAGE SCREEN VIEW STYLES
    --------------------------------------------------- */
    .printable-invoice-wrapper {
        max-width: 850px;
        margin: 20px auto;
        padding: 40px;
        background: #1e1e1e; 
        color: #ffffff;
        border-radius: 8px;
    }
    .printable-invoice-wrapper td, 
    .printable-invoice-wrapper th, 
    .printable-invoice-wrapper h2, 
    .printable-invoice-wrapper h3, 
    .printable-invoice-wrapper p,
    .printable-invoice-wrapper strong {
        color: #ffffff !important; 
    }
    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 25px;
    }
    .invoice-header .logo-container {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
    .invoice-header .logo-container img {
        width: 450px; 
        max-width: 100%;
        height: auto;
    }
    .invoice-header .logo-container h1 {
        margin: 8px 0 0 0 !important;
        padding: 0 !important;
        font-size: 16px;
        font-weight: bold;
        letter-spacing: 0.5px;
    }
    .invoice-header .invoice-details {
        text-align: right;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 25px 0;
    }
    th, td {
        padding: 12px 10px;
        border-bottom: 1px solid #333;
    }
    .text-right {
        text-align: right !important;
    }
    .invoice-total {
        text-align: right;
        margin-top: 20px;
    }
    .no-print {
        display: block;
        margin-top: 20px;
    }

    /* Hide the dedicated print clone block from the screen monitor */
    .pure-print-block {
        display: none;
    }

    /* ---------------------------------------------------
       /* ---------------------------------------------------
   /* ---------------------------------------------------
   A4 PRINT OVERRIDES — Clean White Invoice
--------------------------------------------------- */
/* ---------------------------------------------------
   A4 PRINT OVERRIDES — Clean White Invoice
--------------------------------------------------- */
/* ---------------------------------------------------
   A4 PRINT OVERRIDES — Clean White Invoice
--------------------------------------------------- */
@media print {
  @page {
    size: A4 portrait;
    margin: 5mm; /* tight margins so it fits */
  }

  body {
    margin: 0;
    background: #fff !important;
    color: #000 !important;
  }

  /* Show only the invoice */
  .printable-invoice-wrapper {
    display: block !important;
    max-width: 100% !important;
    margin: 0 auto !important;
    padding: 5mm !important;
    background: #fff !important;
    color: #000 !important;
    page-break-inside: avoid !important;
  }

  /* Hide everything else */
  nav, .sidebar, .top-nav, button, .no-print, header, footer, .navbar, .pure-print-block {
    display: none !important;
  }

  /* Table styling — no borders */
  table {
    border-collapse: collapse !important;
    width: 100% !important;
    margin: 0 !important;
  }
  th, td {
    border: none !important;   /* remove grid lines */
    padding: 6px !important;
    text-align: left !important;
  }
  .text-right {
    text-align: right !important;
  }

  /* Totals section */
  .invoice-total {
    text-align: right !important;
    margin-top: 10px !important;
    font-size: 16px !important;
    font-weight: bold !important;
  }
}




</style>

<div class="printable-invoice-wrapper">
    <div class="invoice-header">
        <div class="logo-container">
            <img src="/hvf-app/images/HVF-Logo.png" alt="HVF Logo">
            
        </div>
        <div class="invoice-details">
            <h2>Invoice <?= h($invoice['invoice_number']) ?></h2>
            <p><strong>Order Ref:</strong> <?= h($customCustomerReference) ?></p>
            <p><strong>Invoice Date:</strong> <?= h($invoice['invoice_date']) ?></p>
        </div>
    </div>

   

    <div class="split-grid">
        <div>
            
            <p><strong><?= h($invoice['client_name'] ?? 'Unknown') ?></strong></p>
            <p><?= h($invoice['phone'] ?? '') ?></p>
            <p><?= h($invoice['email'] ?? '') ?></p>
            <p><?= nl2br(h($invoice['address'] ?? '')) ?></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right" style="width: 15%;">Quantity</th>
                <th class="text-right" style="width: 20%;">Unit Price</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= h($item['description'] ?? 'Order Item') ?></td>
                    <td class="text-right"><?= number_format((float)($item['quantity'] ?? 0), 2) ?> g</td>
                    <td class="text-right"><?= money($item['selling_price'] ?? 0) ?></td>
                    <td class="text-right"><?= money($item['total']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="invoice-total">Total: <?= money($calculatedGrandTotal) ?></h2>

    <div class="banking">
        <h3>Banking Details</h3>
        <p><strong>Bank:</strong> ABSA BANK</p>
        <p><strong>Account Name:</strong> Marc Heath</p>   
        <p><strong>Account Number:</strong> 9098029879</p>
        <p><strong>Branch Code:</strong> 632005</p>
    </div>

    <br>
    <button class="no-print" onclick="prepareAndPrint()">Print / Save PDF</button>
</div>


<div class="pure-print-block">
    <div class="print-header">
        <div class="print-logo-box">
            <img src="/hvf-app/images/HVF-Logo.png" alt="HVF Logo">
            <h1>HVF Business Manager</h1>
        </div>
        <div class="print-details">
            <h2>Invoice <?= h($invoice['invoice_number']) ?></h2>
            <p><strong>Order Ref:</strong> <?= h($customCustomerReference) ?></p>
            <p><strong>Invoice Date:</strong> <?= h($invoice['invoice_date']) ?></p>
        </div>
    </div>

    <hr style="border: 0; border-top: 2px solid #000000; margin: 15px 0;">

    <div class="print-billto">
        <h3>Bill To</h3>
        <p><strong><?= h($invoice['client_name'] ?? 'Unknown') ?></strong></p>
        <p><?= h($invoice['phone'] ?? '') ?></p>
        <p><?= h($invoice['email'] ?? '') ?></p>
        <p><?= nl2br(h($invoice['address'] ?? '')) ?></p>
    </div>

    <table class="print-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right" style="width: 15%;">Quantity</th>
                <th class="text-right" style="width: 20%;">Unit Price</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= h($item['description'] ?? 'Order Item') ?></td>
                    <td class="text-right"><?= number_format((float)($item['quantity'] ?? 0), 2) ?> g</td>
                    <td class="text-right"><?= money($item['selling_price'] ?? 0) ?></td>
                    <td class="text-right"><?= money($item['total']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="print-total">Total: <?= money($calculatedGrandTotal) ?></div>

    <div class="print-banking">
        <h3>Banking Details</h3>
        <p><strong>Bank:</strong> ABSA BANK</p>
        <p><strong>Account Name:</strong> Marc Heath</p>   
        <p><strong>Account Number:</strong> 9098029879</p>
        <p><strong>Branch Code:</strong> 632005</p>
    </div>
</div>

<script>
// Component names from your server data
const paddedSeq = "<?= str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT) ?>";
const custCode = "<?= $customerCode ?>";
const exactFilename = 'INV' + paddedSeq + custCode;

function prepareAndPrint() {
    // 1. Force the browser tab title to change to your custom invoice number
    const originalTitle = document.title;
    document.title = exactFilename;

    // 2. Open the browser print/PDF saving panel
    window.print();

    // 3. Restore the original title after the print prompt finishes
    setTimeout(() => {
        document.title = originalTitle;
    }, 1000);
}

// Automatic background backup processing task
window.addEventListener('load', function() {
    const invoiceHtml = document.documentElement.outerHTML;
    console.log("Attempting automatic background snapshot for reference:", exactFilename);

    const payload = new FormData();
    payload.append('filename', exactFilename);
    payload.append('html', invoiceHtml);

    fetch('/hvf-app/api/save_local_pdf.php', {
        method: 'POST',
        body: payload
    })
    .then(response => response.text())
    .then(data => console.log('Background storage system status:', data))
    .catch(err => console.error('Background storage system error:', err));
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>