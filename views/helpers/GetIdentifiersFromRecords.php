<?php
/**
 * Clean Url Get Identifiers From Records
 */

/**
 * @package Omeka\Plugins\CleanUrl\views\helpers
 */
class CleanUrl_View_Helper_GetIdentifiersFromRecords extends Zend_View_Helper_Abstract
{
    private static $_prefix;

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

        $one = false;
        if (is_object($records)) {
            $one = true;
            $records = array($records);
        }

        $first = reset($records);
        if (is_object($first)) {
            $recordType = get_class($first);
            $records = array_map(function($v) {
                return $v->id;
            }, $records);
        }

        $records = array_filter($records);
        if (empty($records)) {
            return;
        }

        if (!in_array($recordType, array('File', 'Item', 'Collection'))) {
            return;
        }

        $elementId = (integer) get_option('clean_url_identifier_element');

        // Get the list of identifiers.
        $db = get_db();
        $table = $db->getTable('ElementText');
        $alias = $table->getTableAlias();
        $select = $table
            ->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->where($alias . '.element_id = ?', $elementId)
            ->where($alias . '.record_type = ?', $recordType)
            ->where($alias . '.record_id IN (?)', $records)
            // Only one identifier by record.
            ->group($alias . '.record_id')
            ->order($alias . '.record_id ASC');

        $prefix = get_option('clean_url_identifier_prefix');
        if ($prefix) {
            $select
                ->columns(array(
                    // Should be the first column.
                    'id' => $alias . '.record_id',
                    'identifier' => new Zend_Db_Expr('TRIM(SUBSTR(' . $alias . '.text, ' . (strlen($prefix) + 1) . '))'),
                ))
                ->where($alias . '.text LIKE ?', $prefix . '%');
        }
        // No prefix.
        else {
            $select
                ->columns(array(
                    // Should be the first column.
                    $alias . '.record_id AS id',
                    $alias . '.text AS identifier',
                ));
        }

        // TODO Use filters for user or use regular methods (get_record_by_id() checks it).
        if ($checkPublic && !(is_admin_theme() || current_user())) {
            switch ($recordType) {
                case 'File':
                    $select
                        ->joinLeft(
                            array('_files' => $db->File),
                            '_files.id = ' . $alias . '.record_id AND ' . $alias . '.record_type = "File"',
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
                            '_records.id = ' . $alias . '.record_id AND ' . $alias . '.record_type = "Item"',
                            array()
                        );
                    break;

                case 'Collection':
                    $select
                        ->joinLeft(
                            array('_records' => $db->Collection),
                            '_records.id = ' . $alias . '.record_id AND ' . $alias . '.record_type = "Collection"',
                            array()
                        );
                    break;
            }
            $select->where('_records.public = 1');
        }

        if ($one) {
            $select->limit(1);
        }

        $result = $table->fetchPairs($select);
        return $one
            ? array_shift($result)
            : $result;
    }
}
