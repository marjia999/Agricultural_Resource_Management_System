<?php
// Check if resource_id is passed from resources page
$pre_selected_resource = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : 0;

include '../includes/session.php';
redirectIfNotClient();
include '../database.php';

// Get all resources for dropdown
$resources_query = mysqli_query($conn, "SELECT * FROM resources WHERE status = 'Available' ORDER BY type, name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_id = $_SESSION['user_id'];
    $resource_id = mysqli_real_escape_string($conn, $_POST['resource_id']);
    $quantity = (int)$_POST['quantity'];
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    
    // Get resource details
    $resource_query = mysqli_query($conn, "SELECT * FROM resources WHERE id = $resource_id");
    $resource = mysqli_fetch_assoc($resource_query);
    
    // Calculate days and total amount (with quantity)
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $start->diff($end)->days + 1;
    $total_amount = ($days * $resource['price']) * $quantity;
    $resource_type = $resource['type'];
    
    // Create description
    $description = "Quantity: $quantity x " . $resource['name'] . " - Location: $location";
    
    $query = "INSERT INTO service_requests (client_id, resource_id, resource_type, description, start_date, end_date, days, total_amount, quantity, status) 
              VALUES ('$client_id', '$resource_id', '$resource_type', '$description', '$start_date', '$end_date', '$days', '$total_amount', '$quantity', 'Pending')";
    
    if (mysqli_query($conn, $query)) {
        $success = "Request submitted successfully! You requested $quantity x " . $resource['name'] . ". We'll contact you soon.";
    } else {
        $error = "Failed to submit request. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service - AgriRMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7f5;
            color: #1a2e1f;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: #1B4F2B;
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .logo h2 {
            color: #FF8C42;
            font-size: 1.5rem;
        }

        .logo p {
            color: #f0f7f0;
            font-size: 0.75rem;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-links a:hover {
            color: #FFD966 !important;
            transform: translateY(-1px);
        }

        .btn-logout {
            background: #dc3545;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            color: white !important;
            transition: 0.3s;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #f0f7f0 0%, #ffffff 100%);
        }

        .container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #1B4F2B;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: #FF8C42;
            font-size: 2rem;
        }

        .page-header p {
            color: #666;
            margin-top: 0.5rem;
        }

        .form-card {
            background: white;
            border-radius: 32px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border: 1px solid #e8f0e8;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1B4F2B;
            font-size: 0.85rem;
        }

        .form-group label i {
            color: #FF8C42;
            width: 20px;
            margin-right: 5px;
        }

        .form-group label .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #e8f0e8;
            border-radius: 16px;
            font-size: 0.95rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            background: #fefefe;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 4px rgba(255,140,66,0.1);
        }

        .form-group input:read-only {
            background: #f0f7f0;
            cursor: not-allowed;
            color: #1B4F2B;
            font-weight: 600;
        }

        /* Row layouts */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-row-3col {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            background: #f0f7f0;
            border: 1px solid #e8f0e8;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            color: #1B4F2B;
        }

        .quantity-btn:hover {
            background: #FF8C42;
            color: white;
            border-color: #FF8C42;
        }

        .quantity-input {
            width: 80px;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
        }

        .location-input-wrapper {
            position: relative;
        }

        .location-input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #FF8C42;
            font-size: 1rem;
        }

        .location-input-wrapper input {
            padding-left: 45px;
        }

        .price-summary {
            background: linear-gradient(135deg, #f8f9f8, #ffffff);
            border-radius: 20px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 2px solid #FF8C42;
            box-shadow: 0 4px 15px rgba(255,140,66,0.15);
        }

        .price-summary h4 {
            color: #1B4F2B;
            margin-bottom: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .price-summary h4 i {
            color: #FF8C42;
        }

        .price-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .price-item {
            flex: 1;
            text-align: center;
            padding: 0.8rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .price-item .label {
            font-size: 0.7rem;
            color: #888;
            margin-bottom: 0.3rem;
        }

        .price-item .value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1B4F2B;
        }

        .total-amount {
            background: #FF8C42;
            color: #1B4F2B;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            text-align: center;
            min-width: 150px;
        }

        .total-amount .label {
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .total-amount .value {
            font-size: 1.5rem;
            font-weight: 800;
        }

        .calculation-formula {
            margin-top: 0.8rem;
            padding-top: 0.8rem;
            border-top: 1px dashed #ddd;
            text-align: center;
            font-size: 0.7rem;
            color: #888;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-submit {
            flex: 1;
            background: linear-gradient(135deg, #FF8C42, #e67e22);
            color: #1B4F2B;
            padding: 0.9rem;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255,140,66,0.3);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-back {
            flex: 1;
            background: #1B4F2B;
            color: white;
            padding: 0.9rem;
            border-radius: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #0d3b1a;
            transform: translateY(-2px);
        }

        .alert {
            padding: 0.9rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #fee2e2;
            color: #c0392b;
            border-left: 4px solid #c0392b;
        }

        .footer {
            background: #0d2b18;
            color: white;
            padding: 2rem 5%;
            text-align: center;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            .nav-links {
                justify-content: center;
            }
            .main-content {
                padding: 1rem;
            }
            .form-card {
                padding: 1.5rem;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .form-row-3col {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .button-group {
                flex-direction: column;
                gap: 0.8rem;
            }
            .price-details {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <h2><i class="fas fa-leaf"></i> AgriRMS</h2>
            <p>Agricultural Resource Management System</p>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="resources.php"><i class="fas fa-tractor"></i> Resources</a>
            <a href="request_service.php"><i class="fas fa-plus-circle"></i> New Request</a>
            <a href="my_requests.php"><i class="fas fa-list"></i> My Requests</a>
            <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-handshake"></i> Request Service</h1>
                <p>Fill in the details below to request agricultural resources</p>
            </div>

            <div class="form-card">
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                <?php if(isset($error)): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" id="requestForm">
                    <!-- ROW 1: Select Resource + Daily Rate -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-tractor"></i> Select Resource <span class="required">*</span></label>
                            <select name="resource_id" id="resource_id" required onchange="updateDailyRate()">
                                <option value="">-- Select a resource --</option>
                                <?php 
                                $current_type = '';
                                $resources_query = mysqli_query($conn, "SELECT * FROM resources WHERE status = 'Available' ORDER BY type, name");
                                while($resource = mysqli_fetch_assoc($resources_query)): 
                                    if($current_type != $resource['type']):
                                        $current_type = $resource['type'];
                                        echo '<optgroup label="' . $resource['type'] . '">';
                                    endif;
                                ?>
                                    <option value="<?php echo $resource['id']; ?>" 
                                            data-price="<?php echo $resource['price']; ?>"
                                            data-name="<?php echo htmlspecialchars($resource['name']); ?>"
                                            <?php echo ($pre_selected_resource == $resource['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($resource['name']); ?> (৳ <?php echo number_format($resource['price'], 2); ?>/day)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Daily Rate (BDT)</label>
                            <input type="text" id="daily_rate" readonly placeholder="Select a resource">
                        </div>
                    </div>

                    <!-- ROW 2: Quantity + Location -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-boxes"></i> Quantity <span class="required">*</span></label>
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                <input type="number" name="quantity" id="quantity" class="quantity-input" value="1" min="1" max="99" required onchange="calculateTotal()">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                                <span style="margin-left: 0.5rem; color: #888; font-size: 0.8rem;">unit(s)</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Delivery Location <span class="required">*</span></label>
                            <div class="location-input-wrapper">
                                <i class="fas fa-map-pin"></i>
                                <input type="text" name="location" id="location" required placeholder="Enter full delivery address">
                            </div>
                        </div>
                    </div>

                    <!-- ROW 3: Start Date + End Date -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Start Date <span class="required">*</span></label>
                            <input type="date" name="start_date" id="start_date" required onchange="calculateTotal()" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> End Date <span class="required">*</span></label>
                            <input type="date" name="end_date" id="end_date" required onchange="calculateTotal()" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                    </div>

                    <!-- Price Summary Section -->
                    <div class="price-summary" id="priceSummary" style="display: none;">
                        <h4><i class="fas fa-calculator"></i> Price Calculation</h4>
                        <div class="price-details">
                            <div class="price-item">
                                <div class="label">Selected Resource</div>
                                <div class="value" id="selectedResource">—</div>
                            </div>
                            <div class="price-item">
                                <div class="label">Daily Rate</div>
                                <div class="value" id="displayDailyRate">—</div>
                            </div>
                            <div class="price-item">
                                <div class="label">Quantity</div>
                                <div class="value" id="displayQuantity">0</div>
                            </div>
                            <div class="price-item">
                                <div class="label">Number of Days</div>
                                <div class="value" id="daysCount">0</div>
                            </div>
                            <div class="total-amount">
                                <div class="label">Total Amount</div>
                                <div class="value" id="totalAmount">৳ 0</div>
                            </div>
                        </div>
                        <div class="calculation-formula" id="calculationFormula">
                            <i class="fas fa-chart-line"></i> Formula: Daily Rate × Quantity × Days = Total Amount
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn-submit" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                        <a href="dashboard.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 AgriRMS - Agricultural Resource Management System</p>
    </footer>

    <script>
        let selectedResourcePrice = 0;
        let selectedResourceName = '';
        
        function changeQuantity(delta) {
            const quantityInput = document.getElementById('quantity');
            let newValue = parseInt(quantityInput.value) + delta;
            if (newValue < 1) newValue = 1;
            if (newValue > 99) newValue = 99;
            quantityInput.value = newValue;
            calculateTotal();
        }
        
        function updateDailyRate() {
            const resourceSelect = document.getElementById('resource_id');
            const dailyRateInput = document.getElementById('daily_rate');
            const selectedOption = resourceSelect.options[resourceSelect.selectedIndex];
            
            if (resourceSelect.value && selectedOption) {
                selectedResourcePrice = parseFloat(selectedOption.dataset.price) || 0;
                selectedResourceName = selectedOption.dataset.name || '';
                dailyRateInput.value = '৳ ' + selectedResourcePrice.toLocaleString() + ' per day';
                
                document.getElementById('selectedResource').innerHTML = selectedResourceName || '—';
                document.getElementById('displayDailyRate').innerHTML = '৳ ' + selectedResourcePrice.toLocaleString() + '<small>/day</small>';
            } else {
                selectedResourcePrice = 0;
                selectedResourceName = '';
                dailyRateInput.value = 'Select a resource first';
                document.getElementById('selectedResource').innerHTML = '—';
                document.getElementById('displayDailyRate').innerHTML = '—';
            }
            calculateTotal();
        }
        
        function calculateTotal() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const location = document.getElementById('location').value;
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const priceSummary = document.getElementById('priceSummary');
            const submitBtn = document.getElementById('submitBtn');
            
            document.getElementById('displayQuantity').innerHTML = quantity;
            
            if (startDate && endDate && selectedResourcePrice > 0) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (end >= start) {
                    const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
                    const subtotal = days * selectedResourcePrice;
                    const total = subtotal * quantity;
                    
                    document.getElementById('daysCount').innerHTML = days;
                    document.getElementById('totalAmount').innerHTML = '৳ ' + total.toLocaleString();
                    
                    document.getElementById('calculationFormula').innerHTML = '<i class="fas fa-chart-line"></i> ' + 
                        '৳ ' + selectedResourcePrice.toLocaleString() + ' × ' + quantity + ' unit(s) × ' + days + ' days = ৳ ' + total.toLocaleString();
                    
                    if (location.trim() !== '') {
                        priceSummary.style.display = 'block';
                        submitBtn.disabled = false;
                    } else {
                        priceSummary.style.display = 'block';
                        submitBtn.disabled = true;
                    }
                    return;
                }
            }
            
            if (!startDate || !endDate || selectedResourcePrice === 0) {
                priceSummary.style.display = 'none';
                submitBtn.disabled = true;
            } else if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                document.getElementById('daysCount').innerHTML = 'Invalid';
                document.getElementById('totalAmount').innerHTML = '৳ 0';
                document.getElementById('calculationFormula').innerHTML = '<i class="fas fa-exclamation-triangle"></i> End date must be after start date';
                priceSummary.style.display = 'block';
                submitBtn.disabled = true;
            }
        }
        
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').min = today;
        
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            if (startDate) {
                document.getElementById('end_date').min = startDate;
                if (document.getElementById('end_date').value && document.getElementById('end_date').value < startDate) {
                    document.getElementById('end_date').value = '';
                }
            }
            calculateTotal();
        });
        
        document.getElementById('location').addEventListener('input', function() { calculateTotal(); });
        document.getElementById('resource_id').addEventListener('change', updateDailyRate);
        document.getElementById('start_date').addEventListener('change', calculateTotal);
        document.getElementById('end_date').addEventListener('change', calculateTotal);
        
        if (<?php echo $pre_selected_resource; ?> > 0) {
            setTimeout(function() { updateDailyRate(); }, 100);
        }
        updateDailyRate();
    </script>
</body>
</html>