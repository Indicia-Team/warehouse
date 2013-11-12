/* 
 * Function that allows a user to click on a map to call the Site Information page.
 * Function is specified as the customClickFn option for a standard map in the Form Structure.
 */
function call_page(features) {
  window.location.href=indiciaData.linkToPage+features[0].id;
} 

