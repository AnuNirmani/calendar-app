# Offline Setup Guide

This calendar application has been configured to work **without internet access**. All external CDN links have been commented out in the codebase.

## Current Status

✅ **The application works offline** - All HTTP/CDN links are now commented out  
⚠️ **Some features may look different** - Styling and JavaScript from CDN libraries are disabled

## What Was Changed

All external CDN dependencies have been disabled in these files:
- `index.php` - Removed external PDF link
- `circular.php` and `circular.html` - Commented out Bootstrap, Font Awesome, Google Fonts
- All `admin/*.php` files - Commented out Tailwind CSS, Font Awesome, jQuery
- All `admin/posts/*.php` files - Commented out Tailwind CSS, Font Awesome, jQuery, Quill Editor

## Optional: Restoring Full Functionality (Offline)

If you want full styling and functionality without internet, you can download and host libraries locally:

### Step 1: Create Local Library Folders

```bash
mkdir -p assets/css assets/js assets/fonts
```

### Step 2: Download Required Libraries

#### A. **Tailwind CSS** (for admin pages)
1. Download from: https://cdn.tailwindcss.com
2. Save as `assets/js/tailwind.min.js`
3. **OR** use Tailwind CLI to build a custom CSS file (recommended for production)

#### B. **Bootstrap 5.3.0** (for circular pages)
1. CSS: https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css
   - Save as `assets/css/bootstrap.min.css`
2. JS: https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js
   - Save as `assets/js/bootstrap.bundle.min.js`

#### C. **Font Awesome 6.4.0** (for icons)
1. Download from: https://fontawesome.com/download
2. Extract to `assets/fonts/fontawesome/`
3. Include `assets/fonts/fontawesome/css/all.min.css`

#### D. **jQuery 3.7.1**
1. Download from: https://code.jquery.com/jquery-3.7.1.min.js
2. Save as `assets/js/jquery.min.js`

#### E. **jQuery Validate 1.19.5**
1. Download from: https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js
2. Save as `assets/js/jquery.validate.min.js`

#### F. **Quill Editor 1.3.6** (for rich text editing)
1. CSS: https://cdn.quilljs.com/1.3.6/quill.snow.css
   - Save as `assets/css/quill.snow.css`
2. JS: https://cdn.quilljs.com/1.3.6/quill.min.js
   - Save as `assets/js/quill.min.js`

### Step 3: Update File References

Once you've downloaded the libraries, update the commented CDN links to point to local files:

#### Example: In `admin/add.php`
Replace:
```html
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
<!-- <script src="https://cdn.tailwindcss.com"></script> -->
```

With:
```html
<link rel="stylesheet" href="../assets/fonts/fontawesome/css/all.min.css">
<script src="../assets/js/tailwind.min.js"></script>
```

#### Example: In `circular.php`
Replace:
```html
<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
```

With:
```html
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<script src="assets/js/bootstrap.bundle.min.js"></script>
```

## Quick Download Script (PowerShell - Windows)

Run this script to download all libraries automatically:

```powershell
# Create directories
New-Item -ItemType Directory -Force -Path "assets/css", "assets/js", "assets/fonts"

# Download libraries
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" -OutFile "assets/css/bootstrap.min.css"
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" -OutFile "assets/js/bootstrap.bundle.min.js"
Invoke-WebRequest -Uri "https://code.jquery.com/jquery-3.7.1.min.js" -OutFile "assets/js/jquery.min.js"
Invoke-WebRequest -Uri "https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js" -OutFile "assets/js/jquery.validate.min.js"
Invoke-WebRequest -Uri "https://cdn.quilljs.com/1.3.6/quill.min.js" -OutFile "assets/js/quill.min.js"
Invoke-WebRequest -Uri "https://cdn.quilljs.com/1.3.6/quill.snow.css" -OutFile "assets/css/quill.snow.css"

# Download Font Awesome (you'll need to extract the zip)
Invoke-WebRequest -Uri "https://use.fontawesome.com/releases/v6.4.0/fontawesome-free-6.4.0-web.zip" -OutFile "fontawesome.zip"

Write-Host "Libraries downloaded! Extract fontawesome.zip to assets/fonts/fontawesome/"
```

## Alternative: Re-enable CDN Links (If Internet Access is Available)

If you have internet access and want to quickly restore CDN functionality:

1. Find all comments with `<!-- OFFLINE MODE: CDN links commented out` in the files
2. Uncomment the CDN links below each comment
3. The application will work with full styling again (requires internet)

## Files to Update for Local Libraries

When using local libraries, update these files:

### Admin Files:
- `admin/add.php`
- `admin/add_user.php`
- `admin/edit.php`
- `admin/edit_user.php`
- `admin/index.php`
- `admin/manage_users.php`

### Admin Posts Files:
- `admin/posts/add_post.php`
- `admin/posts/edit_post.php`
- `admin/posts/list_posts.php`
- `admin/posts/list_categories.php`
- `admin/posts/list_telephone_directory.php`
- `admin/posts/create_category.php`
- `admin/posts/edit_category.php`
- `admin/posts/edit_telephone_directory.php`
- `admin/posts/add_telephone_directory.php`

### Public Files:
- `circular.php`
- `circular.html`

## Notes

- The application **currently works without any styling libraries** - basic HTML functionality is preserved
- For production offline use, downloading and hosting libraries locally is **highly recommended**
- Tailwind CSS requires special handling - consider using the CLI to build a production CSS file
- Font Awesome requires the web fonts to be available locally for icons to display

## Need Help?

If you need assistance setting up local libraries or have questions about offline functionality, refer to the main `README.md` or contact your system administrator.
