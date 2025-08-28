<?php
require_once 'config.php';

$conn = getConnection();

$summaryQuery = "SELECT name, quantity, price, (quantity * price) AS total_cost FROM toffees ORDER BY name ASC";
$summaryResult = $conn->query($summaryQuery);
$grandTotal = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Toffee Summary</title>
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
            </div>
        </div>
    </div>
    
    <div class="container">
        <h1>Toffee Summary</h1>
        <?php if ($summaryResult && $summaryResult->num_rows > 0): ?>
        <table class="transactions-table">
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
                    <td style="font-weight: bold;">Grand Total</td>
                    <td></td>
                    <td></td>
                    <td style="font-weight: bold;"><?php echo number_format($grandTotal, 2); ?></td>
                </tr>
            </tbody>
        </table>
        <button id="download-summary-btn" class="btn" style="margin-top: 10px;">Download Summary</button>
        <?php else: ?>
        <p style="color: #333;">No toffees available for summary.</p>
        <?php endif; ?>
        <!-- Removed Back to Home button -->
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script>
        const { jsPDF } = window.jspdf;

        document.getElementById('download-summary-btn').addEventListener('click', function() {
            const doc = new jsPDF();
            doc.text("Toffee Summary", 14, 16);

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
                    fontSize: 10,
                    cellPadding: 3,
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

            doc.save('toffee_summary.pdf');
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
