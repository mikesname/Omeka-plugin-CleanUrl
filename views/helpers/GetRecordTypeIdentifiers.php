<?php
/**
 * Clean Url Get Record Type Identifiers
 */

/**
 * @package Omeka\Plugins\CleanUrl\views\helpers
 */
class CleanUrl_View_Helper_GetRecordTypeIdentifiers extends Zend_View_Helper_Abstract
{
    /**
     * Return identifiers for a record type, if any. It can be sanitized.
     *
     * @param string $recordType Should be "Collection", "Item" or "File".
     * @param boolean $rawUrlEncode Sanitize the identifiers for http or not.
     * @return array List of identifiers.
     */
    public function getRecordTypeIdentifiers($recordType, $rawUrlEncode = true)
    {
        if (!in_array($recordType, array('Collection', 'Item', 'File'))) {
            return array();
        }

        // Use a direct query in order to improve speed.
        $db = get_db();
        $elementId = (integer) get_option('clean_url_identifier_element');

        $bind = array();
        $bind[] = $recordType;

        $prefix = get_option('clean_url_identifier_prefix');
        if ($prefix) {
            // Keep only the identifier without the configured prefix.
            $prefixLenght = strlen($prefix) + 1;
            $sqlSelect = 'SELECT TRIM(SUBSTR(element_texts.text, ' . $prefixLenght . '))';
            $sqlWereText = 'AND element_texts.text LIKE ?';
            $bind[] = $prefix . '%';
        }
        else {
            $sqlSelect = 'SELECT element_texts.text';
            $sqlWereText = '';
        }

        $sql = "
            $sqlSelect
            FROM {$db->ElementText} element_texts
            WHERE element_texts.element_id = '$elementId'
                AND element_texts.record_type = ?
                $sqlWereText
            ORDER BY element_texts.record_id ASC, element_texts.id ASC
        ";
        $result = $db->fetchCol($sql, $bind);

        return $rawUrlEncode
            ? array_map('rawurlencode', $result)
            : $result;
    }
}
