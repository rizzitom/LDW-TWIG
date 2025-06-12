<?php

/**
 * Cloudinary configuration
 * Create a free account at https://cloudinary.com/ and get your credentials
 */

// Cloudinary configuration parameters
define('CLOUDINARY_CLOUD_NAME', '');
define('CLOUDINARY_API_KEY', '');
define('CLOUDINARY_API_SECRET', '');

// Folder settings for organization
define('CLOUDINARY_USER_PROFILES_FOLDER', 'user_profiles');
define('CLOUDINARY_PRODUCTS_FOLDER', 'products');
define('CLOUDINARY_MISC_FOLDER', 'misc');

// Image transformation settings
define('PROFILE_PICTURE_WIDTH', 250);
define('PROFILE_PICTURE_HEIGHT', 250);
define('PROFILE_PICTURE_CROP', 'fill');
define('PROFILE_PICTURE_GRAVITY', 'face');

?>
