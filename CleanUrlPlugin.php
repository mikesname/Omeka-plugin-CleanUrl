<?php
/**
 * Clean Url
 *
 * Allows to have URL like http://example.com/my_collection/dc:identifier.
 *
 * @copyright Daniel Berthereau, 2012-2014
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */

/**
 * The Clean Url plugin.
 * @package Omeka\Plugins\CleanUrl
 */
class CleanUrlPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array This plugin's hooks.
     */
    protected $_hooks = array(
        'install',
        'upgrade',
        'uninstall',
        'config_form',
        'config',
        'after_save_collection',
        'after_delete_collection',
        'admin_items_browse_simple_each',
        'define_routes',
    );

    /**
     * @var array This plugin's filters.
     */
    protected $_filters = array(
        'clean_url_route_plugins',
    );

    /**
     * @var array This plugin's options.
     */
    protected $_options = array(
        // 43 is the hard set id of "Dublin Core:Identifier" in default install.
        'clean_url_identifier_element' => 43,
        'clean_url_identifier_prefix' => 'document:',
        'clean_url_identifier_unspace' => false,
        'clean_url_case_insensitive' => false,
        'clean_url_main_path' => '',
        'clean_url_collection_regex' => '',
        'clean_url_collection_generic' => '',
        'clean_url_item_default' => 'generic',
        'clean_url_item_alloweds' => 'a:2:{i:0;s:7:"generic";i:1;s:10:"collection";}',
        'clean_url_item_generic' => 'document/',
        'clean_url_file_default' => 'generic',
        'clean_url_file_alloweds' => 'a:2:{i:0;s:7:"generic";i:1;s:15:"collection_item";}',
        'clean_url_file_generic' => 'file/',
        'clean_url_use_admin' => false,
        'clean_url_display_admin_browse_identifier' => true,
        'clean_url_route_plugins' => 'a:0:{}',
    );

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_installOptions();

        $this->cacheCollectionsRegex();
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
            set_option('clean_url_display_admin_browse_identifier', $this->_options['clean_url_display_admin_browse_identifier']);
        }

        if (version_compare($oldVersion, '2.8', '<')) {
            $itemUrl = get_option('clean_url_item_url');
            set_option('clean_url_item_default', $itemUrl);
            delete_option('clean_url_item_url');
            set_option('clean_url_item_alloweds', serialize(array($itemUrl)));

            $fileUrl = get_option('clean_url_file_url');
            set_option('clean_url_file_default', $fileUrl);
            delete_option('clean_url_file_url');
            set_option('clean_url_file_alloweds', serialize(array($fileUrl)));
        }

        if (version_compare($oldVersion, '2.9', '<')) {
            set_option('clean_url_identifier_element', $this->_options['clean_url_identifier_element']);
        }

        if (version_compare($oldVersion, '2.9.1', '<')) {
            foreach (array(
                    'clean_url_main_path',
                    'clean_url_collection_generic',
                    'clean_url_item_generic',
                    'clean_url_file_generic',
                ) as $option) {
                $path = get_option($option);
                if ($path) {
                    set_option($option, $path . '/');
                }
            }
        }

        if (version_compare($oldVersion, '2.16', '<')) {
            $this->cacheCollectionsRegex();
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
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial('plugins/clean-url-config-form.php');
    }

    /**
     * Saves plugin configuration page.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];

        // Sanitize first.
        $post['clean_url_identifier_prefix'] = trim($post['clean_url_identifier_prefix']);
        foreach (array(
                'clean_url_main_path',
                'clean_url_collection_generic',
                'clean_url_item_generic',
                'clean_url_file_generic',
            ) as $posted) {
            $value = trim($post[$posted], ' /');
            $post[$posted] = empty($value) ? '' : trim($value) . '/';
        }

        // The default url should be allowed for items and files.
        $post['clean_url_item_alloweds'][] = $post['clean_url_item_default'];
        $post['clean_url_item_alloweds'] = array_values(array_unique($post['clean_url_item_alloweds']));
        $post['clean_url_file_alloweds'][] = $post['clean_url_file_default'];
        $post['clean_url_file_alloweds'] = array_values(array_unique($post['clean_url_file_alloweds']));

        foreach ($this->_options as $optionKey => $optionValue) {
            if (in_array($optionKey, array(
                    'clean_url_item_alloweds',
                    'clean_url_file_alloweds',
                    'clean_url_route_plugins',
                ))) {
               $post[$optionKey] = empty($post[$optionKey]) ? serialize(array()) : serialize($post[$optionKey]);
            }
            if (isset($post[$optionKey])) {
                set_option($optionKey, $post[$optionKey]);
            }
        }

        $this->cacheCollectionsRegex();
    }

    public function hookAfterSaveCollection($args)
    {
        $this->cacheCollectionsRegex();
    }

    public function hookAfterDeleteCollection($args)
    {
        $this->cacheCollectionsRegex();
    }

    /**
     * Add the identifiant in the list.
     */
    public function hookAdminItemsBrowseSimpleEach($args)
    {
        if (get_option('clean_url_display_admin_browse_identifier')) {
            $view = $args['view'];
            $item = $args['item'];
            $identifier = $view->getRecordIdentifier($item);
            echo '<div><span>' . ($identifier ?: '<strong>' . __('No document identifier.') . '</strong>') . '</span></div>';
       }
    }

    /**
     * Defines public routes "main_path / my_collection | generic / dc:identifier".
     *
     * @todo Rechecks performance of routes definition.
     */
    public function hookDefineRoutes($args)
    {
        if (is_admin_theme() && !get_option('clean_url_use_admin')) {
            return;
        }

        $router = $args['router'];

        $mainPath = get_option('clean_url_main_path');
        $collectionGeneric = get_option('clean_url_collection_generic');
        $itemGeneric = get_option('clean_url_item_generic');
        $fileGeneric = get_option('clean_url_file_generic');

        $allowedForItems = unserialize(get_option('clean_url_item_alloweds'));
        $allowedForFiles = unserialize(get_option('clean_url_file_alloweds'));

        // Note: order of routes is important: Zend checks from the last one
        // (most specific) to the first one (most generic).

        $collectionsRegex = get_option('clean_url_collection_regex');
        if (!empty($collectionsRegex)) {
            // Add a collection route.
            $route = $mainPath . $collectionGeneric;
            // Use one regex for all collections. Default is case insensitve.
            $router->addRoute('cleanUrl_collections', new Zend_Controller_Router_Route(
                $route . ':record_identifier',
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'collection-show',
                ),
                array(
                    'record_identifier' => $collectionsRegex,
            )));

            // Add a collection route for files.
            if (in_array('collection', $allowedForFiles)) {
                $router->addRoute('cleanUrl_collections_file', new Zend_Controller_Router_Route(
                    $route . ':collection_identifier/:record_identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route-collection-file',
                    ),
                    array(
                        'collection_identifier' => $collectionsRegex,
                )));
            }

            // Add a collection / item route for files.
            if (in_array('collection_item', $allowedForFiles)) {
                $router->addRoute('cleanUrl_collections_item_file', new Zend_Controller_Router_Route(
                    $route . ':collection_identifier/:item_identifier/:record_identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route-collection-item-file',
                    ),
                    array(
                        'collection_identifier' => $collectionsRegex,
                )));
            }

            // Add a collection route for items.
            if (in_array('collection', $allowedForItems)) {
                $router->addRoute('cleanUrl_collections_item', new Zend_Controller_Router_Route(
                    $route . ':collection_identifier/:record_identifier',
                    array(
                        'module' => 'clean-url',
                        'controller' => 'index',
                        'action' => 'route-collection-item',
                    ),
                    array(
                        'collection_identifier' => $collectionsRegex,
                )));
            }
        }

        // Add a generic route for files.
        if (in_array('generic', $allowedForFiles)) {
            $route = $mainPath . $fileGeneric;
            $router->addRoute('cleanUrl_generic_file', new Zend_Controller_Router_Route(
                $route . ':record_identifier',
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'route-file',
                    'collection_id' => NULL,
            )));
        }

        // Add a generic / item route for files.
        if (in_array('generic_item', $allowedForFiles)) {
            $route = $mainPath . $itemGeneric;
            $router->addRoute('cleanUrl_generic_item_file', new Zend_Controller_Router_Route(
                $route . ':item_identifier/:record_identifier',
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'route-item-file',
                    'collection_id' => NULL,
            )));
        }

        // Add a generic route for items.
        if (in_array('generic', $allowedForItems)) {
            $route = $mainPath . trim($itemGeneric, '/');
            $router->addRoute('cleanUrl_generic_items_browse', new Zend_Controller_Router_Route(
                $route,
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'items-browse',
            )));
            $router->addRoute('cleanUrl_generic_item', new Zend_Controller_Router_Route(
                $route . '/:record_identifier',
                array(
                    'module' => 'clean-url',
                    'controller' => 'index',
                    'action' => 'route-item',
                    'collection_id' => NULL,
            )));
        }
    }

    /**
     * Add a route to a plugin.
     *
     * @param array $routePlugins Route plugins array.
     * @return array Filtered route plugins array.
    */
    public function filterCleanUrlRoutePlugins($routePlugins)
    {
        $routePlugins['bookreader'] = array(
            'plugin' => 'BookReader',
            'record_types' => array('Item'),
            'map' => array(
                'id' => 'id',
            ),
            'params' => array(
                'module' => 'book-reader',
                'controller' => 'viewer',
                'action' => 'show',
            ),
        );

        $routePlugins['embed'] = array(
            'plugin' => 'EmbedCodes',
            'record_types' => array('Item'),
            'map' => array(
                'id' => 'id',
            ),
            'params' => array(
                'module' => 'embed-codes',
                'controller' => 'index',
                'action' => 'embed',
            ),
        );

        return $routePlugins;
    }

    /**
     * Cache collection identifiers as string to speed up routing.
     */
    protected function cacheCollectionsRegex()
    {
        // Get all collection identifiers with one query.
        try {
            // The view helper is not available during intall, upgrade and tests.
            $collectionIdentifiers = get_view()
                ->getRecordTypeIdentifiers('Collection', false);
        } catch (Zend_Loader_PluginLoader_Exception $e) {
            $collectionIdentifiers= $this->getViewHelperRTI()
                ->getRecordTypeIdentifiers('Collection', false);
        }

        // To avoid issues with identifiers that contain another identifier,
        // for example "collection_bis" contains "collection", they are ordered
        // by reversed length.
        // This issue occurs in Omeka S, but not in Omeka Classic, but it may
        // allow a quicker routing and simplify upgrade.
        array_multisort(
            array_map('strlen', $collectionIdentifiers),
            $collectionIdentifiers
        );
        $collectionIdentifiers = array_reverse($collectionIdentifiers);

        $collectionsRegex = array_map('preg_quote', $collectionIdentifiers);
        // To avoid a bug with identifiers that contain a "/", that is not
        // escaped with preg_quote().
        $collectionsRegex = str_replace('/', '\/', implode('|', $collectionsRegex));

        set_option('clean_url_collection_regex', $collectionsRegex);
    }


    /**
     * Get the view helper getRecordTypeIdentifiers.
     *
     * @return CleanUrl_View_Helper_GetRecordTypeIdentifiers
     */
    protected function getViewHelperRTI()
    {
        require_once dirname(__FILE__)
            . DIRECTORY_SEPARATOR . 'views'
            . DIRECTORY_SEPARATOR . 'helpers'
            . DIRECTORY_SEPARATOR . 'GetRecordTypeIdentifiers.php';
        return new CleanUrl_View_Helper_GetRecordTypeIdentifiers();
    }
}
