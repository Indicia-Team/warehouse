<?php echo html::script(array(
	'media/js/jquery.ajaxQueue.js',
	'media/js/jquery.bgiframe.min.js',
	'media/js/thickbox-compressd.js',
	'media/js/jquery.autocomplete.js'
), FALSE); ?>
<script type="text/javascript" >
$(document).ready(function() {
	$("input#parent").autocomplete("<?php echo url::site() ?>services/data/taxa_taxon_list", {
		minChars : 1,
		mustMatch : true,
		extraParams : {
			taxon_list_id : "<?php echo $taxon_list_id; ?>",
			orderby : "taxon",
			mode : "json",
			qfield : "taxon",
			preferred : 'true'
		},
		parse: function(data) {
			var results = [];
			var obj = JSON.parse(data);
			$.each(obj, function(i, item) {
				results[results.length] = {
					'data' : item,
					'value' : item.id,
					'result' : item.taxon };
			});
			return results;
		},
		formatItem: function(item) {
			return item.taxon;
		},
		formatResult: function(item) {
			return item.id;
		}
	});
	$("input#parent").result(function(event, data){
		$("input#parent_id").attr('value', data.id);
	});
});
</script>
<form class="cmxform"  name='editList' action="<?php echo url::site().'taxa_taxon_list/save' ?>" method="POST" enctype="multipart/form-data">
<?php echo $metadata ?>
<fieldset>
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<input type="hidden" name="taxon_list_id" id="taxon_list_id" value="<?php echo html::specialchars($taxon_list_id); ?>" />
<legend>Naming</legend>
<ol>
<li>
<input type="hidden" name="taxon_id" id="taxon_id" value="<?php echo html::specialchars($model->taxon_id); ?>" />
<label for="taxon">Taxon Name</label>
<input id="taxon" name="taxon" value="<?php echo (($model->taxon_id != null) ? html::specialchars($model->taxon->taxon) : ''); ?>"/>
<?php echo html::error_message($model->getError('taxon_id')); ?>
</li>
<li>
<label for="language_id">Language</label>
<select id="language_id" name="language_id">
	<option value=''>&lt;Please select&gt;</option>
<?php
	$languages = ORM::factory('language')->orderby('language','asc')->find_all();
	foreach ($languages as $lang) {
		echo '	<option value="'.$lang->id.'" ';
		if ($model->taxon_id != null && $lang->id==$model->taxon->language_id) {
			echo 'selected="selected" ';
		}
		echo '>'.$lang->language.'</option>';
	}
?>
</select>
<?php echo html::error_message($model->getError('language_id')); ?>
</li>
<li>
<label for="commonNames">Common Names<br/> (one per line)</label>
<textarea rows="3" id="commonNames" name="commonNames"><?php echo html::specialchars($commonNames); ?></textarea>
</li>
<li>
<label for="synonomy">Synonyms<br/> (one per line)</label>
<textarea rows="3" id="synonomy" name="synonomy"><?php echo html::specialchars($synonomy); ?></textarea>
</li>
<?php
if ($model->image_path != null)
{
echo html::image(array('src' => 'upload/'.$model->image_path, 'width' => 100));
echo html::error_message($model->getError('image_path'));
}
echo "<li>";
// Image upload
echo "<label for='image_path'>Upload Image: </label>";
echo "<input type='file' name='image_upload' accept='png|jpg|gif' />";
?>
</li>
<li>
<label for="description">Description</label>
<textarea rows="3" id="description" name="description"><?php echo (($model->taxon_id != null) ? html::specialchars($model->taxon->description) : ''); ?></textarea>
</li>
</ol>
</fieldset>
<fieldset>
<legend>Other Details</legend>
<ol>
<li>
<label for="taxon_group_id">Taxon Group</label>
<select id="taxon_group_id" name="taxon_group_id">
	<option value=''>&lt;Please select&gt;</option>
<?php
	$taxon_groups = ORM::factory('taxon_group')->orderby('title','asc')->where('deleted', 'f')->find_all();
	foreach ($taxon_groups as $lang) {
		echo '	<option value="'.$lang->id.'" ';
		if ($model->taxon_id != null && $lang->id==$model->taxon->taxon_group_id) {
			echo 'selected="selected" ';
		}
		echo '>'.$lang->title.'</option>';
	}
?>
</select>
</li>
<li>
<label for="authority">Authority</label>
<input id="authority" name="authority" value="<?php echo (($model->taxon_id != null) ? html::specialchars($model->taxon->authority) : ''); ?>"/>
<?php echo html::error_message($model->getError('authority')); ?>
</li>
<li>
<label for="external_key">External Key</label>
<input id="external_key" name="external_key" value="<?php echo (($model->taxon_id != null) ? html::specialchars($model->taxon->external_key) : ''); ?>"/>
<?php echo html::error_message($model->getError('external_key')); ?>
</li>
<li>
<input type="hidden" name="parent_id" id="parent_id" value="<?php echo html::specialchars($model->parent_id); ?>" />
<label for="parent">Parent Taxon</label>
<input id="parent" name="parent" value="<?php echo (($model->parent_id != null) ? html::specialchars(ORM::factory('taxa_taxon_list', $model->parent_id)->taxon->taxon) : ''); ?>" />
</li>
<li>
<label for="taxonomic_sort_order">Sort Order in List</label>
<input id="taxonomic_sort_order" name="taxonomic_sort_order" class="narrow" value="<?php echo html::specialchars($model->taxonomic_sort_order); ?>" />
<?php echo html::error_message($model->getError('taxonomic_sort_order')); ?>
</li>
<li>
<label for="search_code">Search Code</label>
<input id="search_code" name="search_code" class="narrow" value="<?php echo (($model->taxon_id != null) ? html::specialchars($model->taxon->search_code) : ''); ?>"/>
<?php echo html::error_message($model->getError('search_code')); ?>
</li>
<li>
<input type="hidden" name="taxon_meaning_id" id="taxon_meaning_id" value="<?php echo html::specialchars($model->taxon_meaning_id); ?>" />
</li>
</fieldset>
<input type="submit" name="submit" value="Submit" />
<input type="submit" name="submit" value="Delete" />
<?php echo html::error_message($model->getError('deleted')); ?>
</form>

<?php if ($model->id != '' && $table != null) { ?>
	<br />
	<h2> Child Taxa </h2>
	<?php echo $table; ?>
<form class="cmxform" action="<?php echo url::site(); ?>taxa_taxon_list/create/<?php echo $model->taxon_list_id; ?>" method="post">
	<input type="hidden" name="parent_id" value=<?php echo $model->id ?> />
	<input type="submit" value="New Child Taxon" />
	</form>
<?php } ?>
