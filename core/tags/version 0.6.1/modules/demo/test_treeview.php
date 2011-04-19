<html>
<head>
<?php
include '../../client_helpers/data_entry_helper.php';
include 'data_entry_config.php';
?>
<title>Indicia external site treeview test page</title>
</head>
<body>
<h1>Treeview Test Page</h1>

 <?php
 // This PHP call demonstrates inserting authorisation into the form, for website ID
 // 1 and password 'password'
 $readAuth = data_entry_helper::get_read_auth(1, 'password');
 ?>

<?php 
  $species_list_args=array(
        'label'=>lang::get('species'),
        'fieldname'=>'occurrence:taxa_taxon_list_id',
        'table'=>'taxa_taxon_list',
        'captionField'=>'taxon',
        'valueField'=>'id',
        'parentField'=>'parent_id',
        'extraParams'=>$readAuth
    );
    // Dynamically generate the species selection control required.        
    echo data_entry_helper::tree_browser($species_list_args);
?>

 </body>
 <?php echo data_entry_helper::dump_javascript(); ?>
 </html>
