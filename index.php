<?php
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
    <div class="container">
        <h1>Toffee Stock Management</h1>

        <div class="action-bar">
            <a href="add.php" class="btn" style="background-color: #007bff; color: white;">Add New Toffee</a>
            <a href="issue_load.php" class="btn" style="background-color: #28a745; color: white;">Daily Issue/Load</a>
            <a href="transactions.php" class="btn" style="background-color: #17a2b8; color: white;">Transactions</a>
            <button onclick="window.location.href='summary.php'" class="btn" style="background-color: #ffc107; color: black;">Summary</button>
            
            <form method="GET" class="search-form" style="float: right; display: inline-block;">
                <input
                    type="text"
                    name="search"
                    placeholder="Search toffee..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    style="padding: 8px; margin-right: 5px;"
                />
                <button type="submit" class="btn" style="padding: 8px 15px;">Search</button>
            </form>
        </div>

        <div id="summary-section" style="display:none; margin-top: 20px;">
            <h2>Summary of Toffees</h2>
            <?php
            $summaryQuery = "SELECT name, quantity, price, (quantity * price) AS total_cost FROM toffees ORDER BY name ASC";
            $summaryResult = $conn->query($summaryQuery);
            $grandTotal = 0;
            if ($summaryResult && $summaryResult->num_rows > 0):
            ?>
            <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Quantity</th>
                        <th>Price per item (Rs.)</th>
                        <th>Total Cost (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $summaryResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td><?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo number_format($row['total_cost'], 2); ?></td>
                        </tr>
                        <?php $grandTotal += $row['total_cost']; ?>
                    <?php endwhile; ?>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: bold;">Grand Total</td>
                        <td style="font-weight: bold;"><?php echo number_format($grandTotal, 2); ?></td>
                    </tr>
                </tbody>
            </table>
            <button id="download-summary-btn" class="btn" style="margin-top: 10px;">Download Summary</button>
            <?php else: ?>
                <p>No toffees available for summary.</p>
            <?php endif; ?>
        </div>

        <script>
            document.getElementById('show-summary-btn').addEventListener('click', function() {
                var summarySection = document.getElementById('summary-section');
                if (summarySection.style.display === 'none') {
                    summarySection.style.display = 'block';
                    this.textContent = 'Hide Summary';
                } else {
                    summarySection.style.display = 'none';
                    this.textContent = 'Show Summary';
                }
            });

            document.getElementById('download-summary-btn').addEventListener('click', function() {
                var table = document.querySelector('#summary-section table');
                var rows = table.querySelectorAll('tr');
                var csvContent = '';
                rows.forEach(function(row) {
                    var cols = row.querySelectorAll('th, td');
                    var rowData = [];
                    cols.forEach(function(col) {
                        var data = col.innerText.replace(/,/g, ''); // Remove commas to avoid CSV issues
                        rowData.push('"' + data + '"');
                    });
                    csvContent += rowData.join(',') + '\n';
                });
                var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'toffee_summary.csv';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        </script>

        <div class="two-column-layout">
            <div class="left-column">
                <?php if ($search): ?>
                    <h2>Search Results</h2>
                    <?php if ($searchResult && $searchResult->num_rows > 0): ?>
                        <?php while ($toffee = $searchResult->fetch_assoc()): ?>
                            <div class="search-result-card">
                                <div class="search-result-image">
                                    <?php if ($toffee['image_path'] && file_exists($toffee['image_path'])): ?>
                                        <img src="<?php echo $toffee['image_path']; ?>" alt="<?php echo htmlspecialchars($toffee['name']); ?>" />
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="search-result-details">
                                    <h3><?php echo htmlspecialchars($toffee['name']); ?></h3>
                                    <p><strong>Quantity:</strong> <?php echo $toffee['quantity']; ?></p>
                                    <p><strong>Price per item:</strong> $<?php echo number_format($toffee['price'], 2); ?></p>
                                    <p><strong>Total Cost:</strong> $<?php echo number_format($toffee['quantity'] * $toffee['price'], 2); ?></p>
                                    <?php if (!empty($toffee['fourto'])): ?>
                                        <p><strong>Type:</strong> <?php echo htmlspecialchars($toffee['fourto']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($toffee['description'])): ?>
                                        <p><strong>Description:</strong> <?php echo htmlspecialchars($toffee['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="actions">
                                        <a
                                            href="edit.php?id=<?php echo $toffee['id']; ?>"
                                            class="btn btn-edit"
                                            >Edit</a
                                        >
                                        <a
                                            href="delete.php?id=<?php echo $toffee['id']; ?>"
                                            class="btn btn-delete"
                                            onclick="return confirm('Are you sure?')"
                                            >Delete</a
                                        >
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No toffees found matching "<?php echo htmlspecialchars($search); ?>"</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="right-column">
                <h2>Top Toffees</h2>
                <div class="toffee-grid">
                    <?php if ($topResult && $topResult->num_rows > 0): ?>
                        <?php while ($toffee = $topResult->fetch_assoc()): ?>
                            <div class="toffee-card">
                                <?php if ($toffee['image_path'] && file_exists($toffee['image_path'])): ?>
                                    <img
                                        src="<?php echo $toffee['image_path']; ?>"
                                        alt="<?php echo htmlspecialchars($toffee['name']); ?>"
                                    />
                                <?php else: ?>
                                    <div class="no-image">No Image</div>
                                <?php endif; ?>

                                <h3><?php echo htmlspecialchars($toffee['name']); ?></h3>
                                <p><strong>Quantity:</strong> <?php echo $toffee['quantity']; ?></p>
                                    <p>
                                        <strong>Price per item:</strong> Rs.
                                        <?php echo number_format($toffee['price'], 2); ?>
                                    </p>
                                    <p>
                                        <strong>Total Cost:</strong> Rs.
                                        <?php echo number_format($toffee['quantity'] * $toffee['price'], 2); ?>
                                    </p>

                                <div class="actions">
                                    <a
                                        href="edit.php?id=<?php echo $toffee['id']; ?>"
                                        class="btn btn-edit"
                                        >Edit</a
                                    >
                                    <a
                                        href="delete.php?id=<?php echo $toffee['id']; ?>"
                                        class="btn btn-delete"
                                        onclick="return confirm('Are you sure?')"
                                        >Delete</a
                                    >
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No toffees in stock</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
