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
class Omeka_View_Helper_GetRecordFromIdentifier extends Zend_View_Helper_Abstract
{
    /**
     * Get record from identifier
     *
     * @param string $identifier Contains the prefix or not.
     * @param boolean $withPrefix Indicates if identifier contains the prefix.
     * @return Omeka_Record_AbstractRecord|null
     */
    public function getRecordFromIdentifier($identifier, $withPrefix = true)
    {
        $db = get_db();

        // Clean and lowercase identifier to facilitate search.
        $identifier = trim(strtolower($identifier), ' /\\?<>:*%|"\'`&; ');

        $bind = array();
        if ($withPrefix) {
            $sqlText = 'AND element_texts.text = ?';
            $bind[] = $identifier;
        }
        else {
            $sqlText = 'AND (element_texts.text = ? OR element_texts.text = ?)';
            $bind[] = get_option('clean_url_identifier_prefix') . $identifier;
            // Check with a space between prefix and identifier too.
            $bind[] = get_option('clean_url_identifier_prefix') . ' ' . $identifier;
        }

        $sql = "
            SELECT element_texts.record_type, element_texts.record_id
            FROM {$db->ElementText} element_texts
                JOIN {$db->Element} elements
                    ON element_texts.element_id = elements.id
                JOIN {$db->ElementSet} element_sets
                    ON elements.element_set_id = element_sets.id
            WHERE element_sets.name = 'Dublin Core'
                AND elements.name = 'Identifier'
                $sqlText
            LIMIT 1
        ";
        $result = $db->fetchRow($sql, $bind);

        return $result
            ? get_record_by_id($result['record_type'], $result['record_id'])
            : null;
    }
}
