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
     * @todo Add check public for non-record responses (see getRecordsFromIdentifiers).
     *
     * @param string $identifier The identifier of the record to find.
     * @param boolean $withPrefix Optional. If identifier begins with prefix.
     * @param string $recordType Optional. Search a specific record type if any.
     * @param boolean $onlyRecordId Optional. Return only the record id.
     * @return Omeka_Record_AbstractRecord|null
     */
    public function getRecordFromIdentifier(
        $identifier,
        $withPrefix = false,
        $recordType = null,
        $onlyRecordId = false
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
            WHERE element_texts.element_id = '$elementId'
                $sqlRecordType
                $sqlWhereText
            $sqlOrder
            LIMIT 1
        ";
        $result = $db->fetchRow($sql, $bind);

        if ($result) {
            return $onlyRecordId
                ? $result['record_id']
                : get_record_by_id($result['record_type'], $result['record_id']);
        }
    }
}
