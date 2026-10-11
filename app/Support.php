<?php
declare(strict_types=1);
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . $path); exit; }
function money(float|string $amount): string { return number_format((float)$amount, 2); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = (string)($_POST['csrf'] ?? '');
        if (!hash_equals(csrf_token(), $sent)) { http_response_code(419); exit('คำขอหมดอายุ กรุณากลับไปลองใหม่'); }
    }
}
