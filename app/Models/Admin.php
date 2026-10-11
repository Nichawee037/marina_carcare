<?php
declare(strict_types=1);
require_once __DIR__ . '/../Database.php';
class Admin {
    public function login(string $username, string $password): bool {
        $s=Database::connection()->prepare("SELECT * FROM admins WHERE username=?"); $s->execute([$username]); $row=$s->fetch();
        if ($row && password_verify($password,$row['password_hash'])) { session_regenerate_id(true); $_SESSION['admin_id']=$row['id']; $_SESSION['admin_name']=$row['display_name']; return true; }
        return false;
    }
    public static function check(): bool { return !empty($_SESSION['admin_id']); }
}
