<?php
    echo '<p>' . __('"CleanUrl" plugin allows to have clean, readable and search engine optimized Urls like http://example.com/my_collection/dc:identifier.') . '<br />';
    echo __('A main path can be added before collection names, for example "collections", "library" or "archives", to get Urls like http://example.com/my_archives/my_collection/dc:identifier.') . '<br />';
    echo __('A generic and persistent Url for all items can be used too, for example http://example.com/document/dc:identifier.') . '</p>';
?>
<fieldset id="fieldset-collections"><legend><?php echo __('Collections'); ?></legend>
    <div class="field">
        <label for="clean_url_use_collection">
            <?php echo __('Adds names of collections to Urls');?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formCheckbox('clean_url_use_collection', TRUE,
        array('checked' => (boolean) get_option('clean_url_use_collection')));?>
            <p class="explanation">
                <?php echo __('If selected, each item will be available via http://example.com/my_collection/dc:identifier.') . '<br />';
                echo __('Sanitized short names below will be added to Urls. Names are case sensitive. For practical reasons, lowercase routes are added too.');?>
            </p>
        </div>
    </div>
    <div>
        <?php foreach (loop('collections') as $collection) {
            $id = 'clean_url_collection_shortname_' . $collection->id;
        ?>
        <div class="field">
            <label for="<?php echo $id;?>">
                <?php echo __('Short name for "%s" (#%d)',
                    strip_formatting(metadata('collection', array('Dublin Core', 'Title'))),
                    $collection->id); ?>
            </label>
            <div class="inputs">
                <?php echo get_view()->formText($id, $collection_names[$collection->id], NULL);?>
            </div>
        </div>
        <?php }?>
    </div>
    <div class="field">
        <label for="clean_url_collection_path">
            <?php echo __('Main path to add');?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formText('clean_url_collection_path', get_option('clean_url_collection_path'), NULL);?>
            <p class="explanation">
                <?php echo __('When the previous option is selected, this main path, for example "collections", "library" or "archives", is added before the collection name and allows to get an Url like http://example.com/my_archives/my_collection/dc:identifier.') . '<br />';
                echo __("Let empty if you don't want any and to use only short names of collections above.") . '<br />';?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-generic"><legend><?php echo __('Generic Url'); ?></legend>
    <div class="field">
        <label for="clean_url_use_generic">
            <?php echo __('Use a generic Url for all items');?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formCheckbox('clean_url_use_generic', TRUE, array('checked' => (boolean) get_option('clean_url_use_generic')));?>
            <p class="explanation">
                <?php echo __('If checked, all items will be available via a generic name like http://example.com/document/dc:identifier too.') . '<br />';?>
            </p>
        </div>
    </div>
    <div class="field">
        <label for="clean_url_generic">
            <?php echo __('Generic name to use');?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formText('clean_url_generic', get_option('clean_url_generic'), NULL);?>
            <p class="explanation">
                <?php echo __('The generic name to use if the previous option is selected, for example "item", "record" or "doc".') . '<br />';?>
            </p>
        </div>
    </div>
    <div class="field">
        <label for="clean_url_generic_path">
            <?php echo __('Generic path to add');?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formText('clean_url_generic_path', get_option('clean_url_generic_path'), NULL);?>
            <p class="explanation">
                <?php echo __('The generic path to add before the generic name if the previous option is selected, for example "collections", "library" or "archives".') . '<br />';
                echo __('Let empty if you do not want any.') . '<br />';?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-items"><legend><?php echo __('Items'); ?></legend>
    <div class="field">
        <label for="clean_url_item_identifier_prefix">
            <?php echo __('Prefix of item identifiers to use');?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formText('clean_url_item_identifier_prefix', get_option('clean_url_item_identifier_prefix'), NULL);?>
            <p class="explanation">
                <?php echo __('Clean Url builds Url with the sanitized Dublin Core identifier with the selected prefix, for example "item:", "record:" or "doc:". Let empty to use simply the first item identifier.') . '<br />';
                echo __('If this identifier does not exists, the Omeka item id will be used.') . '<br />';?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-generic"><legend><?php echo __('Insensitive case'); ?></legend>
    <div class="field">
        <label for="clean_url_case_insensitive">
            <?php echo __('Allow case insensitive identifier');?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formCheckbox('clean_url_case_insensitive', TRUE, array('checked' => (boolean) get_option('clean_url_case_insensitive')));?>
            <p class="explanation">
                <?php echo __('If checked, all items will be available via an insensitive url too. This option is generally useless, because searches in database are generally case insensitive by default.') . '<br />';?>
                <?php echo __('Furthermore, it can slow server responses, unless you add an index for lower texts.') . '<br />';?>
            </p>
        </div>
    </div>
</fieldset>
