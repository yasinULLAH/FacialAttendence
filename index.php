<?php
session_start();
header("Access-Control-Allow-Origin: *");
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'facial_attendance_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8 COLLATE utf8_general_ci");
} catch (PDOException $e) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}

$db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("CREATE TABLE IF NOT EXISTS system_config (
    cfg_key VARCHAR(100) PRIMARY KEY,
    cfg_val TEXT
) ENGINE=InnoDB");

$db->exec("CREATE TABLE IF NOT EXISTS departments (
    name VARCHAR(100) PRIMARY KEY
) ENGINE=InnoDB");

$db->exec("CREATE TABLE IF NOT EXISTS holidays (
    date DATE PRIMARY KEY,
    name VARCHAR(100)
) ENGINE=InnoDB");

$db->exec("CREATE TABLE IF NOT EXISTS employees (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100),
    descriptor TEXT,
    department VARCHAR(100),
    role VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(50),
    status VARCHAR(20),
    joined BIGINT
) ENGINE=InnoDB");

$db->exec("CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empId VARCHAR(50),
    name VARCHAR(100),
    department VARCHAR(100),
    role VARCHAR(100),
    timestamp BIGINT,
    dateString VARCHAR(20),
    timeString VARCHAR(20),
    type VARCHAR(20),
    status VARCHAR(20)
) ENGINE=InnoDB");

$stmt = $db->prepare("SELECT COUNT(*) FROM system_config WHERE cfg_key = 'master_password'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $db->prepare("INSERT INTO system_config (cfg_key, cfg_val) VALUES ('master_password', 'admin1234')")->execute();
    $db->prepare("INSERT INTO system_config (cfg_key, cfg_val) VALUES ('liveness_level', 'none')")->execute();
    $db->prepare("INSERT INTO system_config (cfg_key, cfg_val) VALUES ('theme_color', 'indigo')")->execute();
    $db->prepare("INSERT INTO system_config (cfg_key, cfg_val) VALUES ('theme_mode', 'light')")->execute();
    $db->prepare("INSERT INTO system_config (cfg_key, cfg_val) VALUES ('shift_start_time', '09:00')")->execute();
    $db->prepare("INSERT INTO system_config (cfg_key, cfg_val) VALUES ('shift_grace_minutes', '15')")->execute();
    $db->prepare("INSERT INTO system_config (cfg_key, cfg_val) VALUES ('weekend_days', '[0, 6]')")->execute();

    $defaults = ["Administration", "Engineering", "Sales", "Marketing", "Human Resources", "Finance", "Operations"];
    foreach ($defaults as $d) {
        $db->prepare("INSERT IGNORE INTO departments (name) VALUES (?)")->execute([$d]);
    }
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'get_config') {
        $stmt = $db->query("SELECT * FROM system_config");
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['cfg_key']] = $row['cfg_val'];
        }
        echo json_encode($config);
        exit;
    }

    if ($action === 'save_config') {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data) {
            foreach ($data as $key => $val) {
                if ($key === 'weekend_days' && is_array($val)) {
                    $val = json_encode($val);
                }
                $stmt = $db->prepare("INSERT INTO system_config (cfg_key, cfg_val) VALUES (?, ?) ON DUPLICATE KEY UPDATE cfg_val = ?");
                $stmt->execute([$key, $val, $val]);
            }
            echo json_encode(['success' => true]);
        }
        exit;
    }

    if ($action === 'verify_login') {
        $data = json_decode(file_get_contents('php://input'), true);
        $pass = isset($data['password']) ? $data['password'] : '';
        $stmt = $db->prepare("SELECT cfg_val FROM system_config WHERE cfg_key = 'master_password'");
        $stmt->execute();
        $real = $stmt->fetchColumn();
        if ($pass === $real) {
            $_SESSION['admin_authenticated'] = true;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    if ($action === 'check_session') {
        echo json_encode(['authenticated' => isset($_SESSION['admin_authenticated'])]);
        exit;
    }

    if ($action === 'logout') {
        unset($_SESSION['admin_authenticated']);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_employees') {
        $stmt = $db->query("SELECT * FROM employees");
        $emps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($emps as &$emp) {
            $emp['descriptor'] = json_decode($emp['descriptor'], true);
            $emp['joined'] = (int)$emp['joined'];
        }
        echo json_encode($emps);
        exit;
    }

    if ($action === 'save_employee') {
        if (!isset($_SESSION['admin_authenticated'])) {
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data) {
            $stmt = $db->prepare("INSERT INTO employees (id, name, descriptor, department, role, email, phone, status, joined) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE name = ?, descriptor = ?, department = ?, role = ?, email = ?, phone = ?, status = ?");
            $desc = json_encode($data['descriptor']);
            $stmt->execute([
                $data['id'], $data['name'], $desc, $data['department'], $data['role'], $data['email'], $data['phone'], $data['status'], $data['joined'],
                $data['name'], $desc, $data['department'], $data['role'], $data['email'], $data['phone'], $data['status']
            ]);
            echo json_encode(['success' => true]);
        }
        exit;
    }

    if ($action === 'delete_employee') {
        if (!isset($_SESSION['admin_authenticated'])) {
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_logs') {
        $stmt = $db->query("SELECT * FROM attendance_logs");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($logs as &$log) {
            $log['id'] = (int)$log['id'];
            $log['timestamp'] = (int)$log['timestamp'];
        }
        echo json_encode($logs);
        exit;
    }

    if ($action === 'save_log') {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data) {
            $stmt = $db->prepare("INSERT INTO attendance_logs (empId, name, department, role, timestamp, dateString, timeString, type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['empId'], $data['name'], $data['department'], $data['role'], $data['timestamp'], $data['dateString'], $data['timeString'], $data['type'], $data['status']
            ]);
            echo json_encode(['success' => true]);
        }
        exit;
    }

    if ($action === 'update_log') {
        if (!isset($_SESSION['admin_authenticated'])) {
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['id'])) {
            $stmt = $db->prepare("UPDATE attendance_logs SET empId = ?, name = ?, department = ?, role = ?, timestamp = ?, dateString = ?, timeString = ?, type = ?, status = ? WHERE id = ?");
            $stmt->execute([
                $data['empId'], $data['name'], $data['department'], $data['role'], $data['timestamp'], $data['dateString'], $data['timeString'], $data['type'], $data['status'], $data['id']
            ]);
            echo json_encode(['success' => true]);
        }
        exit;
    }

    if ($action === 'delete_log') {
        if (!isset($_SESSION['admin_authenticated'])) {
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $db->prepare("DELETE FROM attendance_logs WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_departments') {
        $stmt = $db->query("SELECT * FROM departments");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($action === 'save_department') {
        if (!isset($_SESSION['admin_authenticated'])) {
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['name'])) {
            $stmt = $db->prepare("INSERT IGNORE INTO departments (name) VALUES (?)");
            $stmt->execute([$data['name']]);
            echo json_encode(['success' => true]);
        }
        exit;
    }

    if ($action === 'delete_department') {
        if (!isset($_SESSION['admin_authenticated'])) {
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }
        $name = isset($_GET['name']) ? $_GET['name'] : '';
        $stmt = $db->prepare("DELETE FROM departments WHERE name = ?");
        $stmt->execute([$name]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_holidays') {
        $stmt = $db->query("SELECT * FROM holidays");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($action === 'save_holiday') {
        if (!isset($_SESSION['admin_authenticated'])) {
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['date']) && isset($data['name'])) {
            $stmt = $db->prepare("INSERT IGNORE INTO holidays (date, name) VALUES (?, ?)");
            $stmt->execute([$data['date'], $data['name']]);
            echo json_encode(['success' => true]);
        }
        exit;
    }

    if ($action === 'delete_holiday') {
        if (!isset($_SESSION['admin_authenticated'])) {
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }
        $date = isset($_GET['date']) ? $_GET['date'] : '';
        $stmt = $db->prepare("DELETE FROM holidays WHERE date = ?");
        $stmt->execute([$date]);
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#4f46e5">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/svg+xml" href="icon.svg">
    <link rel="apple-touch-icon" href="icon.svg">
    <title>Aura Facial Recognition Attendance Suite</title>
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/dist/face-api.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-glow: rgba(79, 70, 229, 0.08);
            --text: #0f172a;
            --muted: #475569;
            --muted-light: #94a3b8;
            --border: #e2e8f0;
            --success: #059669;
            --success-bg: #ecfdf5;
            --warning: #d97706;
            --warning-bg: #fffbeb;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body.dark {
            --bg: #0f172a;
            --panel: #1e293b;
            --text: #f8fafc;
            --muted: #94a3b8;
            --muted-light: #475569;
            --border: #334155;
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.1);
            --warning: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.1);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.1);
        }

        body.accent-indigo { --primary: #4f46e5; --primary-hover: #4338ca; --primary-glow: rgba(79, 70, 229, 0.08); }
        body.accent-emerald { --primary: #059669; --primary-hover: #047857; --primary-glow: rgba(5, 150, 105, 0.08); }
        body.accent-rose { --primary: #e11d48; --primary-hover: #be123c; --primary-glow: rgba(225, 29, 72, 0.08); }
        body.accent-amber { --primary: #d97706; --primary-hover: #b45309; --primary-glow: rgba(217, 119, 6, 0.08); }
        body.accent-blue { --primary: #2563eb; --primary-hover: #1d4ed8; --primary-glow: rgba(37, 99, 235, 0.08); }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: var(--font-family);
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        header {
            background: var(--panel);
            border-bottom: 1px solid var(--border);
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .brand-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-logo {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
        }

        .brand-title {
            font-weight: 800;
            font-size: 16px;
            letter-spacing: -0.3px;
            color: var(--text);
        }

        .brand-badge {
            font-size: 9px;
            font-weight: 700;
            background: var(--bg);
            color: var(--muted);
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px solid var(--border);
            text-transform: uppercase;
        }

        nav {
            display: flex;
            background: var(--bg);
            padding: 3px;
            border-radius: 8px;
            width: 100%;
            border: 1px solid var(--border);
        }

        .tab-btn {
            flex: 1;
            padding: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .tab-btn:hover {
            color: var(--text);
            background: rgba(0, 0, 0, 0.02);
        }

        .tab-btn.active {
            color: var(--primary);
            background: var(--panel);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        main {
            padding: 16px;
            flex: 1;
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        #system-alert-banner {
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            padding: 8px 16px;
            background: var(--primary-glow);
            color: var(--primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        #system-alert-banner::before {
            content: "";
            width: 6px;
            height: 6px;
            background-color: var(--primary);
            border-radius: 50%;
        }

        #toast-container {
            position: fixed;
            right: 16px;
            bottom: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 9999;
            pointer-events: none;
        }

        .toast {
            max-width: 320px;
            background: var(--panel);
            color: var(--text);
            border-radius: 8px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            padding: 10px 12px;
            font-size: 12px;
            line-height: 1.4;
            pointer-events: auto;
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast.success {
            background: var(--success-bg);
            border-color: var(--success);
            color: var(--success);
        }

        .toast.warning {
            background: var(--warning-bg);
            border-color: var(--warning);
            color: var(--warning);
        }

        .toast.error {
            background: var(--danger-bg);
            border-color: var(--danger);
            color: var(--danger);
        }

        .view-section {
            display: none;
            width: 100%;
        }

        .view-section.active {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .grid-2 {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .card {
            background: var(--panel);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        h2 {
            font-size: 15px;
            font-weight: 700;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text);
        }

        .camera-container {
            position: relative;
            width: 100%;
            padding-top: 75%;
            background-color: #020617;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .camera-placeholder-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--panel);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            z-index: 10;
            padding: 24px;
            text-align: center;
        }

        .placeholder-icon {
            font-size: 32px;
            color: var(--muted-light);
            background: var(--bg);
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .placeholder-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
        }

        .placeholder-subtitle {
            font-size: 12px;
            color: var(--muted);
            max-width: 280px;
            line-height: 1.5;
        }

        .camera-controls-bar {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 4px;
            border-radius: 30px;
            display: flex;
            gap: 6px;
            z-index: 15;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .control-btn {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            color: var(--muted);
            background: transparent;
            transition: all 0.2s ease;
        }

        .control-btn svg {
            width: 12px;
            height: 12px;
        }

        .control-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            background: transparent !important;
            color: var(--muted-light) !important;
        }

        .control-btn-start:hover:not(:disabled) {
            color: var(--success);
            background: var(--success-bg);
        }

        .control-btn-pause:hover:not(:disabled) {
            color: var(--warning);
            background: var(--warning-bg);
        }

        .control-btn-stop:hover:not(:disabled) {
            color: var(--danger);
            background: var(--danger-bg);
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
        }

        label {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input,
        select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 13px;
            outline: none;
            width: 100%;
            background: var(--panel);
            color: var(--text);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        input::placeholder {
            color: var(--muted-light);
        }

        input:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .btn {
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            color: #ffffff;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary {
            background: var(--primary);
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.15);
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--panel);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover:not(:disabled) {
            background: var(--bg);
        }

        .btn-danger {
            background: var(--panel);
            color: var(--danger);
            border: 1px solid var(--border);
        }

        .btn-danger:hover:not(:disabled) {
            background: var(--danger-bg);
            border-color: var(--danger);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--panel);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        th {
            background: var(--bg);
            padding: 12px 16px;
            font-weight: 700;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.75px;
        }

        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text);
            white-space: nowrap;
        }

        tr:last-child td {
            border: none;
        }

        tr:hover td {
            background: var(--bg);
        }

        .ledger-box {
            flex: 1;
            min-height: 240px;
            max-height: 480px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding-right: 4px;
        }

        .ledger-row {
            padding: 10px 14px;
            border-radius: 6px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary);
            font-size: 12px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ledger-row.success {
            border-left-color: var(--success);
            background: var(--success-bg);
            border-color: var(--success);
            color: var(--success);
        }

        #admin-auth-gate {
            max-width: 400px;
            margin: 60px auto;
            width: 100%;
        }

        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--muted);
        }

        .status-dot.active {
            background-color: var(--success);
            box-shadow: 0 0 8px var(--success);
        }

        .status-container {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .metric-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px;
            text-align: center;
        }

        .metric-value {
            font-size: 20px;
            font-weight: 800;
            color: var(--text);
            margin-top: 4px;
        }

        .metric-label {
            font-size: 10px;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dropzone-area {
            border: 2px dashed var(--border);
            border-radius: 6px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            background: var(--bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
        }

        .dropzone-area:hover {
            border-color: var(--primary);
            background: var(--primary-glow);
        }

        .datatable-header {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 4px 0;
        }

        .datatable-search-wrapper {
            position: relative;
            width: 100%;
        }

        .datatable-search-wrapper svg {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            color: var(--muted);
        }

        .datatable-search-input {
            padding-left: 32px;
        }

        .datatable-filters {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            width: 100%;
        }

        .datatable-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            font-size: 12px;
            color: var(--muted);
        }

        .datatable-pagination {
            display: flex;
            gap: 6px;
        }

        .datatable-page-btn {
            padding: 6px 12px;
            border: 1px solid var(--border);
            background: var(--panel);
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            color: var(--text);
        }

        .datatable-page-btn:hover:not(:disabled) {
            background: var(--bg);
        }

        .datatable-page-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .datatable-toolbar {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 8px;
        }

        .sub-nav {
            display: flex;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
            overflow-x: auto;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
        }

        .sub-nav-tabs {
            display: flex;
            gap: 20px;
        }

        .sub-tab-btn {
            padding: 10px 0;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            background: none;
            border: none;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .sub-tab-btn:hover {
            color: var(--text);
        }

        .sub-tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .theme-picker-group {
            display: flex;
            gap: 8px;
            margin-top: 6px;
        }

        .color-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .color-dot.active {
            border-color: var(--text);
        }

        .color-dot.indigo { background: #4f46e5; }
        .color-dot.emerald { background: #059669; }
        .color-dot.rose { background: #e11d48; }
        .color-dot.amber { background: #d97706; }
        .color-dot.blue { background: #2563eb; }

        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            width: 100%;
            max-width: 280px;
            margin: 0 auto;
        }

        .heatmap-day {
            aspect-ratio: 1;
            border-radius: 3px;
            background: var(--border);
            font-size: 9px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
        }

        .heatmap-day.val-1 { background: rgba(16, 185, 129, 0.25); color: #10b981; }
        .heatmap-day.val-2 { background: rgba(16, 185, 129, 0.5); color: #ffffff; }
        .heatmap-day.val-3 { background: rgba(16, 185, 129, 0.75); color: #ffffff; }
        .heatmap-day.val-4 { background: rgba(16, 185, 129, 1); color: #ffffff; }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-card {
            background: var(--panel);
            border-radius: 12px;
            border: 1px solid var(--border);
            width: 100%;
            max-width: 440px;
            padding: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        @media (min-width: 768px) {
            header {
                flex-direction: row;
                height: 56px;
                padding: 0 32px;
                justify-content: space-between;
                align-items: center;
            }

            .brand {
                width: auto;
            }

            nav {
                width: auto;
            }

            .tab-btn {
                flex: none;
                padding: 6px 18px;
                font-size: 13px;
            }

            main {
                padding: 24px;
                gap: 24px;
            }

            .grid-2 {
                display: grid;
                grid-template-columns: 1.2fr 0.8fr;
                gap: 24px;
            }

            .card {
                padding: 24px;
            }

            .metrics-grid {
                grid-template-columns: repeat(5, 1fr);
                gap: 12px;
            }

            .datatable-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .datatable-search-wrapper {
                width: 240px;
            }

            .datatable-filters {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                width: auto;
            }

            .datatable-filters select {
                width: 140px;
            }

            .datatable-toolbar {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="brand">
            <div class="brand-info">
                <div class="brand-logo">A</div>
                <span class="brand-title">AURA FACE RECOGNITION</span>
            </div>
            <span class="brand-badge">v3.0</span>
        </div>
        <nav>
            <button id="nav-kiosk" class="tab-btn active" onclick="switchView('kiosk')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="4"></line>
                    <line x1="8" y1="2" x2="8" y2="4"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Kiosk Scanner
            </button>
            <button id="nav-admin" class="tab-btn" onclick="switchView('admin')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Admin Terminal
            </button>
        </nav>
        <div class="status-container">
            <button class="btn btn-secondary" onclick="toggleTheme()" style="padding: 4px 8px; font-size: 11px; margin-right: 8px;">🌓 Theme</button>
            <span id="system-status-dot" class="status-dot active"></span>
            <span id="system-status-text">Core Active</span>
        </div>
    </header>
    <main>
        <div id="system-alert-banner">Initializing System Server Datastore Core...</div>

        <div id="view-kiosk" class="view-section active">
            <div class="grid-2">
                <div class="card">
                    <h2>
                        <span>Facial Recognition Scanner</span>
                        <span id="camera-status-pill" style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger); font-weight: 700;">OFFLINE</span>
                    </h2>
                    <div class="camera-container">
                        <video id="webcam-stream" autoplay loop muted playsinline></video>
                        <canvas id="canvas-runtime-draw"></canvas>
                        <div id="camera-placeholder" class="camera-placeholder-overlay">
                            <div class="placeholder-icon">📷</div>
                            <div class="placeholder-title">Webcam Inactive</div>
                            <div class="placeholder-subtitle">Activate scanner device to start high-precision verification scans.</div>
                            <button id="btn-placeholder-start" class="btn btn-primary" onclick="startCameraStream()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                                <span>Start Webcam</span>
                            </button>
                        </div>
                        <div class="camera-controls-bar">
                            <button id="btn-cam-start" class="control-btn control-btn-start" onclick="startCameraStream()" title="Start/Resume Camera">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                                Start
                            </button>
                            <button id="btn-cam-pause" class="control-btn control-btn-pause" onclick="pauseCameraStream()" title="Pause Camera" disabled>
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                                </svg>
                                Pause
                            </button>
                            <button id="btn-cam-stop" class="control-btn control-btn-stop" onclick="stopCameraStream()" title="Stop Camera" disabled>
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6 6h12v12H6z" />
                                </svg>
                                Stop
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <h2>Live Check-in Ticker</h2>
                    <div id="kiosk-ledger" class="ledger-box"></div>
                </div>
            </div>
        </div>

        <div id="view-admin" class="view-section">
            <div id="admin-auth-gate" class="card">
                <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                    <div class="placeholder-icon" style="color: var(--primary); background: var(--primary-glow); margin-bottom:8px;">🔒</div>
                    <h2 style="border:none; padding:0; font-size:16px;">Administrative Access Gate</h2>
                    <span style="font-size:12px; color: var(--muted); text-align:center; max-width:260px; line-height:1.4;">Enter master passcode to view analytics reports, directories and system settings.</span>
                </div>
                <div class="input-group">
                    <label>Master Passcode</label>
                    <input type="password" id="admin-gate-pass" placeholder="Enter master key passcode" onkeydown="if(event.key==='Enter') verifyAdminCredentialGateAccess()">
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="checkbox" id="show-pass-check" onchange="toggleGatePasswordReveal()" style="width:auto; cursor:pointer;">
                    <label for="show-pass-check" style="cursor:pointer; font-size:11px; color: var(--muted); text-transform:none; letter-spacing:0;">Reveal passcode input characters</label>
                </div>
                <button class="btn btn-primary" onclick="verifyAdminCredentialGateAccess()" style="width:100%;">Verify Identity Signature</button>
                <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px; font-size: 11px; line-height: 1.45;">
                    <div style="background: var(--warning-bg); color: var(--warning); border: 1px solid var(--warning); padding: 8px 10px; border-radius: 6px; display: flex; gap: 8px;">
                        <span style="font-size: 14px;">🔑</span>
                        <div>
                            <strong style="display: block; margin-bottom: 2px;">Default Access Key</strong>
                            The default master passcode is <code style="font-family: monospace; font-weight: 700; background: rgba(0,0,0,0.05); padding: 1px 4px; border-radius: 3px;">admin1234</code>.
                        </div>
                    </div>
                </div>
            </div>

            <div id="admin-panel-content" style="display: none; flex-direction: column; gap: 16px; width: 100%;">
                <div class="sub-nav">
                    <div class="sub-nav-tabs">
                        <button id="sub-tab-reports" class="sub-tab-btn active" onclick="switchSubTab('reports')">📊 Analytics & Reports</button>
                        <button id="sub-tab-employees" class="sub-tab-btn" onclick="switchSubTab('employees')">👥 Profile Directory</button>
                        <button id="sub-tab-settings" class="sub-tab-btn" onclick="switchSubTab('settings')">⚙️ Controls & Policies</button>
                    </div>
                    <button class="btn btn-secondary" onclick="logoutAdminGate()" style="padding: 6px 12px; font-size: 11px;">🔒 Lock Console</button>
                </div>

                <div id="sub-view-reports" class="sub-view-section" style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-label">Profiles Enrolled</div>
                            <div id="stat-total-emp" class="metric-value">0</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-label">Present Today</div>
                            <div id="stat-present" class="metric-value">0</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-label">On-Time</div>
                            <div id="stat-on-time" class="metric-value">0</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-label">Late</div>
                            <div id="stat-late" class="metric-value">0</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-label">Absentees</div>
                            <div id="stat-absent" class="metric-value">0</div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="card">
                            <h2>Weekly Attendance Distribution</h2>
                            <div id="analytics-chart-container" style="min-height: 180px; display: flex; align-items: center; justify-content: center;"></div>
                        </div>
                        <div class="card">
                            <h2>Department Distribution</h2>
                            <div style="display: flex; align-items: center; justify-content: center; min-height: 180px; gap: 16px;">
                                <canvas id="dept-canvas" width="140" height="140" style="max-width: 140px; max-height: 140px;"></canvas>
                                <div id="dept-legend" style="display: flex; flex-direction: column; gap: 4px; font-size: 11px;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="card">
                            <h2>Monthly Logs Heatmap</h2>
                            <div id="heatmap-container" style="min-height: 140px; display: flex; flex-direction: column; justify-content: center; gap: 12px;"></div>
                        </div>
                        <div class="card">
                            <h2>Datatable Advanced Filters</h2>
                            <div class="datatable-header" style="flex-direction: column; align-items: stretch; gap: 10px;">
                                <div class="datatable-search-wrapper">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                    <input type="text" id="report-search-name" class="datatable-search-input" placeholder="Search name or ID..." oninput="reportSearchChange()">
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:8px;">
                                    <div class="input-group">
                                        <label>Date Range</label>
                                        <select id="report-filter-date" onchange="reportFilterChange()">
                                            <option value="all">All Dates</option>
                                            <option value="today">Today</option>
                                            <option value="yesterday">Yesterday</option>
                                            <option value="week">Last 7 Days</option>
                                            <option value="month">Last 30 Days</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label>Department</label>
                                        <select id="report-filter-dept" onchange="reportFilterChange()">
                                            <option value="all">All Departments</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:8px;">
                                    <div class="input-group">
                                        <label>Event Type</label>
                                        <select id="report-filter-type" onchange="reportFilterChange()">
                                            <option value="all">All Types</option>
                                            <option value="Check-In">Check-In</option>
                                            <option value="Check-Out">Check-Out</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label>Policy Status</label>
                                        <select id="report-filter-status" onchange="reportFilterChange()">
                                            <option value="all">All Statuses</option>
                                            <option value="On-Time">On-Time</option>
                                            <option value="Late">Late</option>
                                            <option value="Exempt">Exempt</option>
                                            <option value="-">Check-Out (-)</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:8px;">
                                    <button class="btn btn-primary" onclick="openRetroactiveModal(null)">➕ Add Retro Log</button>
                                    <button class="btn btn-secondary" onclick="exportReportsToCSV()">
                                        📥 Export CSV
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h2>Attendance Records Datatable</h2>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>ID</th>
                                        <th>Employee Name</th>
                                        <th>Department</th>
                                        <th>Role</th>
                                        <th>Event Type</th>
                                        <th>Policy Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="reports-table-body"></tbody>
                            </table>
                        </div>
                        <div class="datatable-footer">
                            <div id="reports-paging-info">Showing 0 to 0 of 0 logs</div>
                            <div class="datatable-pagination">
                                <button id="btn-report-prev" class="datatable-page-btn" onclick="changeReportsPage(-1)">Prev</button>
                                <button id="btn-report-next" class="datatable-page-btn" onclick="changeReportsPage(1)">Next</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="sub-view-employees" class="sub-view-section" style="display: none; flex-direction: column; gap: 16px;">
                    <div class="grid-2">
                        <div class="card">
                            <h2 id="reg-form-title">Enroll New Employee Profile</h2>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                                <div class="input-group">
                                    <label>Employee Name</label>
                                    <input type="text" id="reg-employee-name" placeholder="John Doe">
                                </div>
                                <div class="input-group">
                                    <label>Employee ID</label>
                                    <input type="text" id="reg-employee-id" placeholder="EMP-001">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                                <div class="input-group">
                                    <label>Department</label>
                                    <select id="reg-employee-dept"></select>
                                </div>
                                <div class="input-group">
                                    <label>Role / Title</label>
                                    <input type="text" id="reg-employee-role" placeholder="Software Engineer">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                                <div class="input-group">
                                    <label>Email Address</label>
                                    <input type="email" id="reg-employee-email" placeholder="john.doe@company.com">
                                </div>
                                <div class="input-group">
                                    <label>Phone Number</label>
                                    <input type="tel" id="reg-employee-phone" placeholder="+1234567890">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                                <div class="input-group">
                                    <label>Profile Status</label>
                                    <select id="reg-employee-status">
                                        <option value="Active">Active</option>
                                        <option value="Banned">Suspended</option>
                                    </select>
                                </div>
                            </div>

                            <div class="input-group" style="margin-top: 4px;">
                                <label>Facial Recognition Enrollment Preview</label>
                                <div class="camera-container" style="padding-top: 56.25%; margin-top: 4px; border-radius: 6px;">
                                    <video id="webcam-enroll-stream" autoplay loop muted playsinline></video>
                                    <div id="enroll-camera-placeholder" class="camera-placeholder-overlay" style="background: var(--bg);">
                                        <div class="placeholder-icon" style="width:40px; height:40px; font-size:20px;">📷</div>
                                        <div class="placeholder-title" style="font-size:12px;">Enrollment Camera Inactive</div>
                                        <button class="btn btn-secondary" onclick="startEnrollmentCamera()" style="padding: 4px 10px; font-size: 11px; margin-top: 4px;">Start Preview</button>
                                    </div>
                                </div>
                            </div>

                            <div id="reg-actions-enroll" style="display: flex; flex-direction: column; gap: 8px; margin-top:12px;">
                                <button id="btn-trigger-capture" class="btn btn-primary" onclick="captureAndSerializeTopology()" disabled>Capture Via Webcam</button>
                                <div class="dropzone-area" onclick="document.getElementById('reg-file-upload').click()">
                                    <span>📁 Upload Portrait Photo</span>
                                </div>
                                <input type="file" id="reg-file-upload" accept="image/*" style="display: none;" onchange="handleImageEnrollmentUpload(event)">
                            </div>
                            <div id="reg-actions-edit" style="display: none; flex-direction: column; gap: 8px; margin-top:12px;">
                                <button class="btn btn-primary" onclick="commitEditEmployeeProfile()">Save Profile Changes</button>
                                <button id="btn-trigger-capture-edit" class="btn btn-secondary" onclick="captureAndSerializeTopology()" disabled>Update Face Via Webcam</button>
                                <div class="dropzone-area" onclick="document.getElementById('reg-file-upload-edit').click()">
                                    <span>📁 Update Face Portrait Photo</span>
                                </div>
                                <input type="file" id="reg-file-upload-edit" accept="image/*" style="display: none;" onchange="handleImageEnrollmentUpload(event)">
                                <button class="btn btn-danger" onclick="cancelEditEmployeeMode()">Cancel Editing</button>
                            </div>

                            <h2 style="margin-top:8px;">Manual Check-in Override</h2>
                            <div style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap:8px;">
                                <div class="input-group">
                                    <label>Select employee profile</label>
                                    <select id="manual-log-employee"></select>
                                </div>
                                <div class="input-group">
                                    <label>Check Type</label>
                                    <select id="manual-log-type">
                                        <option value="Check-In">Check-In</option>
                                        <option value="Check-Out">Check-Out</option>
                                    </select>
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="commitManualAttendanceLog()">Register Manual Check</button>
                        </div>

                        <div class="card">
                            <h2>Profiles Records Directory</h2>
                            <div class="datatable-toolbar">
                                <div class="datatable-search-wrapper">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                    <input type="text" id="emp-search-name" class="datatable-search-input" placeholder="Search profiles..." oninput="empSearchChange()">
                                </div>
                                <div class="input-group" style="width: auto;">
                                    <select id="emp-filter-dept" onchange="empSearchChange()" style="width: 150px;">
                                        <option value="all">All Departments</option>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employee Name</th>
                                            <th>Department</th>
                                            <th>Role</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Date Enrolled</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="directory-table-body"></tbody>
                                </table>
                            </div>
                            <div class="datatable-footer">
                                <div id="emp-paging-info">Showing 0 to 0 of 0 profiles</div>
                                <div class="datatable-pagination">
                                    <button id="btn-emp-prev" class="datatable-page-btn" onclick="changeEmpPage(-1)">Prev</button>
                                    <button id="btn-emp-next" class="datatable-page-btn" onclick="changeEmpPage(1)">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="sub-view-settings" class="sub-view-section" style="display: none; flex-direction: column; gap: 16px;">
                    <div class="grid-2">
                        <div class="card">
                            <h2>System Themes & Styling</h2>
                            <div class="input-group">
                                <label>Accent Color Brand Palette</label>
                                <div class="theme-picker-group">
                                    <div class="color-dot indigo" onclick="applyAccentColor('indigo')" id="dot-indigo"></div>
                                    <div class="color-dot emerald" onclick="applyAccentColor('emerald')" id="dot-emerald"></div>
                                    <div class="color-dot rose" onclick="applyAccentColor('rose')" id="dot-rose"></div>
                                    <div class="color-dot amber" onclick="applyAccentColor('amber')" id="dot-amber"></div>
                                    <div class="color-dot blue" onclick="applyAccentColor('blue')" id="dot-blue"></div>
                                </div>
                            </div>

                            <h2 style="margin-top: 10px;">Liveness Challenge Anti-Spoofing</h2>
                            <div class="input-group">
                                <label>Anti-Spoofing Protocol Level</label>
                                <select id="settings-liveness-level" onchange="saveLivenessSettings()">
                                    <option value="none">Level 0: Standard Face Match (No Liveness)</option>
                                    <option value="blink">Level 1: Passive Eye Blink Verification</option>
                                    <option value="challenge">Level 2: Interactive Head Turn Challenge</option>
                                </select>
                            </div>

                            <h2 style="margin-top: 10px;">Shift & Late Policy Policies</h2>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <div class="input-group">
                                    <label>Shift Start Time</label>
                                    <input type="time" id="settings-shift-time" value="09:00">
                                </div>
                                <div class="input-group">
                                    <label>Grace Period (Min)</label>
                                    <input type="number" id="settings-grace-minutes" value="15" min="0">
                                </div>
                            </div>
                            <div class="input-group">
                                <label>Weekend Days Selection</label>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px;">
                                    <label style="display:flex; align-items:center; gap:4px; text-transform:none; font-size:12px; font-weight:500; cursor:pointer;">
                                        <input type="checkbox" id="wk-0" value="0" style="width:auto;"> Sun
                                    </label>
                                    <label style="display:flex; align-items:center; gap:4px; text-transform:none; font-size:12px; font-weight:500; cursor:pointer;">
                                        <input type="checkbox" id="wk-1" value="1" style="width:auto;"> Mon
                                    </label>
                                    <label style="display:flex; align-items:center; gap:4px; text-transform:none; font-size:12px; font-weight:500; cursor:pointer;">
                                        <input type="checkbox" id="wk-2" value="2" style="width:auto;"> Tue
                                    </label>
                                    <label style="display:flex; align-items:center; gap:4px; text-transform:none; font-size:12px; font-weight:500; cursor:pointer;">
                                        <input type="checkbox" id="wk-3" value="3" style="width:auto;"> Wed
                                    </label>
                                    <label style="display:flex; align-items:center; gap:4px; text-transform:none; font-size:12px; font-weight:500; cursor:pointer;">
                                        <input type="checkbox" id="wk-4" value="4" style="width:auto;"> Thu
                                    </label>
                                    <label style="display:flex; align-items:center; gap:4px; text-transform:none; font-size:12px; font-weight:500; cursor:pointer;">
                                        <input type="checkbox" id="wk-5" value="5" style="width:auto;"> Fri
                                    </label>
                                    <label style="display:flex; align-items:center; gap:4px; text-transform:none; font-size:12px; font-weight:500; cursor:pointer;">
                                        <input type="checkbox" id="wk-6" value="6" style="width:auto;"> Sat
                                    </label>
                                </div>
                            </div>
                            <button class="btn btn-secondary" onclick="saveShiftPolicySettings()">Apply Shift & Calendar Rules</button>

                            <h2 style="margin-top: 10px;">Security Access Settings</h2>
                            <div class="input-group">
                                <label>Change Dashboard Password</label>
                                <input type="password" id="admin-new-pass" placeholder="Enter new master key passcode">
                            </div>
                            <button class="btn btn-secondary" onclick="updateAdministrativeSystemPassword()">Update Passcode Key</button>
                        </div>

                        <div class="card">
                            <h2>Corporate Calendars & Divisions</h2>
                            <div class="input-group">
                                <label>Manage Departments</label>
                                <div style="display: flex; gap: 6px;">
                                    <input type="text" id="settings-new-dept" placeholder="Administration">
                                    <button class="btn btn-primary" onclick="addNewDepartment()" style="padding: 0 16px;">Add</button>
                                </div>
                                <div id="dept-list-container" style="display: flex; flex-direction: column; gap: 4px; max-height: 120px; overflow-y: auto; margin-top: 4px; padding-right: 4px;"></div>
                            </div>
                            <div class="input-group" style="margin-top: 8px;">
                                <label>Holidays Exclusions</label>
                                <div style="display: flex; gap: 6px;">
                                    <input type="date" id="settings-new-holiday-date" style="width:130px;">
                                    <input type="text" id="settings-new-holiday-name" placeholder="Christmas Day" style="flex:1;">
                                    <button class="btn btn-primary" onclick="addNewHoliday()" style="padding: 0 16px;">Add</button>
                                </div>
                                <div id="holiday-list-container" style="display: flex; flex-direction: column; gap: 4px; max-height: 120px; overflow-y: auto; margin-top: 4px; padding-right: 4px;"></div>
                            </div>

                            <h2 style="margin-top: 10px;">System Datastore Utilities</h2>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <button class="btn btn-secondary" onclick="exportDatastorePayload()">Export Backup JSON</button>
                                <button class="btn btn-secondary" onclick="document.getElementById('file-import').click()">Import Backup JSON</button>
                                <input type="file" id="file-import" accept=".json" style="display:none" onchange="importDatastorePayload(event)">
                            </div>
                            <button class="btn btn-danger" onclick="purgeSystemLocalMemoryStore()">Purge Local Database Indexes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="retroactive-log-modal">
        <div class="modal-card">
            <h2 id="retro-modal-title">Retroactive Attendance Log Entry</h2>
            <input type="hidden" id="retro-log-id">
            <div class="input-group">
                <label>Select Employee Profile</label>
                <select id="retro-log-employee"></select>
            </div>
            <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 8px;">
                <div class="input-group">
                    <label>Log Date</label>
                    <input type="date" id="retro-log-date">
                </div>
                <div class="input-group">
                    <label>Log Time</label>
                    <input type="time" id="retro-log-time" step="1">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div class="input-group">
                    <label>Event Type</label>
                    <select id="retro-log-type">
                        <option value="Check-In">Check-In</option>
                        <option value="Check-Out">Check-Out</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Policy Status</label>
                    <select id="retro-log-status">
                        <option value="On-Time">On-Time</option>
                        <option value="Late">Late</option>
                        <option value="Exempt">Exempt</option>
                        <option value="-">Check-Out (-)</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 8px;">
                <button class="btn btn-secondary" onclick="closeRetroactiveModal()">Cancel</button>
                <button class="btn btn-primary" onclick="commitRetroactiveAttendanceLog()">Save Attendance Record</button>
            </div>
        </div>
    </div>

    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

    <script>
        const CDN_MODELS_PATH = 'https://justadudewhohacks.github.io/face-api.js/models';

        async function readAllDatabaseRecords(storeName) {
            let act = storeName;
            if (storeName === 'attendance_logs') act = 'logs';
            const res = await fetch(`index.php?action=get_${act}`);
            return await res.json();
        }

        async function writeDatabaseRecord(storeName, valueObject) {
            if (storeName === 'system_config') {
                await writeConfigKey(valueObject.key, valueObject.value);
                return;
            }
            let act = storeName;
            if (storeName === 'attendance_logs') act = 'log';
            else if (storeName === 'employees') act = 'employee';
            else if (storeName === 'departments') act = 'department';
            else if (storeName === 'holidays') act = 'holiday';

            let method = 'save';
            if (storeName === 'attendance_logs' && valueObject.id) {
                method = 'update';
            }

            const res = await fetch(`index.php?action=${method}_${act}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(valueObject)
            });
            return await res.json();
        }

        async function deleteDatabaseRecord(storeName, keyId) {
            let act = storeName;
            let paramKey = 'id';
            if (storeName === 'attendance_logs') {
                act = 'log';
            } else if (storeName === 'employees') {
                act = 'employee';
            } else if (storeName === 'departments') {
                act = 'department';
                paramKey = 'name';
            } else if (storeName === 'holidays') {
                act = 'holiday';
                paramKey = 'date';
            }

            const res = await fetch(`index.php?action=delete_${act}&${paramKey}=${encodeURIComponent(keyId)}`);
            return await res.json();
        }

        async function readConfigKey(key) {
            const res = await fetch('index.php?action=get_config');
            const config = await res.json();
            if (key === 'weekend_days' && typeof config[key] === 'string') {
                return JSON.parse(config[key]);
            }
            return config[key] || null;
        }

        async function writeConfigKey(key, value) {
            const payload = {};
            payload[key] = value;
            await fetch('index.php?action=save_config', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
        }

        const AudioEngine = {
            ctx: null,
            init() {
                if (!this.ctx) {
                    this.ctx = new (window.AudioContext || window.webkitAudioContext)();
                }
            },
            play(type) {
                this.init();
                if (!this.ctx) return;
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.connect(gain);
                gain.connect(this.ctx.destination);
                const now = this.ctx.currentTime;
                if (type === 'success') {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(523.25, now);
                    osc.frequency.setValueAtTime(659.25, now + 0.15);
                    gain.gain.setValueAtTime(0.08, now);
                    gain.gain.linearRampToValueAtTime(0, now + 0.3);
                    osc.start(now);
                    osc.stop(now + 0.3);
                } else if (type === 'warning') {
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(329.63, now);
                    osc.frequency.setValueAtTime(329.63, now + 0.15);
                    gain.gain.setValueAtTime(0.08, now);
                    gain.gain.linearRampToValueAtTime(0, now + 0.3);
                    osc.start(now);
                    osc.stop(now + 0.3);
                } else if (type === 'error') {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(130.81, now);
                    gain.gain.setValueAtTime(0.12, now);
                    gain.gain.linearRampToValueAtTime(0, now + 0.4);
                    osc.start(now);
                    osc.stop(now + 0.4);
                }
            }
        };

        function speakText(text) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.volume = 1.0;
                utterance.rate = 1.05;
                utterance.pitch = 1.0;
                window.speechSynthesis.speak(utterance);
            }
        }

        function triggerToast(text, style = 'success') {
            const container = document.getElementById('toast-container');
            const div = document.createElement('div');
            div.className = `toast ${style}`;
            div.innerText = text;
            container.appendChild(div);
            setTimeout(() => { div.classList.add('show'); }, 50);
            setTimeout(() => {
                div.classList.remove('show');
                setTimeout(() => { div.remove(); }, 300);
            }, 3000);
        }

        const ui = {
            video: document.getElementById('webcam-stream'),
            videoEnroll: document.getElementById('webcam-enroll-stream'),
            enrollPlaceholder: document.getElementById('enroll-camera-placeholder'),
            canvas: document.getElementById('canvas-runtime-draw'),
            banner: document.getElementById('system-alert-banner'),
            captureBtn: document.getElementById('btn-trigger-capture'),
            regName: document.getElementById('reg-employee-name'),
            directoryTable: document.getElementById('directory-table-body'),
            kioskLedger: document.getElementById('kiosk-ledger'),
            authGate: document.getElementById('admin-auth-gate'),
            passInput: document.getElementById('admin-gate-pass'),
            newPassInput: document.getElementById('admin-new-pass'),
            cameraStatus: document.getElementById('camera-status-pill'),
            placeholder: document.getElementById('camera-placeholder'),
            btnStart: document.getElementById('btn-cam-start'),
            btnPause: document.getElementById('btn-cam-pause'),
            btnStop: document.getElementById('btn-cam-stop')
        };

        let applicationFaceMatcherRuntime = null;
        let precompiledProfilesRegistry = [];
        let precompiledEmployeesList = [];
        const transactionThrottleMap = new Map();

        let livenessChallengeMode = "none";
        const livenessTrackingCache = new Map();

        let isDashboardAuthenticatedState = false;
        let isCameraActive = false;
        let isCameraPaused = false;
        let isEnrollCameraActive = false;
        let inferenceIntervalId = null;
        let latestFaceDetections = [];
        let currentEditingEmployeeId = null;

        let scanLinePos = 0;
        let scanLineSpeed = 2.5;
        let scanLineDirection = 1;

        let shiftStartTime = "09:00";
        let shiftGraceMinutes = 15;
        let weekendDays = [0, 6];

        let reportsPage = 1;
        const reportsRowsPerPage = 10;
        let filteredReportsList = [];

        let empPage = 1;
        const empRowsPerPage = 10;
        let filteredEmpList = [];

        async function verifyAdminCredentialGateAccess() {
            const inputString = ui.passInput.value;
            const res = await fetch('index.php?action=verify_login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password: inputString })
            });
            const result = await res.json();
            if (result.success) {
                isDashboardAuthenticatedState = true;
                ui.authGate.style.display = "none";
                document.getElementById('admin-panel-content').style.display = "flex";
                ui.passInput.value = "";
                document.getElementById('show-pass-check').checked = false;
                ui.passInput.type = "password";
                switchSubTab('reports');
                triggerToast("Authentication Signature Verified", "success");
                AudioEngine.play('success');
            } else {
                triggerToast("Access Denied: Signature Mismatch", "error");
                AudioEngine.play('error');
            }
        }

        function toggleGatePasswordReveal() {
            const check = document.getElementById('show-pass-check');
            ui.passInput.type = check.checked ? "text" : "password";
        }

        async function logoutAdminGate() {
            await fetch('index.php?action=logout');
            isDashboardAuthenticatedState = false;
            stopEnrollmentCamera();
            ui.authGate.style.display = "flex";
            document.getElementById('admin-panel-content').style.display = "none";
            ui.passInput.value = "";
            triggerToast("Admin Console Locked", "warning");
            AudioEngine.play('warning');
        }

        async function updateAdministrativeSystemPassword() {
            const targetString = ui.newPassInput.value.trim();
            if (targetString.length < 4) {
                triggerToast("Passcode must be 4+ characters", "error");
                return;
            }
            await writeConfigKey("master_password", targetString);
            ui.newPassInput.value = "";
            triggerToast("Admin Passcode Updated", "success");
            AudioEngine.play('success');
        }

        function updateCameraControlsUI() {
            if (isCameraActive) {
                ui.placeholder.style.opacity = '0';
                ui.placeholder.style.pointerEvents = 'none';
                ui.btnStart.disabled = !isCameraPaused;
                ui.btnPause.disabled = isCameraPaused;
                ui.btnStop.disabled = false;
                ui.cameraStatus.innerText = isCameraPaused ? "PAUSED" : "ONLINE";
                ui.cameraStatus.style.background = isCameraPaused ? "var(--warning-bg)" : "var(--success-bg)";
                ui.cameraStatus.style.color = isCameraPaused ? "var(--warning)" : "var(--success)";
                ui.cameraStatus.style.borderColor = isCameraPaused ? "var(--warning)" : "var(--success)";
            } else {
                ui.placeholder.style.opacity = '1';
                ui.placeholder.style.pointerEvents = 'auto';
                ui.btnStart.disabled = false;
                ui.btnPause.disabled = true;
                ui.btnStop.disabled = true;
                ui.cameraStatus.innerText = "OFFLINE";
                ui.cameraStatus.style.background = "var(--danger-bg)";
                ui.cameraStatus.style.color = "var(--danger)";
                ui.cameraStatus.style.borderColor = "var(--danger)";
            }
        }

        async function startCameraStream() {
            if (isCameraActive) {
                if (isCameraPaused) {
                    ui.video.play();
                    isCameraPaused = false;
                    initiateContinuousInferenceEngineLoop();
                    updateCameraControlsUI();
                    ui.banner.innerText = "Scanner online. Position face in scanner.";
                }
                return;
            }
            ui.banner.innerText = "Starting hardware scanning array connections...";
            try {
                const captureStream = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: { facingMode: "user", width: { ideal: 640 }, height: { ideal: 480 } }
                });
                ui.video.srcObject = captureStream;
                ui.video.onloadedmetadata = () => {
                    ui.video.play().then(() => {
                        isCameraActive = true;
                        isCameraPaused = false;
                        executeCanvasBoundingSynchronization();
                        ui.banner.innerText = "Scanner online. Position face in scanner.";
                        initiateContinuousInferenceEngineLoop();
                        updateCameraControlsUI();
                    });
                };
            } catch (err) {
                ui.banner.innerText = "Hardware Fault: Camera stream connection offline.";
                triggerToast("Webcam Access Denied", "error");
                AudioEngine.play('error');
            }
        }

        function pauseCameraStream() {
            if (isCameraActive && !isCameraPaused) {
                ui.video.pause();
                isCameraPaused = true;
                if (inferenceIntervalId) {
                    clearInterval(inferenceIntervalId);
                    inferenceIntervalId = null;
                }
                latestFaceDetections = [];
                updateCameraControlsUI();
                ui.banner.innerText = "Camera scan stream suspended.";
            }
        }

        function stopCameraStream() {
            if (isCameraActive) {
                if (inferenceIntervalId) {
                    clearInterval(inferenceIntervalId);
                    inferenceIntervalId = null;
                }
                ui.video.pause();
                const stream = ui.video.srcObject;
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
                ui.video.srcObject = null;
                isCameraActive = false;
                isCameraPaused = false;
                latestFaceDetections = [];
                updateCameraControlsUI();
                ui.banner.innerText = "Scanning framework offline.";
            }
        }

        async function startEnrollmentCamera() {
            if (isEnrollCameraActive) return;
            try {
                const captureStream = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: { facingMode: "user", width: { ideal: 640 }, height: { ideal: 480 } }
                });
                ui.videoEnroll.srcObject = captureStream;
                ui.videoEnroll.onloadedmetadata = () => {
                    ui.videoEnroll.play().then(() => {
                        isEnrollCameraActive = true;
                        ui.enrollPlaceholder.style.opacity = '0';
                        ui.enrollPlaceholder.style.pointerEvents = 'none';
                        ui.captureBtn.disabled = false;
                        const editBtn = document.getElementById('btn-trigger-capture-edit');
                        if (editBtn) editBtn.disabled = false;
                    });
                };
            } catch (err) {
                triggerToast("Webcam access failed", "error");
            }
        }

        function stopEnrollmentCamera() {
            if (isEnrollCameraActive) {
                ui.videoEnroll.pause();
                const stream = ui.videoEnroll.srcObject;
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
                ui.videoEnroll.srcObject = null;
                isEnrollCameraActive = false;
                ui.enrollPlaceholder.style.opacity = '1';
                ui.enrollPlaceholder.style.pointerEvents = 'auto';
                ui.captureBtn.disabled = true;
                const editBtn = document.getElementById('btn-trigger-capture-edit');
                if (editBtn) editBtn.disabled = true;
            }
        }

        function executeCanvasBoundingSynchronization() {
            const boundsWidth = ui.video.videoWidth || 640;
            const boundsHeight = ui.video.videoHeight || 480;
            ui.canvas.width = boundsWidth;
            ui.canvas.height = boundsHeight;
            faceapi.matchDimensions(ui.canvas, { width: boundsWidth, height: boundsHeight });
        }

        async function exportDatastorePayload() {
            const emps = await readAllDatabaseRecords("employees");
            const logs = await readAllDatabaseRecords("attendance_logs");
            const depts = await readAllDatabaseRecords("departments");
            const holidays = await readAllDatabaseRecords("holidays");
            const payload = { emps, logs, depts, holidays };
            const blob = new Blob([JSON.stringify(payload)], { type: "application/json" });
            const temporaryAnchor = document.createElement("a");
            temporaryAnchor.href = URL.createObjectURL(blob);
            temporaryAnchor.download = `facial_attendance_registry_backup_${Math.floor(Date.now() / 1000)}.json`;
            temporaryAnchor.click();
            triggerToast("Backup JSON Saved", "success");
            AudioEngine.play('success');
        }

        async function importDatastorePayload(event) {
            const targetFile = event.target.files[0];
            if (!targetFile) return;
            const reader = new FileReader();
            reader.onload = async (e) => {
                try {
                    const dataset = JSON.parse(e.target.result);
                    if (dataset.emps) {
                        for (const item of dataset.emps) {
                            await writeDatabaseRecord("employees", item);
                        }
                    }
                    if (dataset.logs) {
                        for (const item of dataset.logs) {
                            await writeDatabaseRecord("attendance_logs", item);
                        }
                    }
                    if (dataset.depts) {
                        for (const item of dataset.depts) {
                            await writeDatabaseRecord("departments", item);
                        }
                    }
                    if (dataset.holidays) {
                        for (const item of dataset.holidays) {
                            await writeDatabaseRecord("holidays", item);
                        }
                    }
                    triggerToast("Registry Backup Synchronized", "success");
                    AudioEngine.play('success');
                    await rebuildComputationalMatchingGraph();
                    await renderAdministrativeEmployeeDirectoryTable();
                    populateManualOverrideDropdown();
                    suggestNextEmployeeId();
                    await syncActiveFiltersList();
                } catch (err) {
                    triggerToast("File format invalid", "error");
                    AudioEngine.play('error');
                }
            };
            reader.readAsText(targetFile);
        }

        async function purgeSystemLocalMemoryStore() {
            if (confirm("Danger: Wiping all local records and logs. Continue?")) {
                await fetch('index.php?action=delete_employee&id=all');
                await fetch('index.php?action=delete_log&id=all');
                await fetch('index.php?action=delete_holiday&date=all');
                precompiledEmployeesList = [];
                precompiledProfilesRegistry = [];
                applicationFaceMatcherRuntime = null;
                latestFaceDetections = [];
                ui.banner.innerText = "Memory registries purged.";
                triggerToast("Local Database Dropped", "warning");
                AudioEngine.play('warning');
                await renderAdministrativeEmployeeDirectoryTable();
                populateManualOverrideDropdown();
                suggestNextEmployeeId();
                await syncActiveFiltersList();
            }
        }

        async function compileApplicationCoreSubsystems() {
            try {
                startRenderLoop();
                const checkSessionRes = await fetch('index.php?action=check_session');
                const checkSession = await checkSessionRes.json();
                isDashboardAuthenticatedState = checkSession.authenticated;

                const accentColor = await readConfigKey("theme_color");
                applyAccentColor(accentColor);

                const themeMode = await readConfigKey("theme_mode");
                if (themeMode === 'dark') {
                    document.body.classList.add('dark');
                } else {
                    document.body.classList.remove('dark');
                }

                const liveLevel = await readConfigKey("liveness_level");
                livenessChallengeMode = liveLevel;
                document.getElementById('settings-liveness-level').value = liveLevel;

                const savedTime = await readConfigKey("shift_start_time");
                if (savedTime) {
                    shiftStartTime = savedTime;
                    document.getElementById('settings-shift-time').value = savedTime;
                }
                const savedGrace = await readConfigKey("shift_grace_minutes");
                if (savedGrace) {
                    shiftGraceMinutes = parseInt(savedGrace, 10);
                    document.getElementById('settings-grace-minutes').value = savedGrace;
                }
                const savedWk = await readConfigKey("weekend_days");
                if (savedWk) {
                    weekendDays = savedWk;
                    for (let i = 0; i < 7; i++) {
                        document.getElementById(`wk-${i}`).checked = savedWk.includes(i);
                    }
                }

                ui.banner.innerText = "Downloading core weight libraries (approx. 5MB)...";
                if (faceapi.tf && faceapi.tf.setBackend) await faceapi.tf.setBackend('cpu');
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(CDN_MODELS_PATH),
                    faceapi.nets.faceLandmark68Net.loadFromUri(CDN_MODELS_PATH),
                    faceapi.nets.faceRecognitionNet.loadFromUri(CDN_MODELS_PATH)
                ]);
                await rebuildComputationalMatchingGraph();
                ui.banner.innerText = "Facial recognition core loaded. System online.";
                updateCameraControlsUI();
                await loadAttendanceLedgingTicker();
                suggestNextEmployeeId();
                await syncActiveFiltersList();
            } catch (err) {
                ui.banner.innerText = "Fatal core startup framework exception.";
            }
        }

        async function loadEmployeesListFromDB() {
            precompiledEmployeesList = await readAllDatabaseRecords("employees");
        }

        async function rebuildComputationalMatchingGraph() {
            await loadEmployeesListFromDB();
            precompiledProfilesRegistry = [];
            if (precompiledEmployeesList.length > 0) {
                precompiledEmployeesList.forEach(record => {
                    const floatArray = new Float32Array(record.descriptor);
                    precompiledProfilesRegistry.push(new faceapi.LabeledFaceDescriptors(record.id, [floatArray]));
                });
                applicationFaceMatcherRuntime = new faceapi.FaceMatcher(precompiledProfilesRegistry, 0.48);
            } else {
                applicationFaceMatcherRuntime = null;
            }
        }

        async function suggestNextEmployeeId() {
            const list = await readAllDatabaseRecords("employees");
            const nextIdx = list.length + 1;
            document.getElementById('reg-employee-id').value = "EMP-" + String(nextIdx).padStart(3, '0');
        }

        async function captureAndSerializeTopology() {
            const targetLabelString = ui.regName.value.trim();
            const targetIdString = document.getElementById('reg-employee-id').value.trim();
            const targetDeptString = document.getElementById('reg-employee-dept').value;
            const targetRoleString = document.getElementById('reg-employee-role').value.trim();
            const targetEmailString = document.getElementById('reg-employee-email').value.trim();
            const targetPhoneString = document.getElementById('reg-employee-phone').value.trim();
            if (!targetLabelString || !targetIdString) {
                triggerToast("Full Name and ID are required", "warning");
                return;
            }
            const existing = await readAllDatabaseRecords("employees");
            const duplicateId = existing.find(e => e.id.toLowerCase() === targetIdString.toLowerCase());
            if (duplicateId && (!currentEditingEmployeeId || currentEditingEmployeeId.toLowerCase() !== targetIdString.toLowerCase())) {
                triggerToast("Employee ID already registered", "error");
                return;
            }
            ui.banner.innerText = `Resolving face characteristics for: [${targetLabelString}]`;
            ui.captureBtn.disabled = true;
            const captureBtnEdit = document.getElementById('btn-trigger-capture-edit');
            if (captureBtnEdit) captureBtnEdit.disabled = true;

            const result = await faceapi.detectSingleFace(ui.videoEnroll, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }))
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!result) {
                triggerToast("Face not found or lighting weak", "error");
                ui.captureBtn.disabled = false;
                if (captureBtnEdit) captureBtnEdit.disabled = false;
                ui.banner.innerText = "Ready.";
                return;
            }

            const standardArrayPayload = Array.from(result.descriptor);
            const targetStatusString = document.getElementById('reg-employee-status').value;
            const originalRecord = existing.find(e => e.id === currentEditingEmployeeId);
            await writeDatabaseRecord("employees", {
                id: targetIdString,
                name: targetLabelString,
                descriptor: standardArrayPayload,
                department: targetDeptString,
                role: targetRoleString || "Staff Member",
                email: targetEmailString || `${targetLabelString.toLowerCase().replace(/\s+/g, '')}@company.com`,
                phone: targetPhoneString || "+1000000000",
                status: targetStatusString,
                joined: originalRecord ? (originalRecord.joined || Date.now()) : Date.now()
            });

            await rebuildComputationalMatchingGraph();
            await renderAdministrativeEmployeeDirectoryTable();
            populateManualOverrideDropdown();
            suggestNextEmployeeId();
            stopEnrollmentCamera();

            const isEditing = !!currentEditingEmployeeId;
            if (isEditing) {
                cancelEditEmployeeMode();
            } else {
                ui.regName.value = "";
                document.getElementById('reg-employee-role').value = "";
                document.getElementById('reg-employee-email').value = "";
                document.getElementById('reg-employee-phone').value = "";
            }
            triggerToast(isEditing ? "Profile Updated Successfully" : "Employee Enrolled Successfully", "success");
            AudioEngine.play('success');
            ui.banner.innerText = "Ready.";
        }

        async function renderAdministrativeEmployeeDirectoryTable() {
            if (!isDashboardAuthenticatedState) return;
            const listing = await readAllDatabaseRecords("employees");
            const searchQuery = document.getElementById('emp-search-name').value.toLowerCase().trim();
            const deptQuery = document.getElementById('emp-filter-dept').value;

            filteredEmpList = listing.filter(emp => {
                if (searchQuery && !emp.name.toLowerCase().includes(searchQuery) && !emp.id.toLowerCase().includes(searchQuery) && !emp.role.toLowerCase().includes(searchQuery) && !emp.email.toLowerCase().includes(searchQuery)) return false;
                if (deptQuery !== "all" && emp.department !== deptQuery) return false;
                return true;
            });

            filteredEmpList.sort((a, b) => (b.joined || 0) - (a.joined || 0));
            const totalRecords = filteredEmpList.length;
            const totalPages = Math.ceil(totalRecords / empRowsPerPage) || 1;
            if (empPage > totalPages) empPage = totalPages;

            const startIndex = (empPage - 1) * empRowsPerPage;
            const endIndex = Math.min(startIndex + empRowsPerPage, totalRecords);
            const pagedEmps = filteredEmpList.slice(startIndex, endIndex);

            const tbody = ui.directoryTable;
            tbody.innerHTML = "";

            if (totalRecords === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--muted);">No matching profiles found.</td></tr>';
                document.getElementById('emp-paging-info').innerText = "Showing 0 to 0 of 0 profiles";
                document.getElementById('btn-emp-prev').disabled = true;
                document.getElementById('btn-emp-next').disabled = true;
                return;
            }

            pagedEmps.forEach(employee => {
                const joinedDate = employee.joined ? new Date(employee.joined).toLocaleDateString() : "-";
                const isBanned = employee.status === "Banned";
                const statusBadge = isBanned 
                    ? `<span style="padding:2px 6px; border-radius:4px; font-size:10px; font-weight:700; background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger);">Suspended</span>` 
                    : `<span style="padding:2px 6px; border-radius:4px; font-size:10px; font-weight:700; background:var(--success-bg); color:var(--success); border:1px solid var(--success);">Active</span>`;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="font-weight:700; color: var(--primary); font-family:monospace;">${employee.id}</td>
                    <td style="font-weight:600; color: var(--text);">${employee.name}</td>
                    <td>${employee.department}</td>
                    <td>${employee.role}</td>
                    <td>${employee.email}</td>
                    <td>${statusBadge}</td>
                    <td style="color:var(--muted);">${joinedDate}</td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px; border-radius:4px;" onclick="startEditEmployee('${employee.id}')">Edit</button>
                            <button class="btn" style="padding:4px 8px; font-size:11px; border-radius:4px; background:${isBanned ? 'var(--success-bg)' : 'var(--warning-bg)'}; color:${isBanned ? 'var(--success)' : 'var(--warning)'}; border:1px solid ${isBanned ? 'var(--success)' : 'var(--warning)'};" onclick="toggleBanEmployee('${employee.id}')">${isBanned ? 'Activate' : 'Suspend'}</button>
                            <button class="btn btn-danger" style="padding:4px 8px; font-size:11px; border-radius: 4px;" onclick="removeProfileTemplateRecord('${employee.id}')">Delete</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('emp-paging-info').innerText = `Showing ${startIndex + 1} to ${endIndex} of ${totalRecords} profiles`;
            document.getElementById('btn-emp-prev').disabled = empPage === 1;
            document.getElementById('btn-emp-next').disabled = empPage === totalPages;
        }

        async function removeProfileTemplateRecord(nameId) {
            if (confirm(`Confirm action: Drop template for ID [${nameId}]?`)) {
                await deleteDatabaseRecord("employees", nameId);
                await rebuildComputationalMatchingGraph();
                await renderAdministrativeEmployeeDirectoryTable();
                populateManualOverrideDropdown();
                suggestNextEmployeeId();
                await syncActiveFiltersList();
                triggerToast("Biometric Template Deleted", "warning");
                AudioEngine.play('warning');
            }
        }

        const evaluateEuclideanPointsDistance = (pt1, pt2) => Math.sqrt(Math.pow(pt1.x - pt2.x, 2) + Math.pow(pt1.y - pt2.y, 2));

        function processEyeAspectRatioMetric(eyeClusterNodes) {
            const vertVectorA = evaluateEuclideanPointsDistance(eyeClusterNodes[1], eyeClusterNodes[5]);
            const vertVectorB = evaluateEuclideanPointsDistance(eyeClusterNodes[2], eyeClusterNodes[4]);
            const horizVector = evaluateEuclideanPointsDistance(eyeClusterNodes[0], eyeClusterNodes[3]);
            return (vertVectorA + vertVectorB) / (2.0 * horizVector);
        }

        function calculateHeadTurnRatio(landmarks) {
            const leftEye = landmarks.getLeftEye()[0];
            const rightEye = landmarks.getRightEye()[3];
            const noseTip = landmarks.getNose()[6];
            const distLeft = evaluateEuclideanPointsDistance(leftEye, noseTip);
            const distRight = evaluateEuclideanPointsDistance(rightEye, noseTip);
            return distLeft / (distRight || 1);
        }

        function drawCustomFaceOverlay(box, label, color, ctx) {
            const canvasWidth = ui.canvas.width;
            const mirroredX = canvasWidth - box.x - box.width;
            ctx.strokeStyle = color;
            ctx.lineWidth = 2.5;
            ctx.beginPath();
            if (ctx.roundRect) {
                ctx.roundRect(mirroredX, box.y, box.width, box.height, 6);
            } else {
                ctx.rect(mirroredX, box.y, box.width, box.height);
            }
            ctx.stroke();

            ctx.fillStyle = color;
            ctx.font = 'bold 11px "Inter", -apple-system, BlinkMacSystemFont, sans-serif';
            const textPadding = 6;
            const textWidth = ctx.measureText(label).width;
            const labelHeight = 20;
            const labelX = mirroredX;
            let labelY = box.y - labelHeight - 4;
            if (labelY < 0) {
                labelY = box.y + box.height + 4;
            }
            ctx.beginPath();
            if (ctx.roundRect) {
                ctx.roundRect(labelX, labelY, textWidth + textPadding * 2, labelHeight, 4);
            } else {
                ctx.rect(labelX, labelY, textWidth + textPadding * 2, labelHeight);
            }
            ctx.fill();
            ctx.fillStyle = '#ffffff';
            ctx.fillText(label, labelX + textPadding, labelY + 14);
        }

        function startRenderLoop() {
            function render() {
                if (isCameraActive && !isCameraPaused) {
                    const ctx = ui.canvas.getContext('2d');
                    ctx.clearRect(0, 0, ui.canvas.width, ui.canvas.height);
                    scanLinePos += scanLineSpeed * scanLineDirection;
                    if (scanLinePos >= ui.canvas.height) {
                        scanLinePos = ui.canvas.height;
                        scanLineDirection = -1;
                    } else if (scanLinePos <= 0) {
                        scanLinePos = 0;
                        scanLineDirection = 1;
                    }
                    const grad = ctx.createLinearGradient(0, scanLinePos - 6, 0, scanLinePos + 6);
                    grad.addColorStop(0, 'rgba(79, 70, 229, 0)');
                    grad.addColorStop(0.5, 'rgba(79, 70, 229, 0.6)');
                    grad.addColorStop(1, 'rgba(79, 70, 229, 0)');
                    ctx.fillStyle = grad;
                    ctx.fillRect(0, scanLinePos - 6, ui.canvas.width, 12);
                    ctx.strokeStyle = 'rgba(79, 70, 229, 0.8)';
                    ctx.lineWidth = 1.5;
                    ctx.beginPath();
                    ctx.moveTo(0, scanLinePos);
                    ctx.lineTo(ui.canvas.width, scanLinePos);
                    ctx.stroke();
                    latestFaceDetections.forEach(face => {
                        drawCustomFaceOverlay(face.box, face.label, face.color, ctx);
                    });
                }
                requestAnimationFrame(render);
            }
            requestAnimationFrame(render);
        }

        function initiateContinuousInferenceEngineLoop() {
            if (inferenceIntervalId) clearInterval(inferenceIntervalId);
            const renderResolutionDimensions = { width: ui.canvas.width, height: ui.canvas.height };
            inferenceIntervalId = setInterval(async () => {
                if (!isCameraActive || isCameraPaused) return;
                const rawInferenceFrameDetections = await faceapi.detectAllFaces(ui.video, new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.4 }))
                    .withFaceLandmarks()
                    .withFaceDescriptors();
                const parsedResultsCollection = faceapi.resizeResults(rawInferenceFrameDetections, renderResolutionDimensions);

                if (!applicationFaceMatcherRuntime) {
                    let tempDetections = [];
                    parsedResultsCollection.forEach(det => {
                        tempDetections.push({
                            box: det.detection.box,
                            label: "Register profiles inside Admin Terminal to scan",
                            color: "rgba(100, 116, 139, 0.9)"
                        });
                    });
                    latestFaceDetections = tempDetections;
                    return;
                }

                let tempDetections = [];
                parsedResultsCollection.forEach(faceObject => {
                    const matchScoreResolution = applicationFaceMatcherRuntime.findBestMatch(faceObject.descriptor);
                    const assignedStringId = matchScoreResolution.label;
                    let outputInterfaceDisplayString = "Unregistered Profile";
                    let activeHexChromeStrokeColor = "rgba(239, 68, 68, 0.9)";
                    if (assignedStringId !== 'unknown') {
                        const emp = precompiledEmployeesList.find(e => e.id === assignedStringId);
                        const displayName = emp ? emp.name : assignedStringId;
                        const displayDept = emp ? ` (${emp.department})` : "";
                        if (emp && emp.status === "Banned") {
                            outputInterfaceDisplayString = `${displayName} - ACCESS SUSPENDED`;
                            activeHexChromeStrokeColor = "rgba(239, 68, 68, 0.9)";
                        } else {
                            if (!livenessTrackingCache.has(assignedStringId)) {
                                const targetChallenge = Math.random() > 0.5 ? "left" : "right";
                                livenessTrackingCache.set(assignedStringId, {
                                    blinkPassed: false,
                                    turnPassed: false,
                                    challenge: targetChallenge
                                });
                            }
                            const tracker = livenessTrackingCache.get(assignedStringId);
                            const meanEar = (processEyeAspectRatioMetric(faceObject.landmarks.getLeftEye()) + processEyeAspectRatioMetric(faceObject.landmarks.getRightEye())) / 2;
                            if (meanEar < 0.23) {
                                tracker.blinkPassed = true;
                            }
                            const symmetry = calculateHeadTurnRatio(faceObject.landmarks);
                            if (tracker.challenge === "left" && symmetry < 0.6) {
                                tracker.turnPassed = true;
                            } else if (tracker.challenge === "right" && symmetry > 1.6) {
                                tracker.turnPassed = true;
                            }

                            if (livenessChallengeMode === "none") {
                                outputInterfaceDisplayString = `${displayName}${displayDept} - Verified`;
                                activeHexChromeStrokeColor = "rgba(16, 185, 129, 0.9)";
                                registerVerifiedAttendanceEvent(assignedStringId);
                            } else if (livenessChallengeMode === "blink") {
                                if (tracker.blinkPassed) {
                                    outputInterfaceDisplayString = `${displayName}${displayDept} - Verified`;
                                    activeHexChromeStrokeColor = "rgba(16, 185, 129, 0.9)";
                                    registerVerifiedAttendanceEvent(assignedStringId);
                                } else {
                                    outputInterfaceDisplayString = `${displayName} - Please Blink Eyes`;
                                    activeHexChromeStrokeColor = "rgba(245, 158, 11, 0.9)";
                                }
                            } else if (livenessChallengeMode === "challenge") {
                                if (tracker.blinkPassed && tracker.turnPassed) {
                                    outputInterfaceDisplayString = `${displayName}${displayDept} - Verified`;
                                    activeHexChromeStrokeColor = "rgba(16, 185, 129, 0.9)";
                                    registerVerifiedAttendanceEvent(assignedStringId);
                                } else if (!tracker.blinkPassed) {
                                    outputInterfaceDisplayString = `${displayName} - Blink Eyes First`;
                                    activeHexChromeStrokeColor = "rgba(245, 158, 11, 0.9)";
                                } else {
                                    outputInterfaceDisplayString = `${displayName} - Turn Head ${tracker.challenge.toUpperCase()}`;
                                    activeHexChromeStrokeColor = "rgba(245, 158, 11, 0.9)";
                                }
                            }
                        }
                    }
                    tempDetections.push({
                        box: faceObject.detection.box,
                        label: outputInterfaceDisplayString,
                        color: activeHexChromeStrokeColor
                    });
                });
                latestFaceDetections = tempDetections;
            }, 250);
        }

        async function registerVerifiedAttendanceEvent(empId) {
            const timestamp = Date.now();
            const suppressionCooldownPeriod = 20000;
            if (transactionThrottleMap.has(empId)) {
                const historicalTime = transactionThrottleMap.get(empId);
                if (timestamp - historicalTime < suppressionCooldownPeriod) return;
            }
            transactionThrottleMap.set(empId, timestamp);
            const emp = precompiledEmployeesList.find(e => e.id === empId);
            if (emp) {
                await recordAttendanceLogToDB(emp.id, emp.name, emp.department, emp.role);
            }
            setTimeout(() => {
                if (livenessTrackingCache.has(empId)) {
                    livenessTrackingCache.delete(empId);
                }
            }, suppressionCooldownPeriod);
        }

        async function recordAttendanceLogToDB(empId, employeeName, empDept, empRole, manualTypeOverride = null) {
            const now = new Date();
            const todayStr = now.toISOString().split('T')[0];
            const allLogs = await readAllDatabaseRecords("attendance_logs");
            const logsToday = allLogs.filter(l => l.empId === empId && l.dateString === todayStr);

            let eventType = "Check-In";
            if (manualTypeOverride) {
                eventType = manualTypeOverride;
            } else if (logsToday.length > 0) {
                const lastLog = logsToday[logsToday.length - 1];
                eventType = lastLog.type === "Check-In" ? "Check-Out" : "Check-In";
            }

            let status = "On-Time";
            if (eventType === "Check-In") {
                const [shiftH, shiftM] = shiftStartTime.split(':').map(Number);
                const shiftLimit = new Date();
                shiftLimit.setHours(shiftH, shiftM + shiftGraceMinutes, 0, 0);
                if (now.getTime() > shiftLimit.getTime()) {
                    status = "Late";
                }
            } else {
                status = "-";
            }

            const logEntry = {
                empId: empId,
                name: employeeName,
                department: empDept,
                role: empRole,
                timestamp: now.getTime(),
                dateString: todayStr,
                timeString: now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                type: eventType,
                status: status
            };

            await writeDatabaseRecord("attendance_logs", logEntry);
            await loadAttendanceLedgingTicker();
            triggerToast(`${eventType} logged for ${employeeName}`, "success");
            AudioEngine.play('success');
            speakText(`${employeeName} verified. ${eventType} registered.`);
        }

        async function loadAttendanceLedgingTicker() {
            ui.kioskLedger.innerHTML = "";
            const allLogs = await readAllDatabaseRecords("attendance_logs");
            const sortedLogs = allLogs.sort((a, b) => b.timestamp - a.timestamp).slice(0, 20);
            sortedLogs.forEach(log => {
                const div = document.createElement('div');
                div.className = `ledger-row success`;
                div.innerHTML = `<span>[${log.type}] ${log.name} - ${log.status}</span><span style="font-size:10px; opacity:0.65;">${log.timeString}</span>`;
                ui.kioskLedger.appendChild(div);
            });
        }

        async function handleImageEnrollmentUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            const targetName = ui.regName.value.trim();
            const targetId = document.getElementById('reg-employee-id').value.trim();
            const targetDept = document.getElementById('reg-employee-dept').value;
            const targetRole = document.getElementById('reg-employee-role').value.trim();
            const targetEmail = document.getElementById('reg-employee-email').value.trim();
            const targetPhone = document.getElementById('reg-employee-phone').value.trim();
            if (!targetName || !targetId) {
                triggerToast("Name and ID are required", "warning");
                return;
            }
            const existing = await readAllDatabaseRecords("employees");
            const duplicateId = existing.find(e => e.id.toLowerCase() === targetId.toLowerCase());
            if (duplicateId && (!currentEditingEmployeeId || currentEditingEmployeeId.toLowerCase() !== targetId.toLowerCase())) {
                triggerToast("Employee ID taken", "error");
                return;
            }
            ui.banner.innerText = "Analyzing portrait biometric frames...";
            const img = new Image();
            img.onload = async () => {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    const result = await faceapi.detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 }))
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    if (!result) {
                        triggerToast("Face characteristics not verified in file", "error");
                        ui.banner.innerText = "Ready.";
                        return;
                    }
                    const standardArrayPayload = Array.from(result.descriptor);
                    const targetStatus = document.getElementById('reg-employee-status').value;
                    const originalRecord = existing.find(e => e.id === currentEditingEmployeeId);
                    await writeDatabaseRecord("employees", {
                        id: targetId,
                        name: targetName,
                        descriptor: standardArrayPayload,
                        department: targetDept,
                        role: targetRole || "Staff Member",
                        email: targetEmail || `${targetName.toLowerCase().replace(/\s+/g, '')}@company.com`,
                        phone: targetPhone || "+1000000000",
                        status: targetStatus,
                        joined: originalRecord ? (originalRecord.joined || Date.now()) : Date.now()
                    });
                    await rebuildComputationalMatchingGraph();
                    await renderAdministrativeEmployeeDirectoryTable();
                    populateManualOverrideDropdown();
                    suggestNextEmployeeId();
                    await syncActiveFiltersList();

                    const isEditing = !!currentEditingEmployeeId;
                    if (isEditing) {
                        cancelEditEmployeeMode();
                    } else {
                        ui.regName.value = "";
                        document.getElementById('reg-employee-role').value = "";
                        document.getElementById('reg-employee-email').value = "";
                        document.getElementById('reg-employee-phone').value = "";
                    }
                    event.target.value = "";
                    triggerToast("Portrait Vector Saved", "success");
                    AudioEngine.play('success');
                    ui.banner.innerText = "Ready.";
                } catch (e) {
                    triggerToast("Biometric analysis error", "error");
                }
            };
            img.src = URL.createObjectURL(file);
        }

        async function populateManualOverrideDropdown() {
            const selectEl = document.getElementById('manual-log-employee');
            selectEl.innerHTML = '<option value="">Select Employee...</option>';
            precompiledEmployeesList.forEach(emp => {
                const opt = document.createElement('option');
                opt.value = emp.id;
                opt.innerText = `${emp.name} (${emp.id})`;
                selectEl.appendChild(opt);
            });
        }

        async function startEditEmployee(empId) {
            const existing = await readAllDatabaseRecords("employees");
            const emp = existing.find(e => e.id === empId);
            if (!emp) return;
            currentEditingEmployeeId = emp.id;
            document.getElementById('reg-form-title').innerText = `Edit Employee Profile: [${emp.name}]`;
            ui.regName.value = emp.name;
            document.getElementById('reg-employee-id').value = emp.id;
            document.getElementById('reg-employee-id').disabled = true;
            document.getElementById('reg-employee-dept').value = emp.department;
            document.getElementById('reg-employee-role').value = emp.role || "";
            document.getElementById('reg-employee-email').value = emp.email || "";
            document.getElementById('reg-employee-phone').value = emp.phone || "";
            document.getElementById('reg-employee-status').value = emp.status || "Active";
            document.getElementById('reg-actions-enroll').style.display = "none";
            document.getElementById('reg-actions-edit').style.display = "flex";
            const editBtn = document.getElementById('btn-trigger-capture-edit');
            if (editBtn) {
                editBtn.disabled = !isEnrollCameraActive;
            }
        }

        function cancelEditEmployeeMode() {
            currentEditingEmployeeId = null;
            document.getElementById('reg-form-title').innerText = "Enroll New Employee Profile";
            ui.regName.value = "";
            document.getElementById('reg-employee-role').value = "";
            document.getElementById('reg-employee-email').value = "";
            document.getElementById('reg-employee-phone').value = "";
            document.getElementById('reg-employee-status').value = "Active";
            const idField = document.getElementById('reg-employee-id');
            idField.disabled = false;
            suggestNextEmployeeId();
            document.getElementById('reg-actions-enroll').style.display = "flex";
            document.getElementById('reg-actions-edit').style.display = "none";
        }

        async function commitEditEmployeeProfile() {
            const targetLabelString = ui.regName.value.trim();
            const targetIdString = document.getElementById('reg-employee-id').value.trim();
            const targetDeptString = document.getElementById('reg-employee-dept').value;
            const targetRoleString = document.getElementById('reg-employee-role').value.trim();
            const targetEmailString = document.getElementById('reg-employee-email').value.trim();
            const targetPhoneString = document.getElementById('reg-employee-phone').value.trim();
            const targetStatusString = document.getElementById('reg-employee-status').value;
            if (!targetLabelString || !targetIdString) {
                triggerToast("Name and ID are required", "warning");
                return;
            }
            const existing = await readAllDatabaseRecords("employees");
            const originalRecord = existing.find(e => e.id === currentEditingEmployeeId);
            if (!originalRecord) return;
            await writeDatabaseRecord("employees", {
                id: targetIdString,
                name: targetLabelString,
                descriptor: originalRecord.descriptor,
                department: targetDeptString,
                role: targetRoleString || "Staff Member",
                email: targetEmailString || `${targetLabelString.toLowerCase().replace(/\s+/g, '')}@company.com`,
                phone: targetPhoneString || "+1000000000",
                status: targetStatusString,
                joined: originalRecord.joined || Date.now()
            });
            await rebuildComputationalMatchingGraph();
            await renderAdministrativeEmployeeDirectoryTable();
            populateManualOverrideDropdown();
            cancelEditEmployeeMode();
            await syncActiveFiltersList();
            triggerToast("Profile Updated Successfully", "success");
            AudioEngine.play('success');
        }

        async function toggleBanEmployee(empId) {
            const existing = await readAllDatabaseRecords("employees");
            const emp = existing.find(e => e.id === empId);
            if (!emp) return;
            const newStatus = emp.status === "Banned" ? "Active" : "Banned";
            emp.status = newStatus;
            await writeDatabaseRecord("employees", emp);
            await rebuildComputationalMatchingGraph();
            await renderAdministrativeEmployeeDirectoryTable();
            populateManualOverrideDropdown();
            if (currentEditingEmployeeId === empId) {
                document.getElementById('reg-employee-status').value = newStatus;
            }
            triggerToast(`Profile status updated to: ${newStatus}`, "success");
            AudioEngine.play('success');
        }

        async function commitManualAttendanceLog() {
            const selectEl = document.getElementById('manual-log-employee');
            const empId = selectEl.value;
            const logType = document.getElementById('manual-log-type').value;
            if (!empId) {
                triggerToast("Please select employee profile", "warning");
                return;
            }
            const emp = precompiledEmployeesList.find(e => e.id === empId);
            if (!emp) return;
            await recordAttendanceLogToDB(emp.id, emp.name, emp.department, emp.role, logType);
            selectEl.value = "";
        }

        async function saveShiftPolicySettings() {
            const timeVal = document.getElementById('settings-shift-time').value;
            const graceVal = parseInt(document.getElementById('settings-grace-minutes').value, 10);
            shiftStartTime = timeVal;
            shiftGraceMinutes = graceVal;
            await writeConfigKey("shift_start_time", timeVal);
            await writeConfigKey("shift_grace_minutes", graceVal);

            const wkChecked = [];
            for (let i = 0; i < 7; i++) {
                if (document.getElementById(`wk-${i}`).checked) {
                    wkChecked.push(i);
                }
            }
            weekendDays = wkChecked;
            await writeConfigKey("weekend_days", wkChecked);

            triggerToast("Policies Updated", "success");
            AudioEngine.play('success');
            await renderAnalyticsDashboard();
        }

        async function renderAnalyticsDashboard() {
            const employees = await readAllDatabaseRecords("employees");
            const logs = await readAllDatabaseRecords("attendance_logs");
            const holidays = await readAllDatabaseRecords("holidays");

            const now = new Date();
            const todayStr = now.toISOString().split('T')[0];
            const currentDayIndex = now.getDay();
            const isWeekend = weekendDays.includes(currentDayIndex);
            const isHoliday = holidays.some(h => h.date === todayStr);

            const logsToday = logs.filter(l => l.dateString === todayStr);
            const totalCount = employees.filter(e => e.status !== "Banned").length;
            const presentTodayNames = new Set(logsToday.filter(l => l.type === "Check-In").map(l => l.empId));
            const presentTodayCount = presentTodayNames.size;
            const onTimeCount = logsToday.filter(l => l.type === "Check-In" && l.status === "On-Time").length;
            const lateCount = logsToday.filter(l => l.type === "Check-In" && l.status === "Late").length;
            
            let absentCount = 0;
            if (!isWeekend && !isHoliday) {
                absentCount = Math.max(0, totalCount - presentTodayCount);
            }

            document.getElementById('stat-total-emp').innerText = totalCount;
            document.getElementById('stat-present').innerText = presentTodayCount;
            document.getElementById('stat-on-time').innerText = onTimeCount;
            document.getElementById('stat-late').innerText = lateCount;
            document.getElementById('stat-absent').innerText = absentCount;

            filterReportsTable();
            drawWeeklyAnalyticsChart(logs);
            drawDepartmentCanvasChart(employees, logsToday);
            drawMonthlyContributionHeatmap(logs);
        }

        function drawWeeklyAnalyticsChart(logs) {
            const chartContainer = document.getElementById('analytics-chart-container');
            if (!chartContainer) return;
            const last7Days = [];
            for (let i = 6; i >= 0; i--) {
                const d = new Date();
                d.setDate(d.getDate() - i);
                last7Days.push(d.toISOString().split('T')[0]);
            }
            const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const data = last7Days.map(dateStr => {
                const dayLogs = logs.filter(l => l.dateString === dateStr && l.type === "Check-In");
                const onTime = dayLogs.filter(l => l.status === "On-Time").length;
                const late = dayLogs.filter(l => l.status === "Late").length;
                const dateObj = new Date(dateStr);
                return {
                    label: dayNames[dateObj.getDay()] + " " + dateObj.getDate(),
                    onTime: onTime,
                    late: late
                };
            });
            const maxVal = Math.max(...data.map(d => d.onTime + d.late), 4);
            const scale = 110 / maxVal;
            let barsSvg = '';
            data.forEach((d, index) => {
                const x = 30 + index * 56;
                const onTimeHeight = d.onTime * scale;
                const lateHeight = d.late * scale;
                const yOnTime = 130 - onTimeHeight;
                const yLate = yOnTime - lateHeight;
                barsSvg += `
                    <rect x="${x}" y="${yOnTime}" width="16" height="${onTimeHeight}" fill="var(--success)" rx="1.5" />
                    <rect x="${x}" y="${yLate}" width="16" height="${lateHeight}" fill="var(--warning)" rx="1.5" />
                    <text x="${x + 8}" y="146" fill="var(--muted)" font-size="9" text-anchor="middle" font-weight="600">${d.label}</text>
                `;
            });
            chartContainer.innerHTML = `
                <svg viewBox="0 0 440 160" width="100%" height="160" style="font-family: var(--font-family);">
                    <line x1="25" y1="20" x2="420" y2="20" stroke="var(--border)" stroke-width="1" />
                    <line x1="25" y1="75" x2="420" y2="75" stroke="var(--border)" stroke-width="1" />
                    <line x1="25" y1="130" x2="420" y2="130" stroke="var(--muted-light)" stroke-width="1" />
                    <text x="18" y="23" fill="var(--muted)" font-size="8" text-anchor="end">${maxVal}</text>
                    <text x="18" y="78" fill="var(--muted)" font-size="8" text-anchor="end">${Math.round(maxVal / 2)}</text>
                    <text x="18" y="133" fill="var(--muted)" font-size="8" text-anchor="end">0</text>
                    ${barsSvg}
                </svg>
            `;
        }

        function drawDepartmentCanvasChart(employees, logsToday) {
            const canvas = document.getElementById('dept-canvas');
            const legend = document.getElementById('dept-legend');
            if (!canvas || !legend) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            legend.innerHTML = "";

            const presentTodayNames = new Set(logsToday.filter(l => l.type === "Check-In").map(l => l.empId));
            const deptCounts = {};
            presentTodayNames.forEach(empId => {
                const emp = employees.find(e => e.id === empId);
                if (emp) {
                    deptCounts[emp.department] = (deptCounts[emp.department] || 0) + 1;
                }
            });

            const keys = Object.keys(deptCounts);
            const total = Object.values(deptCounts).reduce((a, b) => a + b, 0);
            if (total === 0) {
                ctx.fillStyle = 'var(--border)';
                ctx.beginPath();
                ctx.arc(70, 70, 60, 0, Math.PI * 2);
                ctx.fill();
                ctx.fillStyle = 'var(--panel)';
                ctx.beginPath();
                ctx.arc(70, 70, 40, 0, Math.PI * 2);
                ctx.fill();
                legend.innerHTML = '<span style="color:var(--muted)">0 Checked-in today</span>';
                return;
            }

            const colors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#ec4899', '#8b5cf6', '#14b8a6'];
            let startAngle = -Math.PI / 2;
            keys.forEach((key, idx) => {
                const count = deptCounts[key];
                const percentage = count / total;
                const sliceAngle = percentage * Math.PI * 2;
                const color = colors[idx % colors.length];

                ctx.fillStyle = color;
                ctx.beginPath();
                ctx.moveTo(70, 70);
                ctx.arc(70, 70, 60, startAngle, startAngle + sliceAngle);
                ctx.closePath();
                ctx.fill();

                startAngle += sliceAngle;

                const div = document.createElement('div');
                div.style.display = 'flex';
                div.style.alignItems = 'center';
                div.style.gap = '6px';
                div.innerHTML = `<span style="width:8px; height:8px; background:${color}; border-radius:50%;"></span>
                                 <span>${key}: <strong>${count}</strong></span>`;
                legend.appendChild(div);
            });

            ctx.fillStyle = 'var(--panel)';
            ctx.beginPath();
            ctx.arc(70, 70, 36, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = 'var(--text)';
            ctx.font = 'bold 12px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(total, 70, 70);
        }

        function drawMonthlyContributionHeatmap(logs) {
            const container = document.getElementById('heatmap-container');
            if (!container) return;
            container.innerHTML = "";
            const daysInMonth = 28;
            const grid = document.createElement('div');
            grid.className = 'heatmap-grid';
            const sortedLogs = logs.sort((a,b)=>b.timestamp-a.timestamp);
            const now = new Date();
            for (let i = 27; i >= 0; i--) {
                const d = new Date();
                d.setDate(now.getDate() - i);
                const dayStr = d.toISOString().split('T')[0];
                const dayCount = sortedLogs.filter(l => l.dateString === dayStr && l.type === "Check-In").length;
                let valClass = "";
                if (dayCount > 0 && dayCount <= 1) valClass = "val-1";
                else if (dayCount > 1 && dayCount <= 3) valClass = "val-2";
                else if (dayCount > 3 && dayCount <= 5) valClass = "val-3";
                else if (dayCount > 5) valClass = "val-4";

                const div = document.createElement('div');
                div.className = `heatmap-day ${valClass}`;
                div.innerText = d.getDate();
                div.title = `${dayStr}: ${dayCount} check-ins`;
                grid.appendChild(div);
            }
            const label = document.createElement('div');
            label.style.fontSize = '10px';
            label.style.color = 'var(--muted)';
            label.style.textAlign = 'center';
            label.innerText = "Log volume for past 28 active operational days";
            container.appendChild(grid);
            container.appendChild(label);
        }

        async function filterReportsTable() {
            const logs = await readAllDatabaseRecords("attendance_logs");
            const searchName = document.getElementById('report-search-name').value.toLowerCase().trim();
            const filterDate = document.getElementById('report-filter-date').value;
            const filterDept = document.getElementById('report-filter-dept').value;
            const filterType = document.getElementById('report-filter-type').value;
            const filterStatus = document.getElementById('report-filter-status').value;

            const now = new Date();
            const todayStr = now.toISOString().split('T')[0];
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            const yesterdayStr = yesterday.toISOString().split('T')[0];
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            const monthAgo = new Date();
            monthAgo.setDate(monthAgo.getDate() - 30);

            filteredReportsList = logs.filter(log => {
                if (searchName && !log.name.toLowerCase().includes(searchName) && !log.empId.toLowerCase().includes(searchName)) return false;
                if (filterDate === "today") {
                    if (log.dateString !== todayStr) return false;
                } else if (filterDate === "yesterday") {
                    if (log.dateString !== yesterdayStr) return false;
                } else if (filterDate === "week") {
                    if (log.timestamp < weekAgo.getTime()) return false;
                } else if (filterDate === "month") {
                    if (log.timestamp < monthAgo.getTime()) return false;
                }
                if (filterDept !== "all" && log.department !== filterDept) return false;
                if (filterType !== "all" && log.type !== filterType) return false;
                if (filterStatus !== "all" && log.status !== filterStatus) return false;
                return true;
            });

            filteredReportsList.sort((a, b) => b.timestamp - a.timestamp);
            const totalRecords = filteredReportsList.length;
            const totalPages = Math.ceil(totalRecords / reportsRowsPerPage) || 1;
            if (reportsPage > totalPages) reportsPage = totalPages;

            const startIndex = (reportsPage - 1) * reportsRowsPerPage;
            const endIndex = Math.min(startIndex + reportsRowsPerPage, totalRecords);
            const pagedLogs = filteredReportsList.slice(startIndex, endIndex);

            const tbody = document.getElementById('reports-table-body');
            tbody.innerHTML = "";

            if (totalRecords === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--muted);">No matching attendance records found.</td></tr>';
                document.getElementById('reports-paging-info').innerText = "Showing 0 to 0 of 0 logs";
                document.getElementById('btn-report-prev').disabled = true;
                document.getElementById('btn-report-next').disabled = true;
                return;
            }

            pagedLogs.forEach(log => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="font-weight: 500;">${log.dateString} ${log.timeString}</td>
                    <td style="font-weight: 700; color: var(--primary); font-family: monospace;">${log.empId}</td>
                    <td style="font-weight: 600;">${log.name}</td>
                    <td>${log.department}</td>
                    <td>${log.role}</td>
                    <td style="font-weight: 600; color: var(--primary);">${log.type}</td>
                    <td><span style="padding:2px 6px; border-radius:4px; font-size:10px; font-weight:700; background:${log.status === 'On-Time' ? 'var(--success-bg)' : log.status === 'Late' ? 'var(--warning-bg)' : log.status === 'Exempt' ? 'var(--primary-glow)' : 'var(--bg)'}; color:${log.status === 'On-Time' ? 'var(--success)' : log.status === 'Late' ? 'var(--warning)' : log.status === 'Exempt' ? 'var(--primary)' : 'var(--muted)'};">${log.status}</span></td>
                    <td>
                        <div style="display:flex; gap:4px;">
                            <button class="btn btn-secondary" style="padding:4px 8px; font-size:10px; border-radius:4px;" onclick="openRetroactiveModal(${log.id})">Edit</button>
                            <button class="btn btn-danger" style="padding:4px 8px; font-size:10px; border-radius:4px;" onclick="deleteAttendanceLog(${log.id})">Delete</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('reports-paging-info').innerText = `Showing ${startIndex + 1} to ${endIndex} of ${totalRecords} logs`;
            document.getElementById('btn-report-prev').disabled = reportsPage === 1;
            document.getElementById('btn-report-next').disabled = reportsPage === totalPages;
        }

        function changeReportsPage(dir) {
            reportsPage += dir;
            filterReportsTable();
        }

        function reportSearchChange() {
            reportsPage = 1;
            filterReportsTable();
        }

        function reportFilterChange() {
            reportsPage = 1;
            filterReportsTable();
        }

        function changeEmpPage(dir) {
            empPage += dir;
            renderAdministrativeEmployeeDirectoryTable();
        }

        function empSearchChange() {
            empPage = 1;
            renderAdministrativeEmployeeDirectoryTable();
        }

        async function deleteAttendanceLog(logId) {
            if (confirm("Confirm action: Remove this log record?")) {
                await deleteDatabaseRecord("attendance_logs", logId);
                await renderAnalyticsDashboard();
                await loadAttendanceLedgingTicker();
                triggerToast("Attendance Record Deleted", "warning");
                AudioEngine.play('warning');
            }
        }

        async function exportReportsToCSV() {
            if (!filteredReportsList.length) {
                triggerToast("Records table is empty", "error");
                return;
            }
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Date,Time,Employee ID,Employee Name,Department,Role,Event Type,Status\n";
            filteredReportsList.forEach(log => {
                csvContent += `"${log.dateString}","${log.timeString}","${log.empId}","${log.name}","${log.department}","${log.role}","${log.type}","${log.status}"\n`;
            });
            const encodedUri = encodeURI(csvContent);
            const temporaryAnchor = document.createElement("a");
            temporaryAnchor.setAttribute("href", encodedUri);
            temporaryAnchor.setAttribute("download", `attendance_report_${Math.floor(Date.now() / 1000)}.csv`);
            document.body.appendChild(temporaryAnchor);
            temporaryAnchor.click();
            document.body.removeChild(temporaryAnchor);
            triggerToast("CSV Download Initiated", "success");
            AudioEngine.play('success');
        }

        function switchView(targetViewModeString) {
            document.querySelectorAll('.view-section').forEach(section => section.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            stopEnrollmentCamera();
            if (targetViewModeString === 'kiosk') {
                document.getElementById('view-kiosk').classList.add('active');
                document.getElementById('nav-kiosk').classList.add('active');
            } else {
                document.getElementById('view-admin').classList.add('active');
                document.getElementById('nav-admin').classList.add('active');
                if (!isDashboardAuthenticatedState) {
                    ui.authGate.style.display = "flex";
                    document.getElementById('admin-panel-content').style.display = "none";
                } else {
                    ui.authGate.style.display = "none";
                    document.getElementById('admin-panel-content').style.display = "flex";
                    switchSubTab('reports');
                }
            }
        }

        function switchSubTab(subTabName) {
            document.querySelectorAll('.sub-tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.sub-view-section').forEach(sec => sec.style.display = "none");
            stopEnrollmentCamera();
            document.getElementById(`sub-tab-${subTabName}`).classList.add('active');
            if (subTabName === 'reports') {
                document.getElementById('sub-view-reports').style.display = "flex";
                renderAnalyticsDashboard();
            } else if (subTabName === 'employees') {
                cancelEditEmployeeMode();
                document.getElementById('sub-view-employees').style.display = "flex";
                renderAdministrativeEmployeeDirectoryTable();
                populateManualOverrideDropdown();
                suggestNextEmployeeId();
            } else if (subTabName === 'settings') {
                document.getElementById('sub-view-settings').style.display = "flex";
                renderSettingsLists();
            }
        }

        async function syncActiveFiltersList() {
            const depts = await readAllDatabaseRecords("departments");
            
            const filterReportSelect = document.getElementById('report-filter-dept');
            const filterEmpSelect = document.getElementById('emp-filter-dept');
            const regSelect = document.getElementById('reg-employee-dept');

            const reportVal = filterReportSelect.value;
            const empVal = filterEmpSelect.value;
            const regVal = regSelect.value;

            filterReportSelect.innerHTML = '<option value="all">All Departments</option>';
            filterEmpSelect.innerHTML = '<option value="all">All Departments</option>';
            regSelect.innerHTML = '';

            depts.forEach(d => {
                filterReportSelect.innerHTML += `<option value="${d.name}">${d.name}</option>`;
                filterEmpSelect.innerHTML += `<option value="${d.name}">${d.name}</option>`;
                regSelect.innerHTML += `<option value="${d.name}">${d.name}</option>`;
            });

            if (reportVal && filterReportSelect.querySelector(`option[value="${reportVal}"]`)) {
                filterReportSelect.value = reportVal;
            }
            if (empVal && filterEmpSelect.querySelector(`option[value="${empVal}"]`)) {
                filterEmpSelect.value = empVal;
            }
            if (regVal && regSelect.querySelector(`option[value="${regVal}"]`)) {
                regSelect.value = regVal;
            }
        }

        async function renderSettingsLists() {
            const depts = await readAllDatabaseRecords("departments");
            const deptContainer = document.getElementById('dept-list-container');
            deptContainer.innerHTML = "";
            depts.forEach(d => {
                const div = document.createElement('div');
                div.style = "display:flex; justify-content:space-between; align-items:center; background:var(--bg); border:1px solid var(--border); padding:6px 10px; border-radius:6px; font-size:12px;";
                div.innerHTML = `<span>${d.name}</span>
                                 <button class="btn btn-danger" style="padding:2px 6px; font-size:10px; border-radius:4px;" onclick="deleteDepartment('${d.name}')">Drop</button>`;
                deptContainer.appendChild(div);
            });

            const holidays = await readAllDatabaseRecords("holidays");
            const holidayContainer = document.getElementById('holiday-list-container');
            holidayContainer.innerHTML = "";
            holidays.sort((a,b)=>a.date.localeCompare(b.date)).forEach(h => {
                const div = document.createElement('div');
                div.style = "display:flex; justify-content:space-between; align-items:center; background:var(--bg); border:1px solid var(--border); padding:6px 10px; border-radius:6px; font-size:12px;";
                div.innerHTML = `<span><strong>${h.date}</strong> - ${h.name}</span>
                                 <button class="btn btn-danger" style="padding:2px 6px; font-size:10px; border-radius:4px;" onclick="deleteHoliday('${h.date}')">Drop</button>`;
                holidayContainer.appendChild(div);
            });
        }

        async function addNewDepartment() {
            const name = document.getElementById('settings-new-dept').value.trim();
            if (!name) return;
            await writeDatabaseRecord("departments", { name });
            document.getElementById('settings-new-dept').value = "";
            triggerToast(`Department '${name}' registered`, "success");
            AudioEngine.play('success');
            await syncActiveFiltersList();
            await renderSettingsLists();
        }

        async function deleteDepartment(name) {
            if (confirm(`Confirm dropping department: ${name}?`)) {
                await deleteDatabaseRecord("departments", name);
                triggerToast(`Department '${name}' dropped`, "warning");
                AudioEngine.play('warning');
                await syncActiveFiltersList();
                await renderSettingsLists();
            }
        }

        async function addNewHoliday() {
            const date = document.getElementById('settings-new-holiday-date').value;
            const name = document.getElementById('settings-new-holiday-name').value.trim();
            if (!date || !name) {
                triggerToast("Date and Name required", "warning");
                return;
            }
            await writeDatabaseRecord("holidays", { date, name });
            document.getElementById('settings-new-holiday-date').value = "";
            document.getElementById('settings-new-holiday-name').value = "";
            triggerToast(`Holiday exception registered`, "success");
            AudioEngine.play('success');
            await renderSettingsLists();
            await renderAnalyticsDashboard();
        }

        async function deleteHoliday(date) {
            if (confirm(`Confirm dropping holiday exception for: ${date}?`)) {
                await deleteDatabaseRecord("holidays", date);
                triggerToast(`Holiday exception dropped`, "warning");
                AudioEngine.play('warning');
                await renderSettingsLists();
                await renderAnalyticsDashboard();
            }
        }

        async function saveLivenessSettings() {
            const level = document.getElementById('settings-liveness-level').value;
            livenessChallengeMode = level;
            await writeConfigKey("liveness_level", level);
            triggerToast("Anti-Spoofing Rules Saved", "success");
            AudioEngine.play('success');
        }

        async function applyAccentColor(color) {
            document.body.classList.remove('accent-indigo', 'accent-emerald', 'accent-rose', 'accent-amber', 'accent-blue');
            document.body.classList.add(`accent-${color}`);
            document.querySelectorAll('.color-dot').forEach(dot => dot.classList.remove('active'));
            const activeDot = document.getElementById(`dot-${color}`);
            if (activeDot) activeDot.classList.add('active');
            await writeConfigKey("theme_color", color);
        }

        async function toggleTheme() {
            const isDark = document.body.classList.contains('dark');
            if (isDark) {
                document.body.classList.remove('dark');
                await writeConfigKey("theme_mode", "light");
            } else {
                document.body.classList.add('dark');
                await writeConfigKey("theme_mode", "dark");
            }
        }

        async function openRetroactiveModal(logId = null) {
            const selectEl = document.getElementById('retro-log-employee');
            selectEl.innerHTML = "";
            const emps = await readAllDatabaseRecords("employees");
            emps.forEach(e => {
                selectEl.innerHTML += `<option value="${e.id}">${e.name} (${e.id})</option>`;
            });

            const dateField = document.getElementById('retro-log-date');
            const timeField = document.getElementById('retro-log-time');
            const typeField = document.getElementById('retro-log-type');
            const statusField = document.getElementById('retro-log-status');
            const hiddenId = document.getElementById('retro-log-id');

            if (logId) {
                const logs = await readAllDatabaseRecords("attendance_logs");
                const log = logs.find(l => l.id === logId);
                if (log) {
                    hiddenId.value = log.id;
                    selectEl.value = log.empId;
                    selectEl.disabled = true;
                    dateField.value = log.dateString;
                    timeField.value = log.timeString;
                    typeField.value = log.type;
                    statusField.value = log.status;
                    document.getElementById('retro-modal-title').innerText = "Edit Attendance Log Entry";
                }
            } else {
                hiddenId.value = "";
                selectEl.disabled = false;
                const now = new Date();
                dateField.value = now.toISOString().split('T')[0];
                timeField.value = now.toTimeString().split(' ')[0];
                typeField.value = "Check-In";
                statusField.value = "On-Time";
                document.getElementById('retro-modal-title').innerText = "Create Retroactive Log Entry";
            }
            document.getElementById('retroactive-log-modal').style.display = 'flex';
        }

        function closeRetroactiveModal() {
            document.getElementById('retroactive-log-modal').style.display = 'none';
        }

        async function commitRetroactiveAttendanceLog() {
            const logIdVal = document.getElementById('retro-log-id').value;
            const empId = document.getElementById('retro-log-employee').value;
            const dateStr = document.getElementById('retro-log-date').value;
            const timeStr = document.getElementById('retro-log-time').value;
            const eventType = document.getElementById('retro-log-type').value;
            const statusVal = document.getElementById('retro-log-status').value;

            if (!empId || !dateStr || !timeStr) {
                triggerToast("Complete all details", "warning");
                return;
            }

            const emps = await readAllDatabaseRecords("employees");
            const emp = emps.find(e => e.id === empId);
            if (!emp) return;

            const datetimeObj = new Date(`${dateStr}T${timeStr}`);
            const logEntry = {
                empId: emp.id,
                name: emp.name,
                department: emp.department,
                role: emp.role,
                timestamp: datetimeObj.getTime(),
                dateString: dateStr,
                timeString: timeStr,
                type: eventType,
                status: statusVal
            };

            if (logIdVal) {
                logEntry.id = parseInt(logIdVal, 10);
            }

            await writeDatabaseRecord("attendance_logs", logEntry);
            closeRetroactiveModal();
            triggerToast(logIdVal ? "Log Record Updated" : "Retro Log Entry Appended", "success");
            AudioEngine.play('success');
            await renderAnalyticsDashboard();
            await loadAttendanceLedgingTicker();
        }

        window.onload = compileApplicationCoreSubsystems;

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('SW success:', reg.scope))
                    .catch(err => console.error('SW fail:', err));
            });
        }
    </script>
</body>

</html>
