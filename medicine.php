<?php
/**
 * medicine.php — Medicine / inventory module.
 *
 * Lists medicines, quantities, and expiry dates.
 * Supports a search filter by medicine name.
 */
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/db_connect.php';

// ── Search filter ─────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare(
        'SELECT ItemID, ItemName AS Name, ItemCategory AS GenericName, 
                ItemStockQuantity AS Quantity, Unit, ExpiryDate, 
                ItemStockQuantity AS Stock
         FROM   medicineandstuffs
         WHERE  ItemName LIKE ? OR ItemCategory LIKE ?
         ORDER  BY ItemName
         LIMIT  100'
    );
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result    = $conn->query(
        'SELECT ItemID, ItemName AS Name, ItemCategory AS GenericName, 
                ItemStockQuantity AS Quantity, Unit, ExpiryDate,
                ItemStockQuantity AS Stock
         FROM   medicineandstuffs
         ORDER  BY ItemName
         LIMIT  100'
    );
    $medicines = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$activePage = 'medicine';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Medicine</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">

    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Medicine</p>
                <h2>Medicine</h2>
                <p class="page-description">Inventory management for clinic medicines and supplies.</p>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="header-button outline">Logout</a>
            </div>
        </header>

        <!-- ── Search ── -->
        <div class="panel-card">
            <div class="panel-card-header">
                <h3>Search Medicines</h3>
            </div>
            <div class="panel-card-body">
                <form method="get" action="medicine.php" class="inline-filter-form">
                    <div class="input-group" style="max-width: 340px;">
                        <label for="search">Name or category</label>
                        <input type="text" id="search" name="search"
                               placeholder="e.g. Paracetamol"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-actions" style="margin-top:.5rem;">
                        <button type="submit" class="primary-button">Search</button>
                        <?php if ($search !== ''): ?>
                            <a href="medicine.php" class="secondary-button">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Medicine list ── -->
        <div class="panel-card" style="margin-top: 1.5rem;">
            <div class="panel-card-header">
                <h3>
                    <?php echo $search !== ''
                        ? 'Results for "' . htmlspecialchars($search) . '"'
                        : 'All Medicines'; ?>
                </h3>
            </div>
            <div class="panel-card-body">
                <?php if (empty($medicines)): ?>
                    <p class="muted">No medicines found.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Expiry</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medicines as $med): ?>
                                    <?php
                                    $lowStock = isset($med['Stock']) && (int) $med['Stock'] < 10;
                                    ?>
                                    <tr <?php echo $lowStock ? 'class="row-warning"' : ''; ?>>
                                        <td><?php echo (int) $med['ItemID']; ?></td>
                                        <td><?php echo htmlspecialchars($med['Name'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($med['GenericName'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($med['Quantity'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($med['Unit'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($med['ExpiryDate'] ?? '—'); ?></td>
                                        <td>
                                            <?php if ($lowStock): ?>
                                                <span class="status-badge status-pending">
                                                    Low (<?php echo (int) $med['Stock']; ?>)
                                                </span>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($med['Stock'] ?? '—'); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

</div>
<script src="assets/js/app.js"></script>
</body>
</html>