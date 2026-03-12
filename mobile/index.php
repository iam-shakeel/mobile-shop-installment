<?php
require __DIR__ . "/includes/bootstrap.php";

$page = $_GET["page"] ?? "dashboard";
$allowedPages = [
    "dashboard",
    "products",
    "product_edit",
    "customers",
    "customer_edit",
    "sales_new",
    "installments",
    "reports",
    "settings",
    "login",
    "logout"
];

if (!in_array($page, $allowedPages, true)) {
    $page = "dashboard";
}

if ($page === "logout") {
    logout_user();
    redirect("index.php?page=login");
}

if ($page !== "login" && !is_logged_in()) {
    redirect("index.php?page=login");
}

$pageFile = __DIR__ . "/pages/{$page}.php";

if ($page === "login") {
    require $pageFile;
    exit;
}

require __DIR__ . "/includes/header.php";
require __DIR__ . "/includes/sidebar.php";
require $pageFile;
require __DIR__ . "/includes/footer.php";
