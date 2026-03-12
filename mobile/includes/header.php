<?php
$pageTitle = $pageTitle ?? ($appSettings["business_name"] ?? "Mobile Shop");
$flash = get_flash();
$appSettings = get_app_settings();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/app.css" rel="stylesheet">
    <script>
      (function () {
        const storedTheme = localStorage.getItem("theme") || "light";
        document.documentElement.setAttribute("data-theme", storedTheme);
      })();
    </script>
    <script>
      window.APP_SETTINGS = <?php echo json_encode($appSettings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
      <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><?php echo h($appSettings["business_name"] ?? "Mobile Shop"); ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTop">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarTop">
          <ul class="navbar-nav ms-auto">
            <?php if (is_logged_in()): ?>
              <li class="nav-item d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-light theme-toggle" type="button" data-theme-toggle aria-pressed="false">
                  <i class="bi bi-moon-stars"></i>
                  <span data-theme-label>Dark Mode</span>
                </button>
              </li>
              <li class="nav-item d-flex align-items-center gap-2 ms-lg-2">
                <span class="nav-link p-0">Welcome <?php echo h($_SESSION["username"] ?? "Admin"); ?></span>
                <a class="btn btn-sm btn-danger" href="index.php?page=logout">Logout</a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
    <div class="container-fluid">
      <div class="row">
