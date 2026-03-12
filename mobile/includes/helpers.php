<?php

function h(?string $value): string
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION["flash"] = ["type" => $type, "message" => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION["flash"])) {
        return null;
    }
    $flash = $_SESSION["flash"];
    unset($_SESSION["flash"]);
    return $flash;
}

function format_currency($amount): string
{
    return "PKR " . number_format((float)$amount, 0);
}

function get_app_settings(): array
{
    static $settings = null;
    if (is_array($settings)) {
        return $settings;
    }
    $row = db_fetch_one(db_query("SELECT business_name, logo_url, address, contact FROM settings WHERE id = 1"));
    $settings = $row ?: [
        "business_name" => "Business Name",
        "logo_url" => "",
        "address" => "Office Address",
        "contact" => "+92 123 456 7890"
    ];
    return $settings;
}

function is_logged_in(): bool
{
    return !empty($_SESSION["user_id"]);
}

function login_user(int $userId, string $username): void
{
    session_regenerate_id(true);
    $_SESSION["user_id"] = $userId;
    $_SESSION["username"] = $username;
}

function logout_user(): void
{
    $_SESSION = [];
    if (session_id() !== "") {
        session_destroy();
    }
}
