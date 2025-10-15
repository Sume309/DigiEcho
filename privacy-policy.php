<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Dhaka');
require_once __DIR__ . '/vendor/autoload.php';
$page = 'Privacy Policy';

require __DIR__ . '/components/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h1 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Privacy Policy</h1>
                    <p class="mb-0 mt-2">Protecting Your Privacy is Our Priority</p>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <small class="text-muted">Last Updated: <?= date('F d, Y') ?></small>
                    </div>

                    <section class="mb-4">
                        <h3 class="text-primary border-bottom pb-2"><i class="fas fa-info-circle me-2"></i>Introduction</h3>
                        <p>Welcome to <?= htmlspecialchars(settings()['companyname']) ?>. We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains what information we collect, how we use it, and what rights you have in relation to it.</p>
                        <p>If you have any questions or concerns about our policy or our practices with regard to your personal information, please contact us at <strong>info@digiecho.com</strong>.</p>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-primary border-bottom pb-2"><i class="fas fa-database me-2"></i>Information We Collect</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
                                <ul>
                                    <li>Name and contact information</li>
                                    <li>Email address and phone number</li>
                                    <li>Billing and shipping addresses</li>
                                    <li>Payment information (processed securely)</li>
                                    <li>Account credentials</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-chart-line me-2"></i>Usage Information</h5>
                                <ul>
                                    <li>Browser and device information</li>
                                    <li>IP address and location data</li>
                                    <li>Shopping behavior and preferences</li>
                                    <li>Website interaction patterns</li>
                                    <li>Customer service communications</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-primary border-bottom pb-2"><i class="fas fa-cogs me-2"></i>How We Use Your Information</h3>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-bullhorn me-2"></i>Primary Uses:</h6>
                            <ul class="mb-0">
                                <li><strong>Order Processing:</strong> To process and fulfill your orders</li>
                                <li><strong>Customer Service:</strong> To provide customer support and resolve issues</li>
                                <li><strong>Account Management:</strong> To maintain and secure your account</li>
                                <li><strong>Communication:</strong> To send order updates, promotions, and important notices</li>
                                <li><strong>Website Improvement:</strong> To analyze usage and improve our services</li>
                            </ul>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-primary border-bottom pb-2"><i class="fas fa-share-alt me-2"></i>Information Sharing</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-check-circle me-2"></i>We Share With:</h6>
                                    <ul class="mb-0">
                                        <li>Trusted service providers</li>
                                        <li>Payment processors</li>
                                        <li>Shipping and delivery partners</li>
                                        <li>Legal authorities (when required)</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-times-circle me-2"></i>We Never:</h6>
                                    <ul class="mb-0">
                                        <li>Sell your personal data</li>
                                        <li>Share data with advertisers</li>
                                        <li>Use data for unauthorized purposes</li>
                                        <li>Share without your consent</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-primary border-bottom pb-2"><i class="fas fa-shield-alt me-2"></i>Data Security</h3>
                        <p>We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="p-3 border rounded">
                                    <i class="fas fa-lock fa-2x text-success mb-2"></i>
                                    <h6>SSL Encryption</h6>
                                    <small>All data transmission is encrypted</small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="p-3 border rounded">
                                    <i class="fas fa-server fa-2x text-info mb-2"></i>
                                    <h6>Secure Servers</h6>
                                    <small>Protected infrastructure and databases</small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="p-3 border rounded">
                                    <i class="fas fa-user-shield fa-2x text-warning mb-2"></i>
                                    <h6>Access Control</h6>
                                    <small>Limited access to authorized personnel</small>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-primary border-bottom pb-2"><i class="fas fa-user-cog me-2"></i>Your Rights</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-eye me-2"></i>Access Rights:</h6>
                                <ul>
                                    <li>View your personal data</li>
                                    <li>Request data corrections</li>
                                    <li>Download your information</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-trash-alt me-2"></i>Control Rights:</h6>
                                <ul>
                                    <li>Delete your account</li>
                                    <li>Opt-out of marketing</li>
                                    <li>Restrict data processing</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-primary border-bottom pb-2"><i class="fas fa-cookie-bite me-2"></i>Cookies Policy</h3>
                        <p>We use cookies and similar technologies to enhance your browsing experience, analyze website traffic, and personalize content. You can control cookie settings through your browser preferences.</p>
                        <div class="alert alert-warning">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Note:</strong> Disabling cookies may affect website functionality and your user experience.
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-primary border-bottom pb-2"><i class="fas fa-child me-2"></i>Children's Privacy</h3>
                        <p>Our services are not intended for children under 18 years of age. We do not knowingly collect personal information from children under 18. If you become aware that a child has provided us with personal information, please contact us immediately.</p>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-primary border-bottom pb-2"><i class="fas fa-edit me-2"></i>Policy Updates</h3>
                        <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on our website and updating the "Last Updated" date.</p>
                        <div class="alert alert-info">
                            <strong>Stay Informed:</strong> We recommend reviewing this policy periodically to stay informed about how we protect your information.
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-primary border-bottom pb-2"><i class="fas fa-envelope me-2"></i>Contact Us</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h5><i class="fas fa-headset me-2"></i>Customer Support</h5>
                                        <p class="mb-1"><strong>Email:</strong> info@digiecho.com</p>
                                        <p class="mb-1"><strong>Phone:</strong> +880 1700-000000</p>
                                        <p class="mb-0"><strong>Address:</strong> 123 Street, Dhaka, Bangladesh</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h5><i class="fas fa-balance-scale me-2"></i>Legal Department</h5>
                                        <p class="mb-1"><strong>Email:</strong> legal@digiecho.com</p>
                                        <p class="mb-1"><strong>Business Hours:</strong> 9 AM - 6 PM</p>
                                        <p class="mb-0"><strong>Response Time:</strong> 24-48 hours</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="text-center">
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>Return to Homepage
                        </a>
                        <a href="contact.php" class="btn btn-outline-primary btn-lg ms-2">
                            <i class="fas fa-phone me-2"></i>Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/components/footer.php'; ?>