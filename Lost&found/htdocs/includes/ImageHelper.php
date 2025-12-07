<?php
/**
 * Image Helper Functions
 * 
 * Helper functions for properly encoding image URLs and handling missing images
 */

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
?>

