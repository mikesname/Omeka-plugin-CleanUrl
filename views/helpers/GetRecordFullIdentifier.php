<?php
/**
 * Clean Url Get Record Full Identifier
 *
 * @todo Use a route name?
 * @see Omeka\View\Helper\RecordUrl.php
 */

/**
 * @package Omeka\Plugins\CleanUrl\views\helpers
 */
class CleanUrl_View_Helper_GetRecordFullIdentifier extends Zend_View_Helper_Abstract
{
    /**
     * Get clean url path of a record in the default or specified format.
     *
     * @param Record|array $record The use of an array improves the speed when
     * the identifiers are already known (without prefix). If the upper level
     * identifier is required and not set, it will be fetched. Examples for a
     * collection, an item and a file:
     * ['type' => 'Collection', 'id' => '1', 'identifier' => 'alpha']
     * ['type' => 'Item', 'id' => '2', 'identifier' => 'beta', 'collection' => ['id' => '1', 'identifier' => 'alpha']]
     * ['type' => 'File', 'id' => '3', 'identifier' => 'gamma', 'item' => ['id' => '2', 'identifier' => 'beta', ], 'collection' => ['id' => '1', 'identifier' => 'alpha']]
     * @param boolean $withMainPath
     * @param string $withBasePath Can be empty, 'admin', 'public' or
     * 'current'. If any, implies main path.
     * @param boolean $absoluteUrl If true, implies current / admin or public
     * path and main path.
     * @param string $format Format of the identifier (default one if empty).
     * @return string Full identifier of the record, if any, else empty string.
     */
    public function getRecordFullIdentifier(
        $record,
        $withMainPath = true,
        $withBasePath = 'current',
        $absolute = false,
        $format = null)
    {
        return is_array($record)
            ? $this->_getRecordFullIdentifierFromArray($record, $withMainPath, $withBasePath, $absolute, $format)
            : $this->_getRecordFullIdentifierFromRecord($record, $withMainPath, $withBasePath, $absolute, $format);
    }

    protected function _getRecordFullIdentifierFromRecord(
        $record,
        $withMainPath = true,
        $withBasePath = 'current',
        $absolute = false,
        $format = null)
    {
        $view = $this->view;

        switch (get_class($record)) {
            case 'Collection':
                $identifier = $view->getRecordIdentifier($record);
                if (empty($identifier)) {
                    return '';
                }

                $generic = get_option('clean_url_collection_generic');
                return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $generic . $identifier;

            case 'Item':
                $identifier = $view->getRecordIdentifier($record);
                if (empty($identifier)) {
                    $identifier = $record->id;
                }

                if (empty($format)) {
                    $format = get_option('clean_url_item_default');
                }
                // Else check if the format is allowed.
                elseif (!$this->_isFormatAllowed($format, 'Item')) {
                    return '';
                }

                switch ($format) {
                    case 'generic':
                        $generic = get_option('clean_url_item_generic');
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $generic . $identifier;

                    case 'collection':
                        $collection = $record->getCollection();
                        $collection_identifier = $view->getRecordIdentifier($collection);
                        // The item may be without collection. In that case,
                        // use the generic path if allowed, and if a specific
                        // path is not allowed.
                        if (empty($collection_identifier)) {
                            $genericFormat = $this->_getGenericFormat('Item');
                            return $genericFormat
                                ? $this->getRecordFullIdentifier($record, $withMainPath, $withBasePath, $absolute, $genericFormat)
                                : '';
                        }
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $collection_identifier . '/' . $identifier;
                }
                break;

            case 'File':
                $identifier = $view->getRecordIdentifier($record);
                if (empty($identifier)) {
                    $identifier = $record->id;
                }

                if (empty($format)) {
                    $format = get_option('clean_url_file_default');
                }
                // Else check if the format is allowed.
                elseif (!$this->_isFormatAllowed($format, 'File')) {
                    return '';
                }

                switch ($format) {
                    case 'generic':
                        $generic = get_option('clean_url_file_generic');
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $generic . $identifier;

                    case 'generic_item':
                        $generic = get_option('clean_url_file_generic');

                        $item = $record->getItem();
                        $item_identifier = $view->getRecordIdentifier($item);
                        if (!$item_identifier) {
                            $item_identifier = $item->id;
                        }
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $generic . $item_identifier . '/' . $identifier;

                    case 'collection':
                        $item = $record->getItem();
                        $collection = $item->getCollection();
                        $collection_identifier = $view->getRecordIdentifier($collection);
                        if (!$collection_identifier) {
                            $genericFormat = $this->_getGenericFormat('File');
                            return $genericFormat
                                ? $this->getRecordFullIdentifier($record, $withMainPath, $withBasePath, $absolute, $genericFormat)
                                : '';
                        }
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $collection_identifier . '/' . $identifier;

                    case 'collection_item':
                        $item = $record->getItem();
                        $collection = $item->getCollection();
                        $collection_identifier = $view->getRecordIdentifier($collection);
                        if (!$collection_identifier) {
                            $genericFormat = $this->_getGenericFormat('File');
                            return $genericFormat
                                ? $this->getRecordFullIdentifier($record, $withMainPath, $withBasePath, $absolute, $genericFormat)
                                : '';
                        }
                        $item_identifier = $view->getRecordIdentifier($item);
                        if (!$item_identifier) {
                            $item_identifier = $item->id;
                        }
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $collection_identifier . '/' . $item_identifier . '/' . $identifier;
                }
                break;
        }

        // This record doesn't have a clean url.
        return '';
    }

    protected function _getRecordFullIdentifierFromArray(
        $record,
        $withMainPath = true,
        $withBasePath = 'current',
        $absolute = false,
        $format = null)
    {
        if (empty($record['type'])) {
            return '';
        }

        // Prepare the main identifier and save it in case of a generic need.
        if (!isset($record['identifier'])) {
            $record['identifier'] = $this->view->getRecordIdentifier($record);
        }

        switch ($record['type']) {
            case 'Collection':
                if (empty($record['identifier'])) {
                    return '';
                }

                $generic = get_option('clean_url_collection_generic');
                return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $generic . $record['identifier'];

            case 'Item':
                if (empty($record['identifier'])) {
                    if (empty($record['id'])) {
                        return '';
                    }
                    $record['identifier'] = $record['id'];
                }

                if (empty($format)) {
                    $format = get_option('clean_url_item_default');
                }
                // Else check if the format is allowed.
                elseif (!$this->_isFormatAllowed($format, 'Item')) {
                    return '';
                }

                switch ($format) {
                    case 'generic':
                        $generic = get_option('clean_url_item_generic');
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $generic . $record['identifier'];

                    case 'collection':
                        $record = $this->_completeItemWithCollection($record);
                        if (empty($record)) {
                            return '';
                        }

                        // The item may be without collection. In that case,
                        // use the generic path if allowed, and if a specific
                        // path is not allowed.
                        if (empty($record['collection']['identifier'])) {
                            $genericFormat = $this->_getGenericFormat('Item');
                            return $genericFormat
                                ? $this->getRecordFullIdentifier($record, $withMainPath, $withBasePath, $absolute, $genericFormat)
                                : '';
                        }
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $record['collection']['identifier'] . '/' . $record['identifier'];
                }
                break;

            case 'File':
                if (empty($record['identifier'])) {
                    if (empty($record['id'])) {
                        return '';
                    }
                    $record['identifier'] = $record['id'];
                }

                if (empty($format)) {
                    $format = get_option('clean_url_file_default');
                }
                // Else check if the format is allowed.
                elseif (!$this->_isFormatAllowed($format, 'File')) {
                    return '';
                }

                switch ($format) {
                    case 'generic':
                        $generic = get_option('clean_url_file_generic');
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $generic . $record['identifier'];

                    case 'generic_item':
                        $generic = get_option('clean_url_file_generic');
                        $record = $this->_completeFileWithItem($record);
                        if (empty($record)) {
                            return '';
                        }
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $generic . $record['item']['identifier'] . '/' . $record['identifier'];

                    case 'collection':
                        $record = $this->_completeFileWithCollection($record);
                        if (empty($record)) {
                            return '';
                        }

                        if (empty($record['collection']['identifier'])) {
                            $genericFormat = $this->_getGenericFormat('File');
                            return $genericFormat
                                ? $this->getRecordFullIdentifier($record, $withMainPath, $withBasePath, $absolute, $genericFormat)
                                : '';
                        }
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $record['collection']['identifier'] . '/' . $record['identifier'];

                    case 'collection_item':
                        if (!isset($record['item']['identifier'])) {
                            $record = $this->_completeFileWithItem($record);
                            if (empty($record)) {
                                return '';
                            }
                        }
                        if (!isset($record['collection']['identifier'])) {
                            $record = $this->_completeFileWithCollection($record);
                            if (empty($record)) {
                                return '';
                            }
                        }
                        if (empty($record['collection']['identifier'])) {
                            $genericFormat = $this->_getGenericFormat('File');
                            return $genericFormat
                                ? $this->getRecordFullIdentifier($record, $withMainPath, $withBasePath, $absolute, $genericFormat)
                                : '';
                        }
                        return $this->_getUrlPath($absolute, $withMainPath, $withBasePath) . $record['collection']['identifier'] . '/' . $record['item']['identifier'] . '/' . $record['identifier'];
                }
                break;
        }

        // This record doesn't have a clean url.
        return '';
    }

    /**
     * Helper to get the identifier for the collection from an item.
     *
     * @param array $record
     * @return array| string The updated record or empty string if the item
     * doesn't exist.
     */
    protected function _completeItemWithCollection($record)
    {
        // The identifier of the collection is not set.
        if (!isset($record['collection']['identifier'])) {
            // The collection is not set.
            if (!isset($record['collection']['id'])) {
                if (empty($record['id'])) {
                    $item = $this->view->getRecordFromIdentifier($record['identifier'], false, 'Item');
                    if (empty($item)) {
                        return '';
                    }
                    $record['id'] = $item->Id;
                }
                // Check the record from the id.
                else {
                    $item = get_record_by_id('Item', $record['id']);
                    if (empty($item)) {
                        return '';
                    }
                }
                $record['collection']['id'] = $item->collection_id;
            }
            $record['collection']['identifier'] = $this->view->getRecordIdentifier(array(
                'type' => 'Collection',
                'id' => $record['collection']['id'],
            ));
        }
        return $record;
    }

    /**
     * Helper to get the identifier for the item from a file.
     *
     * @param array $record
     * @return array| string The updated record or empty string if the file
     * doesn't exist.
     */
    protected function _completeFileWithItem($record)
    {
        // The identifier of the item is not set.
        if (!isset($record['item']['identifier'])) {
            // The item is not set.
            if (!isset($record['item']['id'])) {
                if (empty($record['id'])) {
                    $file = $this->view->getRecordFromIdentifier($record['identifier'], false, 'File');
                    if (empty($file)) {
                        return '';
                    }
                    $record['id'] = $file->Id;
                }
                // Check the record from the id.
                else {
                    $file = get_record_by_id('File', $record['id']);
                    if (empty($file)) {
                        return '';
                    }
                }
                $record['item']['id'] = $file->item_id;
            }
            $record['item']['identifier'] = $this->view->getRecordIdentifier(array(
                'type' => 'Item',
                'id' => $record['item']['id'],
            ));
        }
        if (empty($record['item']['identifier'])) {
            $record['item']['identifier'] = $record['item']['id'];
        }
        return $record;
    }

    /**
     * Helper to get the identifier for the collection from a file.
     *
     * @param array $record
     * @return array| string The updated record or empty string if the file
     * doesn't exist.
     */
    protected function _completeFileWithCollection($record)
    {
        // The identifier of the collection is not set.
        if (!isset($record['collection']['identifier'])) {
            // The collection is not set.
            if (!isset($record['collection']['id'])) {
                if (!isset($record['item']['id'])) {
                    if (!isset($record['id'])) {
                        if (empty($record['identifier'])) {
                            return '';
                        }
                        $file = $this->view->getRecordFromIdentifier($record['identifier'], false, 'File');
                        if (empty($file)) {
                            return '';
                        }
                        $record['id'] = $file->id;
                    }
                    $record['item']['id'] = $file->item_id;
                }
                $item = get_record_by_id('Item', $record['item']['id']);
                if (empty($item)) {
                    return '';
                }
                $record['collection']['id'] = $item->collection_id;
            }
            if (empty($record['collection']['id'])) {
                $record['collection']['identifier'] = '';
                return $record;
            }
            $record['collection']['identifier'] = $this->view->getRecordIdentifier(array(
                'type' => 'Collection',
                'id' => $record['collection']['id'],
            ));
        }
        return $record;
    }

    /**
     * Return beginning of the record name if needed.
     *
     * @param boolean $withMainPath
     * @param boolean $withBasePath Implies main path.
     * @return string
     * The string ends with '/'.
     */
    protected function _getUrlPath($absolute, $withMainPath, $withBasePath)
    {
        if ($absolute) {
            $withBasePath = empty($withBasePath) ? 'current' : $withBasePath;
            $withMainPath = true;
        }
        elseif ($withBasePath) {
            $withMainPath = true;
        }

        switch ($withBasePath) {
            case 'public': $basePath = PUBLIC_BASE_URL; break;
            case 'admin': $basePath = ADMIN_BASE_URL; break;
            case 'current': $basePath = CURRENT_BASE_URL; break;
            default: $basePath = '';
        }

        $mainPath = $withMainPath ? get_option('clean_url_main_path') : '';

        return ($absolute ? $this->view->serverUrl() : '') . $basePath . '/' . $mainPath;
    }

    /**
     * Check if a format is allowed for a record type.
     *
     * @param string $format
     * @param string $recordType
     * @return boolean|null True if allowed, false if not, null if no format.
     */
    private function _isFormatAllowed($format, $recordType)
    {
        if (empty($format)) {
            return;
        }

        switch ($recordType) {
            case 'Collection':
                return true;

            case 'Item':
                $allowedForItems = unserialize(get_option('clean_url_item_alloweds'));
                return in_array($format, $allowedForItems);

            case 'File':
                $allowedForFiles = unserialize(get_option('clean_url_file_alloweds'));
                return in_array($format, $allowedForFiles);
        }
    }

    /**
     * Return the generic format, if exists, for item or file.
     *
     * @param string $recordType
     * @return string|null
     */
    private function _getGenericFormat($recordType)
    {
        switch ($recordType) {
            case 'Item':
                $allowedForItems = unserialize(get_option('clean_url_item_alloweds'));
                if (in_array('generic', $allowedForItems)) {
                    return 'generic';
                }
                break;

            case 'File':
                $allowedForFiles = unserialize(get_option('clean_url_file_alloweds'));
                if (in_array('generic_item', $allowedForFiles)) {
                    return 'generic_item';
                }
                if (in_array('generic', $allowedForFiles)) {
                    return 'generic';
                }
                break;
        }
    }
}
