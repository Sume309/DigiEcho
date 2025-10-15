<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/vendor/autoload.php';
$notificationServicePath = __DIR__ . '/src/NotificationService.php';
if (file_exists($notificationServicePath)) {
    require_once $notificationServicePath;
}
$page = "Login";
if(isset($_POST['login'])){
    $db = new MysqliDb();
    $db->where("email", $_POST['email']);
    $row = $db->getOne ("users");
    if($row){
        if(password_verify($_POST['pass1'],$row['password'])){
            $_SESSION['loggedin'] = true;
            $_SESSION['userid'] = $row['id'];
            $_SESSION['username'] = $row['first_name'];
            $_SESSION['role'] = $row['role'];

            // Create user activity notification (login success)
            if (class_exists('NotificationService')) {
                try {
                    $ns = new NotificationService();
                    $username = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ($row['email'] ?? 'User');
                    $ns->createUserActivityNotification((int)$row['id'], $username, 'logged in', [
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]);
                } catch (Exception $e) { /* ignore */ }
            }
            if($row['role'] == "admin"){
                header('Location:admin/');
                exit;
            }
            elseif($row['role'] == "customer"){
                header('Location:index.php');
                exit;
            }
            else{
                header('Location:index.php'); 
                exit; 
            }

        }
        else{
            $message = "Passwords do not match";
            // Failed login attempt security alert
            if (class_exists('NotificationService')) {
                try {
                    $ns = new NotificationService();
                    $ns->createSecurityAlert('Failed Login Attempt', 'Failed login for ' . ($_POST['email'] ?? 'unknown email'), [
                        'email' => $_POST['email'] ?? null,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]);
                } catch (Exception $e) { /* ignore */ }
            }
        }
    }
    else{
        $message = "Invalid Account";
        // Invalid account security alert
        if (class_exists('NotificationService')) {
            try {
                $ns = new NotificationService();
                $ns->createSecurityAlert('Failed Login Attempt', 'Login attempted with unknown account', [
                    'email' => $_POST['email'] ?? null,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
            } catch (Exception $e) { /* ignore */ }
        }
    }

}
?>

<?php require __DIR__ . '/components/header.php';?>

<!-- content start -->

<!-- <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login Page</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
  <style>
    body {
      background-color:rgb(229, 238, 234);
    }
    .login-container {
      max-width: 400px;
      margin: 80px auto;
      padding: 30px;
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

  <div class="login-container">
    <h2 class="text-center mb-4">Login</h2>

    <form class="needs-validation" novalidate method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
      
      <div class="form-floating mb-3">
        <input 
          type="email" 
          class="form-control" 
          id="email" 
          name="email" 
          placeholder="yourname@example.com" 
          required 
          value="">
        <label for="email">Email address</label>
        <div class="invalid-feedback">
          Please enter a valid email address.
        </div>
      </div>

      <div class="form-floating mb-4">
        <input 
          type="password" 
          class="form-control" 
          id="pass1" 
          name="pass1" 
          placeholder="Password" 
          required 
          minlength="5"
          value="">
        <label for="pass1">Password</label>
        <div class="invalid-feedback">
          Please enter a password (min 5 characters).
        </div>
      </div>

      <div class="d-grid">
        <button type="submit" name="login" class="btn btn-primary">
          Login
        </button>
        <h6>Admin login:</h6>
        Email:admin@gmail.com<br>
        Password:12345
      </div>

    </form>
  </div>

  <script>
    // Bootstrap validation script
    (function () {
      'use strict';
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', function (event) {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });
    })();
  </script>
</body>
</html>

<!-- content end -->
<?php
// echo testfunc();
?>

<?php require __DIR__ . '/components/footer.php';?>
    
