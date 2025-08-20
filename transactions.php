<?php
require_once 'config.php';
require_once 'functions.php';

$conn = getConnection();

// Get filter parameters
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_toffee = isset($_GET['toffee']) ? $_GET['toffee'] : '';

// Build query
$query = "SELECT 
    t.id,
    t.transaction_date,
    t.issue_quantity,
    t.load_quantity,
    t.current_quantity,
    t.notes,
    t.created_at,
    tf.name as toffee_name,
    tf.price
FROM toffee_transactions t
JOIN toffees tf ON t.toffee_id = tf.id
WHERE 1=1";

$params = [];
$types = "";

if ($filter_date) {
    $query .= " AND t.transaction_date = ?";
    $params[] = $filter_date;
    $types .= "s";
}

if ($filter_toffee) {
    $query .= " AND t.toffee_id = ?";
    $params[] = $filter_toffee;
    $types .= "i";
}

$query .= " ORDER BY t.transaction_date DESC, t.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get all toffees for filter dropdown
$toffees_result = $conn->query("SELECT id, name FROM toffees ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .filter-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-weight: bold;
        }
        .filter-group select,
        .filter-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .transactions-table th,
        .transactions-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .transactions-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .issue-qty {
            color: #d9534f;
            font-weight: bold;
        }
        .load-qty {
            color: #5cb85c;
            font-weight: bold;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Transaction History</h1>
        
        <div class="navigation">
            <a href="index.php" class="btn">← Back to Stock</a>
            <a href="issue_load.php" class="btn">Enter New Transactions</a>
        </div>

        <div class="filter-section">
            <h2>Filter Transactions</h2>
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="date">Date:</label>
                    <input type="date" 
                           id="date" 
                           name="date" 
                           value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="toffee">Toffee:</label>
                    <select id="toffee" name="toffee">
                        <option value="">All Toffees</option>
                        <?php while ($toffee = $toffees_result->fetch_assoc()): ?>
                            <option value="<?php echo $toffee['id']; ?>" 
                                    <?php echo $filter_toffee == $toffee['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($toffee['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn">Apply Filters</button>
                <a href="transactions.php" class="btn">Clear Filters</a>
            </form>
        </div>

        <?php
        // Calculate summary statistics
        $summary_query = "SELECT 
            SUM(t.issue_quantity) as total_issued,
            SUM(t.load_quantity) as total_loaded,
            COUNT(DISTINCT t.transaction_date) as days_with_transactions,
            COUNT(*) as total_transactions
        FROM toffee_transactions t
        WHERE 1=1";
        
        $summary_params = [];
        $summary_types = "";
        
        if ($filter_date) {
            $summary_query .= " AND t.transaction_date = ?";
            $summary_params[] = $filter_date;
            $summary_types .= "s";
        }
        
        if ($filter_toffee) {
            $summary_query .= " AND t.toffee_id = ?";
            $summary_params[] = $filter_toffee;
            $summary_types .= "i";
        }
        
        $summary_stmt = $conn->prepare($summary_query);
        if (!empty($summary_params)) {
            $summary_stmt->bind_param($summary_types, ...$summary_params);
        }
        $summary_stmt->execute();
        $summary_result = $summary_stmt->get_result();
        $stats = $summary_result->fetch_assoc();
        ?>

        <div class="summary-stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_transactions'] ?? 0; ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_issued'] ?? 0; ?></div>
                <div class="stat-label">Total Issued</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_loaded'] ?? 0; ?></div>
                <div class="stat-label">Total Loaded</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['days_with_transactions'] ?? 0; ?></div>
                <div class="stat-label">Active Days</div>
            </div>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Toffee Name</th>
                        <th>Issue Qty</th>
                        <th>Load Qty</th>
                        <th>Current Stock</th>
                        <th>Notes</th>
                        <th>Transaction Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($transaction = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($transaction['toffee_name']); ?></strong></td>
                            <td class="issue-qty">
                                <?php echo $transaction['issue_quantity'] > 0 ? '-' . $transaction['issue_quantity'] : '-'; ?>
                            </td>
                            <td class="load-qty">
                                <?php echo $transaction['load_quantity'] > 0 ? '+' . $transaction['load_quantity'] : '-'; ?>
                            </td>
                            <td><?php echo $transaction['current_quantity']; ?></td>
                            <td><?php echo htmlspecialchars($transaction['notes']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No transactions found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
