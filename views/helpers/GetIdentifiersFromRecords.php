<?php
/**
 * Clean Url Get Identifiers From Records
 */

/**
 * @package Omeka\Plugins\CleanUrl\views\helpers
 */
class CleanUrl_View_Helper_GetIdentifiersFromRecords extends Zend_View_Helper_Abstract
{
    // The max number of the records to create a temporary table.
    const CHUNK_RECORDS = 10000;

    /**
     * Get identifiers from records.
     *
     * @todo Return public files when public is checked.
     *
     * @param array|Record $records A list of records as object or as array of ids.
     * Types shouldn't be mixed. If object, it should be a record.
     * @param string $recordType The record type if $records is an array.
     * @param boolean $checkPublic Return results depending on users (default)
     * or not. If return format is 'record', check is always done. For files,
     * they are returned only for admin.
     * @return array|string List of strings with id as key and identifier as value.
     * Duplicates are not returned. If a single record is provided, return a
     * single string. Order is not kept.
     */
    public function getIdentifiersFromRecords($records, $recordType = null, $checkPublic = true)
    {
        // Check the list of records.
        if (empty($records)) {
            return;
        }

        $one = is_object($records);
        if ($one) {
            $records = array($records);
        }

        $first = reset($records);
        if (is_object($first)) {
            $recordType = get_class($first);
            $records = array_map(function($v) {
                return $v->id;
            }, $records);
        }
        // Checks records in an array.
        else {
            $records = array_map('intval', $records);
        }

        $records = array_filter($records);
        if (empty($records)) {
            return;
        }

        if (!in_array($recordType, array('File', 'Item', 'Collection'))) {
            return;
        }

        $elementId = (integer) get_option('clean_url_identifier_element');
        $prefix = get_option('clean_url_identifier_prefix');

        // Get the list of identifiers.
        $db = get_db();
        $table = $db->getTable('ElementText');
        $select = $table
            ->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->where('element_texts.element_id = ?', $elementId)
            ->where('element_texts.record_type = ?', $recordType)
            // Only one identifier by record.
            ->group('element_texts.record_id')
            ->order(array('element_texts.record_id ASC', 'element_texts.id ASC'));

        if ($prefix) {
            $select
                ->columns(array(
                    // Should be the first column.
                    'id' => 'element_texts.record_id',
                    'identifier' => new Zend_Db_Expr('TRIM(SUBSTR(element_texts.text, ' . (strlen($prefix) + 1) . '))'),
                ))
                ->where('element_texts.text LIKE ?', $prefix . '%');
        }
        // No prefix.
        else {
            $select
                ->columns(array(
                    // Should be the first column.
                    'id' => 'element_texts.record_id',
                    'identifier' => 'element_texts.text',
                ));
        }

        // TODO Use filters for user or use regular methods (get_record_by_id() checks it).
        if ($checkPublic && !(is_admin_theme() || current_user())) {
            switch ($recordType) {
                case 'File':
                    $select
                        ->joinLeft(
                            array('_files' => $db->File),
                            '_files.id = element_texts.record_id AND element_texts.record_type = "File"',
                            array()
                        )
                        ->joinLeft(
                            array('_records' => $db->Item),
                            '_records.id = _files.item_id',
                            array()
                        );
                    break;

                case 'Item':
                    $select
                        ->joinLeft(
                            array('_records' => $db->Item),
                            '_records.id = element_texts.record_id AND element_texts.record_type = "Item"',
                            array()
                        );
                    break;

                case 'Collection':
                    $select
                        ->joinLeft(
                            array('_records' => $db->Collection),
                            '_records.id = element_texts.record_id AND element_texts.record_type = "Collection"',
                            array()
                        );
                    break;
            }
            $select->where('_records.public = 1');
        }

        if ($one) {
            $select->limit(1);
        }

        // Create a temporary table when the number of records is very big.
        $tempTable = count($records) > self::CHUNK_RECORDS;
        if ($tempTable) {
            $query = 'DROP TABLE IF EXISTS temp_records;';
            $stmt = $db->query($query);
            $query = 'CREATE TEMPORARY TABLE temp_records (id INT UNSIGNED NOT NULL);';
            $stmt = $db->query($query);
            foreach (array_chunk($records, self::CHUNK_RECORDS) as $chunk) {
                $query = 'INSERT INTO temp_records VALUES(' . implode('),(', $chunk) . ');';
                $stmt = $db->query($query);
            }
            $select
                ->joinInner(
                    array('temp_records' => 'temp_records'),
                    'temp_records.id = element_texts.record_id',
                    array()
                );
            // No where condition.
        }
        // The number of records is reasonable.
        else {
            $select
                ->where('element_texts.record_id IN (?)', $records);
        }

        $result = $table->fetchPairs($select);
        return $one
            ? array_shift($result)
            : $result;
    }
}
