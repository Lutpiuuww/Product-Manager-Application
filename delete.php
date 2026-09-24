<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $_SESSION['error'] = "Token keamanan tidak valid.";
        header("Location: index.php");
        exit;
    }

    if ($id) {
        try {
            // Get image_path first
            $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch();

            if ($product && $product['image_path'] && file_exists($product['image_path'])) {
                unlink($product['image_path']);
            }

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = "Produk berhasil dihapus.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Gagal menghapus produk: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "ID produk tidak valid.";
    }
} else {
    // If not POST, redirect
    $_SESSION['error'] = "Metode tidak diizinkan.";
}

header("Location: index.php");
exit;
