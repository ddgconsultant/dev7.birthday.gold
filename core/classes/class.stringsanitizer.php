<?php

class StringSanitizer
{
    /**
     * Remove all class attributes from HTML string
     */
    public static function removeClasses($html)
    {
        // Remove class attributes using regex
        return preg_replace('/\sclass\s*=\s*["\'][^"\']*["\']/i', '', $html);
    }
    
    /**
     * Remove all style attributes from HTML string
     */
    public static function removeInlineStyles($html)
    {
        // Remove style attributes
        return preg_replace('/\sstyle\s*=\s*["\'][^"\']*["\']/i', '', $html);
    }
    
    /**
     * Remove specific attributes from HTML
     */
    public static function removeAttributes($html, $attributes = ['class', 'style', 'id'])
    {
        $pattern = '/\s(' . implode('|', $attributes) . ')\s*=\s*["\'][^"\']*["\']/i';
        return preg_replace($pattern, '', $html);
    }
    
    /**
     * Strip all HTML tags except allowed ones
     */
    public static function stripTagsExcept($html, $allowedTags = '<p><br><strong><em><a>')
    {
        return strip_tags($html, $allowedTags);
    }
    
    /**
     * Clean HTML while preserving structure but removing dangerous attributes
     */
    public static function cleanHtml($html, $options = [])
    {
        $defaults = [
            'allowed_tags' => '<p><br><strong><em><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><div><span>',
            'remove_attributes' => ['class', 'style', 'id', 'onclick', 'onmouseover', 'onload', 'onerror'],
            'allowed_attributes' => ['href', 'title', 'alt', 'src']
        ];
        
        $options = array_merge($defaults, $options);
        
        // First strip to allowed tags only
        $html = strip_tags($html, $options['allowed_tags']);
        
        // Remove dangerous attributes
        $html = self::removeAttributes($html, $options['remove_attributes']);
        
        // Remove javascript: and other dangerous protocols from href/src
        $html = preg_replace('/\s(href|src)\s*=\s*["\']?\s*javascript:[^"\']*["\']?/i', '', $html);
        
        return $html;
    }
    
    /**
     * Advanced sanitization using DOMDocument for more precise control
     */
    public static function sanitizeWithDOM($html, $options = [])
    {
        $defaults = [
            'allowed_tags' => ['p', 'br', 'strong', 'em', 'a', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'div', 'span'],
            'allowed_attributes' => [
                'a' => ['href', 'title'],
                'img' => ['src', 'alt', 'width', 'height']
            ],
            'remove_empty' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        // Create DOMDocument
        $dom = new DOMDocument();
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        // Load HTML with UTF-8 encoding
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Clear errors
        libxml_clear_errors();
        
        // Process all elements
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*');
        
        foreach ($elements as $element) {
            // Remove disallowed tags
            if (!in_array(strtolower($element->nodeName), $options['allowed_tags'])) {
                // Replace with its children
                while ($element->firstChild) {
                    $element->parentNode->insertBefore($element->firstChild, $element);
                }
                $element->parentNode->removeChild($element);
                continue;
            }
            
            // Process attributes
            $attributes = [];
            foreach ($element->attributes as $attr) {
                $attributes[] = $attr->nodeName;
            }
            
            foreach ($attributes as $attrName) {
                $allowed = false;
                
                // Check if attribute is allowed for this tag
                if (isset($options['allowed_attributes'][$element->nodeName])) {
                    if (in_array($attrName, $options['allowed_attributes'][$element->nodeName])) {
                        $allowed = true;
                    }
                } elseif (isset($options['allowed_attributes']['*'])) {
                    if (in_array($attrName, $options['allowed_attributes']['*'])) {
                        $allowed = true;
                    }
                }
                
                if (!$allowed) {
                    $element->removeAttribute($attrName);
                } else {
                    // Additional validation for href/src
                    if (in_array($attrName, ['href', 'src'])) {
                        $value = $element->getAttribute($attrName);
                        if (preg_match('/^\s*javascript:/i', $value)) {
                            $element->removeAttribute($attrName);
                        }
                    }
                }
            }
            
            // Remove empty elements if specified
            if ($options['remove_empty'] && trim($element->nodeValue) === '' && !$element->hasChildNodes()) {
                $element->parentNode->removeChild($element);
            }
        }
        
        // Get cleaned HTML
        $cleanHtml = $dom->saveHTML();
        
        // Remove the XML declaration we added
        $cleanHtml = str_replace('<?xml encoding="UTF-8">', '', $cleanHtml);
        
        return $cleanHtml;
    }
    
    /**
     * Remove all HTML and decode entities
     */
    public static function plainText($html)
    {
        // Decode HTML entities
        $text = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        
        // Strip all tags
        $text = strip_tags($text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Enhanced plain text extraction with better formatting
     */
    public static function emailToPlainText($html)
    {
        // Add line breaks before block elements for better readability
        $html = preg_replace('/<(p|div|br|h[1-6]|li|tr|table)[^>]*>/i', "\n$0", $html);
        
        // Add double line break before headings
        $html = preg_replace('/<h[1-6][^>]*>/i', "\n\n$0", $html);
        
        // Remove script and style content completely
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        
        // Convert links to text with URL
        $html = preg_replace('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i', '$2 [$1]', $html);
        
        // Decode HTML entities
        $text = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        
        // Strip remaining tags
        $text = strip_tags($text);
        
        // Convert multiple spaces to single space
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        // Convert multiple newlines to double newline
        $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text);
        
        // Trim each line
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);
        
        // Remove empty lines at start and end
        return trim($text);
    }
    
    /**
     * Escape HTML for safe output
     */
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

// Example usage for text extraction from HTML email:
/*
// Load your HTML email content
$html_email = file_get_contents('bjs-email.html');

// Method 1: Basic plain text extraction (everything on one line)
$text_basic = StringSanitizer::plainText($html_email);
echo "BASIC TEXT EXTRACTION:\n";
echo $text_basic;

// Method 2: Enhanced email text extraction (preserves some structure)
$text_enhanced = StringSanitizer::emailToPlainText($html_email);
echo "\n\nENHANCED EMAIL TEXT EXTRACTION:\n";
echo $text_enhanced;

// Enhanced output would look like:
/*
Reserve your space and let us do the cooking.

HELLO GURDEEP! MEMBER #: QIPMVHR
CURRENT POINTS: 0 | REWARDS AVAILABLE: 1

A Feast to Remember

Go all in on flavor and fun. Our customizable buffets make it easy to turn any 
get-together into something worth craving, especially when you're serving up 
entrees like Parmesan-Crusted Chicken and Slow-Roasted Sirloin. Just bring 
your appetite—we'll bring the extra.

PREFERRED LOCATION:
3955 Thousand Oaks Boulevard, Westlake Village, CA 91362

© 2025 BJ's Restaurants, Inc.,
7755 Center Ave., Ste. 300, Huntington Beach, CA 92647, United States

For Loyalty ID: QIPMVHR

Restaurant hours are subject to change day-to-day. Go to your preferred 
location page to confirm restaurant operating hours.

This email was sent to you by BJ's Restaurants, Inc. If you no longer wish 
to receive these emails, please unsubscribe here.
*/
