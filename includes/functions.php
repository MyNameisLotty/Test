<?php
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return 'R ' . number_format((float)$value, 2);
}

function redirect_to($path) {
    header('Location: ' . $path);
    exit;
}

function next_order_number(mysqli $conn, string $prefix = 'ORD'): string {
    return $prefix . '-' . date('Ymd-His') . '-' . random_int(100, 999);
}

function next_invoice_number(mysqli $conn): string {
    $result = $conn->query("SELECT id FROM invoices ORDER BY id DESC LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
    $nextId = (int)($row['id'] ?? 0) + 1;
    return 'INV-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
}

function parse_money($value): float {
    $clean = preg_replace('/[^0-9.\-]/', '', (string)$value);
    return $clean === '' ? 0.0 : (float)$clean;
}

function parse_csv_date($value): ?string {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $date = DateTime::createFromFormat('d/m/Y', $value);
    return $date ? $date->format('Y-m-d') : null;
}

function grow_type_label($value): string {
    $value = strtoupper(trim((string)$value));
    if ($value === 'GH') return 'Greenhouse';
    if ($value === 'IN' || $value === 'IND') return 'Indoor';
    if ($value === 'OD') return 'Outdoor';
    return $value !== '' ? $value : 'Greenhouse';
}
?>
