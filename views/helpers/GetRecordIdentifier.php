<?php
/**
 * Clean Url Get Record Identifier
 */

/**
 * @package Omeka\Plugins\CleanUrl\views\helpers
 */
class CleanUrl_View_Helper_GetRecordIdentifier extends Zend_View_Helper_Abstract
{
    /**
     * Return the identifier of a record, if any. It can be sanitized.
     *
     * @param Omeka_Record_AbstractRecord|string $record
     * @param boolean $rawEncoded Sanitize the identifier for http or not.
     * @return string Identifier of the record, if any, else empty string.
     */
    public function getRecordIdentifier($record, $rawEncoded = true)
    {
        // Get the current record from the view if passed as a string.
        if (is_string($record)) {
            $record = $this->view->getCurrentRecord($record);
        }
        if (empty($record)) {
            return '';
        }
        if (!($record instanceof Omeka_Record_AbstractRecord)) {
            throw new Omeka_View_Exception(__('Invalid record passed while getting record URL.'));
        }

        // Use a direct query in order to improve speed.
        $db = get_db();
        $elementId = (integer) get_option('clean_url_identifier_element');
        $bind = array(
            get_class($record),
            $record->id,
        );

        $prefix = get_option('clean_url_identifier_prefix');
        if ($prefix) {
            // TODO Manage the special case where a space is inside the prefix.
            // Keep only the identifier without the configured prefix.
            $prefixLength = strlen($prefix) + 1;
            $sqlSelect = 'SELECT TRIM(SUBSTR(element_texts.text, ' . $prefixLength . '))';
            $sqlWhereText = 'AND element_texts.text LIKE ?';
            $bind[] = $prefix . '%';
        }
        else {
            $sqlSelect = 'SELECT element_texts.text';
            $sqlWhereText = '';
        }

        $sql = "
            $sqlSelect
            FROM {$db->ElementText} element_texts
            WHERE element_texts.element_id = '$elementId'
                AND element_texts.record_type = ?
                AND element_texts.record_id = ?
                $sqlWhereText
            ORDER BY element_texts.id
            LIMIT 1
        ";
        $identifier = $db->fetchOne($sql, $bind);

        return $rawEncoded
            ? rawurlencode($identifier)
            : $identifier;
    }
}
