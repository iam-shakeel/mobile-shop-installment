<?php
$pageTitle = "Login";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $user = db_fetch_one(db_query("SELECT id, username, password_hash FROM users WHERE username = ?", "s", [$username]));
    if ($user && password_verify($password, $user["password_hash"])) {
        login_user((int)$user["id"], $user["username"]);
        redirect("index.php?page=dashboard");
    }

    set_flash("danger", "Invalid username or password.");
    redirect("index.php?page=login");
}

$flash = get_flash();
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
  </head>
  <body class="login-page">
    <div class="login-shell">
      <div class="login-orb login-orb-1"></div>
      <div class="login-orb login-orb-2"></div>
      <div class="login-orb login-orb-3"></div>
      <div class="login-card">
        <div class="login-header">
          <div class="login-badge"><i class="bi bi-shield-lock"></i>Secure Access</div>
          <h1>Sign In</h1>
          <p>Enter your credentials to launch the Mobile Selling Shop console.</p>
        </div>

        <?php if ($flash): ?>
          <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo h($flash["message"]); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-icon">
              <i class="bi bi-person-badge"></i>
              <input class="form-control" name="username" placeholder="Enter your username" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-icon">
              <i class="bi bi-lock"></i>
              <input class="form-control" name="password" type="password" placeholder="Enter your password" required>
            </div>
          </div>
          <button class="btn btn-primary w-100 login-btn"><i class="bi bi-box-arrow-in-right"></i>Login</button>
        </form>
        <div class="login-footnote">
          Build by <a href="https://shakeelahamd.netlify.app/" target="_blank" rel="noopener">Shakeel Ahmad Jan</a>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
