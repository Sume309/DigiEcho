<?php
// Set default timezone for the application
date_default_timezone_set('Asia/Dhaka');

if (!function_exists('settings')) {
    function settings()
    {
        $root = "http://localhost/DigiEcho/";
        return [
            'root' => $root,
            'companyname' => 'DigiEcho',
            'logo' => $root ,
            'homepage' => $root,
            'adminpage' => $root . 'admin/',
            'hostname' => 'localhost',
            'user' => 'root',
            'password' => '',
            'database' => 'digiecho',
            'timezone' => 'Asia/Dhaka',
            'physical_path' => 'http://localhost/DigiEcho/',
            // Email Configuration
            'mail_host' => 'bisew.tahminasumi@gmail.com',
            'mail_port' => 587,
            'mail_username' => 'bisew.tahminasumi@gmail.com',
            'mail_password' => 'jmKLgHZ3{UXO[9Ll',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'bisew.tahminasumi@gmail.com',
            'mail_from_name' => 'DigiEcho',
            'mail_reply_to' => 'bisew.tahminasumi@gmail.com'
        ];
    }
}
if (!function_exists('testfunc')) {
    function testfunc()
    {
        return "<h3>testing common functions</h3>";
    }
}
if (!function_exists('config')) {
    function config($param)
    {
        $parts = explode(".", $param);
        $configFile = __DIR__ . "/../config/" . $parts[0] . ".php";

        if (!file_exists($configFile)) {
            error_log("Config file not found: " . $configFile);
            return null;
        }

        $inc = include $configFile;

        if (!is_array($inc) || !isset($inc[$parts[1]])) {
            error_log("Config key not found: " . $param);
            return null;
        }

        return $inc[$parts[1]];
    }
}
