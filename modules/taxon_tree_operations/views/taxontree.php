<script>
window.baseUrl = '<?php echo url::base(); ?>';
var taxon_list_id = <?php echo kohana::config('uksi_operations.taxon_list_id', FALSE, FALSE ); ?>;
</script>

<style>
  .leaf-icon {
    color: green;
  }

  .branch-icon {
    color: orange;
  }

  .root-icon {
    color: blue;
  }  
</style>

<link rel="stylesheet"
  href="<?php echo url::base(); ?>media/js/jstree/dist/themes/default/style.min.css">

<script  src="<?php echo url::base(); ?>media/js/jstree/dist/jstree.min.js"></script>

<div> 

<div class="container" >
  <div class="section" >
    <div class="row">
      <select id="taxon-list-select" class="form-control" ></select><br/><div id="taxon-list-info"></div>
    </div>
  </div>

  <div class="section" >
    <div class="row">
      <div id="taxon"></div>
      <label>Search: 
        <input id="tree-search" type="text" class="filterInput form-control ">
        <button id="toggle-search-results" class="btn btn-default" title="Show search results">
          <span class="glyphicon glyphicon-chevron-down"></span>
        </button>
      </label>  
        <br/>

        <div id="search-results"></div>
    </div>
  </div>

  <div class="section" >
    <div class="row">
      <div class="col-md-6" >
         <div id="taxon-tree"></div>     
      </div>
      <div class="col-md-6" >
<div id="taxon_details"></div>
      </div>
    </div>
  </div>
</div>




<script  src="<?php echo url::base(); ?>media/js/taxontree/taxontree.js"></script>