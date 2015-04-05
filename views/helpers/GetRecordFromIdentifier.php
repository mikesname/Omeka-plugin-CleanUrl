<?php
/**
 * Clean Url Get Record From Identifier
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * @package Omeka\Plugins\CleanUrl\views\helpers
 */
class CleanUrl_View_Helper_GetRecordFromIdentifier extends Zend_View_Helper_Abstract
{
    /**
     * Get record from identifier
     *
     * @param string $identifier The identifier of the record to find.
     * @param boolean $withPrefix Optional. If identifier begins with prefix.
     * @param string $recordType Optional. Search a specific record type if any.
     * @param string $return Format of the returned value. can be: 'record',
     * 'type and id' or 'id' (to use only when the type is known).
     * @param boolean $checkPublic Return results depending on users (default)
     * or not. If return format is 'record', check is always done. For files,
     * they are returned only for admin.
     * @return Omeka_Record_AbstractRecord|array|integer|null The record.
     */
    public function getRecordFromIdentifier(
        $identifier,
        $withPrefix = false,
        $recordType = null,
        $return = 'record',
        $checkPublic = true
    ) {
        // Url decode identifiers.
        $identifier = rawurldecode($identifier);
        if (empty($identifier)) {
            return null;
        }

        $db = get_db();
        $bind = array();

        $elementId = (integer) get_option('clean_url_identifier_element');

        if ($recordType) {
            $sqlRecordType = "AND element_texts.record_type = ?";
            $bind[] = $recordType;
            $sqlOrder = 'ORDER BY element_texts.record_id, element_texts.id';
        }
        else {
            $sqlRecordType = '';
            $sqlOrder = "ORDER BY FIELD(element_texts.record_type, 'Collection', 'Item', 'File'), element_texts.record_id, element_texts.id";
        }

        // TODO Use filters for user or use regular methods (get_record_by_id() checks it).
        // TODO Check if file is public here, when needed (currently, this is done after).
        if (($checkPublic || $return == 'record') && !(is_admin_theme() || current_user())) {
            $sqlFromIsPublic = "
                LEFT JOIN {$db->Item} items
                    ON element_texts.record_type = 'Item'
                        AND element_texts.record_id = items.id
                LEFT JOIN {$db->Collection} collections
                    ON element_texts.record_type = 'Collection'
                        AND element_texts.record_id = collections.id
                LEFT JOIN {$db->File} files
                    ON element_texts.record_type = 'File'
                        AND element_texts.record_id = files.id
                        AND files.item_id = items.id
            ";
            $sqlWhereIsPublic = "
                AND ((element_texts.record_type = 'Item' AND items.public = 1)
                    OR (element_texts.record_type = 'Collection' AND collections.public = 1)
                    OR (element_texts.record_type = 'File')
                )
            ";
            $checkPublicFile = true;
        }
        else {
            $sqlFromIsPublic = '';
            $sqlWhereIsPublic = '';
            $checkPublicFile = false;
        }

        if ($withPrefix) {
            // If the table is case sensitive, lower-case the search.
            if (get_option('clean_url_case_insensitive')) {
                $identifier = strtolower($identifier);
                $sqlWhereText = "AND LOWER(element_texts.text) = ?";
            }
            // Default.
            else {
                $sqlWhereText = 'AND element_texts.text = ?';
            }
            $bind[] = $identifier;
        }
        else {
            $prefix = get_option('clean_url_identifier_prefix');
            // If the table is case sensitive, lower-case the search.
            if (get_option('clean_url_case_insensitive')) {
                $prefix = strtolower($prefix);
                $identifier = strtolower($identifier);
                $sqlWhereText = 'AND (LOWER(element_texts.text) = ? OR LOWER(element_texts.text) = ?)';
            }
            // Default.
            else {
                $sqlWhereText = 'AND (element_texts.text = ? OR element_texts.text = ?)';
            }
            $bind[] = $prefix . $identifier;
            // Check with a space between prefix and identifier too.
            $bind[] = $prefix . ' ' . $identifier;
        }

        $sql = "
            SELECT element_texts.record_type, element_texts.record_id
            FROM {$db->ElementText} element_texts
                $sqlFromIsPublic
            WHERE element_texts.element_id = '$elementId'
                $sqlRecordType
                $sqlWhereText
                $sqlWhereIsPublic
            $sqlOrder
            LIMIT 1
        ";
        $result = $db->fetchRow($sql, $bind);

        if ($result) {
            switch ($return) {
                case 'record':
                default:
                    return get_record_by_id($result['record_type'], $result['record_id']);

                case 'type and id':
                    if ($checkPublicFile && $result['record_type'] == 'File') {
                        return $this->_checkPublicFile($result['record_id'])
                            ? $result
                            : null;
                    }
                    return $result;

                case 'id':
                    if ($checkPublicFile && $result['record_type'] == 'File') {
                        return $this->_checkPublicFile($result['record_id'])
                            ? $result['record_id']
                            : null;
                    }
                    return $result['record_id'];
            }
        }
    }

    /**
     * Check if the item of a file is public.
     *
     * @todo Check public file via a direct query in getRecordFromIdentifier().
     * @param integer $id File id to check.
     * @return boolean
     */
    private function _checkPublicFile($id)
    {
        return (boolean) get_record_by_id('File', $id);
    }
}
