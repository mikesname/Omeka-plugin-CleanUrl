<?php
/**
 * Helper to return the clean url of a record.
 *
 * @todo Use the route name?
 *
 * @see Omeka\View\Helper\RecordUrl.php
 * @package Omeka\Plugins\CleanUrl\views\helpers
 */
class CleanUrl_View_Helper_RecordUrl extends Omeka_View_Helper_RecordUrl
{
    /**
     * Return a URL to a record.
     *
     * @uses Omeka_Record_AbstractRecord::getCurrentRecord()
     * @uses Omeka_Record_AbstractRecord::getRecordUrl()
     * @uses Omeka_View_Helper_Url::url()
     * @uses Omeka_View_Helper_GetRecordFullIdentifier::getRecordFullIdentifier()
     * @throws Omeka_View_Exception
     * @param Omeka_Record_AbstractRecord|string $record
     * @param string|null $action
     * @param bool $getAbsoluteUrl
     * @param array $queryParams
     * @return string
     */
    public function recordUrl($record, $action = null, $getAbsoluteUrl = false, $queryParams = array())
    {
        if (is_admin_theme() && !get_option('clean_url_use_admin')) {
            return parent::recordUrl($record, $action, $getAbsoluteUrl, $queryParams);
        }

        // Get the current record from the view if passed as a string.
        if (is_string($record)) {
            $record = $this->view->getCurrentRecord($record);
        }
        if (!($record instanceof Omeka_Record_AbstractRecord)) {
            throw new Omeka_View_Exception(__('Invalid record passed while getting record URL.'));
        }

        // Get the clean url if any.
        $cleanUrl = $this->_getCleanUrl($record, $action);
        if ($cleanUrl) {
            $url = $cleanUrl;
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
        }

        return parent::recordUrl($record, $action, $getAbsoluteUrl, $queryParams);
    }

    /**
     * Get clean url path of a record.
     *
     * @param AbstractRecord $record
     * @param string|null $action
     * @return string|null  Identifier of the record, if any, else empty string.
     */
    protected function _getCleanUrl($record, $action)
    {
        if ($action == 'show' || is_null($action)) {
            if (in_array(get_class($record), array(
                    'Collection',
                    'Item',
                    'File',
                ))) {
                return $this->view->getRecordFullIdentifier($record);
            }
        }
    }
}
