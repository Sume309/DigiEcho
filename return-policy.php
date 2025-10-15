<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Dhaka');
require_once __DIR__ . '/vendor/autoload.php';
$page = 'Return Policy';

require __DIR__ . '/components/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark text-center">
                    <h1 class="mb-0"><i class="fas fa-undo me-2"></i>Return Policy</h1>
                    <p class="mb-0 mt-2">Hassle-Free Returns for Your Peace of Mind</p>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <small class="text-muted">Last Updated: <?= date('F d, Y') ?></small>
                    </div>

                    <section class="mb-4">
                        <h3 class="text-warning border-bottom pb-2"><i class="fas fa-heart me-2"></i>Our Commitment</h3>
                        <p>At <?= htmlspecialchars(settings()['companyname']) ?>, customer satisfaction is our top priority. We want you to be completely happy with your purchase. If you're not satisfied, we're here to help with our flexible return policy.</p>
                        <div class="alert alert-success">
                            <strong><i class="fas fa-check-circle me-2"></i>Customer First:</strong> We believe in making returns as easy and stress-free as possible for our valued customers.
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-warning border-bottom pb-2"><i class="fas fa-clock me-2"></i>Return Timeframes</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card border-success h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-mobile-alt fa-3x text-success mb-3"></i>
                                        <h5>Electronics</h5>
                                        <h3 class="text-success">7 Days</h3>
                                        <p class="mb-0">From delivery date</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-info h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-tshirt fa-3x text-info mb-3"></i>
                                        <h5>Fashion Items</h5>
                                        <h3 class="text-info">14 Days</h3>
                                        <p class="mb-0">From delivery date</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-primary h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-couch fa-3x text-primary mb-3"></i>
                                        <h5>Home & Kitchen</h5>
                                        <h3 class="text-primary">30 Days</h3>
                                        <p class="mb-0">From delivery date</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-warning border-bottom pb-2"><i class="fas fa-check-square me-2"></i>Return Conditions</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-thumbs-up me-2"></i>Eligible for Return:</h6>
                                    <ul class="mb-0">
                                        <li>Items in original condition</li>
                                        <li>Original packaging included</li>
                                        <li>All accessories and manuals</li>
                                        <li>Receipt or order confirmation</li>
                                        <li>Within return timeframe</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-thumbs-down me-2"></i>Not Eligible for Return:</h6>
                                    <ul class="mb-0">
                                        <li>Used or damaged items</li>
                                        <li>Items without original packaging</li>
                                        <li>Personalized or custom items</li>
                                        <li>Perishable goods</li>
                                        <li>Digital products</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-warning border-bottom pb-2"><i class="fas fa-route me-2"></i>Return Process</h3>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="timeline">
                                    <div class="row">
                                        <div class="col-md-3 text-center">
                                            <div class="step-circle bg-primary text-white">1</div>
                                            <h6 class="mt-2">Initiate Return</h6>
                                            <p class="small">Contact customer support or use our return portal</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="step-circle bg-info text-white">2</div>
                                            <h6 class="mt-2">Return Approval</h6>
                                            <p class="small">Receive return authorization and instructions</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="step-circle bg-warning text-white">3</div>
                                            <h6 class="mt-2">Ship Items</h6>
                                            <p class="small">Package and send items using provided label</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="step-circle bg-success text-white">4</div>
                                            <h6 class="mt-2">Refund Process</h6>
                                            <p class="small">Inspection and refund within 3-5 business days</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-warning border-bottom pb-2"><i class="fas fa-money-bill-wave me-2"></i>Refund Information</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-warning">
                                    <tr>
                                        <th>Return Reason</th>
                                        <th>Refund Amount</th>
                                        <th>Processing Time</th>
                                        <th>Shipping Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Defective/Damaged</td>
                                        <td class="text-success">100% + Shipping</td>
                                        <td>1-3 Business Days</td>
                                        <td class="text-success">Free Return</td>
                                    </tr>
                                    <tr>
                                        <td>Wrong Item Sent</td>
                                        <td class="text-success">100% + Shipping</td>
                                        <td>1-3 Business Days</td>
                                        <td class="text-success">Free Return</td>
                                    </tr>
                                    <tr>
                                        <td>Customer Changed Mind</td>
                                        <td class="text-warning">100% (minus shipping)</td>
                                        <td>3-5 Business Days</td>
                                        <td class="text-danger">Customer Pays</td>
                                    </tr>
                                    <tr>
                                        <td>Size/Color Issue</td>
                                        <td class="text-info">100% (free exchange)</td>
                                        <td>2-4 Business Days</td>
                                        <td class="text-success">Free Exchange</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-warning border-bottom pb-2"><i class="fas fa-exchange-alt me-2"></i>Exchange Policy</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-sync me-2"></i>Size/Color Exchange</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="mb-0">
                                            <li>Free size/color exchanges</li>
                                            <li>Same product, different variant</li>
                                            <li>Subject to availability</li>
                                            <li>One-time exchange per order</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Product Exchange</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="mb-0">
                                            <li>Exchange for different product</li>
                                            <li>Price difference may apply</li>
                                            <li>Both products must be eligible</li>
                                            <li>Additional shipping charges</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-warning border-bottom pb-2"><i class="fas fa-shipping-fast me-2"></i>Return Shipping</h3>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Return Shipping Options:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Free Return Shipping:</h6>
                                    <ul>
                                        <li>Defective or damaged items</li>
                                        <li>Wrong item shipped</li>
                                        <li>Quality issues</li>
                                        <li>Orders over ৳2000</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Customer Paid Returns:</h6>
                                    <ul>
                                        <li>Change of mind</li>
                                        <li>Size/fit issues (non-defective)</li>
                                        <li>Orders under ৳2000</li>
                                        <li>Return shipping: ৳120</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-warning border-bottom pb-2"><i class="fas fa-ban me-2"></i>Non-Returnable Items</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-times-circle me-2"></i>Cannot Be Returned:</h6>
                                    <ul class="mb-0">
                                        <li>Undergarments and intimate apparel</li>
                                        <li>Perishable food items</li>
                                        <li>Software and digital downloads</li>
                                        <li>Gift cards and vouchers</li>
                                        <li>Customized/personalized items</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Special Conditions:</h6>
                                    <ul class="mb-0">
                                        <li>Makeup and cosmetics (sealed only)</li>
                                        <li>Electronics (with original warranty)</li>
                                        <li>Large appliances (inspection required)</li>
                                        <li>Plants and live goods (special terms)</li>
                                        <li>Fragile items (packaging dependent)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-warning border-bottom pb-2"><i class="fas fa-headset me-2"></i>Customer Support</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card border-primary h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                                        <h5>Phone Support</h5>
                                        <p><strong>+880 1700-000000</strong></p>
                                        <p class="mb-0">9 AM - 10 PM Daily</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-envelope fa-2x text-success mb-3"></i>
                                        <h5>Email Support</h5>
                                        <p><strong>returns@digiecho.com</strong></p>
                                        <p class="mb-0">24-48 hour response</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-info h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-comments fa-2x text-info mb-3"></i>
                                        <h5>Live Chat</h5>
                                        <p><strong>Website Chat</strong></p>
                                        <p class="mb-0">Instant assistance</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="text-warning border-bottom pb-2"><i class="fas fa-question-circle me-2"></i>Frequently Asked Questions</h3>
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq1">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                        How long does the return process take?
                                    </button>
                                </h2>
                                <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Once we receive your returned item, we inspect it within 1-2 business days. Refunds are processed within 3-5 business days after approval.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                        Can I return items purchased with a discount?
                                    </button>
                                </h2>
                                <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Yes, items purchased with discounts can be returned. The refund will be for the amount actually paid after the discount.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                        What if my item was damaged during shipping?
                                    </button>
                                </h2>
                                <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Items damaged during shipping are eligible for free return and full refund including shipping costs. Please contact us within 24 hours of delivery.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="text-center">
                        <a href="index.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-home me-2"></i>Return to Homepage
                        </a>
                        <a href="contact.php" class="btn btn-outline-warning btn-lg ms-2">
                            <i class="fas fa-headset me-2"></i>Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.timeline {
    margin: 2rem 0;
}
</style>

<?php require __DIR__ . '/components/footer.php'; ?>