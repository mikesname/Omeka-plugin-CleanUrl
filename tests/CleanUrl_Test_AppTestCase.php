<?php
/**
 * @copyright Daniel Berthereau, 2012-2014
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 * @package CleanUrl
 */

/**
 * Base class for CleanUrl tests.
 */
class CleanUrl_Test_AppTestCase extends Omeka_Test_AppTestCase
{
    const PLUGIN_NAME = 'CleanUrl';

    protected $_isAdminTest = false;

    protected $_view;

    // All records are saved during start, so all identifiers and routes can be
    // checked together.
    protected $_recordsByType = array(
        'Collection' => array(
            array(
                'collection_id' => 1,
                'Title' => 'Title of Collection #1',
                'Identifier' => array(
                    'document: Identifier_of_Collection_1',
            )),
            array(
                'collection_id' => 2,
                'Title' => 'Title of Collection #2',
                'Identifier' => array(
                    'document: Identifier_of_Collection_2',
                    // A duplicate identifier of an item.
                    'document: Identifier_of_Item_3',
                    // A duplicate identifier of a file.
                    'document: Identifier_of_File_2',
            )),
            array(
                'collection_id' => 3,
                'Title' => 'Title of Collection with utf-8 characters, as é',
                'Identifier' => array(
                    // An identifier with spaces and non-american character.
                    'document: Identifier of Collection with a space and é character',
            )),
        ),

        'Item' => array(
            array(
                'collection_id' => 1,
                'item_id' => 1,
                'files' => 2,
                'Title' => 'Title of Item #1',
                'Identifier' => array(
                    'document: Identifier_of_Item_1',
            )),
            array(
                'Title' => 'Title of Item #2',
                'Identifier' => array(
                    'document: Identifier_of_Item_2',
                    'document: Second_Identifier_of_Item_2',
                    // Unsanitized identifier of Item 1 (with spaces).
                    'document: Unsanitized identifier of Item 2',
                    // A metadata that is not an identifier (no prefix).
                    'Third_Identifier_of_Item_2',
                    // An identifier that contains a forbidden character ("/").
                    'document: Fourth/Identifier_of_Item_2',
                    // A duplicate identifier of a collection.
                    'document: Identifier_of_Collection_2',
                    // A duplicate identifier of a file.
                    'document: Identifier_of_File_2',
                    // A duplicate identifier of a file.
                    'document: Second_Identifier_of_File_2',
            )),
            array(
                'Title' => 'Title of Item #3',
                'Identifier' => array(
                    'document: Identifier_of_Item_3',
                    // Another prefix.
                    'doc: Identifier_of_Item_3',
            )),
            array(
                'collection_id' => 3,
                'item_id' => 4,
                'files' => 1,
                'Title' => 'Title of Item with µ character',
                'Identifier' => array(
                    'document: Identifier of Item with µ character',
            )),
        ),

        'File' => array(
            array(
                'item_id' => 1,
                'file_key' => 0,
                'Title' => 'Title of File #1',
                'Identifier' => array(
                    'document: Identifier_of_File_1',
            )),
            array(
                'item_id' => 1,
                'file_key' => 1,
                'Title' => 'Title of File #2',
                'Identifier' => array(
                    'document: Identifier_of_File_2',
                    'document: Second_Identifier_of_File_2',
                    // A duplicate identifier of a collection.
                    'document: Identifier_of_Collection_2',
                    // A duplicate identifier of an item.
                    'document: Identifier_of_Item_2',
                    // A duplicate identifier of an item.
                    'document: Identifier_of_Item_3',
            )),
            array(
                'item_id' => 4,
                'file_key' => 0,
                'Title' => 'Title of File with Æ character',
                'Identifier' => array(
                    'document: Identifier of File with Æ character',
            )),
        ),
    );

    public function setUp()
    {
        parent::setUp();

        $this->_view = get_view();
        $this->_view->addHelperPath(CLEAN_URL_DIR . '/views/helpers', self::PLUGIN_NAME . '_View_Helper_');

        $pluginHelper = new Omeka_Test_Helper_Plugin;
        $pluginHelper->setUp(self::PLUGIN_NAME);

        // Add constraints if derivatives have been added in the config file.
        $fileDerivatives = Zend_Registry::get('bootstrap')->getResource('Config')->fileDerivatives;
        if (!empty($fileDerivatives) && !empty($fileDerivatives->paths)) {
            foreach ($fileDerivatives->paths->toArray() as $type => $path) {
                set_option($type . '_constraint', 1);
            }
        }

        $this->_prepareRecords();
        $this->_reloadRoutes();
    }

    public function assertPreConditions()
    {
        $records = $this->db->getTable('Collection')->findAll();
        $count = count($this->_recordsByType['Collection']);
        $this->assertEquals($count, count($records), sprintf('There should be %d collections.', $count));

        $records = $this->db->getTable('Item')->findAll();
        $count = count($this->_recordsByType['Item']);
        $this->assertEquals($count, count($records), sprintf('There should be %d items.', $count));

        $records = $this->db->getTable('File')->findAll();
        $count = count($this->_recordsByType['File']);
        $this->assertEquals($count, count($records), sprintf('There should be %d files.', $count));
    }

    /**
     * Get a record by title.
     *
     * @internal This function allows a quick check of records, because id can
     * change between tests.
     */
    protected function getRecordByTitle($title)
    {
        $record = null;
        $elementSetName = 'Dublin Core';
        $elementName = 'Title';
        $element = $this->db->getTable('Element')->findByElementSetNameAndElementName($elementSetName, $elementName);
        $elementTexts = $this->db->getTable('ElementText')->findBy(array('element_id' => $element->id, 'text' => $title), 1);
        $elementText = reset($elementTexts);
        if ($elementText) {
            $record = get_record_by_id($elementText->record_type, $elementText->record_id);
        }
        return $record;
    }

    /**
     * Set some records with identifier to test.
     */
    protected function _prepareRecords()
    {
        // Remove default records.
        $this->_deleteAllRecords();

        $metadata = array('public' => true);
        $isHtml = false;

        $collections = array();
        $items = array();
        $files = array();
        foreach ($this->_recordsByType as $type => $recordsMetadata) {
            foreach ($recordsMetadata as $recordMetadata) {
                $identifiers = array();
                foreach ($recordMetadata['Identifier'] as $identifier) {
                    $identifiers[] = array('text' => $identifier, 'html' => $isHtml);
                }
                $elementTexts = array('Dublin Core' => array(
                    'Title' => array(array('text' => $recordMetadata['Title'], 'html' => $isHtml)),
                    'Identifier' => $identifiers,
                ));
                switch ($type) {
                    case 'Collection':
                        $collections[$recordMetadata['collection_id']] = insert_collection($metadata, $elementTexts);
                        break;
                    case 'Item':
                        $metadataItem = $metadata;
                        if (!empty($recordMetadata['collection_id'])) {
                            $metadataItem['collection_id'] = $collections[$recordMetadata['collection_id']]->id;
                        }
                        $record = insert_item($metadataItem, $elementTexts);
                        if (!empty($recordMetadata['files'])) {
                            $fileUrl = TEST_DIR . '/_files/test.txt';
                            $files[$recordMetadata['item_id']] = insert_files_for_item($record, 'Filesystem', array_fill(0, $recordMetadata['files'], $fileUrl));
                        }
                        break;
                    case 'File':
                        $record = $files[$recordMetadata['item_id']][$recordMetadata['file_key']];
                        $record->addElementTextsByArray($elementTexts);
                        $record->save();
                        break;
                }
            }
        }
    }

    protected function _deleteAllRecords()
    {
        foreach (array('Collection', 'Item', 'File') as $recordType) {
            $records = get_records($recordType, array(), 0);
            foreach ($records as $record) {
                $record->delete();
            }
        }
    }

    protected function _reloadRoutes()
    {
        $plugin = new CleanUrlPlugin;
        $plugin->hookDefineRoutes(array('router' => Zend_Controller_Front::getInstance()->getRouter()));
    }
}
