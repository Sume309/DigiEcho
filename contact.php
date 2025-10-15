<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/vendor/autoload.php';
$page = "Contact Us";

// Open Graph data for contact page
$og_title = "Contact Us - " . settings()['companyname'];
$og_description = "Get in touch with " . settings()['companyname'] . ". We're here to help with any questions, concerns, or feedback you may have.";
$og_image = settings()['homepage'] . ltrim(settings()['logo'], '/');
$og_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$og_type = "website";

// Handle form submission
$message_sent = false;
$error_message = '';

if ($_POST && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Database connection
            $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get client information
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Determine priority based on subject
            $priority = 'normal';
            if (in_array($subject, ['Technical Issue', 'Order Support'])) {
                $priority = 'high';
            } elseif ($subject === 'Partnership') {
                $priority = 'low';
            }
            
            // Insert message into database
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, phone, ip_address, user_agent, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message, $phone, $ip_address, $user_agent, $priority]);
            
            $message_id = $pdo->lastInsertId();
            
            // Create notification for admin using MysqliDb (consistent with other parts of the app)
            try {
                // Use MysqliDb instead of PDO for consistency
                require_once __DIR__ . '/src/db/MysqliDb.php';
                $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
                
                $notification_title = "New Contact Message: " . $subject;
                $notification_message = "From: " . $name . " (" . $email . ")\nSubject: " . $subject . "\nMessage: " . substr($message, 0, 100) . "...";
                $notification_metadata = json_encode([
                    'message_id' => $message_id,
                    'sender_name' => $name,
                    'sender_email' => $email,
                    'phone' => $phone,
                    'priority' => $priority,
                    'subject' => $subject,
                    'type' => 'contact_message'
                ]);
                
                // Insert notification using MysqliDb
                $notificationData = [
                    'title' => $notification_title,
                    'message' => $notification_message,
                    'type' => 'contact_message',
                    'metadata' => $notification_metadata,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $db->insert('notifications', $notificationData);
                
            } catch(Exception $e) {
                // Log error but don't break contact form submission
                error_log("Contact form notification creation failed: " . $e->getMessage());
                // Continue with form processing - notification is optional
            }
            
            $message_sent = true;
            
            // Clear form data on success
            $_POST = [];
            $name = $email = $subject = $message = $phone = '';
            
        } catch(PDOException $e) {
            $error_message = 'Sorry, there was an error sending your message. Please try again later.';
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}

// Clear variables after successful submission to prevent display
if ($message_sent) {
    $name = $email = $subject = $message = $phone = '';
}
?>

<?php require __DIR__ . '/components/header.php'; ?>
<style>
.contact-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
}

.contact-section {
    padding: 2rem 0;
}

.contact-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    height: 100%;
    transition: transform 0.3s ease;
}

.contact-card:hover {
    transform: translateY(-5px);
}

.contact-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
}

.form-control:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.map-container {
    height: 400px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.alert-custom {
    border-radius: 10px;
    border: none;
    padding: 1rem 1.5rem;
}
</style>

<!-- Hero Section -->
<div class="contact-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">Contact Us</h1>
                <p class="lead mb-4">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
            </div>
            <div class="col-lg-4 text-center">
                <i class="fas fa-comments fa-5x opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<!-- Contact Info Cards -->
<div class="contact-section">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card contact-card">
                    <div class="card-body text-center p-4">
                        <div class="contact-icon bg-primary text-white">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5 class="card-title">Address</h5>
                        <p class="card-text text-muted">
                            123 Shopping Street<br>
                            Dhaka, Bangladesh<br>
                            Postal Code: 1000
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card contact-card">
                    <div class="card-body text-center p-4">
                        <div class="contact-icon bg-success text-white">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5 class="card-title">Phone</h5>
                        <p class="card-text text-muted">
                            <a href="tel:+8801234567890" class="text-decoration-none">+880 1234-567890</a><br>
                            <a href="tel:+8801987654321" class="text-decoration-none">+880 1987-654321</a><br>
                            <small>Mon - Fri: 9AM - 8PM</small>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card contact-card">
                    <div class="card-body text-center p-4">
                        <div class="contact-icon bg-info text-white">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5 class="card-title">Email</h5>
                        <p class="card-text text-muted">
                            <a href="mailto:info@digiecho.com" class="text-decoration-none">info@digiecho.com</a><br>
                            <a href="mailto:support@digiecho.com" class="text-decoration-none">support@digiecho.com</a><br>
                            <small>We reply within 24 hours</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Form and Map -->
<div class="contact-section bg-light">
    <div class="container">
        <div class="row g-5">
            <!-- Contact Form -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="mb-4">Send us a Message</h3>
                        
                        <?php if ($message_sent): ?>
                            <!-- Success message disabled as requested -->
                            <?php // <div class="alert alert-success alert-custom"> ?>
                                <?php // <i class="fas fa-check-circle me-2"></i> ?>
                                <?php // Thank you for your message! We'll get back to you soon. ?>
                            <?php // </div> ?>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-custom">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($error_message) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars(($message_sent ? '' : ($_POST['name'] ?? ''))) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars(($message_sent ? '' : ($_POST['email'] ?? ''))) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number (Optional)</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars(($message_sent ? '' : ($_POST['phone'] ?? ''))) ?>" 
                                           placeholder="+880 1234-567890">
                                </div>
                                <div class="col-12">
                                    <label for="subject" class="form-label">Subject *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">Choose a subject...</option>
                                        <option value="General Inquiry" <?= (($message_sent ? '' : ($_POST['subject'] ?? ''))) === 'General Inquiry' ? 'selected' : '' ?>>General Inquiry</option>
                                        <option value="Product Question" <?= (($message_sent ? '' : ($_POST['subject'] ?? ''))) === 'Product Question' ? 'selected' : '' ?>>Product Question</option>
                                        <option value="Order Support" <?= (($message_sent ? '' : ($_POST['subject'] ?? ''))) === 'Order Support' ? 'selected' : '' ?>>Order Support</option>
                                        <option value="Technical Issue" <?= (($message_sent ? '' : ($_POST['subject'] ?? ''))) === 'Technical Issue' ? 'selected' : '' ?>>Technical Issue</option>
                                        <option value="Partnership" <?= (($message_sent ? '' : ($_POST['subject'] ?? ''))) === 'Partnership' ? 'selected' : '' ?>>Partnership</option>
                                        <option value="Feedback" <?= (($message_sent ? '' : ($_POST['subject'] ?? ''))) === 'Feedback' ? 'selected' : '' ?>>Feedback</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">Message *</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" 
                                              placeholder="Please describe your inquiry in detail..." required><?= htmlspecialchars($message_sent ? '' : ($_POST['message'] ?? '')) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="submit_contact" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Map and Additional Info -->
            <div class="col-lg-6">
                <div class="mb-4">
                    <h3 class="mb-4">Find Us</h3>
                    <div class="map-container">
                        <!-- Replace with actual map embed or use Google Maps API -->
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d233668.38703692676!2d90.25487385820311!3d23.780753032797265!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b8b087026b81%3A0x8fa563bbdd5904c2!2sDhaka!5e0!3m2!1sen!2sbd!4v1642687914457!5m2!1sen!2sbd" 
                            width="100%" 
                            height="400" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
                
                <!-- Business Hours -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-clock me-2 text-primary"></i>Business Hours
                        </h5>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between py-1">
                                <span>Monday - Friday:</span>
                                <span class="fw-bold">9:00 AM - 8:00 PM</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span>Saturday:</span>
                                <span class="fw-bold">10:00 AM - 6:00 PM</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span>Sunday:</span>
                                <span class="fw-bold">10:00 AM - 4:00 PM</span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-top pt-2 mt-2">
                                <span>Customer Support:</span>
                                <span class="fw-bold text-primary">24/7 Online</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="contact-section">
    <div class="container">
        <h3 class="text-center mb-5">Frequently Asked Questions</h3>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                How can I track my order?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                You can track your order by logging into your account and visiting the "My Orders" section. You'll receive a tracking number via email once your order is shipped.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                What payment methods do you accept?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We accept various payment methods including bKash, Nagad, Rocket, bank transfers, and cash on delivery for applicable areas.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What is your return policy?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We offer a 7-day return policy for most items. Products must be in original condition with all packaging. Contact our support team to initiate a return.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                How long does delivery take?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Delivery typically takes 2-5 business days within Dhaka and 3-7 business days for other areas in Bangladesh, depending on your location and product availability.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/components/footer.php'; ?>

<script>
// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

</body>
</html>