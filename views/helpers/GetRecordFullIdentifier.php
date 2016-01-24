<?php
/**
 * Clean Url Get Record Full Identifier
 *
 * @todo Use a route name?
 *
 * @see Omeka\View\Helper\RecordUrl.php
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * @package Omeka\Plugins\CleanUrl\views\helpers
 */
class CleanUrl_View_Helper_GetRecordFullIdentifier extends Zend_View_Helper_Abstract
{
    /**
     * Get clean url path of a record in the default or specified format.
     *
     * @param Record $record
     * @param boolean $withMainPath
     * @param string $withBasePath Can be empty, 'admin', 'public' or
     * 'current'. If any, implies main path.
     * @param boolean $absoluteUrl If true, implies current / admin or public
     * path and main path.
     * @param string $format Format of the identifier (default one if empty).
     *
     * @return string
     *   Full identifier of the record, if any, else empty string.
     */
    public function getRecordFullIdentifier(
        $record,
        $withMainPath = true,
        $withBasePath = 'current',
        $absolute = false,
        $format = null)
    {
        switch (get_class($record)) {
            case 'Collection':
                $identifier = $this->view->getRecordIdentifier($record);
                if (empty($identifier)) {
                    return '';
                }

                $generic = get_option('clean_url_collection_generic');
                return $this->_getUrlPath($absolute, $withBasePath, $withMainPath) . $generic . $identifier;

            case 'Item':
                $identifier = $this->view->getRecordIdentifier($record);
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
                        return $this->_getUrlPath($absolute, $withBasePath, $withMainPath) . $generic . $identifier;

                    case 'collection':
                        $collection = $record->getCollection();
                        $collection_identifier = $this->view->getRecordIdentifier($collection);
                        // The item may be without collection. In that case,
                        // use the generic path if allowed, and if a specific
                        // path is not allowed.
                        if (!$collection_identifier) {
                            $genericFormat = $this->_getGenericFormat('Item');
                            return $genericFormat
                                ? $this->getRecordFullIdentifier($record, $withMainPath, $withBasePath, $absolute, $genericFormat)
                                : '';
                        }
                        return $this->_getUrlPath($absolute, $withBasePath, $withMainPath) . $collection_identifier . '/' . $identifier;
                }
                break;

            case 'File':
                $identifier = $this->view->getRecordIdentifier($record);
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
                        return $this->_getUrlPath($absolute, $withBasePath, $withMainPath) . $generic . $identifier;

                    case 'generic_item':
                        $generic = get_option('clean_url_file_generic');

                        $item = $record->getItem();
                        $item_identifier = $this->view->getRecordIdentifier($item);
                        if (!$item_identifier) {
                            $item_identifier = $item->id;
                        }
                        return $this->_getUrlPath($absolute, $withBasePath, $withMainPath) . $generic . $item_identifier . '/' . $identifier;

                    case 'collection':
                        $item = $record->getItem();
                        $collection = $item->getCollection();
                        $collection_identifier = $this->view->getRecordIdentifier($collection);
                        if (!$collection_identifier) {
                            $genericFormat = $this->_getGenericFormat('File');
                            return $genericFormat
                                ? $this->getRecordFullIdentifier($record, $withMainPath, $withBasePath, $absolute, $genericFormat)
                                : '';
                        }
                        return $this->_getUrlPath($absolute, $withBasePath, $withMainPath) . $collection_identifier . '/' . $identifier;

                    case 'collection_item':
                        $item = $record->getItem();
                        $collection = $item->getCollection();
                        $collection_identifier = $this->view->getRecordIdentifier($collection);
                        if (!$collection_identifier) {
                            $genericFormat = $this->_getGenericFormat('File');
                            return $genericFormat
                                ? $this->getRecordFullIdentifier($record, $withMainPath, $withBasePath, $absolute, $genericFormat)
                                : '';
                        }
                        $item_identifier = $this->view->getRecordIdentifier($item);
                        if (!$item_identifier) {
                            $item_identifier = $item->id;
                        }
                        return $this->_getUrlPath($absolute, $withBasePath, $withMainPath) . $collection_identifier . '/' . $item_identifier . '/' . $identifier;
                }
                break;
        }

        // This record don't have a clean url.
        return '';
    }

    /**
     * Return beginning of the record name if needed.
     *
     * @param boolean $withBasePath Implies main path.
     * @param boolean $withMainPath
     *
     * @return string
     * The string ends with '/'.
     */
    protected function _getUrlPath($absolute, $withBasePath, $withMainPath)
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

        return ($absolute ? get_view()->serverUrl() : '') . $basePath . '/' . $mainPath;
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
