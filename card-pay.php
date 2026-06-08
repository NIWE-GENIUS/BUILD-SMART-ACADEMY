<?php
// payment/card-pay.php
// Credit Card Payment Page (Redirect to secure gateway)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

// Check if there's a pending payment
if (!isset($_SESSION['pending_payment'])) {
    redirect('courses/');
}

$payment = $_SESSION['pending_payment'];

if ($payment['method'] != 'card') {
    redirect('payment/checkout.php?course_id=' . $payment['course_id']);
}

$db = Database::getConnection();
$user = getUserById(getCurrentUserId());

// For demo purposes, we'll show a simulated payment form
// In production, integrate with IyziPay, Stripe, or Flutterwave

$page_title = 'Card Payment';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .card-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-pay {
            width: 100%;
            background: var(--orange);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .secure-badge {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        
        .error-message {
            background: #e74c3c;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="payment-container">
            <h2 style="text-align: center;">Credit Card Payment</h2>
            <p style="text-align: center; color: #666;">Amount: <strong><?php echo number_format($payment['amount']); ?> RWF</strong></p>
            
            <div id="errorMsg" class="error-message" style="display: none;"></div>
            
            <form id="cardForm">
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                
                <div class="card-row">
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="text" id="expiry" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="password" id="cvv" placeholder="123" maxlength="4">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" id="card_name" placeholder="Name on card">
                </div>
                
                <button type="button" onclick="processPayment()" class="btn-pay">
                    <i class="fas fa-lock"></i> Pay <?php echo number_format($payment['amount']); ?> RWF
                </button>
            </form>
            
            <div class="secure-badge">
                <i class="fas fa-shield-alt"></i> Secure payment processing
            </div>
        </div>
    </div>

    <script>
        // Format card number
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            e.target.value = value;
        });
        
        // Format expiry
        document.getElementById('expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2,4);
            }
            e.target.value = value;
        });
        
        function processPayment() {
            const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
            const expiry = document.getElementById('expiry').value;
            const cvv = document.getElementById('cvv').value;
            const cardName = document.getElementById('card_name').value;
            const errorDiv = document.getElementById('errorMsg');
            
            // Basic validation
            if (!cardNumber || cardNumber.length < 16) {
                errorDiv.textContent = 'Please enter a valid card number';
                errorDiv.style.display = 'block';
                return;
            }
            
            if (!expiry || expiry.length < 5) {
                errorDiv.textContent = 'Please enter expiry date (MM/YY)';
                errorDiv.style.display = 'block';
                return;
            }
            
            if (!cvv || cvv.length < 3) {
                errorDiv.textContent = 'Please enter CVV';
                errorDiv.style.display = 'block';
                return;
            }
            
            if (!cardName) {
                errorDiv.textContent = 'Please enter cardholder name';
                errorDiv.style.display = 'block';
                return;
            }
            
            errorDiv.style.display = 'none';
            
            // Simulate payment processing
            const submitBtn = event.target;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            
            setTimeout(() => {
                // Simulate success - redirect to process
                window.location.href = '<?php echo SITE_URL; ?>payment/card-process.php';
            }, 2000);
        }
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>