# Admin Settings System Guide

## Overview
The Admin Settings System provides complete control over your website's frontend appearance and content. You can manage banners, site information, notice bars, and more from a single dashboard.

## Accessing Settings
1. Login to admin panel
2. Click **Settings** in the sidebar
3. Navigate through the tabs to manage different aspects

## Features

### 1. General Settings
**Location:** Settings → General Tab

**What you can manage:**
- **Site Name:** The name displayed in browser title and throughout the site
- **Site Description:** Tagline or description for your marketplace
- **Contact Email:** Primary contact email for the business
- **Contact Phone:** Business phone number
- **Address:** Business address

**How to update:**
1. Go to General tab
2. Fill in the form fields
3. Click "Save General Settings"
4. Changes appear immediately across the site

### 2. Banner Management
**Location:** Settings → Banners Tab

**What you can do:**
- Add multiple sliding banners for homepage
- Edit existing banners
- Delete unwanted banners
- Reorder banner display sequence
- Enable/disable specific banners

**Banner Fields:**
- **Title:** Main headline (e.g., "Welcome to DigiEcho")
- **Subtitle:** Secondary text
- **Description:** Detailed description
- **Image:** Banner background image (recommended: 1920x600px)
- **Button Text:** Call-to-action button text (e.g., "Shop Now")
- **Button Link:** Where the button should redirect
- **Status:** Active or Inactive
- **Sort Order:** Display sequence (1 = first)

**How to add a banner:**
1. Click "Add Banner" button
2. Fill in all fields
3. Upload banner image
4. Set status to "Active"
5. Click "Save Banner"

**How to edit a banner:**
1. Click the edit (pencil) icon in the Actions column
2. Modify fields as needed
3. Upload new image (optional)
4. Click "Update Banner"

**How to delete a banner:**
1. Click the delete (trash) icon in the Actions column
2. Confirm deletion
3. Banner and its image file will be removed

### 3. Notice Bar
**Location:** Settings → Notice Bar Tab

**What it does:**
- Displays important announcements at the top of all pages
- Can be enabled/disabled as needed
- Supports different alert types for visual emphasis

**Options:**
- **Enable Notice Bar:** Checkbox to show/hide the notice
- **Notice Text:** Your announcement message
- **Notice Type:** 
  - Info (Blue) - General information
  - Success (Green) - Positive announcements
  - Warning (Yellow) - Important notices
  - Important (Red) - Urgent announcements

**How to use:**
1. Check "Enable Notice Bar"
2. Enter your message
3. Select appropriate type
4. Click "Save Notice Settings"
5. Notice appears immediately on all pages

**Examples:**
- "🎉 Welcome! Get 20% off your first order with code WELCOME20"
- "⚠️ Maintenance scheduled for Sunday 2-4 AM"
- "✅ Free shipping on orders over $50!"

### 4. Appearance Settings
**Location:** Settings → Appearance Tab

**Logo Management:**
- View current logo
- Upload new logo
- Automatic updates across the site

**How to change logo:**
1. Go to Appearance tab
2. Click "Choose File" under Upload New Logo
3. Select your logo image (recommended: 200x60px, PNG/JPG)
4. Click "Update Logo"
5. Logo updates immediately in navigation

## Best Practices

### Banner Images
- **Size:** 1920x600px for best results
- **Format:** JPG or PNG
- **File size:** Keep under 500KB for fast loading
- **Content:** Ensure text is readable on the image

### Notice Bar
- Keep messages concise and clear
- Use appropriate alert types for context
- Update regularly to keep content fresh
- Disable when not needed to avoid clutter

### Logo
- Use transparent PNG for best results
- Maintain consistent branding
- Test on different screen sizes
- Keep file size reasonable

## Technical Notes

### Database Tables
- `site_settings`: Stores all configuration settings
- `banners`: Stores banner slider content

### File Locations
- Banner images: `assets/images/banners/`
- Logo images: `assets/images/`
- Settings files: `admin/settings.php` and `admin/settings-ajax.php`

### Backup Recommendations
- Backup database before major changes
- Keep copies of important banner images
- Test changes on staging environment first

## Troubleshooting

### Banner not showing
1. Check if banner status is "Active"
2. Verify image file exists and is accessible
3. Check sort order (lower numbers display first)

### Settings not saving
1. Ensure you have admin privileges
2. Check file permissions on upload directories
3. Verify database connection

### Images not uploading
1. Check file size (should be under server limit)
2. Verify file format (JPG, PNG, GIF supported)
3. Ensure upload directory has write permissions

## Support
If you encounter issues:
1. Check the browser console for JavaScript errors
2. Verify PHP error logs
3. Ensure all required directories exist and are writable
4. Test with different browsers

---

**Last Updated:** <?= date('Y-m-d H:i:s') ?>

**System Version:** 1.0.0
