function addRowToGrid(url, readAuth){
  // The inner function returned should be suitable for use in an onClick() method
  return function() {
    /* We need to do various things here. Firstly, we use the taxonId to execute a call
     * to the data services, returning the species details.
     */
    var speciesName;
    var authority;
    var ttlId = $('#addSpeciesBox').val();
    if (ttlId == '') return null;
    alert(readAuth['auth_token']);
    $.getJSON(url+"/taxa_taxon_list/"+ttlId+"?mode=json"+
        "&auth_token="+readAuth['auth_token']+
        "&nonce="+readAuth['nonce']+
        "&callback=?",
      function(data) {
       $.each(data, function(i, item){
         speciesName = item.taxon;
         authority = item.authority;
         /*
          * Now we need to clone the sample row, drop in
          * the correct values and place it in the correct
          * place in the DOM.
          */
         $('tr#scClonableRow td.scTaxonCell').html(speciesName + " "+authority);
         var a = 'sc:'+ttlId+':present';
         $('tr#scClonableRow td.scPresenceCell input').val(a).attr('name', a);
         // Iterate over all the occurrence attribute cells
         $('tr#scClonableRow td.scOccAttrCell > *').each(
           function(index) {
             a = $(this).attr('id').replace(/oa:(\d+)/, 'sc:'+ttlId+':occAttr:$1');
             $(this).attr('name', a);
           }
         );
         // Clone the row, drop it in the correct place
         // and remove its id attribute.
         $('tr#scClonableRow').clone(true)
            .appendTo('table.speciesCheckList tbody')
             .removeAttr('id');
       });
      }
    );
  }
}
