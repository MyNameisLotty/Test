<?php
// Prevent undefined variable errors
$content = $content ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<<head>
  <meta charset="UTF-8">
  <title>HVF Business Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Global Styles -->
  <link rel="stylesheet" href="/hvf-app/css/style.css">
  <link rel="icon" type="image/x-icon" href="/hvf-app/images/favicon.png">
  <link rel="stylesheet" href="/hvf-app/css/fontawesome-web/css/all.min.css">
</head>

<script>
  const menuBtn = document.querySelector('.menu-btn');
  const sidebar = document.querySelector('.sidebar');

  menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('active');
  });
</script>


<body>
  <!-- SIDEBAR -->
  <div class="sidebar" id="sidebar">
    <!-- logo + menu -->
    <?php include __DIR__ . '/sidebar.php'; ?>
  </div>

  <!-- Overlay for mobile -->
  <div class="overlay"></div>

  <!-- MAIN CONTENT WRAPPER -->
  <div class="main-content">
    <!-- TOP BAR -->
    <div class="topbar">
      <button class="menu-btn">
        <i class="fas fa-bars"></i>
      </button>
      <h1>Dashboard</h1>
    </div>

    <!-- PAGE CONTENT -->
    <div class="page-content">
      <?= $content ?>
    </div>
  </div>

  <!-- OPTIONAL FOOTER -->
  <?php if (file_exists(__DIR__ . '/footer.php')) include __DIR__ . '/footer.php'; ?>

  <!-- Global JS -->
  <script src="/hvf-app/js/app.js"></script>

  <!-- Sidebar toggle logic -->
  <script>
    const menuBtn = document.querySelector('.menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');

    menuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });

    overlay.addEventListener('click', () => {
      sidebar.classList.remove('active');
    });
  </script>
</body>

</html>

