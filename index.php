<?php
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
require_once 'functions.php';

$conn = getConnection();
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query top toffees (limit 10)
$topQuery = "SELECT * FROM toffees ORDER BY quantity DESC LIMIT 10";
$topResult = $conn->query($topQuery);

// Query search results if search term provided
$searchResult = null;
if ($search) {
    $searchQuery = "SELECT * FROM toffees WHERE name LIKE ? ORDER BY name ASC";
    $stmt = $conn->prepare($searchQuery);
    $searchParam = '%' . $search . '%';
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $searchResult = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Toffee Stock Management</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">Toffee Stock Management</a>
            <div class="navbar-nav">
                <a href="add.php" class="nav-link">Add Item</a>
                <a href="issue_load.php" class="nav-link">Daily Issue/Load</a>
                <a href="transactions.php" class="nav-link">Transactions</a>
                <a href="summary.php" class="nav-link">Summary</a>
                <a href="logout.php" class="nav-link" style="margin-left: auto;">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1 class="text-center">Toffee Stock Management</h1>

        <div class="action-bar">
            <form method="GET" class="search-form">
                <input
                    type="text"
                    name="search"
                    placeholder="Search toffee..."
                    value="<?php echo htmlspecialchars($search); ?>"
                />
                <button type="submit" class="btn">Search</button>
            </form>
        </div>

        <div>
            <?php if ($search): ?>
                <?php if ($searchResult && $searchResult->num_rows > 0): ?>
                    <h2 style="color: white;">Search Results</h2>
                    <div class="toffee-grid">
                        <?php while ($toffee = $searchResult->fetch_assoc()): ?>
                            <div class="toffee-card">
                                <?php if ($toffee['image_path'] && file_exists($toffee['image_path'])): ?>
                                    <img src="<?php echo $toffee['image_path']; ?>" alt="<?php echo htmlspecialchars($toffee['name']); ?>" />
                                <?php else: ?>
                                    <div class="no-image">No Image</div>
                                <?php endif; ?>
                                <h3 style="color: #333;"><?php echo htmlspecialchars($toffee['name']); ?></h3>
                                <p style="color: #333;"><strong>Quantity:</strong> <?php echo $toffee['quantity']; ?></p>
                                <p style="color: #333;"><strong>Price per item:</strong> Rs. <?php echo number_format($toffee['price'], 2); ?></p>
                                <p style="color: #333;"><strong>Total Cost:</strong> Rs. <?php echo number_format($toffee['quantity'] * $toffee['price'], 2); ?></p>
                                <div class="actions">
                                    <a href="edit.php?id=<?php echo $toffee['id']; ?>" class="btn btn-edit">Edit</a>
                                    <a href="delete.php?id=<?php echo $toffee['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #333;">No toffees found matching "<?php echo htmlspecialchars($search); ?>"</p>
                <?php endif; ?>
            <?php endif; ?>

            <h2 style="color: white;">Top Toffees</h2>
            <div class="toffee-grid">
                <?php if ($topResult && $topResult->num_rows > 0): ?>
                    <?php while ($toffee = $topResult->fetch_assoc()): ?>
                        <div class="toffee-card">
                            <?php if ($toffee['image_path'] && file_exists($toffee['image_path'])): ?>
                                <img src="<?php echo $toffee['image_path']; ?>" alt="<?php echo htmlspecialchars($toffee['name']); ?>" />
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>

                            <h3 style="color: #333;"><?php echo htmlspecialchars($toffee['name']); ?></h3>
                            <p style="color: #333;"><strong>Quantity:</strong> <?php echo $toffee['quantity']; ?></p>
                            <p style="color: #333;"><strong>Price per item:</strong> Rs. <?php echo number_format($toffee['price'], 2); ?></p>
                            <p style="color: #333;"><strong>Total Cost:</strong> Rs. <?php echo number_format($toffee['quantity'] * $toffee['price'], 2); ?></p>

                            <div class="actions">
                                <a href="edit.php?id=<?php echo $toffee['id']; ?>" class="btn btn-edit">Edit</a>
                                <a href="delete.php?id=<?php echo $toffee['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #333;">No toffees in stock</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
