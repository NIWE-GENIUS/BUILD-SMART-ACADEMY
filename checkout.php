<?php
// payment/checkout.php
// Payment Checkout Page for Paid Courses

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$user_id = getCurrentUserId();

if (!$course_id) {
    redirect('courses/');
}

// Get course details
$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM courses WHERE id = ? AND status = 'published'");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    redirect('courses/');
}

// Check if already enrolled
if (isEnrolled($user_id, $course_id)) {
    redirect('courses/course-details.php?id=' . $course_id);
}

// Check if user has lifetime free access
if (hasLifetimeFree()) {
    redirect('courses/enroll.php?id=' . $course_id);
}

// Check if course is free
if (!$course['is_paid'] || $course['price'] <= 0) {
    redirect('courses/enroll.php?id=' . $course_id);
}

$error = '';
$success = '';

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $payment_method = $_POST['payment_method'] ?? '';
        $phone_number = sanitizeInput($_POST['phone_number'] ?? '');
        
        if ($payment_method == 'momo' && empty($phone_number)) {
            $error = 'Phone number is required for Mobile Money.';
        } elseif ($payment_method == 'momo' && !validatePhone($phone_number)) {
            $error = 'Please enter a valid phone number (+250XXXXXXXXX)';
        } else {
            // Create payment record
            $transaction_id = 'TXN_' . time() . '_' . $user_id . '_' . $course_id;
            
            $stmt = $db->prepare("
                INSERT INTO payments (user_id, course_id, amount, payment_method, transaction_id, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$user_id, $course_id, $course['price'], $payment_method, $transaction_id]);
            $payment_id = $db->lastInsertId();
            
            // Store payment session
            $_SESSION['pending_payment'] = [
                'payment_id' => $payment_id,
                'course_id' => $course_id,
                'amount' => $course['price'],
                'method' => $payment_method,
                'phone' => $phone_number
            ];
            
            // Redirect to payment gateway
            if ($payment_method == 'momo') {
                redirect('payment/momo-pay.php');
            } else {
                redirect('payment/card-pay.php');
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = 'Checkout - ' . $course['title'];
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
        .checkout-container {
            max-width: 800px;
            margin: 50px auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .order-summary {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .payment-form {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .course-image {
            background: linear-gradient(135deg, var(--blue), var(--orange));
            height: 100px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            margin-bottom: 20px;
        }
        
        .price {
            font-size: 28px;
            font-weight: bold;
            color: var(--orange);
            margin: 15px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .payment-methods {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            flex: 1;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method.selected {
            border-color: var(--orange);
            background: #fff8f0;
        }
        
        .payment-method i {
            font-size: 32px;
            margin-bottom: 8px;
            display: block;
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
        
        .btn-pay:hover {
            background: #e55a2b;
        }
        
        .hidden {
            display: none;
        }
        
        .error-message {
            background: #e74c3c;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="checkout-container">
            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="course-image">
                    <i class="fas fa-building"></i>
                </div>
                <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                <p style="color: #666; margin: 10px 0;"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                <div class="price">
                    <?php echo number_format($course['price']); ?> RWF
                </div>
                <hr>
                <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                    <strong>Total:</strong>
                    <strong><?php echo number_format($course['price']); ?> RWF</strong>
                </div>
            </div>
            
            <!-- Payment Form -->
            <div class="payment-form">
                <h3>Payment Method</h3>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" id="paymentForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="payment-methods">
                        <div class="payment-method" data-method="momo" onclick="selectMethod('momo')">
                            <i class="fas fa-mobile-alt"></i>
                            <strong>Mobile Money</strong>
                            <small style="display: block;">MTN MoMo / Airtel</small>
                        </div>
                        <div class="payment-method" data-method="card" onclick="selectMethod('card')">
                            <i class="fas fa-credit-card"></i>
                            <strong>Credit Card</strong>
                            <small style="display: block;">Visa / Mastercard</small>
                        </div>
                    </div>
                    
                    <div id="momoFields" class="hidden">
                        <div class="form-group">
                            <label for="phone_number">Mobile Money Number</label>
                            <input type="tel" id="phone_number" name="phone_number" placeholder="+250XXXXXXXXX">
                            <small style="color: #666;">Enter your MTN or Airtel number</small>
                        </div>
                    </div>
                    
                    <div id="cardFields" class="hidden">
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" placeholder="1234 5678 9012 3456" disabled>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <input type="text" placeholder="MM/YY" disabled>
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" placeholder="123" disabled>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Cardholder Name</label>
                            <input type="text" placeholder="Name on card" disabled>
                        </div>
                        <small style="color: #666;">You will be redirected to secure payment page</small>
                    </div>
                    
                    <input type="hidden" name="payment_method" id="selectedMethod" value="">
                    
                    <button type="submit" class="btn-pay" id="payBtn" disabled>
                        <i class="fas fa-lock"></i> Complete Payment
                    </button>
                </form>
                
                <p style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
                    <i class="fas fa-shield-alt"></i> Secure payment. Your information is protected.
                </p>
            </div>
        </div>
    </div>

    <script>
        let selectedMethod = null;
        
        function selectMethod(method) {
            selectedMethod = method;
            document.getElementById('selectedMethod').value = method;
            
            // Update UI
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            document.querySelector(`[data-method="${method}"]`).classList.add('selected');
            
            // Show/hide fields
            if (method === 'momo') {
                document.getElementById('momoFields').classList.remove('hidden');
                document.getElementById('cardFields').classList.add('hidden');
                document.getElementById('payBtn').disabled = false;
            } else {
                document.getElementById('momoFields').classList.add('hidden');
                document.getElementById('cardFields').classList.remove('hidden');
                document.getElementById('payBtn').disabled = false;
            }
        }
        
        // Form validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            if (selectedMethod === 'momo') {
                const phone = document.getElementById('phone_number').value;
                if (!phone.match(/^\+250[0-9]{9}$/)) {
                    e.preventDefault();
                    alert('Please enter a valid phone number starting with +250');
                }
            }
        });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>