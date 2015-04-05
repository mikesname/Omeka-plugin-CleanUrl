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
     * @param string $return Format of the returned value. can be: 'record',
     * 'type and id' or 'id'.
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

        // TODO Use filters for user or use regular methods (get_record_by_id() checks it).
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
                    OR (element_texts.record_type = 'File' AND items.public = 1)
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
        }

        // TODO Secure bind for identifiers.
        // If the table is case sensitive, lower-case the search.
        if (get_option('clean_url_case_insensitive')) {
            $identifiers = array_map('strtolower', $identifiers);
            $commaIdentifiers = "'" . implode("', '", $identifiers) . "'";
            $sqlWhereText = "AND LOWER(element_texts.text) IN ($commaIdentifiers)";
        }
        // Default.
        else {
            $commaIdentifiers = "'" . implode("', '", $identifiers) . "'";
            $sqlWhereText = "AND element_texts.text IN ($commaIdentifiers)";
        }

        $sqlLimit = $one ? 'LIMIT 1' : '';

        $sql = "
            SELECT element_texts.record_type as 'type', element_texts.record_id as 'id'
            FROM {$db->ElementText} element_texts
                $sqlFromIsPublic
            WHERE element_texts.element_id = '$elementId'
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
                    foreach ($results as $key => $result) {
                        $results[$key] = get_record_by_id($result['type'], $result['id']);
                    }
                    break;

                case 'type and id':
                    break;

                case 'id':
                    foreach ($results as $key => $result) {
                        $results[$key] = $result['id'];
                    }
                    break;

                default:
                    return null;
            }
            return $one
                ? array_shift($results)
                : $results;
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
}
