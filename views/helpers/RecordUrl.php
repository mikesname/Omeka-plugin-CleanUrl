<?php
/**
 * Clean Url Return Record Url
 *
 * This file is the default RecordUrl helper, excepted for public record show.
 * For them, the clean url is returned instead of the default url.
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
class Omeka_View_Helper_RecordUrl extends Zend_View_Helper_Abstract
{
    /**
     * Return a URL to a record.
     *
     * @uses Omeka_Record_AbstractRecord::getCurrentRecord()
     * @uses Omeka_Record_AbstractRecord::getRecordUrl()
     * @uses Omeka_View_Helper_Url::url()
     * @throws Omeka_View_Exception
     * @param Omeka_Record_AbstractRecord|string $record
     * @param string|null $action
     * @param bool $getAbsoluteUrl
     * @param array $queryParams
     * @return string
     */
    public function recordUrl($record, $action = null, $getAbsoluteUrl = false, $queryParams = array())
    {
        // Get the current record from the view if passed as a string.
        if (is_string($record)) {
            $record = $this->view->getCurrentRecord($record);
        }
        if (empty($record)) {
            return '';
        }
        if (!($record instanceof Omeka_Record_AbstractRecord)) {
            throw new Omeka_View_Exception(__('Invalid record passed while getting record URL.'));
        }

        // Get the clean url if any.
        if ($url = $this->_getCleanUrl($record, $action)) {
            // Base url is ready.
        }
        // If no action is passed, use the default action set in the signature
        // of Omeka_Record_AbstractRecord::getRecordUrl().
        elseif (is_null($action)) {
            $url = $record->getRecordUrl();
        } else if (is_string($action)) {
            $url = $record->getRecordUrl($action);
        } else {
            throw new Omeka_View_Exception(__('Invalid action passed while getting record URL.'));
        }

        // Assume a well-formed URL if getRecordUrl() returns a string.
        if (is_string($url)) {
            if ($getAbsoluteUrl) {
                $url = $this->view->serverUrl() . $url;
            }
            if ($queryParams) {
                $query = http_build_query($queryParams);
                // Append params if query is already part of the URL.
                if (strpos($url, '?') === false) {
                    $url .= '?' . $query;
                } else {
                    $url .= '&' . $query;
                }
            }
            return $url;
        // Assume routing parameters if getRecordUrl() returns an array.
        } else if (is_array($url)) {
            if (isset($url['id']) && !isset($url['module'])) {
                $route = 'id';
            } else {
                $route = 'default';
            }
            $urlString = $this->view->url($url, $route, $queryParams);
            if ($getAbsoluteUrl) {
                $urlString = $this->view->serverUrl() . $urlString;
            }
            return $urlString;
        } else {
            throw new Omeka_View_Exception(__('Invalid return value while getting record URL.'));
        }
    }

    /**
     * Get clean url path of a record.
     *
     * @param Record $record
     *
     * @return string
     *   Identifier of the record, if any, else empty string.
     */
    protected function _getCleanUrl($record, $action)
    {
        if (is_admin_theme()) {
            return '';
        }

        switch (get_class($record)) {
            case 'Collection':
                if ($action == 'show' || is_null($action)) {
                    $identifier = $this->view->getRecordIdentifier($record);
                    if (empty($identifier)) {
                        return '';
                    }

                    $main_path = get_option('clean_url_main_path');
                    $main_path = $main_path ? '/' . $main_path : '';

                    $generic = get_option('clean_url_collection_generic');
                    $generic = $generic ?  '/' . $generic : '';

                    return CURRENT_BASE_URL . $main_path . $generic . '/' . $identifier;
                }
                break;

            case 'Item':
                if ($action == 'show' || is_null($action)) {
                    $identifier = $this->view->getRecordIdentifier($record);
                    if (empty($identifier)) {
                        $identifier = $record->id;
                    }

                    $main_path = get_option('clean_url_main_path');
                    $main_path = $main_path ? '/' . $main_path : '';

                    switch (get_option('clean_url_item_url')) {
                        case 'generic':
                            $generic = get_option('clean_url_item_generic');
                            $generic = $generic ?  '/' . $generic : '';
                            return CURRENT_BASE_URL . $main_path . $generic . '/' . $identifier;

                        case 'collection':
                            $collection = $record->getCollection();
                            $collection_identifier = $this->view->getRecordIdentifier($collection);
                            if (!$collection_identifier) {
                                return '';
                            }
                            return CURRENT_BASE_URL . $main_path . '/' . $collection_identifier . '/' . $identifier;
                    }
                }
                break;

            case 'File':
                if ($action == 'show' || is_null($action)) {
                    $identifier = $this->view->getRecordIdentifier($record);
                    if (empty($identifier)) {
                        $identifier = $record->id;
                    }

                    $main_path = get_option('clean_url_main_path');
                    $main_path = $main_path ? '/' . $main_path : '';

                    switch (get_option('clean_url_file_url')) {
                        case 'generic':
                            $generic = get_option('clean_url_file_generic');
                            $generic = $generic ?  '/' . $generic : '';
                            return CURRENT_BASE_URL . $main_path . $generic . '/' . $identifier;

                        case 'generic_item':
                            $generic = get_option('clean_url_file_generic');
                            $generic = $generic ?  '/' . $generic : '';

                            $item = $record->getItem();
                            $item_identifier = $this->view->getRecordIdentifier($item);
                            if (!$item_identifier) {
                                $item_identifier = $item->id;
                            }
                            return CURRENT_BASE_URL . $main_path . $generic . '/' . $item_identifier . '/' . $identifier;

                        case 'collection':
                            $item = $record->getItem();
                            $collection = $item->getCollection();
                            $collection_identifier = $this->view->getRecordIdentifier($collection);
                            if (!$collection_identifier) {
                                return '';
                            }
                            return CURRENT_BASE_URL . $main_path . '/' . $collection_identifier . '/' . $identifier;

                        case 'collection_item':
                            $item = $record->getItem();
                            $collection = $item->getCollection();
                            $collection_identifier = $this->view->getRecordIdentifier($collection);
                            if (!$collection_identifier) {
                                return '';
                            }
                            $item_identifier = $this->view->getRecordIdentifier($item);
                            if (!$item_identifier) {
                                $item_identifier = $item->id;
                            }
                            return CURRENT_BASE_URL . $main_path . '/' . $collection_identifier . '/' . $item_identifier . '/' . $identifier;
                    }
                }
                break;
        }

        // This record don't have  a clean url.
        return '';
    }
}
