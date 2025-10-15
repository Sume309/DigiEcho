<?php
// Professional Payment Methods Component for Bangladesh E-commerce
?>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Methods</h3>
    </div>
    <div class="card-body">
        
        <!-- Mobile Banking (Most Popular) -->
        <div class="mb-4">
            <h5 class="text-success mb-3"><i class="fas fa-mobile-alt me-2"></i>Mobile Banking (Most Popular)</h5>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="bkash">
                        <div class="payment-card active-payment">
                            <img src="assets/images/payment-logos/bkash.svg" alt="bKash" class="payment-logo">
                            <div class="payment-name">bKash</div>
                            <div class="payment-desc">QR + USSD</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="nagad">
                        <div class="payment-card">
                            <img src="assets/images/payment-logos/nagad.svg" alt="Nagad" class="payment-logo">
                            <div class="payment-name">Nagad</div>
                            <div class="payment-desc">Digital Payment</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="rocket">
                        <div class="payment-card">
                            <img src="assets/images/payment-logos/rocket.svg" alt="Rocket" class="payment-logo">
                            <div class="payment-name">Rocket</div>
                            <div class="payment-desc">DBBL Mobile</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="upay">
                        <div class="payment-card">
                            <img src="assets/images/payment-logos/upay.svg" alt="Upay" class="payment-logo">
                            <div class="payment-name">Upay</div>
                            <div class="payment-desc">Mobile Banking</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards -->
        <div class="mb-4">
            <h5 class="text-primary mb-3"><i class="fas fa-credit-card me-2"></i>Cards</h5>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="visa">
                        <div class="payment-card">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" class="payment-logo">
                            <div class="payment-name">Visa</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="mastercard">
                        <div class="payment-card">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="MasterCard" class="payment-logo">
                            <div class="payment-name">MasterCard</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="amex">
                        <div class="payment-card">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/American_Express_logo_%282018%29.svg" alt="American Express" class="payment-logo">
                            <div class="payment-name">Amex</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="local_debit">
                        <div class="payment-card">
                            <div class="payment-icon bg-info"><i class="fas fa-university"></i></div>
                            <div class="payment-name">Local Debit</div>
                            <div class="payment-desc">DBBL Nexus, Q-Cash</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Internet Banking -->
        <div class="mb-4">
            <h5 class="text-info mb-3"><i class="fas fa-university me-2"></i>Internet Banking</h5>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="brac_bank">
                        <div class="payment-card">
                            <div class="payment-icon bg-success"><i class="fas fa-building"></i></div>
                            <div class="payment-name">BRAC Bank</div>
                            <div class="payment-desc">iBanking</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="dbbl_banking">
                        <div class="payment-card">
                            <div class="payment-icon bg-primary"><i class="fas fa-building"></i></div>
                            <div class="payment-name">DBBL</div>
                            <div class="payment-desc">Internet Banking</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="city_bank">
                        <div class="payment-card">
                            <div class="payment-icon bg-warning"><i class="fas fa-building"></i></div>
                            <div class="payment-name">City Bank</div>
                            <div class="payment-desc">Citytouch</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="payment-option" data-method="islami_bank">
                        <div class="payment-card">
                            <div class="payment-icon bg-success"><i class="fas fa-building"></i></div>
                            <div class="payment-name">Islami Bank</div>
                            <div class="payment-desc">iBanking</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Digital Wallets & Global Options -->
        <div class="mb-4">
            <h5 class="text-secondary mb-3"><i class="fas fa-globe me-2"></i>Digital Wallets & Global</h5>
            <div class="row g-3">
                <div class="col-6 col-md-4">
                    <div class="payment-option" data-method="paypal">
                        <div class="payment-card">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal" class="payment-logo">
                            <div class="payment-name">PayPal</div>
                            <div class="payment-desc">International</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="payment-option" data-method="apple_pay">
                        <div class="payment-card">
                            <div class="payment-icon bg-dark"><i class="fab fa-apple"></i></div>
                            <div class="payment-name">Apple Pay</div>
                            <div class="payment-desc">Cross-border</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="payment-option" data-method="google_pay">
                        <div class="payment-card">
                            <div class="payment-icon bg-danger"><i class="fab fa-google"></i></div>
                            <div class="payment-name">Google Pay</div>
                            <div class="payment-desc">Cross-border</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash-Based Options -->
        <div class="mb-4">
            <h5 class="text-warning mb-3"><i class="fas fa-money-bill-wave me-2"></i>Cash-Based Options</h5>
            <div class="row g-3">
                <div class="col-6 col-md-6">
                    <div class="payment-option" data-method="cod">
                        <div class="payment-card">
                            <div class="payment-icon bg-warning"><i class="fas fa-truck"></i></div>
                            <div class="payment-name">Cash on Delivery</div>
                            <div class="payment-desc">Pay when received</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-6">
                    <div class="payment-option" data-method="pickup_point">
                        <div class="payment-card">
                            <div class="payment-icon bg-info"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="payment-name">Pay at Pickup</div>
                            <div class="payment-desc">Offline hub payment</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Forms -->
        <div id="payment-forms" class="mt-4">
            <!-- Mobile Banking Form -->
            <div id="mobile-banking-form" class="payment-form d-none">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Instructions:</strong> Complete the payment using your mobile banking app, then enter the transaction ID below.
                </div>
                <div class="mb-3">
                    <label for="mobile_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="tel" id="mobile_number" class="form-control" placeholder="01XXXXXXXXX" required>
                </div>
                <div class="mb-3">
                    <label for="transaction_id" class="form-label">Transaction ID <span class="text-danger">*</span></label>
                    <input type="text" id="transaction_id" class="form-control" placeholder="Enter transaction ID" required>
                </div>
                <div class="mb-3">
                    <label for="payment_amount" class="form-label">Amount Paid</label>
                    <input type="text" id="payment_amount" class="form-control" readonly>
                </div>
            </div>

            <!-- Card Form -->
            <div id="card-form" class="payment-form d-none">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="card_holder" class="form-label">Cardholder Name <span class="text-danger">*</span></label>
                        <input type="text" id="card_holder" class="form-control" placeholder="John Doe" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="card_number" class="form-label">Card Number <span class="text-danger">*</span></label>
                        <input type="text" id="card_number" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="card_expiry" class="form-label">Expiry Date <span class="text-danger">*</span></label>
                        <input type="text" id="card_expiry" class="form-control" placeholder="MM/YY" maxlength="5" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="card_cvv" class="form-label">CVV <span class="text-danger">*</span></label>
                        <input type="text" id="card_cvv" class="form-control" placeholder="123" maxlength="4" required>
                    </div>
                </div>
            </div>

            <!-- Internet Banking Form -->
            <div id="banking-form" class="payment-form d-none">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> You will be redirected to your bank's secure login page to complete the payment.
                </div>
                <div class="mb-3">
                    <label for="bank_account" class="form-label">Account Number (Optional)</label>
                    <input type="text" id="bank_account" class="form-control" placeholder="Your account number">
                </div>
            </div>

            <!-- Cash-based Form -->
            <div id="cash-form" class="payment-form d-none">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Selected:</strong> <span id="cash-method-name">Cash on Delivery</span>
                </div>
                <div class="mb-3">
                    <label for="delivery_instructions" class="form-label">Special Delivery Instructions (Optional)</label>
                    <textarea id="delivery_instructions" class="form-control" rows="3" placeholder="Any special instructions for delivery..."></textarea>
                </div>
            </div>
        </div>

        <!-- Hidden input for selected payment method -->
        <input type="hidden" id="payment_method" name="payment_method" value="bkash">
    </div>
</div>

<style>
.payment-option {
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    transition: all 0.3s ease;
    background: #fff;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.payment-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 8px rgba(0,123,255,0.2);
    transform: translateY(-2px);
}

.payment-card.active-payment {
    border-color: #28a745;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    box-shadow: 0 4px 12px rgba(40,167,69,0.3);
}

.payment-logo {
    height: 40px;
    width: auto;
    max-width: 80px;
    margin-bottom: 8px;
}

.payment-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    color: white;
    font-size: 18px;
}

.payment-name {
    font-weight: 600;
    font-size: 14px;
    color: #333;
    margin-bottom: 2px;
}

.payment-desc {
    font-size: 11px;
    color: #666;
    margin: 0;
}

.payment-form {
    border: 1px solid #dee2e6;
    border-radius: 10px;
    padding: 20px;
    background: #f8f9fa;
}

.payment-form .alert {
    margin-bottom: 20px;
    border-radius: 8px;
}

@media (max-width: 576px) {
    .payment-card {
        padding: 10px;
    }
    
    .payment-logo {
        height: 30px;
    }
    
    .payment-icon {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
    
    .payment-name {
        font-size: 12px;
    }
    
    .payment-desc {
        font-size: 10px;
    }
}
</style>

<script>
$(document).ready(function() {
    let selectedPaymentMethod = 'bkash'; // Default to bKash
    
    // Payment method categories
    const mobileBanking = ['bkash', 'nagad', 'rocket', 'upay'];
    const cards = ['visa', 'mastercard', 'amex', 'local_debit'];
    const internetBanking = ['brac_bank', 'dbbl_banking', 'city_bank', 'islami_bank'];
    const digitalWallets = ['paypal', 'apple_pay', 'google_pay'];
    const cashBased = ['cod', 'pickup_point'];
    
    // Payment option click handler
    $('.payment-option').on('click', function() {
        $('.payment-card').removeClass('active-payment');
        $(this).find('.payment-card').addClass('active-payment');
        
        selectedPaymentMethod = $(this).data('method');
        $('#payment_method').val(selectedPaymentMethod);
        
        updatePaymentForm();
    });
    
    function updatePaymentForm() {
        // Hide all forms
        $('.payment-form').addClass('d-none');
        
        // Show appropriate form based on payment method
        if (mobileBanking.includes(selectedPaymentMethod)) {
            $('#mobile-banking-form').removeClass('d-none');
            updatePaymentAmount();
        } else if (cards.includes(selectedPaymentMethod)) {
            $('#card-form').removeClass('d-none');
        } else if (internetBanking.includes(selectedPaymentMethod)) {
            $('#banking-form').removeClass('d-none');
        } else if (digitalWallets.includes(selectedPaymentMethod)) {
            $('#card-form').removeClass('d-none'); // Use card form for wallets
        } else if (cashBased.includes(selectedPaymentMethod)) {
            $('#cash-form').removeClass('d-none');
            const methodName = selectedPaymentMethod === 'cod' ? 'Cash on Delivery' : 'Pay at Pickup Point';
            $('#cash-method-name').text(methodName);
        }
    }
    
    function updatePaymentAmount() {
        const grandTotal = $('#grandTotal').text();
        $('#payment_amount').val(grandTotal);
    }
    
    // Card number formatting
    $('#card_number').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        $(this).val(value);
    });
    
    // Expiry date formatting
    $('#card_expiry').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        $(this).val(value);
    });
    
    // CVV validation
    $('#card_cvv').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        $(this).val(value);
    });
    
    // Mobile number formatting
    $('#mobile_number').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 11) {
            value = value.substring(0, 11);
        }
        $(this).val(value);
    });
    
    // Initialize default form
    updatePaymentForm();
    
    // Update payment amount when grand total changes
    $(document).on('DOMSubtreeModified', '#grandTotal', function() {
        updatePaymentAmount();
    });
});
</script>