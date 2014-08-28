/* ************ BACK BUTTON FIX *****************************************************
 * Since the back button does not work in current iOS 7.1.1 while in app mode, it is 
 * necessary to manually assign the back button urls.
 * 
 * Set up the URL replacements so that the id of the page is matched with the new URL 
 * of the back buttons it contains. The use of wild cards is possible eg.

 backButtonUrls = {
  'app-*':'home',
  'app-examples':'home',
  'tab-location':'home' 
};

*/

/*
 * Fixes back buttons for specific page
 */
function fixPageBackButtons(page_id, backButtonUrls){
  console.log('FIXING: back buttons ( ' + page_id + ')');

  var url = "";
  //check if in array
  for (var regex in backButtonUrls){
    var re = new RegExp(regex, "i");
    if(re.test(page_id)){
      url = backButtonUrls[regex];
      break;
    } 
  }
  
  //return if no match
  if (url == ""){
   return; 
  }
  
  var buttons = jQuery("div[id='" + page_id + "'] a[data-rel='back']");
  buttons.each( function(index, button){
    //assign new url to the button
    jQuery(button).attr('href', url);   
  });
}

/************ END BACK BUTTON FIX ****************************************************/


/*
 * Generic function to detect the browser
 * 
 * Chrome has to have and ID of both Chrome and Safari therefore
 * Safari has to have an ID of only Safari and not Chrome
 */
function browserDetect(browser){
    if (browser == 'Chrome' || browser == 'Safari'){
        var is_chrome = navigator.userAgent.indexOf('Chrome') > -1;
        var is_safari = navigator.userAgent.indexOf("Safari") > -1;

        if (is_safari){
          if (browser == 'Chrome'){
            //Chrome
            return (is_chrome) ? true : false;
          } else {
            //Safari
            return (!is_chrome) ? true : false;
          }
        } 
        return false;
    }
    return (navigator.userAgent.indexOf(browser) > -1);
}