<?php
require_once 'config.php';
require_once 'functions.php';

$conn = getConnection();

// Get filter parameters
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'daily';
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$filter_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
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

// Apply date filters based on filter type
if ($filter_type === 'daily' && $filter_date) {
    $query .= " AND t.transaction_date = ?";
    $params[] = $filter_date;
    $types .= "s";
} elseif ($filter_type === 'monthly' && $filter_month) {
    $query .= " AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
    $params[] = $filter_month;
    $types .= "s";
} elseif ($filter_type === 'annually' && $filter_year) {
    $query .= " AND YEAR(t.transaction_date) = ?";
    $params[] = $filter_year;
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
            background: rgba(27, 30, 43, 0.95);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            backdrop-filter: blur(5px);
            color: #fff;
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
            color: #fff;
        }
        .filter-group select,
        .filter-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, #667eea, #764ba2);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
    </style>
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
            </div>
        </div>
    </div>
    
    <div class="container">

        <div class="filter-section">
            <form method="GET" class="filter-form">
                <input type="hidden" name="filter_type" id="filter_type" value="<?php echo htmlspecialchars($filter_type); ?>">
                
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <button type="button" class="btn <?php echo $filter_type === 'daily' ? 'btn-primary' : 'btn-secondary'; ?>" onclick="setFilterType('daily')">Daily</button>
                    <button type="button" class="btn <?php echo $filter_type === 'monthly' ? 'btn-primary' : 'btn-secondary'; ?>" onclick="setFilterType('monthly')">Monthly</button>
                    <button type="button" class="btn <?php echo $filter_type === 'annually' ? 'btn-primary' : 'btn-secondary'; ?>" onclick="setFilterType('annually')">Annually</button>
                </div>
                
                <div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                    <div class="filter-group" id="daily-filter" style="display: <?php echo $filter_type === 'daily' ? 'flex' : 'none'; ?>;">
                        <label for="date">Date:</label>
                        <input type="date" 
                               id="date" 
                               name="date" 
                               value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    
                    <div class="filter-group" id="monthly-filter" style="display: <?php echo $filter_type === 'monthly' ? 'flex' : 'none'; ?>;">
                        <label for="month">Month:</label>
                        <input type="month" 
                               id="month" 
                               name="month" 
                               value="<?php echo htmlspecialchars($filter_month); ?>">
                    </div>
                    
                    <div class="filter-group" id="annual-filter" style="display: <?php echo $filter_type === 'annually' ? 'flex' : 'none'; ?>;">
                        <label for="year">Year:</label>
                        <input type="number" 
                               id="year" 
                               name="year" 
                               min="2000" 
                               max="2030" 
                               value="<?php echo htmlspecialchars($filter_year); ?>"
                               style="width: 100px;">
                    </div>
                    
                    <div class="filter-group">
                        <label for="toffee">Toffee:</label>
                        <select id="toffee" name="toffee">
                            <option value="">All Toffees</option>
                            <?php 
                            // Reset toffees_result pointer and fetch again
                            $toffees_result->data_seek(0);
                            while ($toffee = $toffees_result->fetch_assoc()): ?>
                                <option value="<?php echo $toffee['id']; ?>" 
                                        <?php echo $filter_toffee == $toffee['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($toffee['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="transactions.php" class="btn btn-warning">Clear Filters</a>
                </div>
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
        
        // Apply date filters based on filter type for summary
        if ($filter_type === 'daily' && $filter_date) {
            $summary_query .= " AND t.transaction_date = ?";
            $summary_params[] = $filter_date;
            $summary_types .= "s";
        } elseif ($filter_type === 'monthly' && $filter_month) {
            $summary_query .= " AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
            $summary_params[] = $filter_month;
            $summary_types .= "s";
        } elseif ($filter_type === 'annually' && $filter_year) {
            $summary_query .= " AND YEAR(t.transaction_date) = ?";
            $summary_params[] = $filter_year;
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
            <?php 
            // Show download button only if filters are applied (check if any filter parameter is present in URL)
            $hasFilters = isset($_GET['date']) || isset($_GET['month']) || isset($_GET['year']) || isset($_GET['toffee']);
            if ($hasFilters): ?>
                <button id="download-transactions-btn" class="btn" style="margin-top: 20px;">Download Transactions</button>
            <?php endif; ?>
        <?php else: ?>
            <p>No transactions found.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script>
        const { jsPDF } = window.jspdf;

        // Function to set filter type and show/hide appropriate inputs
        function setFilterType(type) {
            document.getElementById('filter_type').value = type;
            
            // Hide all filter inputs
            document.getElementById('daily-filter').style.display = 'none';
            document.getElementById('monthly-filter').style.display = 'none';
            document.getElementById('annual-filter').style.display = 'none';
            
            // Show the selected filter input
            if (type === 'daily') {
                document.getElementById('daily-filter').style.display = 'flex';
            } else if (type === 'monthly') {
                document.getElementById('monthly-filter').style.display = 'flex';
            } else if (type === 'annually') {
                document.getElementById('annual-filter').style.display = 'flex';
            }
            
            // Update button styles
            document.querySelectorAll('button[onclick^="setFilterType"]').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            
            const activeBtn = document.querySelector(`button[onclick="setFilterType('${type}')"]`);
            activeBtn.classList.remove('btn-secondary');
            activeBtn.classList.add('btn-primary');
        }

        // Hide download button initially if no filters are applied (showing current date by default)
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hasFilters = urlParams.has('date') || urlParams.has('month') || urlParams.has('year') || urlParams.has('toffee');
            
            const downloadBtn = document.getElementById('download-transactions-btn');
            if (downloadBtn) {
                downloadBtn.style.display = hasFilters ? 'block' : 'none';
            }
        });

        document.getElementById('download-transactions-btn').addEventListener('click', function() {
            const doc = new jsPDF();
            doc.text("Transaction History", 14, 16);

            const table = document.querySelector('table');
            const rows = [];
            const headers = [];

            // Get headers
            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach(cell => headers.push(cell.innerText));

            // Get data rows
            const dataRows = table.querySelectorAll('tbody tr');
            dataRows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => rowData.push(cell.innerText));
                rows.push(rowData);
            });

            doc.autoTable({
                head: [headers],
                body: rows,
                startY: 25,
                theme: 'grid',
                styles: {
                    fontSize: 8,
                    cellPadding: 2,
                    overflow: 'linebreak',
                    halign: 'left'
                },
                headStyles: {
                    fillColor: [200, 200, 200],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [240, 240, 240]
                }
            });

            doc.save('transaction_history.pdf');
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
