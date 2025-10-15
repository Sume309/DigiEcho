<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Login - DigiEcho Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Admin Auto Login</h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3">Logging you in as admin...</p>
                        </div>
                        
                        <div id="loginStatus" class="alert d-none"></div>
                        
                        <div class="d-grid gap-2">
                            <button id="manualLogin" class="btn btn-outline-secondary" style="display: none;">
                                Manual Login Instead
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit login form
        function performAutoLogin() {
            const formData = new FormData();
            formData.append('email', 'admin@gmail.com');
            formData.append('pass1', '12345');
            formData.append('login', '1');

            fetch('../login.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (response.redirected) {
                    // Login successful, redirect to admin
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful!',
                        text: 'You have been logged in as admin.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'category-management.php';
                    });
                } else {
                    return response.text();
                }
            })
            .then(text => {
                if (text) {
                    // Check if login failed
                    if (text.includes('Passwords do not match') || text.includes('Invalid Account')) {
                        showError('Login failed. Please check credentials.');
                    } else {
                        // Login successful
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful!',
                            text: 'You have been logged in as admin.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = 'category-management.php';
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                showError('Login failed. Please try manual login.');
            });
        }

        function showError(message) {
            const statusDiv = document.getElementById('loginStatus');
            statusDiv.className = 'alert alert-danger';
            statusDiv.textContent = message;
            statusDiv.classList.remove('d-none');
            
            document.querySelector('.spinner-border').style.display = 'none';
            document.getElementById('manualLogin').style.display = 'block';
        }

        // Manual login button
        document.getElementById('manualLogin').addEventListener('click', function() {
            window.location.href = '../login.php';
        });

        // Start auto login after page loads
        window.addEventListener('load', function() {
            setTimeout(performAutoLogin, 1000);
        });
    </script>
</body>
</html>