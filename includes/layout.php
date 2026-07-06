<?php
// Prevent undefined variable errors
$content = $content ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HVF Business Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Global Styles -->
    <link rel="stylesheet" href="/hvf-app/css/style.css">
    <link rel="icon" type="image/x-icon" href="/hvf-app/images/favicon-new.ico?v=1">
    <style>
/* Strong logo protection */
.logo-area img,
.sidebar img {
    max-width: 180px !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain;
}

/* Ensure sidebar doesn't collapse */
.sidebar {
    flex-shrink: 0;
    min-width: 250px;
}
</style>
</head>

<body>

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="main-content">

        <!-- TOP BAR -->
        <?php include __DIR__ . '/header.php'; ?>

        <!-- PAGE CONTENT -->
        <div class="page-content">
            <?= $content ?>
        </div>

    </div>

    <!-- OPTIONAL FOOTER -->
    <?php
    if (file_exists(__DIR__ . '/footer.php')) {
        include __DIR__ . '/footer.php';
    }
    ?>

    <!-- Global JS -->
    <script src="/hvf-app/js/app.js"></script>

    <!-- Stock Form Auto-Fill Logic -->
    <script>
    function fetchStrainDetails() {
        let strainId = document.getElementById('strain_code')?.value;
        let growType = document.getElementById('grow_type')?.value;

        if (strainId && growType) {
            fetch('/hvf-app/api/strain_lookup.php?id=' + strainId + '&grow_type=' + growType)
            .then(res => res.json())
            .then(data => {
                if (data.product_name) {
                    document.getElementById('product_name').value = data.product_name;
                    document.getElementById('selling_price').value = data.default_price;
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        let strainSelect = document.getElementById('strain_code');
        let growSelect   = document.getElementById('grow_type');

        if (strainSelect) strainSelect.addEventListener('change', fetchStrainDetails);
        if (growSelect)   growSelect.addEventListener('change', fetchStrainDetails);
    });
    </script>

</body>
</html>
