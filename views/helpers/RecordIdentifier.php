<?php
/**
 * Clean Url Record Identifier
 */

/**
 * @package Omeka\Plugins\CleanUrl\views\helpers
 */
class Omeka_View_Helper_RecordIdentifier extends Zend_View_Helper_Abstract
{
    /**
     * Return the identifier of a record, if any. It can be sanitized.
     *
     * @param Omeka_Record_AbstractRecord|string $record
     * @param boolean $sanitize Sanitize the identifier or not.
     *
     * @return string
     *   Identifier of the record, if any, else empty string.
     *
     * @todo Use one query.
     */
    public function recordIdentifier($record, $sanitize = true)
    {
        // Get the current record from the view if passed as a string.
        if (is_string($record)) {
            $record = $this->view->getCurrentRecord($record);
        }
        if (!($record instanceof Omeka_Record_AbstractRecord)) {
            throw new Omeka_View_Exception(__('Invalid record passed while getting record URL.'));
        }

        $identifiers = $record->getElementTexts('Dublin Core', 'Identifier');
        if (empty($identifiers)) {
            return '';
        }

        // Get all identifiers with the chosen prefix in case they are multiple.
        foreach ($identifiers as $key => $identifier) {
            $identifiers[$key] = $identifier->text;
        }
        $filtered_identifiers = array_values(array_filter($identifiers, 'self::_filteredIdentifier'));
        if (!isset($filtered_identifiers[0])) {
            return '';
        }

        // Keep only the first identifier without the configured prefix.
        $prefix = get_option('clean_url_identifier_prefix');
        $identifier = trim(substr($filtered_identifiers[0], strlen($prefix)));

        // Sanitize the identifier in order to use it securely in a clean url.
        if ($sanitize) {
            $identifier = $this->_sanitizeString(trim($identifier, ' /\\'));
        }

        return $identifier;
    }

    /**
     * Check if an identifier of an item begins with the configured prefix.
     *
     * @param string $identifier
     *   Identifier to check.
     *
     * @return boolean
     *   True if identifier begins with the prefix, false else.
     */
    private function _filteredIdentifier($identifier)
    {
        static $prefix;
        static $prefix_len;

        if ($prefix === null) {
            $prefix = strtolower(get_option('clean_url_identifier_prefix'));
            $prefix_len = strlen($prefix);
        }

        return (strtolower(substr($identifier, 0, $prefix_len)) == $prefix);
    }

    /**
     * Returns a sanitized and unaccentued string for folder or file path.
     *
     * @param string $string The string to sanitize.
     *
     * @return string The sanitized string to use as a folder or a file name.
     */
    private function _sanitizeString($string)
    {
        $space = '';
        $string = trim(strip_tags($string));
        $string = htmlentities($string, ENT_NOQUOTES, 'utf-8');
        $string = preg_replace('#\&([A-Za-z])(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml)\;#', '\1', $string);
        $string = preg_replace('#\&([A-Za-z]{2})(?:lig)\;#', '\1', $string);
        $string = preg_replace('#\&[^;]+\;#', '_', $string);
        $string = preg_replace('/[^[:alnum:]\(\)\[\]_\-\.#~@+:' . $space . ']/', '_', $string);
        return preg_replace('/_+/', '_', $string);
    }
}
