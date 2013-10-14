<?php
/**
 * Clean Url
 *
 * Allows to have URL like http://example.com/my_collection/dc:identifier.
 *
 * @copyright Daniel Berthereau, 2012-2013
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */

/**
 * The Clean Url plugin.
 * @package Omeka\Plugins\CleanUrl
 */
class CleanUrlPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'install',
        'upgrade',
        'uninstall',
        'config_form',
        'config',
        'define_routes',
    );

    protected $_options = array(
        'clean_url_identifier_prefix' => 'document:',
        'clean_url_case_insensitive' => FALSE,
        'clean_url_main_path' => '',
        'clean_url_collection_generic' => '',
        'clean_url_item_url' => 'generic',
        'clean_url_item_generic' => 'document',
        'clean_url_file_url' => 'generic',
        'clean_url_file_generic' => 'file',
    );

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_installOptions();
    }

    /**
     * Upgrades the plugin.
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];

        if (version_compare($oldVersion, '2.4', '<')) {
            set_option('clean_url_identifier_prefix', get_option('clean_url_item_identifier_prefix'));
            delete_option('clean_url_item_identifier_prefix');
            set_option('clean_url_case_insensitive', get_option('clean_url_case_insensitive'));
            set_option('clean_url_main_path', get_option('clean_url_generic_path'));
            delete_option('clean_url_generic_path');
            delete_option('clean_url_use_collection');
            delete_option('clean_url_collection_shortnames');
            set_option('clean_url_collection_generic', get_option('clean_url_collection_path'));
            delete_option('clean_url_collection_path');
            set_option('clean_url_item_url', get_option('clean_url_use_generic') ? 'generic' : 'collection');
            delete_option('clean_url_use_generic');
            set_option('clean_url_item_generic', get_option('clean_url_generic'));
            delete_option('clean_url_generic');
            set_option('clean_url_file_url', $this->_options['clean_url_file_url']);
            set_option('clean_url_file_generic', $this->_options['clean_url_file_generic']);
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
        set_option('clean_url_identifier_prefix', $this->_sanitizePrefix($post['clean_url_identifier_prefix']));
        set_option('clean_url_case_insensitive', (int) (boolean) $post['clean_url_case_insensitive']);
        set_option('clean_url_main_path', $this->_sanitizeString(trim($post['clean_url_main_path'], ' /\\')));
        set_option('clean_url_collection_generic', $this->_sanitizeString(trim($post['clean_url_collection_generic'], ' /\\')));
        set_option('clean_url_item_url', $post['clean_url_item_url']);
        set_option('clean_url_item_generic', $this->_sanitizeString(trim($post['clean_url_item_generic'], ' /\\')));
        set_option('clean_url_file_url', $post['clean_url_file_url']);
        set_option('clean_url_file_generic', $this->_sanitizeString(trim($post['clean_url_file_generic'], ' /\\')));
    }

    /**
     * Defines public routes "main_path / my_collection | generic / dc:identifier".
     *
     * @todo Rechecks performance of routes definition.
     */
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];

        if (is_admin_theme()) {
            return;
        }

        $main_path = get_option('clean_url_main_path');
        $main_path = $main_path ? $main_path . '/' : '';

        $collection_generic = get_option('clean_url_collection_generic');
        $collection_generic = $collection_generic ? $collection_generic . '/' : '';

        // For performance and security reasons, one route is added for each
        // collection instead of one jokerised main route.
        // TODO Recheck in order to simplify or let.
        $collections = get_records('Collection', array(), 0);
        foreach ($collections as $collection) {
            $view = get_view();
            $collection_identifier = $view->recordIdentifier($collection);
            if (empty($collection_identifier)) {
                continue;
            }

            // Add a route for the collection show view.
            $route = $main_path . $collection_generic . $collection_identifier;
            $router->addRoute('cleanUrl_collection_' . $collection->id, new Zend_Controller_Router_Route(
                $route,
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'collection-show',
                    'collection_id' => $collection->id,
            )));

            // Add a lowercase route to prevent some practical issues.
            if ($route != strtolower($route)) {
                $router->addRoute('cleanUrl_collection_' . $collection->id . '_lower', new Zend_Controller_Router_Route(
                    strtolower($route),
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'collection-show',
                        'collection_id' => $collection->id,
                )));
            }

            // Add a collection route for items.
            if (get_option('clean_url_item_url') == 'collection') {
                $route = $main_path . $collection_identifier;
                $router->addRoute('cleanUrl_collection_' . $collection->id . '_item', new Zend_Controller_Router_Route(
                    $route . '/:dc-identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route-collection-item',
                        'collection_id' => $collection->id,
                )));

                // Add a lowercase route to prevent some practical issues.
                if ($route != strtolower($route)) {
                    $router->addRoute('cleanUrl_collection_' . $collection->id . '_item_lower', new Zend_Controller_Router_Route(
                        strtolower($route) . '/:dc-identifier',
                        array(
                            'module' => 'clean-url',
                            'controller' => 'index',
                            'action' => 'route-collection-item',
                            'collection_id' => $collection->id,
                    )));
                }
            }

            // Add a collection route for files.
            if (get_option('clean_url_file_url') == 'collection') {
                $route = $main_path . $collection_identifier;
                $router->addRoute('cleanUrl_collection_' . $collection->id . '_file', new Zend_Controller_Router_Route(
                    $route . '/:dc-identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route-collection-file',
                        'collection_id' => $collection->id,
                )));

                // Add a lowercase route to prevent some practical issues.
                if ($route != strtolower($route)) {
                    $router->addRoute('cleanUrl_collection_' . $collection->id . '_file_lower', new Zend_Controller_Router_Route(
                        strtolower($route) . '/:dc-identifier',
                        array(
                            'module' => 'clean-url',
                            'controller' => 'index',
                            'action' => 'route-collection-file',
                            'collection_id' => $collection->id,
                    )));
                }
            }
            // Add a collection / item route for files.
            elseif (get_option('clean_url_file_url') == 'collection_item') {
                $route = $main_path . $collection_identifier;
                $router->addRoute('cleanUrl_collection_item_' . $collection->id . '_file', new Zend_Controller_Router_Route(
                    $route . '/:item-dc-identifier/:dc-identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route-collection-item-file',
                        'collection_id' => $collection->id,
                )));

                // Add a lowercase route to prevent some practical issues.
                if ($route != strtolower($route)) {
                    $router->addRoute('cleanUrl_collection_item_' . $collection->id . '_file_lower', new Zend_Controller_Router_Route(
                        strtolower($route) . '/:item-dc-identifier/:dc-identifier',
                        array(
                            'module' => 'clean-url',
                            'controller' => 'index',
                            'action' => 'route-collection-item-file',
                            'collection_id' => $collection->id,
                    )));
                }
            }
        }

        // Add a generic route for items.
        if (get_option('clean_url_item_url') == 'generic') {
            $item_generic = get_option('clean_url_item_generic');
            $route = $main_path . $item_generic;
            $router->addRoute('cleanUrl_generic_items_browse', new Zend_Controller_Router_Route(
                $route,
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'items-browse',
            )));
            $router->addRoute('cleanUrl_generic_item', new Zend_Controller_Router_Route(
                $route . '/:dc-identifier',
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'route-item',
                    'collection_id' => NULL,
            )));

            // Add a lowercase route to prevent some practical issues.
            if ($route != strtolower($route)) {
                $router->addRoute('cleanUrl_generic_items_browse_lower', new Zend_Controller_Router_Route(
                    strtolower($route),
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'items-browse',
                )));
                $router->addRoute('cleanUrl_generic_item_lower', new Zend_Controller_Router_Route(
                    strtolower($route) . '/:dc-identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route-item',
                        'collection_id' => NULL,
                )));
            }
        }

        // Add a generic route for files.
        if (get_option('clean_url_file_url') == 'generic') {
            $file_generic = get_option('clean_url_file_generic');
            $route = $main_path . $file_generic;
            $router->addRoute('cleanUrl_generic_file', new Zend_Controller_Router_Route(
                $route . '/:dc-identifier',
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'route-file',
                    'collection_id' => NULL,
            )));

            // Add a lowercase route to prevent some practical issues.
            if ($route != strtolower($route)) {
                $router->addRoute('cleanUrl_generic_file_lower', new Zend_Controller_Router_Route(
                    strtolower($route) . '/:dc-identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route-file',
                        'collection_id' => NULL,
                )));
            }
        }
        // Add a generic / item route for files.
        elseif (get_option('clean_url_file_url') == 'generic_item') {
            $file_generic = get_option('clean_url_file_generic');
            $route = $main_path . $file_generic;
            $router->addRoute('cleanUrl_generic_item_file', new Zend_Controller_Router_Route(
                $route . '/:item-dc-identifier/:dc-identifier',
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'route-item-file',
                    'collection_id' => NULL,
            )));

            // Add a lowercase route to prevent some practical issues.
            if ($route != strtolower($route)) {
                $router->addRoute('cleanUrl_generic_item_file_lower', new Zend_Controller_Router_Route(
                    strtolower($route) . '/:item-dc-identifier/:dc-identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route-item-file',
                        'collection_id' => NULL,
                )));
            }
        }
    }

    /**
     * Returns a sanitized and unaccentued string for folder or file path.
     *
     * @param string $string The string to sanitize.
     *
     * @return string The sanitized string to use as a folder or a file name.
     */
    private function _sanitizeString($string)
    {
        return $this->_sanitizeAnyString($string, '');
    }

    /**
      * Returns a sanitized and unaccentued string for prefix.
     *
     * Difference with default sanitization is that space is allowed.
     *
     * @param string $string The string to sanitize.
     *
     * @return string The sanitized string to use as a prefix.
     */
    private function _sanitizePrefix($string)
    {
        return $this->_sanitizeAnyString($string, ' ');
    }

    /**
     * Returns a sanitized and unaccentued string for folder or file path.
     *
     * @param string $string The string to sanitize.
     * @param string $space Add space as an allowed characters.
     *
     * @return string The sanitized string to use as a folder or a file name.
     */
    private function _sanitizeAnyString($string, $space = '')
    {
        $string = trim(strip_tags($string));
        $string = htmlentities($string, ENT_NOQUOTES, 'utf-8');
        $string = preg_replace('#\&([A-Za-z])(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml)\;#', '\1', $string);
        $string = preg_replace('#\&([A-Za-z]{2})(?:lig)\;#', '\1', $string);
        $string = preg_replace('#\&[^;]+\;#', '_', $string);
        $string = preg_replace('/[^[:alnum:]\(\)\[\]_\-\.#~@+:' . $space . ']/', '_', $string);
        return preg_replace('/_+/', '_', $string);
    }
}
