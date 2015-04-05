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
     * @param boolean $rawEncoded Sanitize the identifier for http or not.
     * @return array Associative array of record id and identifiers.
     */
    public function getRecordTypeIdentifiers($recordType, $rawEncoded = true)
    {
        if (!in_array($recordType, array('Collection', 'Item', 'File'))) {
            return array();
        }

        // Use a direct query in order to improve speed.
        $db = get_db();
        $elementId = (integer) get_option('clean_url_identifier_element');
        $bind = array();

        $prefix = get_option('clean_url_identifier_prefix');
        if ($prefix) {
            // Keep only the identifier without the configured prefix.
            $prefixLenght = strlen($prefix) + 1;
            $sqlSelect = 'SELECT element_texts.record_id, TRIM(SUBSTR(element_texts.text, ' . $prefixLenght . '))';
            $sqlWereText = 'AND element_texts.text LIKE ?';
            $bind[] = $prefix . '%';
        }
        else {
            $sqlSelect = 'SELECT element_texts.record_id, element_texts.text';
            $sqlWereText = '';
        }

        // The "order by id DESC" allows to get automatically the first row in
        // php result and avoids a useless subselect in sql (useless because in
        // almost all cases, there is only one identifier).
        $sql = "
            $sqlSelect
            FROM {$db->ElementText} element_texts
            WHERE element_texts.element_id = '$elementId'
                AND element_texts.record_type = '$recordType'
                $sqlWereText
            ORDER BY element_texts.record_id, element_texts.id DESC
        ";
        $result = $db->fetchPairs($sql, $bind);

        return $rawEncoded
            ? array_map('rawurlencode', $result)
            : $result;
    }
}
