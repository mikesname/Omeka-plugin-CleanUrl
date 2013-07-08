<?php
/**
 * The plugin controller for index pages.
 *
 * @package CleanUrl
 */
class CleanUrl_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
    * Routes a clean url of an item to the default url.
    */
    public function routeAction()
    {
        $db = get_db();

        // Identifier to check (use of ordered placeholders).
        $dc_identifier = $this->_getParam('dc-identifier');
        $bind = array();
        $bind[] = get_option('clean_url_item_identifier_prefix') . $dc_identifier;
        // Check with a space between prefix and identifier too.
        $bind[] = get_option('clean_url_item_identifier_prefix') . ' ' . $dc_identifier;

        // Checks if url contains generic or true collection.
        $collection_id = $this->_getParam('collection_id');
        $sql_collection = '';
        if ($collection_id) {
            $sql_collection = 'AND items.collection_id = ?';
            $bind[] = $collection_id;
        }

        $sql = "
            SELECT items.id
            FROM {$db->Item} items
                JOIN {$db->ElementText} element_texts
                    ON items.id = element_texts.record_id
                JOIN {$db->Element} elements
                    ON element_texts.element_id = elements.id
                JOIN {$db->ElementSet} element_sets
                    ON elements.element_set_id = element_sets.id
            WHERE element_sets.name = 'Dublin Core'
                AND elements.name = 'Identifier'
                AND (element_texts.text = ?
                    OR element_texts.text = ?)
                $sql_collection
            LIMIT 1
        ";
        $id = $db->fetchOne($sql, $bind);

        // If no identifier exists, the plugin uses the item id directly.
        if (!$id) {
            // Checks directly the item id.
            $item = get_record_by_id('item', $dc_identifier);
            if (!$item
                    // Checks if the item belongs to the collection, except for generic.
                    || ($collection_id && ($item->collection_id != $collection_id))
                ) {
                return $this->_forward('not-found', 'error', 'default');
            }
            $id = $dc_identifier;
        }

        return $this->_forward('show', 'items', 'default', array('id' => $id));
    }

    public function collectionShowAction()
    {
        return $this->_forward('show', 'collections', 'default', array('id' => $this->_getParam('collection_id')));
    }

    public function itemsBrowseAction()
    {
        return $this->_forward('browse', 'items', 'default');
    }
}
