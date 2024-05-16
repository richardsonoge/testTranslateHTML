<?php
require_once 'vendor/autoload.php';

use Stichoza\GoogleTranslate\GoogleTranslate;

$tr = new GoogleTranslate('fr');

// Function to load HTML content and handle errors
function loadHTMLContent($html) {
    $dom = new DOMDocument();
    // Suppress warnings for invalid HTML
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    // Clear errors
    libxml_clear_errors();
    return $dom;
}

function getPageContainerContent($html) {
    $dom = loadHTMLContent($html);

    $pageContainer = $dom->getElementById('page-container');
    if ($pageContainer) {
        $content = '';
        foreach ($pageContainer->childNodes as $node) {
            $content .= $dom->saveHTML($node);
        }
        return $content;
    } else {
        return "Page container not found.";
    }
}

function replace_page_container($html_content, $new_content) {
    $dom = loadHTMLContent($html_content);
    
    $page_container = $dom->getElementById('page-container');
    
    if ($page_container) {
        $page_container->nodeValue = '';
        
        $new_dom = loadHTMLContent($new_content);
        
        foreach ($new_dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
            $imported_node = $dom->importNode($node, true);
            $page_container->appendChild($imported_node);
        }
        
        return $dom->saveHTML();
    } else {
        return $html_content;
    }
}

function extractTextFromElement($element) {
    $texts = [];

    foreach ($element->childNodes as $childNode) {
        if ($childNode->nodeType === XML_TEXT_NODE) {
            $texts[] = $childNode->nodeValue;
        } elseif ($childNode->nodeType === XML_ELEMENT_NODE) {
            // Recursively extract text content from nested elements
            $texts = array_merge($texts, extractTextFromElement($childNode));
        }
    }

    return $texts;
}

function extractTextFromHTMLToArray($html) {
    // Check if $html is null
    if ($html === null) {
        return [];
    }

    // Create a DOMDocument
    $dom = new DOMDocument;

    // Load HTML content, ignoring errors
    libxml_use_internal_errors(true);
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // Remove script and style elements
    $scriptNodes = $dom->getElementsByTagName('script');
    foreach ($scriptNodes as $scriptNode) {
        $scriptNode->parentNode->removeChild($scriptNode);
    }

    $styleNodes = $dom->getElementsByTagName('style');
    foreach ($styleNodes as $styleNode) {
        $styleNode->parentNode->removeChild($styleNode);
    }

    // Extract only visible text content
    $texts = [];
    $body = $dom->getElementsByTagName('body')->item(0);

    if ($body) {
        foreach ($body->childNodes as $childNode) {
            if ($childNode->nodeType === XML_TEXT_NODE) {
                $texts[] = $childNode->nodeValue;
            } elseif ($childNode->nodeType === XML_ELEMENT_NODE) {
                // Extract text content from nested elements
                $texts = array_merge($texts, extractTextFromElement($childNode));
            }
        }
    }

    // Trim whitespace from each text fragment
    $texts = array_map('trim', $texts);

    // Remove empty fragments
    $texts = array_filter($texts);

    return array_values($texts);
}

function htmlToArray($htmlContent) {  
    if (!empty($htmlContent)) {
        $texts = extractTextFromHTMLToArray($htmlContent);

        // Remove empty values from the array
        $arrayTxt = array_filter($texts);

        // Re-index the array if needed
        $arrayTxts = array_values($texts);

        return $texts;
    }   
}

/**
 * Remove spaces between HTML tags and handle entity characters.
 *
 * @param string $html The HTML content.
 * @return string The HTML content with spaces removed between HTML tags and adjusted entity characters.
 */
function removeSpacesBetweenTags($html) {
    $search = [
        '< ',
        ' >',
    ];
    $replace = [
        '<',
        '>',
    ];
    $html = str_replace($search, $replace, $html);

    return $html;
}

function translate_html($html, $originalHTMLToArray, $translateHTMLToArray) {
    // 
}

$htmlContent = file_get_contents('fss4.html');
$getContent = getPageContainerContent($htmlContent);

$stringSplit = str_split($getContent, 3700);
$countStringSplit = count($stringSplit);

$translateHTML = '';

for ($i = 0; $i < $countStringSplit; $i++) {
    // Translate each chunk of html
    $translateHTML .= $tr->translate($stringSplit[$i]);
}

$getCorrectTranslateHtml = preg_replace('/\/\s/', '/', $translateHTML);

$getCorrectTranslateHtml = removeSpacesBetweenTags($getCorrectTranslateHtml);

$new_html = replace_page_container($htmlContent, $getCorrectTranslateHtml);
$new_html = html_entity_decode($new_html);
$new_html = removeSpacesBetweenTags($new_html);
file_put_contents('fss4_translated.html', $new_html);

// $translateHTMLContent = file_get_contents('content2.html');

// $originalHTMLToArray = htmlToArray($htmlContent);
// $translateHTMLToArray = htmlToArray($translateHTMLContent);