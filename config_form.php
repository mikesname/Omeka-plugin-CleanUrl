<p>
<?php
    echo __('"CleanUrl" plugin allows to have clean, readable and search engine optimized Urls like http://example.com/my_collection/dc:identifier.') . '<br />';
    echo __('A main path can be added before collection names, for example "collections", "library" or "archives", to get Urls like http://example.com/my_archives/my_collection/dc:identifier.') . '<br />';
    echo __('If an item or a file has no identifier, its id is used, for example "http://example.com/library/image/20/13", depending on selected formats.');
?>
</p>
<fieldset id="fieldset-identifiers"><legend><?php echo __('Identifiers'); ?></legend>
    <div class="field">
        <label for="clean_url_identifier_prefix">
            <?php echo __('Prefix of identifiers to use'); ?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formText('clean_url_identifier_prefix', get_option('clean_url_identifier_prefix'), NULL); ?>
            <p class="explanation">
                <?php echo __('Urls are built with the sanitized Dublin Core identifier with the selected prefix, for example "item:", "record:" or "doc =". Let empty to use simply the first identifier.') . '<br />'; ?>
                <?php echo __('If this identifier does not exists, the Omeka item id will be used.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <label for="clean_url_case_insensitive">
            <?php echo __('Allow case insensitive identifier'); ?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formCheckbox('clean_url_case_insensitive', TRUE, array('checked' => (boolean) get_option('clean_url_case_insensitive'))); ?>
            <p class="explanation">
                <?php echo __('If checked, all items will be available via an insensitive url too. This option is generally useless, because searches in database are generally case insensitive by default.') . '<br />'; ?>
                <?php echo __('Furthermore, it can slow server responses, unless you add an index for lower texts.'); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-main-path"><legend><?php echo __('Main base path'); ?></legend>
    <div class="field">
        <label for="clean_url_main_path">
            <?php echo __('Main path to add'); ?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formText('clean_url_main_path', get_option('clean_url_main_path'), NULL); ?>
            <p class="explanation">
                <?php echo __('The main path to add in the beginning of the url, for example "collections", "library" or "archives".'); ?>
                <?php echo __('Let empty if you do not want any.'); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-collections"><legend><?php echo __('Collections'); ?></legend>
    <div class="field">
        <label for="clean_url_collection_generic">
            <?php echo __('Generic name to add before collection identifier'); ?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formText('clean_url_collection_generic', get_option('clean_url_collection_generic'), NULL); ?>
            <p class="explanation">
                <?php echo __('This main path is added before the collection name, for example "my_collections".'); ?>
                <?php echo __("Let empty if you don't want any."); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-items"><legend><?php echo __('Items'); ?></legend>
    <div class="field">
        <label for="clean_url_item_url">
            <?php echo __('Url of items'); ?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formRadio('clean_url_item_url', get_option('clean_url_item_url'), NULL, array(
                    'generic' => '/ generic / dc:identifier',
                    'collection' => '/ collection identifier / dc:identifier',
                )); ?>
            <p class="explanation">
                <?php echo __('Select the form of the url for items.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <label for="clean_url_item_generic">
            <?php echo __('Generic name to add before item identifier'); ?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formText('clean_url_item_generic', get_option('clean_url_item_generic'), NULL); ?>
            <p class="explanation">
                <?php echo __('The generic name to use if generic identifier is selected above, for example "item", "record" or "doc".'); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-files"><legend><?php echo __('Files'); ?></legend>
    <div class="field">
        <label for="clean_url_files_url">
            <?php echo __('Url of files'); ?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formRadio('clean_url_file_url', get_option('clean_url_file_url'), NULL, array(
                    'generic' => '/ generic / dc:identifier',
                    'generic_item' => '/ generic / item identifier / dc:identifier',
                    'collection' => '/ collection identifier / dc:identifier',
                    'collection_item' => '/ collection identifier / item identifier / dc:identifier',
                )); ?>
            <p class="explanation">
                <?php echo __('Select the form of the url for files.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <label for="clean_url_file_generic">
            <?php echo __('Generic name to add before file identifier'); ?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formText('clean_url_file_generic', get_option('clean_url_file_generic'), NULL); ?>
            <p class="explanation">
                <?php echo __('The generic name to use if generic identifier is selected above, for example "file", "record" or "image".'); ?>
                <?php echo __('Currently, it should be different from the name used for items.'); ?>
            </p>
        </div>
    </div>
</fieldset>
