<?php
// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Dashboard - SB Admin</title>
        <!-- jQuery (must be loaded first) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- DataTables CSS -->
        <link href="https://cdn.datatables.net/v/bs5/dt-2.3.2/datatables.min.css" rel="stylesheet">
        <!-- Custom CSS -->
        <link href="<?= settings()['adminpage'] ?>assets/css/styles.css" rel="stylesheet" onerror="console.warn('Failed to load styles.css from: <?= settings()['adminpage'] ?>assets/css/styles.css'); this.href='assets/css/styles.css';" />
        <!-- Font Awesome -->
        <script src="https://use.fontawesome.com/releases/v6.1.0/js/all.js" crossorigin="anonymous"></script>
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <!-- Admin Navbar Functionality -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing admin functionality...');
            
            // Search functionality
            const searchForm = document.getElementById('navbarSearchForm');
            const searchInput = document.getElementById('navbarSearchInput');
            
            if (searchForm && searchInput) {
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const query = searchInput.value.trim();
                    if (query) {
                        window.location.href = 'search-results.php?q=' + encodeURIComponent(query);
                    }
                });
                
                searchInput.addEventListener('keyup', function(e) {
                    if (e.key === 'Enter') {
                        searchForm.dispatchEvent(new Event('submit'));
                    }
                });
                console.log('Search functionality initialized');
            }
            
            // Debug: Check if Bootstrap is loaded
            console.log('Bootstrap object:', typeof bootstrap !== 'undefined' ? 'Available' : 'Not available');
            console.log('jQuery object:', typeof $ !== 'undefined' ? 'Available' : 'Not available');
            
            // Initialize Bootstrap dropdowns
            setTimeout(function() {
                try {
                    // Method 1: Using Bootstrap 5 native
                    if (typeof bootstrap !== 'undefined') {
                        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                            return new bootstrap.Dropdown(dropdownToggleEl);
                        });
                        console.log('Bootstrap 5 dropdowns initialized:', dropdownList.length);
                    }
                    
                    // Method 2: Manual click handlers as fallback
                    document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
                        toggle.addEventListener('click', function(e) {
                            e.preventDefault();
                            const dropdown = this.nextElementSibling;
                            if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                                dropdown.classList.toggle('show');
                                console.log('Manual dropdown toggle');
                            }
                        });
                    });
                    
                    // Close dropdowns when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!e.target.closest('.dropdown')) {
                            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                                menu.classList.remove('show');
                            });
                        }
                    });
                    
                } catch (error) {
                    console.error('Error initializing dropdowns:', error);
                }
            }, 200);
        });
        </script>
        
        <!-- Admin Navbar Styles -->
        <style>
        /* Dropdown Styles */
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .dropdown-menu.show {
            display: block !important;
        }
        
        .dropdown-toggle::after {
            margin-left: 0.5em;
        }
        
        .notification-dropdown {
            width: 400px;
            max-height: 500px;
            overflow-y: auto;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }
        
        .notification-dropdown .dropdown-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .notification-item {
            border-left: 3px solid transparent;
            transition: all 0.2s ease-in-out;
            position: relative;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa !important;
        }
        
        .notification-item.bg-light {
            background-color: #f1f8ff !important;
            border-left-color: #0d6efd;
        }
        
        .notification-item .dropdown-item {
            white-space: normal;
            padding: 0.75rem 1rem;
            border-radius: 0.25rem;
            margin: 0.125rem 0.5rem;
            width: auto;
        }
        
        .notification-item .dropdown-item:hover {
            background-color: transparent;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(13, 110, 253, 0.1);
            flex-shrink: 0;
            margin-right: 0.75rem;
        }
        
        .notification-icon i {
            font-size: 1.1rem;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 2px;
            color: #212529;
            font-size: 0.9rem;
        }
        .notification-message {
            margin-bottom: 0;
            white-space: normal;
            line-height: 1.4;
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: #6c757d;
            white-space: nowrap;
            margin-left: 0.5rem;
        }
        
        #notificationCount {
            font-size: 0.6rem;
            padding: 0.25em 0.5em;
            top: 5px;
            right: -5px;
            font-weight: 600;
            box-shadow: 0 0 0 2px #fff;
        }
        
        /* Animation for new notifications */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        #notificationCount.pulse {
            animation: pulse 0.5s ease-in-out;
        }
        
        /* Scrollbar styling */
        .notification-dropdown::-webkit-scrollbar {
            width: 6px;
        }
        
        .notification-dropdown::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .notification-dropdown::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        .notification-dropdown::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        </style>
    </head>
    <body>
        <!-- Notification System -->
        <script>
            // Load notifications.js with fallback
            (function() {
                var script = document.createElement('script');
                script.src = '<?= settings()['adminpage'] ?>assets/js/notifications.js';
                script.onerror = function() {
                    console.warn('Failed to load notifications.js from settings path, trying relative path');
                    var fallbackScript = document.createElement('script');
                    fallbackScript.src = 'assets/js/notifications.js';
                    fallbackScript.onerror = function() {
                        console.warn('Notifications.js not found, creating empty fallback');
                        // Create minimal notification functions to prevent errors
                        window.initNotifications = window.initNotifications || function() { console.log('Notifications disabled - file not found'); };
                        window.showNotification = window.showNotification || function() { console.log('Notifications disabled - file not found'); };
                    };
                    document.head.appendChild(fallbackScript);
                };
                document.head.appendChild(script);
            })();
        </script>