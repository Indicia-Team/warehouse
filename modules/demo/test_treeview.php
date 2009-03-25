<html>
<head>
<?php
include '../../client_helpers/data_entry_helper.php';
include 'data_entry/data_entry_config.php';
?>
<title>Indicia external site treeview test page</title>
<link rel="stylesheet" href="../../media/css/jquery.treeview.css" />

<script type="text/javascript" src="../../media/js/jquery-1.3.1.js"></script>
<script type="text/javascript" src="../../media/js/ui.core.js"></script>
<script type="text/javascript" src="../../media/js/json2.js"></script>

	<script src="../../media/js/jquery.cookie.js" type="text/javascript"></script>
	<script src="../../media/js/jquery.treeview.js" type="text/javascript"></script>
	<script src="../../media/js/jquery.treeview.edit.js" type="text/javascript"></script>
	<script src="../../media/js/jquery.treeview.async.js" type="text/javascript"></script>
	
</head>
<body>
<h1>Treeview Test Page</h1>

 <?php
 // This PHP call demonstrates inserting authorisation into the form, for website ID
 // 1 and password 'password'
 echo data_entry_helper::get_auth(1,'password');
 $readAuth = data_entry_helper::get_read_auth(1, 'password');
 ?>
<form action="" method='post'>
<input name='tree1' id='tree1' value='<?php echo $_POST['termtree1']; ?>'/><br />
<input name='tree2' id='tree2' value='<?php echo $_POST['termtree2']; ?>'/><br />
<input name='tree3' id='tree3' value='<?php echo $_POST['termtree3']; ?>'/><br />
<input type='submit' value='Show Values' />
<br /><br />
<label for='termtree1'>Termlist treeview, default style</label>
<?php echo data_entry_helper::treeview('termtree1', 'termlists_term', 'term', 'id', 'termlist', 'DAFOR', 'parent_id', null, $readAuth + array('view' => 'detail')); ?>
<label for='termtree2'>Termlist treeview, treeview-red style</label>
<?php echo data_entry_helper::treeview('termtree2', 'termlists_term', 'term', 'id', 'termlist', 'Surroundings', 'parent_id', null, $readAuth + array('view' => 'detail'), 'treeview-red'); ?>
<label for='termtree3'>Termlist treeview, treeview-black style</label>
<?php echo data_entry_helper::treeview('termtree3', 'termlists_term', 'term', 'id', 'termlist', 'Site_Usages', 'parent_id', null, $readAuth + array('view' => 'detail'), 'treeview-black'); ?>
</form>
 </body>
 <?php echo data_entry_helper::dump_javascript(); ?>
 </html>
 