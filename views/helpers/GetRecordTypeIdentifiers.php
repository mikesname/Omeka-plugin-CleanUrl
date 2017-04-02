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

        $elementId = (integer) get_option('clean_url_identifier_element');
        $prefix = get_option('clean_url_identifier_prefix');

        // Use a direct query in order to improve speed.
        $db = get_db();
        $table = $db->getTable('ElementText');
        $select = $table
            ->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->where('element_texts.element_id = ?', $elementId)
            ->where('element_texts.record_type = ?', $recordType)
            ->order(array('element_texts.record_id ASC', 'element_texts.id ASC'));

        if ($prefix) {
            $select
                ->columns(array(
                    new Zend_Db_Expr('TRIM(SUBSTR(element_texts.text, ' . (strlen($prefix) + 1) . '))'),
                ))
                ->where('element_texts.text LIKE ?', $prefix . '%');
        }
        // No prefix.
        else {
            $select
                ->columns(array(
                    'element_texts.text',
                ));
        }

        $result = $table->fetchCol($select);
        return $rawUrlEncode
            ? array_map('rawurlencode', $result)
            : $result;
    }
}
