<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Dhaka');
require_once __DIR__ . '/vendor/autoload.php';
$page = 'Terms & Conditions';

require __DIR__ . '/components/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white text-center">
                    <h1 class="mb-0"><i class="fas fa-file-contract me-2"></i>Terms & Conditions</h1>
                    <p class="mb-0 mt-2">Please Read These Terms Carefully Before Using Our Services</p>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <small class="text-muted">Last Updated: <?= date('F d, Y') ?></small>
                    </div>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-handshake me-2"></i>Agreement to Terms</h3>
                        <p>Welcome to <?= htmlspecialchars(settings()['companyname']) ?>! By accessing or using our website and services, you agree to be bound by these Terms & Conditions. If you do not agree with any of these terms, please do not use our services.</p>
                        <div class="alert alert-warning">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Important:</strong> These terms constitute a legally binding agreement between you and <?= htmlspecialchars(settings()['companyname']) ?>.
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-user-check me-2"></i>User Accounts</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-plus-circle me-2"></i>Account Creation</h5>
                                <ul>
                                    <li>You must be 18 years or older</li>
                                    <li>Provide accurate and complete information</li>
                                    <li>Maintain account security</li>
                                    <li>Notify us of unauthorized access</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-shield-alt me-2"></i>Account Responsibilities</h5>
                                <ul>
                                    <li>You are responsible for all account activity</li>
                                    <li>Keep login credentials secure</li>
                                    <li>Update information when necessary</li>
                                    <li>Comply with all applicable laws</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-shopping-cart me-2"></i>Orders and Purchases</h3>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Order Process:</h6>
                            <ol class="mb-0">
                                <li><strong>Product Selection:</strong> Browse and select products from our catalog</li>
                                <li><strong>Order Placement:</strong> Add items to cart and complete checkout process</li>
                                <li><strong>Order Confirmation:</strong> Receive email confirmation with order details</li>
                                <li><strong>Payment Processing:</strong> Secure payment through our approved gateways</li>
                                <li><strong>Fulfillment:</strong> Order processing and shipping preparation</li>
                            </ol>
                        </div>
                        <p><strong>Order Acceptance:</strong> All orders are subject to acceptance and availability. We reserve the right to refuse or cancel any order at our discretion.</p>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-credit-card me-2"></i>Pricing and Payment</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-tag me-2"></i>Pricing Policy</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="mb-0">
                                            <li>All prices in Bangladesh Taka (৳)</li>
                                            <li>Prices subject to change without notice</li>
                                            <li>Taxes and fees included where applicable</li>
                                            <li>Special offers have terms and conditions</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Payment Methods</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="mb-0">
                                            <li>Credit/Debit Cards</li>
                                            <li>Mobile Banking (bKash, Nagad)</li>
                                            <li>Bank Transfer</li>
                                            <li>Cash on Delivery (COD)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-truck me-2"></i>Shipping and Delivery</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-success">
                                    <tr>
                                        <th>Delivery Area</th>
                                        <th>Delivery Time</th>
                                        <th>Shipping Cost</th>
                                        <th>Additional Info</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Dhaka City</td>
                                        <td>1-2 Business Days</td>
                                        <td>৳60</td>
                                        <td>Same-day delivery available</td>
                                    </tr>
                                    <tr>
                                        <td>Outside Dhaka</td>
                                        <td>3-5 Business Days</td>
                                        <td>৳120</td>
                                        <td>Express delivery available</td>
                                    </tr>
                                    <tr>
                                        <td>Remote Areas</td>
                                        <td>5-7 Business Days</td>
                                        <td>৳150</td>
                                        <td>Subject to courier availability</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-warning">
                            <strong>Note:</strong> Delivery times are estimates and may vary due to weather, location, or other unforeseen circumstances.
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-ban me-2"></i>Prohibited Uses</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-times-circle me-2"></i>You May Not:</h6>
                                    <ul class="mb-0">
                                        <li>Use site for illegal purposes</li>
                                        <li>Transmit malicious code</li>
                                        <li>Attempt unauthorized access</li>
                                        <li>Copy or reproduce content</li>
                                        <li>Harass other users</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Violations Result In:</h6>
                                    <ul class="mb-0">
                                        <li>Account suspension</li>
                                        <li>Order cancellation</li>
                                        <li>Legal action</li>
                                        <li>Permanent ban</li>
                                        <li>Forfeiture of credits</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-copyright me-2"></i>Intellectual Property</h3>
                        <p>All content on this website, including but not limited to text, graphics, logos, images, software, and design, is the property of <?= htmlspecialchars(settings()['companyname']) ?> or its content suppliers and is protected by intellectual property laws.</p>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="p-3 border rounded">
                                    <i class="fas fa-trademark fa-2x text-success mb-2"></i>
                                    <h6>Trademarks</h6>
                                    <small>Protected brand names and logos</small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="p-3 border rounded">
                                    <i class="fas fa-image fa-2x text-info mb-2"></i>
                                    <h6>Images</h6>
                                    <small>Copyrighted product photos</small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="p-3 border rounded">
                                    <i class="fas fa-code fa-2x text-warning mb-2"></i>
                                    <h6>Website Code</h6>
                                    <small>Proprietary software and design</small>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-exclamation-triangle me-2"></i>Disclaimers and Limitations</h3>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-info-circle me-2"></i>Service Availability:</h6>
                            <p class="mb-0">We strive to maintain uninterrupted service but cannot guarantee 100% uptime. Services may be temporarily unavailable due to maintenance, updates, or technical issues.</p>
                        </div>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-shield-alt me-2"></i>Limitation of Liability:</h6>
                            <p class="mb-0">Our liability is limited to the amount paid for the specific product or service. We are not liable for indirect, incidental, or consequential damages.</p>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-gavel me-2"></i>Governing Law</h3>
                        <p>These Terms & Conditions are governed by and construed in accordance with the laws of Bangladesh. Any disputes arising from these terms will be subject to the exclusive jurisdiction of the courts of Bangladesh.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-balance-scale me-2"></i>Dispute Resolution:</h6>
                                <ol>
                                    <li>Direct negotiation</li>
                                    <li>Mediation process</li>
                                    <li>Legal proceedings</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-clock me-2"></i>Limitation Period:</h6>
                                <p>Claims must be brought within one (1) year of the date the cause of action arose.</p>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-sync-alt me-2"></i>Changes to Terms</h3>
                        <p>We reserve the right to modify these Terms & Conditions at any time. Changes will be effective immediately upon posting on our website. Continued use of our services after changes constitutes acceptance of the new terms.</p>
                        <div class="alert alert-success">
                            <strong>Stay Updated:</strong> We recommend checking this page periodically for any changes to our terms and conditions.
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-success border-bottom pb-2"><i class="fas fa-phone me-2"></i>Contact Information</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h5><i class="fas fa-building me-2"></i>Legal Department</h5>
                                        <p class="mb-1"><strong>Email:</strong> legal@digiecho.com</p>
                                        <p class="mb-1"><strong>Phone:</strong> +880 1700-000000</p>
                                        <p class="mb-0"><strong>Address:</strong> 123 Street, Dhaka, Bangladesh</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h5><i class="fas fa-headset me-2"></i>Customer Support</h5>
                                        <p class="mb-1"><strong>Email:</strong> info@digiecho.com</p>
                                        <p class="mb-1"><strong>Hours:</strong> 9 AM - 10 PM Daily</p>
                                        <p class="mb-0"><strong>Response:</strong> Within 24 hours</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="text-center">
                        <a href="index.php" class="btn btn-success btn-lg">
                            <i class="fas fa-home me-2"></i>Return to Homepage
                        </a>
                        <a href="privacy-policy.php" class="btn btn-outline-success btn-lg ms-2">
                            <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/components/footer.php'; ?>