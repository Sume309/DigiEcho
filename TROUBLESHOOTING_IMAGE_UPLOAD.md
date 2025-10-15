# Product Image Upload Troubleshooting Guide

## Common Issues and Solutions

### 1. **No images are uploading at all**

**Diagnosis Steps:**
1. Visit `http://localhost/Family-Haat-Bazar/admin/test-upload-simple.php`
2. Try uploading a small image file (under 1MB)
3. Check if the file appears in the `assets/products/` directory

**Common Causes:**
- Directory permissions (solution: ensure `assets/products/` is writable)
- PHP upload settings too restrictive
- Form missing `enctype="multipart/form-data"`

### 2. **Images upload but don't save to database**

**Diagnosis Steps:**
1. Visit `http://localhost/Family-Haat-Bazar/admin/debug-product-creation.php`
2. Test the complete product creation flow
3. Check browser developer console for JavaScript errors

**Common Causes:**
- Database connection issues
- Missing `image` column in products table
- AJAX submission errors

### 3. **Enhanced forms don't work**

**Diagnosis Steps:**
1. Check browser console for JavaScript errors
2. Verify jQuery and SweetAlert2 are loading
3. Test with `product-add.php` (non-enhanced version)

**Common Causes:**
- Missing JavaScript libraries
- CORS or path issues with assets
- Form validation preventing submission

### 4. **File size or type errors**

**Current Settings:**
- Maximum file size: 5MB
- Allowed types: jpg, jpeg, png, gif, webp
- PHP upload_max_filesize and post_max_size should be larger than 5MB

**Solution:**
Edit `php.ini` if needed:
```ini
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 128M
```

## Quick Fixes Applied

### Fixed Issues:
1. ✅ **Directory Path**: Changed from `/uploads/products/` to `/assets/products/`
2. ✅ **File Validation**: Added proper file type and size validation
3. ✅ **Error Handling**: Improved error messages and logging
4. ✅ **Path Resolution**: Fixed directory path resolution issues
5. ✅ **WebP Support**: Added WebP format support

### Files Modified:
- `admin/product-ajax.php` - Enhanced upload handling and error reporting
- `admin/product-add.php` - Fixed directory path and validation
- `admin/product-edit-enhanced.php` - Minor UI improvements

### Test Files Created:
- `admin/test-upload-simple.php` - Basic upload testing
- `admin/debug-product-creation.php` - Complete flow testing
- `admin/test-image-upload.php` - System diagnostics

## Next Steps

1. **Test the fixes:**
   ```
   1. Go to: http://localhost/Family-Haat-Bazar/admin/test-upload-simple.php
   2. Upload a test image
   3. Verify it appears in assets/products/ directory
   ```

2. **Test product creation:**
   ```
   1. Go to: http://localhost/Family-Haat-Bazar/admin/product-add-enhanced.php
   2. Fill out the form with a test product
   3. Upload an image
   4. Submit and check for success
   ```

3. **If issues persist:**
   ```
   1. Check admin/debug-product-creation.php for detailed diagnostics
   2. Look at browser console for JavaScript errors
   3. Check PHP error log for server-side issues
   ```

## Important Notes

- Make sure you're logged into the admin panel before testing
- The `assets/products/` directory must be writable by the web server
- Clear browser cache if JavaScript changes don't take effect
- Check that your XAMPP/web server has sufficient permissions

## File Locations

- **Upload Directory**: `Family-Haat-Bazar/assets/products/`
- **Main Forms**: `admin/product-add-enhanced.php`, `admin/product-edit-enhanced.php`
- **AJAX Handler**: `admin/product-ajax.php`
- **Test Files**: `admin/test-*.php`, `admin/debug-*.php`