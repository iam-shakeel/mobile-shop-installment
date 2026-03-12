<?php

function db(): mysqli
{
    static $db = null;
    static $schemaChecked = false;
    static $migrationsChecked = false;
    if ($db instanceof mysqli) {
        return $db;
    }

    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($db->connect_errno) {
        if ($db->connect_errno === 1049) {
            // Unknown database: bootstrap connection to create it.
            $bootstrap = new mysqli(DB_HOST, DB_USER, DB_PASS, "", DB_PORT);
            if ($bootstrap->connect_errno) {
                http_response_code(500);
                die("Database bootstrap failed: " . $bootstrap->connect_error);
            }

            $safeName = $bootstrap->real_escape_string(DB_NAME);
            $createSql = "CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if (!$bootstrap->query($createSql)) {
                http_response_code(500);
                die("Database create failed: " . $bootstrap->error);
            }
            $bootstrap->close();

            $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            if ($db->connect_errno) {
                http_response_code(500);
                die("Database connection failed: " . $db->connect_error);
            }
        } else {
            http_response_code(500);
            die("Database connection failed: " . $db->connect_error);
        }
    }

    $db->set_charset("utf8mb4");
    if (!$schemaChecked) {
        db_ensure_schema($db);
        $schemaChecked = true;
    }
    if (!$migrationsChecked) {
        db_ensure_migrations($db);
        $migrationsChecked = true;
    }
    return $db;
}

function db_ensure_schema(mysqli $db): void
{
    $schemaName = $db->real_escape_string(DB_NAME);
    $checkSql = "SELECT 1 FROM information_schema.tables WHERE table_schema='{$schemaName}' AND table_name='products' LIMIT 1";
    $check = $db->query($checkSql);
    if ($check && $check->num_rows > 0) {
        return;
    }

    $schemaPath = __DIR__ . "/../sql/schema.sql";
    if (!is_file($schemaPath)) {
        http_response_code(500);
        die("Database schema file missing: " . $schemaPath);
    }

    $schemaSql = file_get_contents($schemaPath);
    if ($schemaSql === false) {
        http_response_code(500);
        die("Database schema read failed.");
    }

    if (!$db->multi_query($schemaSql)) {
        http_response_code(500);
        die("Database schema init failed: " . $db->error);
    }

    do {
        $result = $db->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    } while ($db->more_results() && $db->next_result());

    if ($db->errno) {
        http_response_code(500);
        die("Database schema init failed: " . $db->error);
    }
}

function db_ensure_migrations(mysqli $db): void
{
    $schemaName = $db->real_escape_string(DB_NAME);
    $checkSql = "SELECT 1 FROM information_schema.columns WHERE table_schema='{$schemaName}' AND table_name='installment_plans' AND column_name='remaining_amount' LIMIT 1";
    $check = $db->query($checkSql);
    if ($check && $check->num_rows > 0) {
        db_ensure_settings($db);
        db_ensure_users($db);
        return;
    }

    if (!$db->query("ALTER TABLE installment_plans ADD COLUMN remaining_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_amount")) {
        http_response_code(500);
        die("Database migration failed: " . $db->error);
    }

    $updateSql = "UPDATE installment_plans ip
        LEFT JOIN (
          SELECT installment_plan_id, COALESCE(SUM(amount), 0) AS paid
          FROM installment_payments
          GROUP BY installment_plan_id
        ) p ON p.installment_plan_id = ip.id
        SET ip.remaining_amount = GREATEST(0, ip.total_amount - COALESCE(p.paid, 0)),
            ip.status = CASE WHEN (ip.total_amount - COALESCE(p.paid, 0)) <= 0 THEN 'completed' ELSE ip.status END";
    if (!$db->query($updateSql)) {
        http_response_code(500);
        die("Database migration failed: " . $db->error);
    }

    db_ensure_settings($db);
    db_ensure_users($db);
}

function db_ensure_settings(mysqli $db): void
{
    if (!$db->query(
        "CREATE TABLE IF NOT EXISTS settings (
            id INT PRIMARY KEY,
            business_name VARCHAR(255) NOT NULL DEFAULT 'Business Name',
            logo_url VARCHAR(500) DEFAULT NULL,
            address VARCHAR(255) DEFAULT 'Office Address',
            contact VARCHAR(100) DEFAULT '+92 123 456 7890',
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    )) {
        http_response_code(500);
        die("Database migration failed: " . $db->error);
    }

    $schemaName = $db->real_escape_string(DB_NAME);
    $check = $db->query(
        "SELECT 1 FROM information_schema.columns WHERE table_schema='{$schemaName}' AND table_name='settings' AND column_name='address' LIMIT 1"
    );
    if ($check && $check->num_rows === 0) {
        if (!$db->query("ALTER TABLE settings ADD COLUMN address VARCHAR(255) DEFAULT 'Office Address'")) {
            http_response_code(500);
            die("Database migration failed: " . $db->error);
        }
    }
    $check = $db->query(
        "SELECT 1 FROM information_schema.columns WHERE table_schema='{$schemaName}' AND table_name='settings' AND column_name='contact' LIMIT 1"
    );
    if ($check && $check->num_rows === 0) {
        if (!$db->query("ALTER TABLE settings ADD COLUMN contact VARCHAR(100) DEFAULT '+92 123 456 7890'")) {
            http_response_code(500);
            die("Database migration failed: " . $db->error);
        }
    }

    $db->query(
        "INSERT INTO settings (id, business_name, address, contact)
         VALUES (1, 'Business Name', 'Office Address', '+92 123 456 7890')
         ON DUPLICATE KEY UPDATE id = id"
    );
}

function db_ensure_users(mysqli $db): void
{
    if (!$db->query(
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    )) {
        http_response_code(500);
        die("Database migration failed: " . $db->error);
    }

    $row = $db->query("SELECT id FROM users LIMIT 1");
    if ($row && $row->num_rows === 0) {
        $hash = password_hash("admin123", PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        if ($stmt) {
            $username = "admin";
            $stmt->bind_param("ss", $username, $hash);
            $stmt->execute();
            $stmt->close();
        }
    }
}

function db_query(string $sql, string $types = "", array $params = []): mysqli_stmt
{
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        die("Database query failed: " . db()->error);
    }

    if ($types !== "" && $params) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        http_response_code(500);
        die("Database execute failed: " . $stmt->error);
    }

    return $stmt;
}

function db_fetch_all(mysqli_stmt $stmt): array
{
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function db_fetch_one(mysqli_stmt $stmt): ?array
{
    $result = $stmt->get_result();
    if (!$result) {
        return null;
    }
    $row = $result->fetch_assoc();
    return $row ?: null;
}
