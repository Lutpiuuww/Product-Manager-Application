<?php
session_start();
require_once 'config/db.php';

// Generate CSRF token for delete form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch all distinct categories for navigation
$catStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Search & Pagination Configuration
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Build query safely
$whereClause = "";
$params = [];
$conditions = [];

if ($search !== '') {
    $conditions[] = "(name LIKE :search OR category LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($category !== '') {
    $conditions[] = "category = :category";
    $params[':category'] = $category;
}

if (!empty($conditions)) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// Count total rows for pagination
$countQuery = "SELECT COUNT(*) FROM products $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Fetch products using prepared statements
$query = "SELECT * FROM products $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Helper to build URL params
function buildUrlParams($paramsArray) {
    $query = http_build_query(array_filter($paramsArray));
    return $query ? '?' . $query : 'index.php';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Manager</title>
    <link rel="stylesheet" href="assets/style.css">
    <script>
        // Init Dark Mode
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
                <h1>Product Manager</h1>
            </div>
            <div class="header-actions">
                <button onclick="toggleTheme()" class="btn theme-toggle" title="Toggle Theme" style="padding: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 2a10 10 0 0 0 0 20z" fill="currentColor"></path>
                    </svg>
                </button>
                <a href="create.php" class="btn btn-primary">Tambah Produk</a>
            </div>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Category Navigation -->
        <div class="category-nav" style="margin-bottom: 24px; display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="<?php echo buildUrlParams(['search' => $search]); ?>" class="btn <?php echo $category === '' ? 'btn-primary' : ''; ?>" style="background: <?php echo $category === '' ? 'var(--primary)' : 'transparent'; ?>; color: <?php echo $category === '' ? '#111827' : 'var(--text-main)'; ?>;">SEMUA</a>
            
            <?php foreach ($categories as $cat): ?>
                <a href="<?php echo buildUrlParams(['search' => $search, 'category' => $cat]); ?>" 
                   class="btn <?php echo $category === $cat ? 'btn-primary' : ''; ?>"
                   style="background: <?php echo $category === $cat ? 'var(--primary)' : 'transparent'; ?>; color: <?php echo $category === $cat ? '#111827' : 'var(--text-main)'; ?>;">
                   <?php echo htmlspecialchars(strtoupper($cat), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search Form -->
        <form method="GET" action="index.php" class="search-form">
            <?php if ($category !== ''): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="CARI NAMA PRODUK..." value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn">CARI</button>
            <?php if($search !== '' || $category !== ''): ?>
                <a href="index.php" class="btn btn-warning">RESET</a>
            <?php endif; ?>
        </form>

        <!-- Product Cards -->
        <div class="products-grid">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="card <?php echo $product['stock'] <= 0 ? 'out-of-stock' : ''; ?>">
                        <div class="card-badge">
                            <?php echo $product['stock'] <= 0 ? 'HABIS' : htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        
                        <?php if ($product['image_path'] && file_exists($product['image_path'])): ?>
                            <div class="card-image-wrapper" style="border: 2px solid var(--border-color); margin-bottom: 16px; height: 180px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #fff;">
                                <img src="<?php echo htmlspecialchars($product['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        <?php else: ?>
                            <div class="card-image-wrapper" style="border: 2px dashed var(--border-color); margin-bottom: 16px; height: 180px; display: flex; align-items: center; justify-content: center; background: var(--bg-color);">
                                <span style="font-weight: 700; color: var(--text-muted); font-family: monospace;">[NO IMAGE]</span>
                            </div>
                        <?php endif; ?>

                        <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        
                        <div class="card-data">
                            <span class="label">Harga</span>
                            <span class="value">Rp <?php echo number_format($product['price'], 2, ',', '.'); ?></span>
                        </div>
                        <div class="card-data">
                            <span class="label">Stok</span>
                            <span class="value"><?php echo htmlspecialchars($product['stock'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>

                        <div class="card-actions">
                            <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn">Edit</a>
                            <form action="delete.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus produk ini?');" style="flex:1; display:flex;">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <button type="submit" class="btn btn-danger" style="width:100%;">Hapus</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert" style="width: 100%;">
                    TIDAK ADA PRODUK DITEMUKAN.
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?php echo buildUrlParams(['search' => $search, 'category' => $category, 'page' => $i]); ?>" 
                       class="btn <?php echo $page === $i ? 'btn-primary' : ''; ?>">
                       <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
