<?php
/**
 * Image Helper Functions
 * 
 * Helper functions for properly encoding image URLs and handling missing images
 */

/**
 * Get the correct image path based on deployment environment
 * Handles both local development and Render deployment
 * 
 * @param string $photoURL The PhotoURL from database (e.g., "assets/uploads/file.jpg")
 * @return string Properly formatted image path
 */
function getImagePath($photoURL) {
    if (empty($photoURL)) {
        return '';
    }
    
    // If already a full URL (http/https), return as is
    if (preg_match('/^https?:\/\//', $photoURL)) {
        return $photoURL;
    }
    
    // Remove leading slash if present
    $photoURL = ltrim($photoURL, '/');
    
    // For Render deployment with Apache alias:
    // - DocumentRoot is set to /var/www/html/Lost&found/htdocs/public
    // - Apache alias /assets points to /var/www/html/Lost&found/htdocs/assets
    // - So "assets/uploads/file.jpg" should be accessed as "/assets/uploads/file.jpg"
    // - This works from any page since it's an absolute path from web root
    
    // Check if path already starts with ../
    if (strpos($photoURL, '../') === 0) {
        // Remove ../ prefix and use absolute path
        $photoURL = str_replace('../', '', $photoURL);
    }
    
    // Ensure path starts with / for absolute path from web root
    if (strpos($photoURL, 'assets/') === 0) {
        return encodeImageUrl('/' . $photoURL);
    }
    
    // If it doesn't start with assets/, add it
    if (strpos($photoURL, '/') !== 0) {
        return encodeImageUrl('/' . $photoURL);
    }
    
    // Otherwise, return as is (already encoded)
    return encodeImageUrl($photoURL);
}

/**
 * Encode image URL for use in HTML src attribute
 * Replaces spaces with %20 and handles other URL encoding
 * 
 * @param string $url The image URL/path
 * @return string Properly encoded URL
 */
function encodeImageUrl($url) {
    if (empty($url)) {
        return '';
    }
    // Replace spaces with %20 for URL encoding
    $url = str_replace(' ', '%20', $url);
    // Use htmlspecialchars for XSS protection
    return htmlspecialchars($url);
}

/**
 * Get placeholder image (SVG data URI)
 * 
 * @return string SVG data URI for "No Image" placeholder
 */
function getPlaceholderImage() {
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='180'%3E%3Crect fill='%23ddd' width='300' height='180'/%3E%3Ctext fill='%23999' font-family='sans-serif' font-size='14' x='50%25' y='50%25' text-anchor='middle' dominant-baseline='middle'%3ENo Image%3C/text%3E%3C/svg%3E";
}

/**
 * Get onerror handler for images (fallback to placeholder)
 * 
 * @return string onerror attribute value
 */
function getImageErrorHandler() {
    $placeholder = getPlaceholderImage();
    return "this.onerror=null; this.src='$placeholder';";
}
