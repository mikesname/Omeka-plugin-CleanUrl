<?php
    echo '<p>' . __('"CleanUrl" plugin allows to have clean, readable and search engine optimized URL like http://example.com/my_collection/dc:identifier.') . '<br />';
    echo __('A main path can be added before collection names, for example "collections" or "library", to get url like http://example.com/main_path/my_collection/dc:identifier.') . '<br />';
    echo __('A generic and persistent url for all items can be used too, for example http://example.com/record/dc:identifier.') . '</p>';
?>
<div class="field">
    <label for="clean_url_use_collection">
        <?php echo __('Use Url with names of collections');?>
    </label>
    <div class="inputs">
        <?php echo __v()->formCheckbox('clean_url_use_collection', TRUE,
    array('checked' => (boolean) get_option('clean_url_use_collection')));?>
        <p class="explanation">
            <?php echo __('If selected, each item will be available via http://example.com/my_collection/dc:identifier.') . '<br />';?>
        </p>
    </div>
</div>
<div>
    <label>
        <?php echo __('Short names of collections');?>
    </label>
    <p class="explanation">
        <?php echo '<br />' . __('These short names are used if the previous option is selected.') . '<br />';
        echo __('The name is case sensitive. For practical reasons, a lowercase route is added too.');?>
    </p>
    <?php foreach ($collections as $collection) {
        $id = 'clean_url_collection_shortname_' . $collection->id;
    ?>
    <div class="field">
        <label for="<?php echo $id;?>">
            <?php echo '"' . $collection->name . '"';?>
        </label>
        <div class="inputs">
            <?php echo __v()->formText($id, $collection_names[$collection->id], NULL);?>
        </div>
    </div>
    <?php }?>
</div>
<div class="field">
    <label for="clean_url_collection_path">
        <?php echo __('Collection path to add');?>
    </label>
    <div class="inputs">
        <?php echo __v()->formText('clean_url_collection_path', get_option('clean_url_collection_path'), NULL);?>
        <p class="explanation">
            <?php echo __('The collection path to add if the previous option is selected, for example "collections" or "library".') . '<br />';
            echo __('Let empty if you do not want any.') . '<br />';?>
        </p>
    </div>
</div>
<div class="field">
    <label for="clean_url_use_generic">
        <?php echo __('Use a generic collection for all items');?>
    </label>
    <div class="inputs">
        <?php echo __v()->formCheckbox('clean_url_use_generic', TRUE, array('checked' => (boolean) get_option('clean_url_use_generic')));?>
        <p class="explanation">
            <?php echo __('If checked, all items will be available via a generic name http://example.com/generic/dc:identifier too.') . '<br />';?>
        </p>
    </div>
</div>
<div class="field">
    <label for="clean_url_generic">
        <?php echo __('Generic name to use');?>
    </label>
    <div class="inputs">
        <?php echo __v()->formText('clean_url_generic', get_option('clean_url_generic'), NULL);?>
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
        <?php echo __v()->formText('clean_url_generic_path', get_option('clean_url_generic_path'), NULL);?>
        <p class="explanation">
            <?php echo __('The generic path to add if the previous option is selected, for example "collections" or "library".') . '<br />';
            echo __('Let empty if you do not want any.') . '<br />';?>
        </p>
    </div>
</div>
<div class="field">
    <label for="clean_url_item_identifier_prefix">
        <?php echo __('Prefix of item identifiers to use');?>
    </label>
    <div class="inputs">
        <?php echo __v()->formText('clean_url_item_identifier_prefix', get_option('clean_url_item_identifier_prefix'), NULL);?>
        <p class="explanation">
            <?php echo __('The clean url uses the sanitized Dublin Core identifier with the selected prefix, for example "item:", "record:" or "doc:". Let empty to use simply the first item identifier.') . '<br />';
            echo __('If this identifier does not exists, the Omeka item id will be used.') . '<br />';?>
        </p>
    </div>
</div>
