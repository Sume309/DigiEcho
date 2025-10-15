<?php
require_once 'src/settings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Under Construction - <?= htmlspecialchars(settings()['companyname']) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .construction-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            padding: 50px;
            text-align: center;
            max-width: 600px;
            width: 100%;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .construction-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 30px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .main-title {
            font-size: 3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .sub-title {
            font-size: 1.4rem;
            font-weight: 500;
            color: #667eea;
            margin-bottom: 30px;
        }
        
        .message {
            font-size: 1.1rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        
        .progress-container {
            background: #f8f9fa;
            border-radius: 50px;
            padding: 5px;
            margin: 30px 0;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .progress {
            height: 25px;
            border-radius: 50px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            animation: progressAnimation 3s ease-in-out infinite;
        }
        
        @keyframes progressAnimation {
            0% { width: 60%; }
            50% { width: 85%; }
            100% { width: 60%; }
        }
        
        .contact-info {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            border-left: 5px solid #667eea;
        }
        
        .contact-info h5 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .contact-item {
            margin-bottom: 10px;
            color: #555;
        }
        
        .contact-item i {
            color: #667eea;
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        .social-links {
            margin-top: 30px;
        }
        
        .social-links h5 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .social-btn {
            display: inline-block;
            width: 50px;
            height: 50px;
            line-height: 50px;
            border-radius: 50%;
            margin: 0 10px;
            font-size: 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            color: white;
        }
        
        .facebook { background: #3b5998; }
        .twitter { background: #1da1f2; }
        .instagram { background: #e4405f; }
        .youtube { background: #ff0000; }
        
        .newsletter-signup {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .newsletter-signup h5 {
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .newsletter-form {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .newsletter-form input {
            flex: 1;
            border: none;
            border-radius: 25px;
            padding: 12px 20px;
            outline: none;
        }
        
        .newsletter-form button {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .newsletter-form button:hover {
            background: white;
            color: #667eea;
        }
        
        .back-home {
            margin-top: 30px;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .countdown {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .countdown-item {
            text-align: center;
            min-width: 80px;
        }
        
        .countdown-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            display: block;
        }
        
        .countdown-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .construction-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .main-title {
                font-size: 2.2rem;
            }
            
            .sub-title {
                font-size: 1.2rem;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .countdown {
                gap: 15px;
            }
            
            .countdown-number {
                font-size: 1.5rem;
            }
        }

        
.countdown-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px;
    border-radius: 15px;
    color: white;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    max-width: 800px;
    margin: 20px auto;
    font-family: 'Arial', sans-serif;
}

.countdown-subtitle {
    margin: 0 0 25px 0;
    opacity: 0.9;
    font-size: 1.2em;
    font-weight: 300;
}

.countdown-grid {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
    margin: 25px 0;
}

.countdown-item {
    background: rgba(0, 8, 5, 0.7);
    padding: 20px 15px;
    border-radius: 12px;
    min-width: 90px;
   
    border: 1px solid rgba(240, 15, 15, 0.79);
    transition: transform 0.3s ease;
}

.countdown-item:hover {
    transform: translateY(-5px);
}

.countdown-value {
    font-size: 2.2em;
    font-weight: bold;
    display: block;
    text-shadow: 2px 2px 4px rgba(0, 3, 153, 0.91);
    margin-bottom: 5px;
}

.countdown-label {
    font-size: 0.9em;
    opacity: 0.9;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-shadow: 2px 2px 4px rgba(33, 0, 153, 0.91);
}

    </style>
</head>
<body>
    <div class="construction-container">
        <!-- Construction Icon -->
        <div class="construction-icon">
            <i class="fas fa-mobile-alt"></i>
        </div>
        
        <!-- Main Content -->
        <h1 class="main-title">App Under Construction</h1>
        <h2 class="sub-title">We're working hard to bring you something awesome!</h2>
        
        <p class="message">
            Our mobile application is currently undergoing development and improvements. 
            We apologize for any inconvenience and appreciate your patience while we create 
            an amazing shopping experience for you.
        </p>
        
        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress" style="width: 75%;"></div>
        <small class="text-muted">Development is under inprogress:.......</small>
     </div>
            <div class="countdown-container">
   <p class="countdown-subtitle">Expected Launch: Early 2026</p>
   
   <div class="countdown-grid">
        <div class="countdown-item">
            <span id="years" class="countdown-value">--</span>
            <span class="countdown-label">Years</span>
        </div>
        <div class="countdown-item">
            <span id="months" class="countdown-value">--</span>
            <span class="countdown-label">Months</span>
        </div>
        <div class="countdown-item">
            <span id="days" class="countdown-value">--</span>
            <span class="countdown-label">Days</span>
        </div>
        <div class="countdown-item">
            <span id="hours" class="countdown-value">--</span>
            <span class="countdown-label">Hours</span>
        </div>
        <div class="countdown-item">
            <span id="minutes" class="countdown-value">--</span>
            <span class="countdown-label">Minutes</span>
        </div>
        <div class="countdown-item">
            <span id="seconds" class="countdown-value">--</span>
            <span class="countdown-label">Seconds</span>
        </div>
    </div>
</div>

<script>
    const countdownDate = new Date("July 13, 2028 10:00:00").getTime();
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = countdownDate - now;
        
        if (distance < 0) {
            document.querySelector('.countdown-grid').innerHTML = 
                '<div style="background: rgba(76, 175, 80, 0.9); padding: 30px; border-radius: 12px; font-size: 1.8em; font-weight: bold; backdrop-filter: blur(10px);">🎉 App is Now Live! 🎉</div>';
            return;
        }
        
        const years = Math.floor(distance / (1000 * 60 * 60 * 24 * 365));
        const months = Math.floor((distance % (1000 * 60 * 60 * 24 * 365)) / (1000 * 60 * 60 * 24 * 30));
        const days = Math.floor((distance % (1000 * 60 * 60 * 24 * 30)) / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        document.getElementById('years').textContent = years.toString().padStart(2, '0');
        document.getElementById('months').textContent = months.toString().padStart(2, '0');
        document.getElementById('days').textContent = days.toString().padStart(2, '0');
        document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
        document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
        document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
    }
    
    updateCountdown();
    setInterval(updateCountdown, 1000);
</script>

        <!-- Contact Information -->
        <div class="contact-info">
            <h5><i class="fas fa-headset me-2"></i>Need Immediate Assistance?</h5>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <strong>Email:</strong> <a href="mailto:info@digiecho.com">info@digiecho.com</a>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <strong>Phone:</strong> <a href="tel:+8801700000000">+880 1700-000000</a>
            </div>
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <strong>Support Hours:</strong> 9:00 AM - 9:00 PM (Daily)
            </div>
        </div>
        
        <!-- Social Media Links -->
        <div class="social-links">
            <h5><i class="fas fa-share-alt me-2"></i>Follow Us for Updates</h5>
            <a href="#" class="social-btn facebook" title="Facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="social-btn twitter" title="Twitter">
                <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="social-btn instagram" title="Instagram">
                <i class="fab fa-instagram"></i>
            </a>
            <a href="#" class="social-btn youtube" title="YouTube">
                <i class="fab fa-youtube"></i>
            </a>
        </div>
        
        <!-- Newsletter Signup -->
        <div class="newsletter-signup">
            <h5><i class="fas fa-bell me-2"></i>Get Notified When We Launch!</h5>
            <p class="mb-0">Be the first to know when our mobile app goes live.</p>
            <form class="newsletter-form" onsubmit="subscribeNewsletter(event)">
                <input type="email" placeholder="Enter your email address" required>
                <button type="submit">
                    <i class="fas fa-paper-plane me-2"></i>Notify Me
                </button>
            </form>
        </div>
        
        <!-- Back to Home -->
        <div class="back-home">
            <a href="index.php" class="btn-home">
                <i class="fas fa-home me-2"></i>Continue Shopping on Website
            </a>
        </div>
    </div>
</body>
</html>