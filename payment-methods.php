<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/vendor/autoload.php';
use App\User;
use App\model\Category;
$db = new MysqliDb();

// Page metadata
$page = 'Payment Methods';
$og_title = 'Payment Methods - ' . settings()['companyname'];
$og_description = 'Discover all the secure payment methods we accept for your convenience. Shop with confidence using your preferred payment option.';
$og_image = settings()['homepage'] . ltrim(settings()['logo'], '/');

require __DIR__ . '/components/header.php';
?>

<style>
.payment-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 4rem 0;
    margin-bottom: 3rem;
}

.payment-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    margin-bottom: 2rem;
    overflow: hidden;
}

.payment-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.payment-method-logo {
    width: 80px;
    height: 50px;
    object-fit: contain;
    border-radius: 8px;
    background: #f8f9fa;
    padding: 8px;
}

.payment-category-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 1rem;
    margin: 0;
    font-weight: 600;
}

.payment-method-row {
    transition: all 0.3s ease;
}

.payment-method-row:hover {
    background-color: #f8f9fa;
    transform: scale(1.02);
}

.security-badge {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

.stats-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    border: 1px solid #dee2e6;
}

.stats-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #007bff;
    display: block;
}

.stats-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}
</style>

<!-- Payment Methods Header -->
<div class="payment-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="mb-3">
                    <i class="fas fa-credit-card me-3"></i>Payment Methods
                </h1>
                <p class="lead mb-0">We accept multiple secure payment options for your convenience and safety</p>
            </div>
            <div class="col-lg-4 text-center">
                <div class="security-badge">
                    <i class="fas fa-shield-alt me-2"></i>100% Secure Payments
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <!-- Payment Statistics -->
    <div class="row mb-5">
        <div class="col-md-3">
            <div class="stats-card">
                <span class="stats-number">10+</span>
                <div class="stats-label">Payment Methods</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <span class="stats-number">100%</span>
                <div class="stats-label">Secure Transactions</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <span class="stats-number">24/7</span>
                <div class="stats-label">Payment Support</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <span class="stats-number">SSL</span>
                <div class="stats-label">Encrypted</div>
            </div>
        </div>
    </div>

    <!-- Credit & Debit Cards -->
    <div class="payment-card">
        <h4 class="payment-category-header">
            <i class="fas fa-credit-card me-2"></i>Credit & Debit Cards
        </h4>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Logo</th>
                            <th>Payment Method</th>
                            <th>Description</th>
                            <th>Processing Time</th>
                            <th>Security</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center">
                                    <i class="fab fa-cc-visa text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </td>
                            <td><strong>Visa</strong></td>
                            <td>All Visa credit and debit cards accepted worldwide</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><i class="fas fa-shield-alt text-success"></i> 3D Secure</td>
                        </tr>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center">
                                    <i class="fab fa-cc-mastercard text-warning" style="font-size: 2rem;"></i>
                                </div>
                            </td>
                            <td><strong>Mastercard</strong></td>
                            <td>All Mastercard credit and debit cards accepted</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><i class="fas fa-shield-alt text-success"></i> SecureCode</td>
                        </tr>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center">
                                    <i class="fab fa-cc-amex text-info" style="font-size: 2rem;"></i>
                                </div>
                            </td>
                            <td><strong>American Express</strong></td>
                            <td>American Express credit cards accepted</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><i class="fas fa-shield-alt text-success"></i> SafeKey</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mobile Banking -->
    <div class="payment-card">
        <h4 class="payment-category-header">
            <i class="fas fa-mobile-alt me-2"></i>Mobile Banking (Bangladesh)
        </h4>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Logo</th>
                            <th>Payment Method</th>
                            <th>Description</th>
                            <th>Processing Time</th>
                            <th>Fees</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #e91e63, #f06292); padding: 10px; border-radius: 8px; min-height: 50px;">
                                    <div style="background: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #e91e63; font-size: 16px; letter-spacing: 1px;">
                                        bKash
                                    </div>
                                </div>
                            </td>
                            <td><strong>bKash</strong></td>
                            <td>Most popular mobile banking in Bangladesh</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><span class="badge bg-info">1.85%</span></td>
                        </tr>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #f47920, #ff9800); padding: 10px; border-radius: 8px; min-height: 50px;">
                                    <div style="background: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #f47920; font-size: 16px; letter-spacing: 1px;">
                                        Nagad
                                    </div>
                                </div>
                            </td>
                            <td><strong>Nagad</strong></td>
                            <td>Digital financial service by Bangladesh Post Office</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><span class="badge bg-info">1.59%</span></td>
                        </tr>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #8e44ad, #9b59b6); padding: 10px; border-radius: 8px; min-height: 50px;">
                                    <div style="background: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #8e44ad; font-size: 16px; letter-spacing: 1px;">
                                        Rocket
                                    </div>
                                </div>
                            </td>
                            <td><strong>Rocket</strong></td>
                            <td>Dutch-Bangla Bank mobile banking service</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><span class="badge bg-info">1.8%</span></td>
                        </tr>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #00a651, #4caf50); padding: 10px; border-radius: 8px; min-height: 50px;">
                                    <div style="background: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #00a651; font-size: 16px; letter-spacing: 1px;">
                                        Upay
                                    </div>
                                </div>
                            </td>
                            <td><strong>Upay</strong></td>
                            <td>UCB Fintech Company mobile financial service</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><span class="badge bg-info">1.5%</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Digital Wallets -->
    <div class="payment-card">
        <h4 class="payment-category-header">
            <i class="fas fa-wallet me-2"></i>Digital Wallets
        </h4>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Logo</th>
                            <th>Payment Method</th>
                            <th>Description</th>
                            <th>Processing Time</th>
                            <th>Security</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center">
                                    <i class="fab fa-paypal text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </td>
                            <td><strong>PayPal</strong></td>
                            <td>Secure online payments worldwide</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><i class="fas fa-shield-alt text-success"></i> Buyer Protection</td>
                        </tr>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center">
                                    <i class="fab fa-google-pay text-success" style="font-size: 2rem;"></i>
                                </div>
                            </td>
                            <td><strong>Google Pay</strong></td>
                            <td>Fast and secure payments with Google</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><i class="fas fa-shield-alt text-success"></i> Tokenization</td>
                        </tr>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center">
                                    <i class="fab fa-apple-pay text-dark" style="font-size: 2rem;"></i>
                                </div>
                            </td>
                            <td><strong>Apple Pay</strong></td>
                            <td>Secure payments using Touch ID or Face ID</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><i class="fas fa-shield-alt text-success"></i> Biometric Auth</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bank Transfer -->
    <div class="payment-card">
        <h4 class="payment-category-header">
            <i class="fas fa-university me-2"></i>Bank Transfer
        </h4>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Logo</th>
                            <th>Payment Method</th>
                            <th>Description</th>
                            <th>Processing Time</th>
                            <th>Fees</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center">
                                    <i class="fas fa-university text-primary" style="font-size: 1.5rem;"></i>
                                </div>
                            </td>
                            <td><strong>Direct Bank Transfer</strong></td>
                            <td>Transfer directly from your bank account</td>
                            <td><span class="badge bg-warning">1-3 Days</span></td>
                            <td><span class="badge bg-success">Free</span></td>
                        </tr>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center">
                                    <i class="fas fa-mobile-alt text-info" style="font-size: 1.5rem;"></i>
                                </div>
                            </td>
                            <td><strong>Online Banking</strong></td>
                            <td>Pay through your bank's online portal</td>
                            <td><span class="badge bg-success">Instant</span></td>
                            <td><span class="badge bg-success">Free</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cash on Delivery -->
    <div class="payment-card">
        <h4 class="payment-category-header">
            <i class="fas fa-hand-holding-usd me-2"></i>Cash on Delivery
        </h4>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Logo</th>
                            <th>Payment Method</th>
                            <th>Description</th>
                            <th>Availability</th>
                            <th>Fees</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="payment-method-row">
                            <td>
                                <div class="payment-method-logo d-flex align-items-center justify-content-center">
                                    <i class="fas fa-money-bill-wave text-success" style="font-size: 1.5rem;"></i>
                                </div>
                            </td>
                            <td><strong>Cash on Delivery</strong></td>
                            <td>Pay with cash when your order is delivered</td>
                            <td><span class="badge bg-info">Major Cities</span></td>
                            <td><span class="badge bg-warning">৳50 Fee</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Security Features -->
    <div class="row mt-5 mb-5">
        <div class="col-12">
            <h3 class="text-center mb-4">Why Our Payments Are Secure</h3>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h5>SSL Encryption</h5>
                <p class="text-muted">All transactions are protected with 256-bit SSL encryption</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h5>PCI Compliant</h5>
                <p class="text-muted">We follow PCI DSS standards for secure card processing</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="feature-icon">
                    <i class="fas fa-eye-slash"></i>
                </div>
                <h5>No Data Storage</h5>
                <p class="text-muted">We never store your card details on our servers</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h5>24/7 Support</h5>
                <p class="text-muted">Round-the-clock support for payment issues</p>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="payment-card">
        <h4 class="payment-category-header">
            <i class="fas fa-question-circle me-2"></i>Frequently Asked Questions
        </h4>
        <div class="card-body">
            <div class="accordion" id="paymentFAQ">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq1">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                            Is it safe to use my credit card on your website?
                        </button>
                    </h2>
                    <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#paymentFAQ">
                        <div class="accordion-body">
                            Yes, absolutely! We use industry-standard SSL encryption and are PCI DSS compliant. Your card details are processed securely and we never store them on our servers.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                            How long does it take for payments to process?
                        </button>
                    </h2>
                    <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#paymentFAQ">
                        <div class="accordion-body">
                            Most payments (cards, mobile banking, digital wallets) are processed instantly. Bank transfers may take 1-3 business days to reflect in our account.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                            Are there any additional fees for payments?
                        </button>
                    </h2>
                    <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#paymentFAQ">
                        <div class="accordion-body">
                            Most payment methods are free for customers. Mobile banking services may charge their standard transaction fees. Cash on Delivery has a ৳50 handling fee.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq4">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
                            What should I do if my payment fails?
                        </button>
                    </h2>
                    <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#paymentFAQ">
                        <div class="accordion-body">
                            If your payment fails, please try again or use an alternative payment method. If the problem persists, contact our customer support team for assistance.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Support -->
    <div class="text-center mt-5 mb-5">
        <h4>Need Help with Payments?</h4>
        <p class="text-muted mb-4">Our customer support team is here to help you with any payment-related questions</p>
        <div class="row justify-content-center">
            <div class="col-md-4">
                <a href="contact.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-headset me-2"></i>Contact Support
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/components/footer.php'; 
$db->disconnect();
?>
