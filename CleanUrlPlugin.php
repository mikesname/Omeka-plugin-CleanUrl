<?php
/**
 * Allows to have URL like http://example.com/my_collection/dc:identifier.
 *
 * @copyright Daniel Berthereau, 2012-2013
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt
 * @package CleanUrl
 */

/**
 * Contains code used to integrate the plugin into Omeka.
 *
 * @package CleanUrl
 */
class CleanUrlPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'install',
        'uninstall',
        'config_form',
        'config',
        'after_save_collection',
        'define_routes',
    );

    protected $_options = array(
        'clean_url_use_collection' => TRUE,
        'clean_url_collection_shortnames' => NULL,
        'clean_url_collection_path' => '',
        'clean_url_use_generic' => TRUE,
        'clean_url_generic' => 'document',
        'clean_url_generic_path' => '',
        'clean_url_item_identifier_prefix' => 'document:',
    );

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_installOptions();

        // Set default short names of collection.
        $collection_names = array();
        $collections = get_records('Collection', array(), 0);
        set_loop_records('collections', $collections);
        foreach (loop('collections') as $collection) {
            $collection_names[$collection->id] = $this->_createCollectionDefaultName($collection);

            // Names should be saved immediately to avoid side effects if other
            // similar names are created.
            set_option('clean_url_collection_shortnames', serialize($collection_names));
        }
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm()
    {
        $collections = get_records('Collection', array(), 0);
        set_loop_records('collections', $collections);
        $collection_names = unserialize(get_option('clean_url_collection_shortnames'));

        require 'config_form.php';
    }

    /**
     * Saves plugin configuration page.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];

        // Save settings.
        set_option('clean_url_use_collection', (int) (boolean) $post['clean_url_use_collection']);
        set_option('clean_url_collection_path', $this->_sanitizeString(trim($post['clean_url_collection_path'], ' /\\')));
        set_option('clean_url_use_generic', (int) (boolean) $post['clean_url_use_generic']);
        set_option('clean_url_generic', $this->_sanitizeString($post['clean_url_generic']));
        set_option('clean_url_generic_path', $this->_sanitizeString(trim($post['clean_url_generic_path'], ' /\\')));
        set_option('clean_url_item_identifier_prefix', $this->_sanitizeString($post['clean_url_item_identifier_prefix']));

        $collections = get_records('Collection', array(), 0);
        set_loop_records('collections', $collections);
        $collection_names = unserialize(get_option('clean_url_collection_shortnames'));
        foreach (loop('collections') as $collection) {
            $id = 'clean_url_collection_shortname_' . $collection->id;
            $collection_names[$collection->id] = $this->_sanitizeString(trim($post[$id], ' /\\'));
        }
        set_option('clean_url_collection_shortnames', serialize($collection_names));
    }

    /**
     * Updates routes with a default name for the new collection.
     *
     * @return void.
     */
    public function hookAfterSaveCollection($args)
    {
        $collection = $args['record'];

        // Create the collection default route.
        $collection_names = unserialize(get_option('clean_url_collection_shortnames'));
        if (!isset($collection_names[$collection->id])) {
            $collection_names[$collection->id] = $this->_createCollectionDefaultName($collection);
            set_option('clean_url_collection_shortnames', serialize($collection_names));
        }
    }

    /**
     * Defines public routes "main_path / my_collection|generic / dc:identifier".
     *
     * @todo Rechecks performance of routes definition.
     */
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];

        if (is_admin_theme()) {
            return;
        }

        // For performance and security reasons, one route is added for each
        // collection instead of one main route.
        if (get_option('clean_url_use_collection') == '1') {
            $collection_path = get_option('clean_url_collection_path');
            $collection_path = ($collection_path) ? $collection_path . '/' : '';
            $collection_names = unserialize(get_option('clean_url_collection_shortnames'));
            $collections = get_records('Collection', array(), 0);
            set_loop_records('collections', $collections);
            foreach (loop('collections') as $collection) {
                // Add the full url.
                $router->addRoute('cleanUrl_collection_route_' . $collection->id, new Zend_Controller_Router_Route(
                    $collection_path . $collection_names[$collection->id] . '/:dc-identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route',
                        'collection_id' => $collection->id,
                )));

                // Add a route for the collection show view.
                $router->addRoute('cleanUrl_collection_' . $collection->id, new Zend_Controller_Router_Route(
                    $collection_path . $collection_names[$collection->id],
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'collection-show',
                        'collection_id' => $collection->id,
                )));

                // Add a lowercase route for both to prevent some pratical issues.
                $lowercase = strtolower($collection_path . $collection_names[$collection->id]);
                if ($lowercase != ($collection_path . $collection_names[$collection->id])) {
                    $router->addRoute('cleanUrl_collection_route_' . $collection->id . '_lower', new Zend_Controller_Router_Route(
                        $lowercase . '/:dc-identifier',
                        array(
                            'module' => 'clean-url',
                            'controller' => 'index',
                            'action' => 'route',
                            'collection_id' => $collection->id,
                    )));

                    $router->addRoute('cleanUrl_collection_' . $collection->id . '_lower', new Zend_Controller_Router_Route(
                        $lowercase,
                        array(
                            'module' => 'clean-url',
                            'controller' => 'index',
                            'action' => 'collection-show',
                            'collection_id' => $collection->id,
                    )));
                }
            }
        }

        // Add generic custom routes.
        if (get_option('clean_url_use_generic') == '1') {
            $generic_path = get_option('clean_url_generic_path');
            $generic_path = ($generic_path) ? $generic_path . '/' : '';
            $prefix = get_option('clean_url_generic');
            $router->addRoute('cleanUrl_generic_item_dcid', new Zend_Controller_Router_Route(
                $generic_path . $prefix . '/:dc-identifier',
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'route',
                    'collection_id' => NULL,
            )));

            $router->addRoute('cleanUrl_generic_browse', new Zend_Controller_Router_Route(
                $generic_path . $prefix,
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'items-browse',
            )));

            // Add a lowercase route to prevent some pratical issues.
            $lowercase = strtolower($generic_path . $prefix);
            if ($lowercase != ($generic_path . $prefix)) {
                $router->addRoute('cleanUrl_generic_item_dcid_lower', new Zend_Controller_Router_Route(
                    $lowercase . '/:dc-identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route',
                        'collection_id' => NULL,
                )));

                $router->addRoute('cleanUrl_generic_browse_lower', new Zend_Controller_Router_Route(
                    $lowercase,
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'items-browse',
                )));
            }
        }
    }

    /**
     * Creates the default short name of a collection.
     *
     * Default name is the first word of the collection name. The id is added if
     * this name is already used.
     *
     * @param object $collection
     *
     * @return string Unique sanitized name of the collection.
     */
    private function _createCollectionDefaultName($collection)
    {
        $collection_names = unserialize(get_option('clean_url_collection_shortnames'));
        if ($collection_names === FALSE) {
            $collection_names = array();
        }
        else {
            // Remove the current collection id to simplify check.
            unset($collection_names[$collection->id]);
        }

        // Default name is the first word of the collection name.
        $default_name = trim(strtok(trim(metadata($collection, array('Dublin Core', 'Title'))), " \n\r\t"));

        // If this name is already used, the id is added until name is unique.
        While (in_array($default_name, $collection_names)) {
            $default_name .= '_' . $collection->id;
        }

        return $this->_sanitizeString($default_name);
    }

    /**
     * Returns a sanitized and unaccentued string for folder or file path.
     *
     * @param string $string The string to sanitize.
     *
     * @return string The sanitized string to use as a folder or a file name.
     */
    private function _sanitizeString($string) {
        $string = trim(strip_tags($string));
        $string = htmlentities($string, ENT_NOQUOTES, 'utf-8');
        $string = preg_replace('#\&([A-Za-z])(?:uml|circ|tilde|acute|grave|cedil|ring)\;#', '\1', $string);
        $string = preg_replace('#\&([A-Za-z]{2})(?:lig)\;#', '\1', $string);
        $string = preg_replace('#\&[^;]+\;#', '_', $string);
        $string = preg_replace('/[^[:alnum:]\(\)\[\]_\-\.#~@+:]/', '_', $string);
        return preg_replace('/_+/', '_', $string);
    }
}
