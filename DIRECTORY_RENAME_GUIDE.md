# Directory Rename Guide - DigiEcho

## Step-by-Step Instructions

### 1. Stop XAMPP Services
- Open XAMPP Control Panel
- Stop Apache and MySQL services
- This ensures no files are locked during the rename

### 2. Rename the Project Directory
**Current Path:** `D:\xampp\htdocs\Family-Haat-Bazar\`
**New Path:** `D:\xampp\htdocs\DigiEcho\`

**Steps:**
1. Navigate to `D:\xampp\htdocs\`
2. Right-click on `Family-Haat-Bazar` folder
3. Select "Rename"
4. Change name to `DigiEcho`
5. Press Enter to confirm

### 3. Restart XAMPP Services
- Start Apache and MySQL services again
- Wait for both to show "Running" status

### 4. Test the New URLs

#### Frontend URLs:
- **Homepage:** `http://localhost/DigiEcho/`
- **Products:** `http://localhost/DigiEcho/index.php`
- **Contact:** `http://localhost/DigiEcho/contact.php`
- **About:** `http://localhost/DigiEcho/about.php`

#### Admin Panel URLs:
- **Admin Dashboard:** `http://localhost/DigiEcho/admin/`
- **Products Management:** `http://localhost/DigiEcho/admin/product-management.php`
- **Categories:** `http://localhost/DigiEcho/admin/category-all.php`
- **Orders:** `http://localhost/DigiEcho/admin/order-all.php`

### 5. Verification Checklist

#### ✅ Frontend Verification:
- [ ] Homepage loads with "Welcome to DigiEcho"
- [ ] Navigation menu works properly
- [ ] Product listings display correctly
- [ ] Contact page shows DigiEcho branding
- [ ] Images and CSS load properly

#### ✅ Admin Panel Verification:
- [ ] Admin login page accessible
- [ ] Dashboard loads with DigiEcho branding
- [ ] Product management functions work
- [ ] File uploads still function
- [ ] Reports and statistics display

#### ✅ Database Verification:
- [ ] Product data displays correctly
- [ ] Categories and subcategories work
- [ ] Orders and user data intact
- [ ] No broken database connections

### 6. Update Bookmarks
Update any bookmarks from:
- `http://localhost/Family-Haat-Bazar/` → `http://localhost/DigiEcho/`
- `http://localhost/Family-Haat-Bazar/admin/` → `http://localhost/DigiEcho/admin/`

## Troubleshooting

### If you encounter issues:

#### Problem: "Page not found" errors
**Solution:** 
- Verify the folder was renamed correctly
- Check XAMPP services are running
- Clear browser cache

#### Problem: Images not loading
**Solution:**
- Check that the `assets/` folder exists in the new location
- Verify file permissions are correct

#### Problem: Database connection errors
**Solution:**
- Restart MySQL service in XAMPP
- Check if database 'haatbazar' still exists in phpMyAdmin

## Files Already Updated
All these files have been pre-configured for the new directory structure:
- ✅ `src/settings.php` - Root URL and physical path updated
- ✅ All PHP files use dynamic settings() function
- ✅ Admin panel references updated
- ✅ Email configurations updated

## Success Indicators
When everything is working correctly, you should see:
- **Browser title:** "DigiEcho - Home"
- **Homepage header:** "Welcome to DigiEcho"
- **Footer:** "© DigiEcho 2025"
- **Admin panel:** "DigiEcho" in navigation
- **All links and images load properly**

## Backup Recommendation
Before renaming, consider creating a backup of the current folder just in case you need to revert changes.