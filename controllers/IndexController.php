<?php
/**
 * The plugin controller for index pages.
 *
 * @package CleanUrl
 */
class CleanUrl_IndexController extends Omeka_Controller_AbstractActionController
{
    protected $_recordType = '';
    private $_dc_identifier = '';
    private $_collection_id = '';
    private $_item_dc_identifier = '';

    /**
     * Initialize the controller.
     */
    public function init()
    {
        // Reset script paths (will be regenerated in forwarded destination).
        get_view()->setScriptPath(null);
    }

    /**
     * Routes a clean url of an item to the default url.
     */
    public function routeItemAction()
    {
        return $this->routeCollectionItemAction();
    }

    /**
     * Routes a clean url of an item to the default url.
     */
    public function routeCollectionItemAction()
    {
        $this->_recordType = 'Item';

        $id = $this->_routeRecord();

        // If no identifier exists, the plugin tries to use the record id directly.
        if (!$id) {
            $record = get_record_by_id($this->_recordType, $this->_dc_identifier);
            if (!$record) {
                return $this->forward('not-found', 'error', 'default');
            }

            // Check if the found item belongs to the collection, if any.
            if (!empty($this->_collection_id)
                    && $this->_collection_id != $record->collection_id
                ) {
                return $this->forward('not-found', 'error', 'default');
            }

            $id = $this->_dc_identifier;
        }

        return $this->forward('show', 'items', 'default', array(
            'module' => null,
            'controller' => 'items',
            'action' => 'show',
            'record_type' => 'Item',
            'id' => $id,
        ));
    }

    /**
     * Routes a clean url of a file to the default url.
     */
    public function routeFileAction()
    {
        $this->_recordType = 'File';

        $id = $this->_routeRecord();

        // If no identifier exists, the plugin tries to use the record id directly.
        if (!$id) {
            $record = get_record_by_id($this->_recordType, $this->_dc_identifier);
            if (!$record) {
                return $this->forward('not-found', 'error', 'default');
            }

            $id = $this->_dc_identifier;
        }

        return $this->forward('show', 'files', 'default', array(
            'module' => null,
            'controller' => 'files',
            'action' => 'show',
            'record_type' => 'File',
            'id' => $id,
        ));
    }

    /**
     * Routes a clean url of a file with item to the default url.
     */
    public function routeItemFileAction()
    {
        return $this->routeCollectionItemFileAction();
    }

    /**
     * Routes a clean url of a file with item to the default url.
     */
    public function routeCollectionFileAction()
    {
        return $this->routeCollectionItemFileAction();
    }

    /**
     * Routes a clean url of a file with collection and item to the default url.
     */
    public function routeCollectionItemFileAction()
    {
        $this->_recordType = 'File';

        $id = $this->_routeRecord();

        // If no identifier exists, the plugin tries to use the record id directly.
        if (!$id) {
            $record = get_record_by_id($this->_recordType, $this->_dc_identifier);
            if (!$record) {
                return $this->forward('not-found', 'error', 'default');
            }

            // Check if the found file belongs to the collection.
            if (!$this->_checkCollectionFile($record)) {
                return $this->forward('not-found', 'error', 'default');
            }

            // Check if the found file belongs to the item.
            if (!$this->_checkItemFile($record)) {
                return $this->forward('not-found', 'error', 'default');
            }

            $id = $this->_dc_identifier;
        }

        return $this->forward('show', 'files', 'default', array(
            'module' => null,
            'controller' => 'files',
            'action' => 'show',
            'record_type' => 'File',
            'id' => $id,
        ));
    }

    /**
     * Checks if a file belongs to a collection.
     *
     * @param File $file File to check.
     *
     * @return boolean
     */
    protected function _checkCollectionFile($file)
    {
        // Get the item.
        $item = $file->getItem();

        // Check if the found file belongs to the collection.
        if (!empty($this->_collection_id)
                && $item->collection_id != $this->_collection_id
            ) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a file belongs to an item.
     *
     * @param File $file File to check.
     *
     * @return boolean
     */
    protected function _checkItemFile($file)
    {
        // Get the item.
        $item = $file->getItem();

        // Check if the found file belongs to the item.
        if (!empty($this->_item_dc_identifier)) {
            // Get the item identifier.
            $item_identifier = $this->view->RecordIdentifier($item);

            // Check identifier and id of item.
            if (strtolower($this->_item_dc_identifier) != strtolower($item_identifier)
                    && $this->_item_dc_identifier != $item->id
                ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Routes a clean url of an item to the default url.
     *
     * @return id
     *   Id of the record.
     */
    protected function _routeRecord()
    {
        $db = get_db();

        // Identifiers to check.
        $this->_dc_identifier = $this->_getParam('dc-identifier');
        $this->_collection_id = $this->_getParam('collection_id');
        $this->_item_dc_identifier = $this->_getParam('item-dc-identifier');

        // Select table.
        switch ($this->_recordType) {
            case 'Item':
                $sql_from = "FROM {$db->Item} records";
                break;
            case 'File':
                $sql_from = "FROM {$db->File} records";
                break;
        }

        // Use of ordered placeholders.
        $bind = array();

        // Check the dublin core identifier of the record.
        $bind[] = get_option('clean_url_identifier_prefix') . $this->_dc_identifier;
        // Check with a space between prefix and identifier too.
        $bind[] = get_option('clean_url_identifier_prefix') . ' ' . $this->_dc_identifier;

        // Check only lowercase if needed.
        if (get_option('clean_url_case_insensitive') != '1') {
            $sql_text = "
                    AND (element_texts.text = ?
                        OR element_texts.text = ?)";
        }
        else {
            $bind[0] = strtolower($bind[0]);
            $bind[1] = strtolower($bind[1]);
            $sql_text = "
                    AND (LOWER(element_texts.text) = ?
                        OR LOWER(element_texts.text) = ?)";
        }

        // Checks if url contains generic or true collection.
        $sql_collection = '';
        if ($this->_collection_id) {
            switch ($this->_recordType) {
                case 'Item':
                    $sql_collection = 'AND records.collection_id = ?';
                    $bind[] = $this->_collection_id;
                    break;
                case 'File':
                    $sql_from .= "
                        JOIN {$db->Item} items
                            ON records.item_id = items.id";
                    $sql_collection = 'AND items.collection_id = ?';
                    $bind[] = $this->_collection_id;
                    break;
            }
        }

        // Checks if prefixes for items and files are the same (currently not recommended).
        if (get_option('clean_url_items_generic') != get_option('clean_url_files_generic')) {
            $sql_record_type = 'AND (element_texts.record_type = "Item" OR element_texts.record_type = "File")';
        }
        else {
            $sql_record_type = 'AND element_texts.record_type = "' . $this->_recordType . '"';
        }

        $sql = "
            SELECT records.id
            $sql_from
                JOIN {$db->ElementText} element_texts
                    ON records.id = element_texts.record_id
                JOIN {$db->Element} elements
                    ON element_texts.element_id = elements.id
                JOIN {$db->ElementSet} element_sets
                    ON elements.element_set_id = element_sets.id
            WHERE element_sets.name = 'Dublin Core'
                AND elements.name = 'Identifier'
                AND (element_texts.text = ?
                    OR element_texts.text = ?)
                $sql_collection
                $sql_record_type
            LIMIT 1
        ";
        $id = $db->fetchOne($sql, $bind);

        // Additional check for item identifier : the file should belong to item.
        // TODO Include this in the query.
        if ($id && !empty($this->_item_dc_identifier) && $this->_recordType == 'File') {
            // Check if the found file belongs to the item.
            $file = get_record_by_id('File', $id);
            if (!$this->_checkItemFile($file)) {
                return null;
            }
        }

        return $id;
    }

    public function collectionShowAction()
    {
        return $this->forward('show', 'collections', 'default', array(
            'module' => null,
            'controller' => 'collections',
            'action' => 'show',
            'record_type' => 'Collection',
            'id' => $this->_getParam('collection_id'),
        ));
    }

    public function itemsBrowseAction()
    {
        return $this->forward('browse', 'items', 'default', array(
            'module' => null,
            'controller' => 'items',
            'action' => 'browse',
            'record_type' => 'Item',
        ));
    }
}
