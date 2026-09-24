<?php
session_start();
require_once 'config/db.php';

$errors = [];
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch old data
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: index.php");
    exit;
}

$old = [
    'name' => $product['name'],
    'category' => $product['category'],
    'price' => $product['price'],
    'stock' => $product['stock']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';

    $old = [
        'name' => $name,
        'category' => $category,
        'price' => $price,
        'stock' => $stock
    ];

    // Validations
    if (strlen($name) < 3) {
        $errors[] = "Panjang nama produk minimal 3 karakter.";
    }

    if (!is_numeric($price) || $price <= 0) {
        $errors[] = "Harga harus lebih dari 0.";
    }

    if (!is_numeric($stock) || $stock < 0) {
        $errors[] = "Stok tidak boleh bernilai negatif.";
    }

    // Check duplicate name excluding current ID
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name = :name AND id != :id");
        $stmt->execute([':name' => $name, ':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Nama produk sudah ada di database.";
        }
    }
    
    $imagePath = $product['image_path']; // Default to old image
    
    // Image Upload Handling
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $errors[] = "Tipe file gambar tidak didukung (hanya JPG, PNG, WEBP, GIF).";
        } elseif ($_FILES['image']['size'] > $maxSize) {
            $errors[] = "Ukuran gambar maksimal 2MB.";
        } else {
            // Generate unique filename
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_') . '.' . $ext;
            $destination = 'assets/uploads/' . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $imagePath = $destination;
                // Delete old image if it exists
                if ($product['image_path'] && file_exists($product['image_path'])) {
                    unlink($product['image_path']);
                }
            } else {
                $errors[] = "Gagal mengunggah gambar baru.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET name = :name, category = :category, price = :price, stock = :stock, image_path = :image_path WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':category' => $category,
                ':price' => $price,
                ':stock' => $stock,
                ':image_path' => $imagePath,
                ':id' => $id
            ]);
            
            $_SESSION['success'] = "Produk berhasil diperbarui.";
            header("Location: index.php");
            exit; // PRG pattern
        } catch (Exception $e) {
            $errors[] = "Gagal memperbarui data: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Product Manager</title>
    <link rel="stylesheet" href="assets/style.css">
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', target);
            localStorage.setItem('theme', target);
        }
    </script>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>Edit Produk</h1>
            </div>
            <div class="header-actions">
                <button type="button" onclick="toggleTheme()" class="btn theme-toggle" title="Toggle Theme" style="padding: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 2a10 10 0 0 0 0 20z" fill="currentColor"></path>
                    </svg>
                </button>
                <a href="index.php" class="btn">Kembali</a>
            </div>
        </header>

        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nama Produk</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($old['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="category" value="<?php echo htmlspecialchars($old['category'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Harga</label>
                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($old['price'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stock" value="<?php echo htmlspecialchars($old['stock'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                
                <?php if ($product['image_path']): ?>
                <div class="form-group">
                    <label>Gambar Saat Ini</label>
                    <div style="margin-bottom: 12px; border: 2px solid var(--border-color); padding: 4px; display: inline-block;">
                        <img src="<?php echo htmlspecialchars($product['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Gambar Produk" style="max-width: 150px; display: block;">
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Ganti Gambar Produk (Opsional, Max 2MB)</label>
                    <input type="file" name="image" accept="image/jpeg, image/png, image/webp, image/gif">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">UPDATE PRODUK</button>
            </form>
        </div>
    </div>
</body>
</html>
