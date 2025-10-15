<?php

// Include autoloader if not already loaded
if (!class_exists('MysqliDb')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class SettingsHelper {
    private static $settings = null;
    
    public static function get($key = null, $default = null) {
        if (self::$settings === null) {
            self::loadSettings();
        }
        
        if ($key === null) {
            return self::$settings;
        }
        
        return self::$settings[$key] ?? $default;
    }
    
    public static function set($key, $value) {
        try {
            $db = new MysqliDb();
            
            // Check if setting exists
            $existing = $db->where('setting_key', $key)->getOne('site_settings');
            
            if ($existing) {
                $result = $db->where('setting_key', $key)->update('site_settings', [
                    'setting_value' => $value,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                $result = $db->insert('site_settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Update local cache
            if (self::$settings !== null) {
                self::$settings[$key] = $value;
            }
            
            $db->disconnect();
            return $result;
            
        } catch (Exception $e) {
            error_log("SettingsHelper::set error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getBanners($activeOnly = true, $pageType = 'homepage') {
        try {
            $db = new MysqliDb();
            
            if ($activeOnly) {
                $db->where('status', 'active');
            }
            
            // Filter by page type
            $db->where('page_type', $pageType);
            
            $banners = $db->orderBy('sort_order', 'ASC')->get('banners');
            $db->disconnect();
            
            return $banners ?: [];
            
        } catch (Exception $e) {
            error_log("SettingsHelper::getBanners error: " . $e->getMessage());
            return [];
        }
    }
    
    public static function getNoticeBar() {
        $enabled = self::get('notice_enabled', '0');
        $text = self::get('notice_text', '');
        $type = self::get('notice_type', 'info');
        
        if ($enabled === '1' && !empty($text)) {
            return [
                'enabled' => true,
                'text' => $text,
                'type' => $type
            ];
        }
        
        return ['enabled' => false];
    }
    
    private static function loadSettings() {
        self::$settings = [];
        
        try {
            $db = new MysqliDb();
            
            // Create table if it doesn't exist
            $db->rawQuery("CREATE TABLE IF NOT EXISTS site_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $settingsData = $db->get('site_settings');
            
            foreach ($settingsData as $setting) {
                self::$settings[$setting['setting_key']] = $setting['setting_value'];
            }
            
            // Set defaults if not exist
            $defaults = [
                'site_name' => 'DigiEcho',
                'site_description' => 'Your trusted online marketplace',
                'contact_email' => 'info@digiecho.com',
                'contact_phone' => '+880 1234567890',
                'address' => 'Dhaka, Bangladesh',
                'logo' => 'assets/images/logo.png',
                'notice_enabled' => '0',
                'notice_text' => '',
                'notice_type' => 'info'
            ];
            
            foreach ($defaults as $key => $value) {
                if (!isset(self::$settings[$key])) {
                    self::$settings[$key] = $value;
                    // Insert into database
                    $db->insert('site_settings', [
                        'setting_key' => $key,
                        'setting_value' => $value
                    ]);
                }
            }
            
            $db->disconnect();
            
        } catch (Exception $e) {
            error_log("SettingsHelper::loadSettings error: " . $e->getMessage());
            // Set basic defaults
            self::$settings = [
                'site_name' => 'DigiEcho',
                'site_description' => 'Your trusted online marketplace',
                'logo' => 'assets/images/logo.png',
                'notice_enabled' => '0'
            ];
        }
    }
    
    public static function clearCache() {
        self::$settings = null;
    }
}
?>
