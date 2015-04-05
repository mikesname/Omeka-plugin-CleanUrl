<?php
class CleanUrl_RoutingTest extends CleanUrl_Test_AppTestCase
{
    public $baseCollection;
    public $baseItem;
    public $baseFile;

    public function setUp()
    {
        parent::setUp();

        $mainPath = '/' . get_option('clean_url_collection_generic');
        $this->baseCollection = $mainPath . get_option('clean_url_collection_generic');
        $this->baseItem = $mainPath . get_option('clean_url_item_generic');
        $this->baseFile = $mainPath . get_option('clean_url_file_generic');
    }

    /**
     * Tests to check routing system for collections.
     */
    public function testRoutingCollection()
    {
        $record = $this->getRecordByTitle('Title of Collection #1');
        $url = $this->baseCollection . 'Identifier_of_Collection_1';
        $route = 'cleanUrl_collections';
        $this->dispatch($url);
        $this->assertRoute($route);
        $this->assertModule('default');
        $this->assertController('collections');
        $this->assertAction('show');
        $this->assertEquals($record->id, (integer) $this->request->getParam('id'));
    }

    /**
     * Tests to check routing system for collections with non-ascii characters.
     */
    public function testRoutingCollectionsCharacters()
    {
        $record = $this->getRecordByTitle('Title of Collection with utf-8 characters, as é');
        foreach (array(
                'Identifier of Collection with a space and é character',
                'Identifier of Collection with a space and %C3%A9 character',
                'Identifier%20of%20Collection%20with%20a%20space%20and%20%C3%A9%20character',
            ) as $value) {
            $url = $this->baseCollection . $value;
            $route = 'cleanUrl_collections';
            $this->dispatch($url);
            $this->assertRoute($route);
            $this->assertModule('default');
            $this->assertController('collections');
            $this->assertAction('show');
            $this->assertEquals($record->id, (integer) $this->request->getParam('id'));
         }
    }

    /**
     * Tests to check routing system for items.
     *
     * @todo The route "cleanUrl_collections_item" fails if "collection" is
     * allowed for files, but the code works fine, so issue is in the test.
     */
    public function testRoutingItem()
    {
        $record = $this->getRecordByTitle('Title of Item #1');
        $collectionIdentifier = 'Identifier_of_Collection_1';
        $itemIdentifier = 'Identifier_of_Item_1';
        foreach (array(
                $this->baseItem . $itemIdentifier => 'cleanUrl_generic_item',
                $this->baseCollection . $collectionIdentifier . '/' . $itemIdentifier => 'cleanUrl_collections_item',
            ) as $url => $route) {
            $this->dispatch($url);
            $this->assertRoute($route);
            $this->assertModule('default');
            $this->assertController('items');
            $this->assertAction('show');
            $this->assertEquals($record->id, (integer) $this->request->getParam('id'));
        }
    }

    /**
     * Tests to check routing system for items with non-ascii characters.
     */
    public function testRoutingItemCharacters()
    {
        $record = $this->getRecordByTitle('Title of Item with µ character');
        $collectionIdentifier = 'Identifier of Collection with a space and é character';
        $itemIdentifier = 'Identifier of Item with µ character';
        foreach (array(
                $this->baseItem . $itemIdentifier => 'cleanUrl_generic_item',
                $this->baseCollection . $collectionIdentifier . '/' . $itemIdentifier => 'cleanUrl_collections_item',
            ) as $url => $route) {
            $this->dispatch($url);
            $this->assertRoute($route);
            $this->assertModule('default');
            $this->assertController('items');
            $this->assertAction('show');
            $this->assertEquals($record->id, (integer) $this->request->getParam('id'));
        }
    }

    /**
     * Tests to check routing system for files.
     */
    public function testRoutingFile()
    {
        // Allow all routes to check all of them.
        set_option('clean_url_file_alloweds', serialize(array(
            'generic', 'generic_item', 'collection', 'collection_item',
        )));
        $this->_reloadRoutes();

        $record = $this->getRecordByTitle('Title of File #1');
        $collectionIdentifier = 'Identifier_of_Collection_1';
        $itemIdentifier = 'Identifier_of_Item_1';
        $fileIdentifier = 'Identifier_of_File_1';
        foreach (array(
                $this->baseFile . $fileIdentifier => 'cleanUrl_generic_file',
                $this->baseItem . $itemIdentifier . '/' . $fileIdentifier => 'cleanUrl_generic_item_file',
                $this->baseCollection . $collectionIdentifier . '/' . $fileIdentifier => 'cleanUrl_collections_file',
                $this->baseCollection . $collectionIdentifier . '/' . $itemIdentifier . '/' . $fileIdentifier => 'cleanUrl_collections_item_file',
            ) as $url => $route) {
            $this->dispatch($url);
            $this->assertRoute($route);
            $this->assertModule('default');
            $this->assertController('files');
            $this->assertAction('show');
            $this->assertEquals($record->id, (integer) $this->request->getParam('id'));
        }
    }

    /**
     * Tests to check routing system for files with non-ascii characters.
     */
    public function testRoutingFileCharacters()
    {
        // Allow all routes to check all of them.
        set_option('clean_url_file_alloweds', serialize(array(
            'generic', 'generic_item', 'collection', 'collection_item',
        )));
        $this->_reloadRoutes();

        $record = $this->getRecordByTitle('Title of File with Æ character');
        $collectionIdentifier = 'Identifier of Collection with a space and é character';
        $itemIdentifier = 'Identifier of Item with µ character';
        $fileIdentifier = 'Identifier of File with Æ character';
        foreach (array(
                $this->baseFile . $fileIdentifier => 'cleanUrl_generic_file',
                $this->baseItem . $itemIdentifier . '/' . $fileIdentifier => 'cleanUrl_generic_item_file',
                $this->baseCollection . $collectionIdentifier . '/' . $fileIdentifier => 'cleanUrl_collections_file',
                $this->baseCollection . $collectionIdentifier . '/' . $itemIdentifier . '/' . $fileIdentifier => 'cleanUrl_collections_item_file',
            ) as $url => $route) {
            $this->dispatch($url);
            $this->assertRoute($route);
            $this->assertModule('default');
            $this->assertController('files');
            $this->assertAction('show');
            $this->assertEquals($record->id, (integer) $this->request->getParam('id'));
        }
    }

    /**
     * Tests to check routing system for bad url.
     */
    public function testRoutingBadIdentifier1()
    {
        $url = $this->baseItem . 'False_Identifier';
        $this->setExpectedException('Omeka_Controller_Exception_404');
        $this->dispatch($url);
    }

    /**
     * Tests to check routing system for bad url.
     */
    public function testRoutingBadIdentifier2()
    {
        $url = $this->baseCollection . 'Identifier_of_Collection_1' . '/' . 'False_Identifier';
        $this->setExpectedException('Omeka_Controller_Exception_404');
        $this->dispatch($url);
    }

    /**
     * Tests to check routing system for bad url.
     */
    public function testRoutingBadIdentifier3()
    {
        $url = $this->baseCollection . 'Identifier_of_Collection_2' . '/' . 'Identifier_of_Item_1';
        $this->setExpectedException('Omeka_Controller_Exception_404');
        $this->dispatch($url);
    }

    /**
     * Tests to check routing system for bad url.
     */
    public function testRoutingBadIdentifier4()
    {
        $url = $this->baseCollection . 'Identifier_of_Collection_1' . '/' . 'Identifier_of_Item_2' . '/' . 'Identifier_of_File_1';
        $this->setExpectedException('Omeka_Controller_Exception_404');
        $this->dispatch($url);
    }

    /**
     * Tests to check routing system for bad url.
     */
    public function testRoutingBadIdentifier5()
    {
        $url = $this->baseItem . 'Identifier_of_Item_1' . '/' . 'False_Identifier';
        $this->setExpectedException('Zend_Controller_Dispatcher_Exception');
        $this->dispatch($url);
    }

    /**
     * Tests to check routing system for bad url.
     */
    public function testRoutingBadIdentifier6()
    {
        $url = $this->baseItem . 'Fourth/Identifier_of_Item_2';
        $this->setExpectedException('Zend_Controller_Dispatcher_Exception');
        $this->dispatch($url);
    }

    /**
     * Tests to check routing system for bad url.
     */
    public function testRoutingBadIdentifier7()
    {
        $url = $this->baseItem . 'Fourth/Identifier_of_Item_2';
        $this->setExpectedException('Zend_Controller_Dispatcher_Exception');
        $this->dispatch($url);
    }

    /**
     * Tests to check routing system for bad url.
     */
    public function testRoutingBadIdentifier8()
    {
        $url = $this->baseCollection . 'False_Identifier' . '/' . 'Identifier_of_Item_1';
        $this->setExpectedException('Zend_Controller_Dispatcher_Exception');
        $this->dispatch($url);
    }
}
