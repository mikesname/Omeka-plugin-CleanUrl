<?php
/**
 * Clean Url Get Records From Identifiers
 */

/**
 * @package Omeka\Plugins\CleanUrl\views\helpers
 */
class CleanUrl_View_Helper_GetRecordsFromIdentifiers extends Zend_View_Helper_Abstract
{
    private static $_prefix;

    /**
     * Get records from raw encoded identifiers.
     *
     * Currently, files records are returned only in admin view.
     *
     * @todo Return public files when public is checked.
     *
     * @param string|array $identifiers One or a list of identifiers. May
     * contains the prefix.
     * @param boolean $withPrefix Indicates if identifier contains the prefix.
     * @param string $recordType Optional. Search a specific record type if any.
     * @param string $return Format of the returned value. can be: 'record',
     * 'type and id' or 'id' (to use only when the type is known).
     * @param boolean $checkPublic Return results depending on users (default)
     * or not. If return format is 'record', check is always done. For files,
     * they are returned only for admin.
     * @return Omeka_Record_AbstractRecord|array|integer|null
     * Found record, or ordered records array, or null. Duplicates are not
     * returned.
     */
    public function getRecordsFromIdentifiers(
        $identifiers,
        $withPrefix = true,
        $recordType = null,
        $return = 'record',
        $checkPublic = true
    ) {
        $one = false;
        if (is_string($identifiers)) {
            $one = true;
            $identifiers = array($identifiers);
        }

        // Url decode identifiers.
        $identifiers = array_map('rawurldecode', $identifiers);
        $identifiers = array_filter($identifiers);
        if (empty($identifiers)) {
            return null;
        }

        $db = get_db();
        $bind = array();

        $elementId = (integer) get_option('clean_url_identifier_element');

        if ($recordType) {
            $sqlRecordType = "AND element_texts.record_type = ?";
            $bind[] = $recordType;
        }
        else {
            $sqlRecordType = '';
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
        }
        else {
            $sqlFromIsPublic = '';
            $sqlWhereIsPublic = '';
        }

        if (!$withPrefix) {
            self::$_prefix = get_option('clean_url_identifier_prefix');
            // Check with a space between prefix and identifier too.
            $ids = array_map('self::_addPrefixToIdentifier', $identifiers);
            $identifiers = array_merge($ids, array_map('self::_addPrefixSpaceToIdentifier', $identifiers));
            // Check prefix with a space and a no-break space.
            if (get_option('clean_url_identifier_unspace')) {
                $unspace = str_replace(array(' ', ' '), '', self::$_prefix);
                if (self::$_prefix != $unspace) {
                    $identifiers = array_merge($identifiers, array_map('self::_addUnspacedPrefixToIdentifier', $identifiers));
                    $identifiers = array_merge($identifiers, array_map('self::_addUnspacedPrefixSpaceToIdentifier', $identifiers));
                }
            }
        }

        // TODO Secure bind for identifiers.
        // If the table is case sensitive, lower-case the search.
        if (get_option('clean_url_case_insensitive')) {
            $identifiers = array_map('strtolower', $identifiers);
            $quoted = $db->quote($identifiers);
            $sqlWhereText = "AND LOWER(element_texts.text) IN ($quoted)";
        }
        // Default.
        else {
            $quoted = $db->quote($identifiers);
            $sqlWhereText = "AND element_texts.text IN ($quoted)";
        }

        $sqlLimit = $one ? 'LIMIT 1' : '';

        $sql = "
            SELECT element_texts.record_type, element_texts.record_id
            FROM {$db->ElementText} element_texts
                $sqlFromIsPublic
            WHERE element_texts.element_id = '$elementId'
                $sqlRecordType
                $sqlWhereText
                $sqlWhereIsPublic
            ORDER BY
                FIELD(element_texts.text, $commaIdentifiers)
            $sqlLimit
        ";
        $results = $db->fetchAll($sql, $bind);

        if ($results) {
            switch ($return) {
                case 'record':
                default:
                    foreach ($results as $key => $result) {
                        $results[$key] = get_record_by_id($result['record_type'], $result['record_id']);
                    }
                    // Remove private files (empty now) if needed.
                    $results = array_filter($results);
                    break;

                case 'type and id':
                    if ($checkPublic) {
                        $results = array_filter($results, 'self::_removePrivateFile');
                    }
                    break;

                case 'id':
                    if ($checkPublic) {
                        $results = array_filter($results, 'self::_removePrivateFile');
                    }
                    foreach ($results as $key => $result) {
                        $results[$key] = $result['record_id'];
                    }
                    break;
            }
            return $one
                ? array_shift($results)
                : $results;
        }

        // Return the records via the Omeka id.
        elseif ($recordType) {
            $ids = array_filter(array_map('intval', $identifiers));
            if ($ids) {
                $table = $db->getTable($recordType);
                $alias = $table->getTableAlias();
                $records = $table->findBySql($alias . '.id IN (' . implode(',', $ids) . ')');
                if ($records) {
                    // Public is automatically checked.
                    switch ($return) {
                        case 'record':
                        default:
                            return $records;

                        case 'type and id':
                            $results = array();
                            foreach ($records as $record) {
                                $results[] = array(
                                    'record_type' => $recordType,
                                    'record_id' => $record->id,
                                );
                            }
                            return $results;

                        case 'id':
                            $results = array();
                            foreach ($records as $record) {
                                $results[] = $record->id;
                            }
                            return $results;
                    }
                }
            }
        }
    }

    /**
     * Add prefix to an identifier.
     */
    private static function _addPrefixToIdentifier($string)
    {
        return self::$_prefix . $string;
    }

    /**
     * Add prefix and space to an identifier.
     */
    private static function _addPrefixSpaceToIdentifier($string)
    {
        return self::$_prefix . ' ' . $string;
    }

    /**
     * Add unspaced prefix to an identifier.
     */
    private static function _addUnspacedPrefixToIdentifier($string)
    {
        return str_replace(array(' ', ' '), '', self::$_prefix) . $string;
    }

    /**
     * Add unspaced prefix and space to an identifier.
     */
    private static function _addUnspacedPrefixSpaceToIdentifier($string)
    {
        return str_replace(array(' ', ' '), '', self::$_prefix) . ' ' . $string;
    }

    /**
     * Return false if a record is a private file.
     *
     * @todo Check public file via a direct query in getRecordFromIdentifier().
     * @param record $id File id to check.
     * @return boolean
     */
    private static function _removePrivateFile($record)
    {
        if ($record['record_type'] == 'File') {
            return (boolean) get_record_by_id('File', $record['record_id']);
        }

        return true;
    }
}
