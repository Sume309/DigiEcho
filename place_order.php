<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['message'] = "You must log in to place an order.";
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/src/settings.php';
require_once __DIR__ . '/src/db/MysqliDb.php';

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
$page = "Checkout";

// Debug: Test config function
$vatDefault = config('vat.default');
$vatRates = config('vat.rates');
if (!$vatDefault || !$vatRates) {
    error_log("Config error in place_order.php - VAT Default: " . ($vatDefault ?: 'NULL') . ", VAT Rates: " . (is_array($vatRates) ? 'OK' : 'NULL'));
}

// Get user profile for pre-filling forms
$userId = $_SESSION['userid'];
$userProfile = $db->where('user_id', $userId)->getOne('user_profiles');

// If no profile exists, create a default one
if (!$userProfile) {
    $defaultProfile = [
        'user_id' => $userId,
        'first_name' => '',
        'last_name' => '',
        'phone' => '',
        'billing_country' => 'Bangladesh',
        'shipping_country' => 'Bangladesh',
        'same_as_billing' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    $db->insert('user_profiles', $defaultProfile);
    $userProfile = $db->where('user_id', $userId)->getOne('user_profiles');
}
?>
<?php require __DIR__ . '/components/header.php';?>
<!-- content start -->
<div class="container my-5">
    <h2 class="text-center mb-4 fw-bold"><i class="fas fa-shopping-cart me-2"></i>Checkout</h2>
    
    <div id="cartEmptyMessage" class="alert alert-info text-center d-none">
        <i class="fas fa-info-circle me-2"></i>Your cart is empty. Start shopping now!
    </div>

    <div class="row">
        <!-- Cart and Address Section -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="mb-0"><i class="fas fa-cart-arrow-down me-2"></i>Cart Items</h3>
                </div>
                <div class="card-body">
                    <table class="table table-hover table-bordered" id="cartTableMain">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="cartTable"></tbody>
                    </table>
                </div>
            </div>

            <!-- Billing Address -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h3 class="mb-0"><i class="fas fa-address-card me-2"></i>Billing Address</h3>
                </div>
                <div class="card-body">
                    <form id="billingForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="billing_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" id="billing_first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="billing_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" id="billing_last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="billing_company" class="form-label">Company (Optional)</label>
                            <input type="text" id="billing_company" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="billing_address_line_1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" id="billing_address_line_1" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="billing_address_line_2" class="form-label">Address Line 2 (Optional)</label>
                            <input type="text" id="billing_address_line_2" class="form-control">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="billing_city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" id="billing_city" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="billing_state" class="form-label">State <span class="text-danger">*</span></label>
                                <input type="text" id="billing_state" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="billing_postal_code" class="form-label">Postal Code <span class="text-danger">*</span></label>
                                <input type="text" id="billing_postal_code" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="billing_country" class="form-label">Country <span class="text-danger">*</span></label>
                                <input type="text" id="billing_country" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="billing_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="tel" id="billing_phone" class="form-control" required>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h3 class="mb-0"><i class="fas fa-truck me-2"></i>Shipping Address</h3>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="same_as_shipping">
                        <label class="form-check-label" for="same_as_shipping">Same as Billing Address</label>
                    </div>
                    <form id="shippingForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="shipping_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" id="shipping_first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="shipping_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" id="shipping_last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="shipping_company" class="form-label">Company (Optional)</label>
                            <input type="text" id="shipping_company" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="shipping_address_line_1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" id="shipping_address_line_1" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="shipping_address_line_2" class="form-label">Address Line 2 (Optional)</label>
                            <input type="text" id="shipping_address_line_2" class="form-control">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="shipping_city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" id="shipping_city" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="shipping_state" class="form-label">State <span class="text-danger">*</span></label>
                                <input type="text" id="shipping_state" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="shipping_postal_code" class="form-label">Postal Code <span class="text-danger">*</span></label>
                                <input type="text" id="shipping_postal_code" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="shipping_country" class="form-label">Country <span class="text-danger">*</span></label>
                                <input type="text" id="shipping_country" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="shipping_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="tel" id="shipping_phone" class="form-control" required>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order Summary and Payment -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <td>Net Total</td>
                            <td id="netTotal">৳0.00</td>
                        </tr>
                        <tr>
                            <td>VAT (<?= config('vat.default') ?>)</td>
                            <td id="vatAmount">৳0.00</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Grand Total</td>
                        
                            <td id="grandTotal" class="fw-bold">৳0.00</td>
                        </tr>
                    </table>

                    <?php include __DIR__ . '/components/payment-methods.php'; ?>


                    <!-- Notes Section -->
                    

                    <div class="text-center mt-4">
                        <button id="placeOrder" class="btn btn-success btn-lg"><i class="fas fa-check-circle me-2"></i>Place Order</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- content end -->
<?php require __DIR__ . '/components/footer.php'; 
$db->disconnect();
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<!-- cart.js already loaded in header.php -->
<script>
$(document).ready(function() {
    function populateItems(items, tableId) {
        $(tableId).html("");
        if (items.length === 0) {
            $("#cartEmptyMessage").removeClass("d-none");
            $("#cartTableMain").addClass("d-none");
            return;
        } else {
            $("#cartEmptyMessage").addClass("d-none");
            $("#cartTableMain").removeClass("d-none");
        }
        items.forEach(item => {
            $(tableId).append(`
                <tr>
                    <td class="align-middle">${item.name}</td>
                    <td class="align-middle">
                        <input type="number" class="form-control form-control-sm qty-input" data-id="${item.id}" value="${item.quantity}" min="1" style="width: 80px;">
                    </td>
                    <td class="align-middle">৳${parseFloat(item.price).toFixed(2)}</td>
                    <td class="align-middle">৳${(item.quantity * item.price).toFixed(2)}</td>
                    <td class="align-middle">
                        <button class="btn btn-danger btn-sm remove-item" data-id="${item.id}">
                            <i class="fas fa-trash-alt"></i> Remove
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    let cart = new Cart();
    window.updateCartDisplay = function() {
        let allitems = cart.getSummary();
        $("#cartCountButton").text(cart.getTotalItems());
        populateItems(allitems.items, "#cartContent table tbody");
        populateItems(allitems.items, "#cartTable");
        setTotal(); // Update totals when cart changes
    }

    function setTotal() {
        let netTotal = parseFloat(cart.getTotalPrice());
        let vatRate = <?php 
            $vatConfig = config('vat.rates');
            $defaultVat = config('vat.default');
            foreach ($vatConfig as $vat) {
                if ($vat['name'] === $defaultVat) {
                    echo $vat['value'];
                    break;
                }
            }
        ?>;
        let vatAmount = netTotal * vatRate;
        let grandTotal = netTotal + vatAmount;
        
        $("#netTotal").text(`৳${netTotal.toFixed(2)}`);
        $("#vatAmount").text(`৳${vatAmount.toFixed(2)}`);
        $("#grandTotal").text(`৳${grandTotal.toFixed(2)}`);
    }

    // Initial cart load
    updateCartDisplay();
    setTotal();

    // Pre-fill forms with user profile data
    function populateUserProfile() {
        <?php if ($userProfile): ?>
            // Populate billing form
            $("#billing_first_name").val("<?= htmlspecialchars($userProfile['first_name'] ?? '') ?>");
            $("#billing_last_name").val("<?= htmlspecialchars($userProfile['last_name'] ?? '') ?>");
            $("#billing_company").val("<?= htmlspecialchars($userProfile['billing_company'] ?? '') ?>");
            $("#billing_address_line_1").val("<?= htmlspecialchars($userProfile['billing_address_line_1'] ?? '') ?>");
            $("#billing_address_line_2").val("<?= htmlspecialchars($userProfile['billing_address_line_2'] ?? '') ?>");
            $("#billing_city").val("<?= htmlspecialchars($userProfile['billing_city'] ?? '') ?>");
            $("#billing_state").val("<?= htmlspecialchars($userProfile['billing_state'] ?? '') ?>");
            $("#billing_postal_code").val("<?= htmlspecialchars($userProfile['billing_postal_code'] ?? '') ?>");
            $("#billing_country").val("<?= htmlspecialchars($userProfile['billing_country'] ?? 'Bangladesh') ?>");
            $("#billing_phone").val("<?= htmlspecialchars($userProfile['billing_phone'] ?? $userProfile['phone'] ?? '') ?>");

            // Handle same as billing checkbox
            <?php if ($userProfile['same_as_billing'] ?? 1): ?>
                $("#same_as_shipping").prop("checked", true);
                // Copy billing to shipping
                $("#shipping_first_name").val("<?= htmlspecialchars($userProfile['first_name'] ?? '') ?>");
                $("#shipping_last_name").val("<?= htmlspecialchars($userProfile['last_name'] ?? '') ?>");
                $("#shipping_company").val("<?= htmlspecialchars($userProfile['billing_company'] ?? '') ?>");
                $("#shipping_address_line_1").val("<?= htmlspecialchars($userProfile['billing_address_line_1'] ?? '') ?>");
                $("#shipping_address_line_2").val("<?= htmlspecialchars($userProfile['billing_address_line_2'] ?? '') ?>");
                $("#shipping_city").val("<?= htmlspecialchars($userProfile['billing_city'] ?? '') ?>");
                $("#shipping_state").val("<?= htmlspecialchars($userProfile['billing_state'] ?? '') ?>");
                $("#shipping_postal_code").val("<?= htmlspecialchars($userProfile['billing_postal_code'] ?? '') ?>");
                $("#shipping_country").val("<?= htmlspecialchars($userProfile['billing_country'] ?? 'Bangladesh') ?>");
                $("#shipping_phone").val("<?= htmlspecialchars($userProfile['billing_phone'] ?? $userProfile['phone'] ?? '') ?>");
            <?php else: ?>
                $("#same_as_shipping").prop("checked", false);
                // Populate shipping form with separate data
                $("#shipping_first_name").val("<?= htmlspecialchars($userProfile['first_name'] ?? '') ?>");
                $("#shipping_last_name").val("<?= htmlspecialchars($userProfile['last_name'] ?? '') ?>");
                $("#shipping_company").val("<?= htmlspecialchars($userProfile['shipping_company'] ?? '') ?>");
                $("#shipping_address_line_1").val("<?= htmlspecialchars($userProfile['shipping_address_line_1'] ?? '') ?>");
                $("#shipping_address_line_2").val("<?= htmlspecialchars($userProfile['shipping_address_line_2'] ?? '') ?>");
                $("#shipping_city").val("<?= htmlspecialchars($userProfile['shipping_city'] ?? '') ?>");
                $("#shipping_state").val("<?= htmlspecialchars($userProfile['shipping_state'] ?? '') ?>");
                $("#shipping_postal_code").val("<?= htmlspecialchars($userProfile['shipping_postal_code'] ?? '') ?>");
                $("#shipping_country").val("<?= htmlspecialchars($userProfile['shipping_country'] ?? 'Bangladesh') ?>");
                $("#shipping_phone").val("<?= htmlspecialchars($userProfile['shipping_phone'] ?? $userProfile['phone'] ?? '') ?>");
            <?php endif; ?>
        <?php endif; ?>
    }

    // Call the function to populate forms
    populateUserProfile();

    // Same as shipping checkbox
    $("#same_as_shipping").on("change", function() {
        if ($(this).is(":checked")) {
            $("#shippingForm input").each(function() {
                let field = $(this).attr("id").replace("shipping_", "billing_");
                $(this).val($(`#${field}`).val());
            });
        }
    });

    // Simple helpers for cards
    function detectCardBrand(number) {
        const n = (number || '').replace(/\D/g, '');
        if (/^4[0-9]{6,}$/.test(n)) return 'visa';
        if (/^5[1-5][0-9]{5,}$/.test(n)) return 'mastercard';
        if (/^3[47][0-9]{5,}$/.test(n)) return 'amex';
        if (/^6(?:011|5[0-9]{2})[0-9]{3,}$/.test(n)) return 'discover';
        return 'card';
    }

    function luhnCheck(val) {
        let sum = 0; let shouldDouble = false;
        const digits = val.replace(/\D/g, '').split('').reverse();
        for (let d of digits) {
            let digit = parseInt(d, 10);
            if (shouldDouble) {
                digit *= 2; if (digit > 9) digit -= 9;
            }
            sum += digit; shouldDouble = !shouldDouble;
        }
        return sum % 10 === 0;
    }

    function isValidExpiry(mmYY) {
        const m = mmYY.match(/^(0[1-9]|1[0-2])\/(\d{2})$/);
        if (!m) return false;
        const month = parseInt(m[1], 10);
        const year = 2000 + parseInt(m[2], 10);
        const now = new Date();
        const exp = new Date(year, month); // first day of next month
        return exp > now;
    }

    // Payment validation helper functions
    function validateMobileBanking() {
        const mobileNumber = $('#mobile_number').val();
        const transactionId = $('#transaction_id').val();
        
        if (!mobileNumber || mobileNumber.length !== 11) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Mobile Number',
                text: 'Please enter a valid 11-digit mobile number.'
            });
            return false;
        }
        
        if (!transactionId || transactionId.length < 5) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Transaction ID',
                text: 'Please enter a valid transaction ID.'
            });
            return false;
        }
        
        return true;
    }
    
    function validateCardPayment() {
        const holder = $('#card_holder').val().trim();
        const numberRaw = $('#card_number').val();
        const number = (numberRaw || '').replace(/\D/g, '');
        const expiry = $('#card_expiry').val().trim();
        const cvv = $('#card_cvv').val().trim();

        if (!holder) {
            Swal.fire({
                icon: 'error',
                title: 'Card Holder Required',
                text: 'Please enter the cardholder name.'
            });
            return false;
        }
        
        if (number.length < 13 || !luhnCheck(number)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Card Number',
                text: 'Please enter a valid card number.'
            });
            return false;
        }
        
        if (!isValidExpiry(expiry)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Expiry Date',
                text: 'Please enter a valid expiry date (MM/YY).'
            });
            return false;
        }
        
        if (!(cvv.length === 3 || cvv.length === 4)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid CVV',
                text: 'Please enter a valid 3 or 4 digit CVV.'
            });
            return false;
        }
        
        return true;
    }

    // Remove item
    $(document).on("click", ".remove-item", function() {
        let id = $(this).data('id');
        cart.removeItem(id);
        updateCartDisplay();
        Swal.fire({
            icon: 'success',
            title: 'Item Removed',
            text: 'The item has been removed from your cart.',
            timer: 1500,
            showConfirmButton: false
        });
    });

    // Quantity change
    $(document).on("change", ".qty-input", function() {
        let id = $(this).data('id');
        let quantity = parseInt($(this).val());
        if (quantity < 1) {
            $(this).val(1);
            quantity = 1;
        }
        cart.editItem(id, quantity);
        updateCartDisplay();
    });

    // Update offcanvas cart when shown
    $('#offcanvasCart').on('show.bs.offcanvas', updateCartDisplay);

    // Form validation and place order
    $("#placeOrder").on("click", function() {
        // Guard: prevent placing order with empty cart
        const summaryBeforeSubmit = cart.getSummary();
        if (!summaryBeforeSubmit.items || summaryBeforeSubmit.items.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Cart is empty',
                text: 'Please add items to your cart before placing the order.'
            });
            return;
        }
        let billingValid = $("#billingForm")[0].checkValidity();
        let shippingValid = $("#shippingForm")[0].checkValidity();
        if (!billingValid || !shippingValid) {
            $("#billingForm, #shippingForm").addClass("was-validated");
            Swal.fire({
                icon: 'error',
                title: 'Incomplete Form',
                text: 'Please fill out all required fields.',
            });
            return;
        }

        // Get selected payment method and validate
        const paymentMethod = $("#payment_method").val();
        const mobileBanking = ['bkash', 'nagad', 'rocket', 'upay'];
        const cards = ['visa', 'mastercard', 'amex', 'local_debit'];
        const internetBanking = ['brac_bank', 'dbbl_banking', 'city_bank', 'islami_bank'];
        const digitalWallets = ['paypal', 'apple_pay', 'google_pay'];
        const cashBased = ['cod', 'pickup_point'];
        
        // Validate payment method specific fields
        if (mobileBanking.includes(paymentMethod)) {
            if (!validateMobileBanking()) {
                return;
            }
        } else if (cards.includes(paymentMethod) || digitalWallets.includes(paymentMethod)) {
            if (!validateCardPayment()) {
                return;
            }
        } else if (internetBanking.includes(paymentMethod)) {
            // Internet banking validation if needed
            // Currently no specific validation required
        } else if (cashBased.includes(paymentMethod)) {
            // Cash-based payments don't need validation
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Payment Method',
                text: 'Please select a valid payment method.'
            });
            return;
        }

        let data = {
            billing_first_name: $("#billing_first_name").val(),
            billing_last_name: $("#billing_last_name").val(),
            billing_company: $("#billing_company").val(),
            billing_address_line_1: $("#billing_address_line_1").val(),
            billing_address_line_2: $("#billing_address_line_2").val(),
            billing_city: $("#billing_city").val(),
            billing_state: $("#billing_state").val(),
            billing_postal_code: $("#billing_postal_code").val(),
            billing_country: $("#billing_country").val(),
            billing_phone: $("#billing_phone").val(),
            shipping_first_name: $("#shipping_first_name").val(),
            shipping_last_name: $("#shipping_last_name").val(),
            shipping_company: $("#shipping_company").val(),
            shipping_address_line_1: $("#shipping_address_line_1").val(),
            shipping_address_line_2: $("#shipping_address_line_2").val(),
            shipping_city: $("#shipping_city").val(),
            shipping_state: $("#shipping_state").val(),
            shipping_postal_code: $("#shipping_postal_code").val(),
            shipping_country: $("#shipping_country").val(),
            shipping_phone: $("#shipping_phone").val(),
            payment_method: paymentMethod,
            notes: $("#notes").val(),
            items: cart.getSummary().items,
            totalItems: cart.getTotalItems(),
            totalPrice: cart.getTotalPrice(),
            discount_amount: 0,
            tax_amount: $("#vatAmount").text().replace("৳", ""),
            grandTotal: $("#grandTotal").text().replace("৳", ""),
        };

        // Add payment-specific data based on method
        if (mobileBanking.includes(paymentMethod)) {
            data.mobile_number = $('#mobile_number').val();
            data.transaction_id = $('#transaction_id').val();
        } else if (cards.includes(paymentMethod) || digitalWallets.includes(paymentMethod)) {
            const holder = $('#card_holder').val().trim();
            const numberRaw = $('#card_number').val();
            const number = (numberRaw || '').replace(/\D/g, '');
            const expiry = $('#card_expiry').val().trim();
            
            const brand = detectCardBrand(number);
            const last4 = number.slice(-4);
            const expParts = expiry.split('/');
            const exp_month = expParts[0];
            const exp_year = '20' + expParts[1];

            data.card_meta = {
                brand,
                last4,
                exp_month,
                exp_year,
                type: paymentMethod,
                holder_name: holder
            };
        } else if (internetBanking.includes(paymentMethod)) {
            data.bank_account = $('#bank_account').val();
        } else if (cashBased.includes(paymentMethod)) {
            data.delivery_instructions = $('#delivery_instructions').val();
        }

        $.ajax({
            url: "<?= settings()['root'] ?>apis/processOrder.php",
            method: "POST",
            dataType: "json",
            data: data,
            success: function(response) {
                console.log(response);

                if (response.success) {
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Order Placed Successfully",
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Clear cart and redirect to order confirmation page with order number
                        cart.clearCart();
                        const orderNo = response.order_number || '';
                        if (orderNo) {
                            window.location.href = `order_confirmation.php?order=${encodeURIComponent(orderNo)}`;
                        } else {
                            window.location.href = "index.php";
                        }
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Order Failed",
                        text: response.message || "An error occurred while placing your order."
                    });
                }
            },
            error: function(jqXHR) {
                let msg = "An error occurred while processing your request.";
                if (jqXHR && jqXHR.responseText) {
                    msg += "\n\n" + jqXHR.responseText;
                }
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: msg
                });
            }
        });
    });
});
</script>
<style>
.card {
    border-radius: 10px;
}
.table th, .table td {
    vertical-align: middle;
}
.btn-success {
    padding: 10px 30px;
    font-size: 1.1rem;
}
.alert-info {
    border-radius: 8px;
    padding: 20px;
}
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}
.form-control, .form-select {
    border-radius: 6px;
}
.was-validated .form-control:invalid {
    border-color: #dc3545;
}
</style>
</body>
</html>