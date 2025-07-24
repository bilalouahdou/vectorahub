<?php
/**
 * SVG Sanitizer
 * Removes potentially dangerous elements from SVG files
 */

class SVGSanitizer {
    private $dangerousElements = [
        'script', 'object', 'embed', 'iframe', 'frame', 'frameset',
        'applet', 'base', 'link', 'meta', 'style'
    ];
    
    private $dangerousAttributes = [
        'onload', 'onerror', 'onclick', 'onmouseover', 'onmouseout',
        'onmousedown', 'onmouseup', 'onkeydown', 'onkeyup', 'onkeypress',
        'onfocus', 'onblur', 'onchange', 'onsubmit', 'onreset',
        'javascript:', 'vbscript:', 'data:', 'file:'
    ];
    
    /**
     * Sanitize SVG content
     */
    public function sanitize($svgContent) {
        try {
            // Load SVG into DOMDocument
            $dom = new DOMDocument();
            $dom->loadXML($svgContent, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR);
            
            // Remove dangerous elements
            $this->removeDangerousElements($dom);
            
            // Remove dangerous attributes
            $this->removeDangerousAttributes($dom);
            
            // Remove external references
            $this->removeExternalReferences($dom);
            
            // Return sanitized SVG
            return $dom->saveXML();
            
        } catch (Exception $e) {
            error_log("SVG Sanitization error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove dangerous elements
     */
    private function removeDangerousElements($dom) {
        foreach ($this->dangerousElements as $tagName) {
            $elements = $dom->getElementsByTagName($tagName);
            $elementsToRemove = [];
            
            foreach ($elements as $element) {
                $elementsToRemove[] = $element;
            }
            
            foreach ($elementsToRemove as $element) {
                $element->parentNode->removeChild($element);
            }
        }
    }
    
    /**
     * Remove dangerous attributes
     */
    private function removeDangerousAttributes($dom) {
        $xpath = new DOMXPath($dom);
        $allElements = $xpath->query('//*');
        
        foreach ($allElements as $element) {
            $attributesToRemove = [];
            
            foreach ($element->attributes as $attribute) {
                $attrName = strtolower($attribute->name);
                $attrValue = strtolower($attribute->value);
                
                // Check for dangerous attribute names
                foreach ($this->dangerousAttributes as $dangerous) {
                    if (strpos($attrName, $dangerous) !== false || 
                        strpos($attrValue, $dangerous) !== false) {
                        $attributesToRemove[] = $attribute->name;
                        break;
                    }
                }
            }
            
            foreach ($attributesToRemove as $attrName) {
                $element->removeAttribute($attrName);
            }
        }
    }
    
    /**
     * Remove external references
     */
    private function removeExternalReferences($dom) {
        $xpath = new DOMXPath($dom);
        
        // Remove external stylesheets
        $links = $xpath->query('//link[@rel="stylesheet"]');
        foreach ($links as $link) {
            $link->parentNode->removeChild($link);
        }
        
        // Remove external images
        $images = $xpath->query('//image[@href]');
        foreach ($images as $image) {
            $href = $image->getAttribute('href');
            if (strpos($href, 'http') === 0 || strpos($href, '//') === 0) {
                $image->parentNode->removeChild($image);
            }
        }
    }
    
    /**
     * Validate SVG file
     */
    public function isValidSVG($filePath) {
        $content = file_get_contents($filePath);
        
        // Check if it's actually SVG
        if (strpos($content, '<svg') === false) {
            return false;
        }
        
        // Check for XML validity
        $dom = new DOMDocument();
        if (!@$dom->loadXML($content)) {
            return false;
        }
        
        // Check for dangerous content
        foreach ($this->dangerousElements as $element) {
            if (strpos($content, "<$element") !== false) {
                return false;
            }
        }
        
        return true;
    }
}
?>
