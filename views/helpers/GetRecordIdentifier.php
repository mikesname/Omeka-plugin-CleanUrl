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
     * @param Omeka_Record_AbstractRecord|string|array $record If array, the
     * type and the id: ['type' => 'Item', 'id' => 1].
     * @param boolean $rawEncoded Sanitize the identifier for http or not.
     * @return string Identifier of the record, if any, else empty string.
     */
    public function getRecordIdentifier($record, $rawEncoded = true)
    {
        if (empty($record)) {
            return '';
        }

        // Prepare the record if passed as an array.
        if (is_array($record)) {
            if (empty($record['type']) || empty($record['id'])) {
                throw new Omeka_View_Exception(__('Invalid record passed as array while getting record URL.'));
            }
            $recordType = $record['type'];
            $recordId = (integer) $record['id'];
        }
        // Get the current record from the view if passed as a string.
        elseif (is_string($record)) {
            $record = $this->view->getCurrentRecord($record);
            if (empty($record)) {
                throw new Omeka_View_Exception(__('Invalid record passed as string while getting record URL.'));
            }
            $recordType = get_class($record);
            $recordId = $record->id;
        }
        // The record is an object.
        elseif (is_object($record)) {
            if (!($record instanceof Omeka_Record_AbstractRecord)) {
                throw new Omeka_View_Exception(__('Invalid record passed as object while getting record URL.'));
            }
            $recordType = get_class($record);
            $recordId = $record->id;
        }
        // There is an error in parameters.
        else {
            throw new Omeka_View_Exception(__('Invalid record passed while getting record URL.'));
        }

        // Use a direct query in order to improve speed.
        $db = get_db();
        $elementId = (integer) get_option('clean_url_identifier_element');
        $bind = array($recordType, $recordId);

        $prefix = get_option('clean_url_identifier_prefix');
        $checkUnspace = false;
        if ($prefix) {
            $bind[] = $prefix . '%';
            // Check prefix with a space and a no-break space.
            $unspace = str_replace(array(' ', ' '), '', $prefix);
            if ($prefix != $unspace && get_option('clean_url_identifier_unspace')) {
                $checkUnspace = true;
                $sqlWhereText = 'AND (element_texts.text LIKE ? OR element_texts.text LIKE ?)';
                $bind[] = $unspace . '%';
            }
            // Normal prefix.
            else {
                $sqlWhereText = 'AND element_texts.text LIKE ?';
            }
        }
        // No prefix.
        else {
            $sqlWhereText = '';
        }

        $sql = "
            SELECT element_texts.text
            FROM {$db->ElementText} element_texts
            WHERE element_texts.element_id = '$elementId'
                AND element_texts.record_type = ?
                AND element_texts.record_id = ?
                $sqlWhereText
            ORDER BY element_texts.id
            LIMIT 1
        ";
        $identifier = $db->fetchOne($sql, $bind);

        // Keep only the identifier without the configured prefix.
        if ($identifier) {
            if ($prefix) {
                $length = $checkUnspace && strpos($identifier, $unspace) === 0
                    // May be a prefix with space.
                    ? strlen($unspace)
                    // Normal prefix.
                    : strlen($prefix);
                $identifier = trim(substr($identifier, $length));
            }
            return $rawEncoded
                ? rawurlencode($identifier)
                : $identifier;
        }

        return '';
    }
}
