<?php
require_once 'config.php';
require_once 'functions.php';

$conn = getConnection();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    
    try {
        $date = date('Y-m-d');
        $success = true;
        
        foreach ($_POST['toffees'] as $toffee_id => $data) {
            $issue_qty = isset($data['issue']) && $data['issue'] > 0 ? (int)$data['issue'] : 0;
            $load_qty = isset($data['load']) && $data['load'] > 0 ? (int)$data['load'] : 0;
            
            if ($issue_qty > 0 || $load_qty > 0) {
                // Get current quantity
                $stmt = $conn->prepare("SELECT quantity, name FROM toffees WHERE id = ?");
                $stmt->bind_param("i", $toffee_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $toffee = $result->fetch_assoc();
                
                if ($toffee) {
                    $current_qty = $toffee['quantity'];
                    $new_qty = $current_qty - $issue_qty + $load_qty;
                    
                    if ($new_qty < 0) {
                        throw new Exception("Insufficient stock for {$toffee['name']}. Current: $current_qty, Issue: $issue_qty");
                    }
                    
                    // Update toffee quantity
                    $update_stmt = $conn->prepare("UPDATE toffees SET quantity = ? WHERE id = ?");
                    $update_stmt->bind_param("ii", $new_qty, $toffee_id);
                    $update_stmt->execute();
                    
                    // Insert transaction record
                    $trans_stmt = $conn->prepare("INSERT INTO toffee_transactions (toffee_id, transaction_date, issue_quantity, load_quantity, current_quantity, notes) VALUES (?, ?, ?, ?, ?, ?)");
                    $notes = isset($data['notes']) ? $data['notes'] : '';
                    $trans_stmt->bind_param("isiiis", $toffee_id, $date, $issue_qty, $load_qty, $new_qty, $notes);
                    $trans_stmt->execute();
                }
            }
        }
        
        $conn->commit();
        $message = "Daily transactions saved successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Get all toffees
$query = "SELECT id, name, quantity, price, image_path FROM toffees ORDER BY name ASC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Toffee Issue & Load Entry</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .issue-load-form {
            background: rgba(27, 30, 43, 0.95);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            backdrop-filter: blur(5px);
        }
        .quantity-input {
            width: 80px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        .notes-input {
            width: 200px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .toffee-image-small {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .current-qty {
            font-weight: bold;
            color: #333;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        <h1>Daily Toffee Issue & Load Entry</h1>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="issue-load-form">
            <h2>Enter Daily Transactions - <?php echo date('d/m/Y'); ?></h2>
            
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Toffee Name</th>
                        <th>Current Stock</th>
                        <th>Issue Quantity</th>
                        <th>Load Quantity</th>
                        <th>New Stock</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($toffee = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($toffee['image_path'] && file_exists($toffee['image_path'])): ?>
                                    <img src="<?php echo $toffee['image_path']; ?>" alt="<?php echo htmlspecialchars($toffee['name']); ?>" class="toffee-image-small">
                                <?php else: ?>
                                    <div class="no-image">No Image</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($toffee['name']); ?></strong></td>
                            <td class="current-qty" data-current="<?php echo $toffee['quantity']; ?>">
                                <?php echo $toffee['quantity']; ?>
                            </td>
                            <td>
                                <input type="number" 
                                       name="toffees[<?php echo $toffee['id']; ?>][issue]" 
                                       class="quantity-input issue-input" 
                                       min="0" 
                                       max="<?php echo $toffee['quantity']; ?>"
                                       value="0"
                                       onchange="calculateNewStock(this)">
                            </td>
                            <td>
                                <input type="number" 
                                       name="toffees[<?php echo $toffee['id']; ?>][load]" 
                                       class="quantity-input load-input" 
                                       min="0" 
                                       value="0"
                                       onchange="calculateNewStock(this)">
                            </td>
                            <td class="new-stock">
                                <?php echo $toffee['quantity']; ?>
                            </td>
                            <td>
                                <input type="text" 
                                       name="toffees[<?php echo $toffee['id']; ?>][notes]" 
                                       class="notes-input" 
                                       placeholder="Optional notes">
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Save Daily Transactions</button>
        </form>
    </div>

    <script>
        function calculateNewStock(input) {
            const row = input.closest('tr');
            const currentQty = parseInt(row.querySelector('.current-qty').textContent);
            const issueQty = parseInt(row.querySelector('.issue-input').value) || 0;
            const loadQty = parseInt(row.querySelector('.load-input').value) || 0;
            
            const newStock = currentQty - issueQty + loadQty;
            row.querySelector('.new-stock').textContent = newStock;
            
            // Validate issue quantity
            if (issueQty > currentQty) {
                input.setCustomValidity('Issue quantity cannot exceed current stock');
                input.style.borderColor = 'red';
            } else {
                input.setCustomValidity('');
                input.style.borderColor = '';
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            let hasValidData = false;
            const inputs = document.querySelectorAll('.issue-input, .load-input');
            
            inputs.forEach(input => {
                if (parseInt(input.value) > 0) {
                    hasValidData = true;
                }
            });
            
            if (!hasValidData) {
                e.preventDefault();
                alert('Please enter at least one issue or load quantity');
                return false;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
