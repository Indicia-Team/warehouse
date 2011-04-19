<?php
require '../../client_helpers/data_entry_helper.php';
require 'data_entry_config.php';
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
$entity = null;
function getField($fname)
{
  global $entity;
  if ($entity != null && array_key_exists($fname, $entity))
  {
    return $entity[$fname];
  }
  else
  {
    return null;
  }
}
// If we have POST data, we're posting a comment.
if ($_POST){
  $comments = data_entry_helper::wrap($_POST, 'occurrence_comment');
  $submission = array('submission' => array('entries' => array(
  array ( 'model' => $comments ))));
  $response = data_entry_helper::forward_post_to('save', $submission);
  // We look at the id parameter passed in the get string
 } else if (array_key_exists('id', $_GET)){
  $url = 'http://localhost/indicia/index.php/services/data/occurrence/'.$_GET['id'];
  $url .= "?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth['nonce'];
  $session = curl_init($url);
  curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
  $entity = json_decode(curl_exec($session), true);
  $entity = $entity[0];

  // Now grab the list of occurrence comments.
  $url = 'http://localhost/indicia/index.php/services/data/occurrence_comment';
  $url .= "?mode=json&occurrence_id=".$_GET['id'];
  $csess = curl_init($url);
  curl_setopt($csess, CURLOPT_RETURNTRANSFER, true);
  $comments = json_decode(curl_exec($csess), true);

  $commentDivContent = '';

  foreach ($comments as $comment){
    $commentDivContent .= "<div class='comment'>";
    $commentDivContent .= "<div class='header'>";
    $commentDivContent .= "<span class='user'>";
    if ($comment['username'] != 'Unknown')
    {
      $commentDivContent .= $comment['username'];
    }
    else if ($comment['person_name']!='')
    {
      $commentDivContent .= $comment['person_name'];
    }
    else
    {
      $commentDivContent .= "Anonymous";
    }
    $commentDivContent .= "</span>";
    $commentDivContent .= "<span class='timestamp'>";
    $commentDivContent .= $comment['updated_on'];
    $commentDivContent .= "</span>";
    $commentDivContent .= "</div>";
    $commentDivContent .= "<div class='commentText'>";
    $commentDivContent .= $comment['comment'];
    $commentDivContent .= "</div>";
    $commentDivContent .= "</div>";
  }

  if (array_key_exists('refreshComments', $_GET) && $_GET['refreshComments'] == true):
    // Just return comments div
    echo $commentDivContent;
  else:
    ?>
    <html>
    <head>
    <link rel='stylesheet' href='demo.css' />
  <link rel='stylesheet' href='../../media/css/viewform.css' />
    <link rel='stylesheet' href='../../media/css/comments.css' />
    <script type="text/javascript" src="../../media/js/jquery.js"></script>
    <script type="text/javascript" src="../../media/js/jquery.form.js"></script>
    <script type="text/javascript" src="../../media/js/jquery-ui.custom.min.js"></script>
    <script type="text/javascript" src="../../media/js/json2.js"></script>
    <script type='text/javascript'>
    (function($){
      $(document).ready(function(){
        $("div#addComment").hide();
        $("div#addCommentToggle").click(function(e){
          $("div#addComment").toggle('slow');
        });
        $("#commentForm").ajaxForm({type: 'post', clearForm: true, success: function(response){
       // Close the comments box.
       $("div#addComment").toggle('slow');
       // Add the new comment to the thread.
       $("div#comments").load(window.location + '&refreshComments=true');
     }
   });
   $("#commentForm input#cancelComment").click(function(e){
     $("#commentForm").resetForm();
   });
       });
     })(jQuery);
     </script>
     <title>Occurrence Viewer: Occurrence no <?php echo getField('id'); ?></title>
     </head>
     <body>
     <div id="wrap">
     <h1>Occurrence Details</h1>
     <div class='viewform'>
     <ol>
     <li><span class='label'>Taxon:</span><span class='item'><?php echo getField('taxon'); ?></span></li>
     <li><span class='label'>Date:</span><span class='item'><?php echo getField('date_start').' to '. $entity['date_end']; ?></span></li>
     <li><span class='label'>Location:</span><span class='item'><?php echo getField('location'); ?></span></li>
     <li><span class='label'>Spatial Ref:</span><span class='item'><?php echo getField('entered_sref'); ?></span></li>
     <li><span class='label'>Determiner:</span><span class='item'><?php echo getField('determiner'); ?></span></li>
     </ol>
     </div>
     <div id='addCommentToggle'>Add Comment</div>
     <div id='addComment'>
     <form method='post' id='commentForm'>
     <?php
     // This PHP call demonstrates inserting authorisation into the form, for website ID
     // 1 and password 'password'
     echo data_entry_helper::get_auth(1,'password');
     $readAuth = data_entry_helper::get_read_auth(1, 'password');
     ?>
     <input type='hidden' name='occurrence_id' value='<?php echo $_GET['id']; ?>' />
     <fieldset>
     <legend>Add New Comment.</legend>
     <!-- pointless check here - eventually we replace this with a check of whether a user is logged in -->
     <?php if (false): ?>
     <!-- Here we put details of the logged in user -->
     <?php else: ?>
     <label for='email_address'>E-mail:</label>
     <input type='text' id='email_address' name='email_address' value='' />
     <br />
     <label for='person_name'>Name:</label>
     <input type='text' id='person_name' name='person_name' value='' />
     <?php endif; ?>
     <textarea id='comment' name='comment' rows='5'></textarea>
     </fieldset>
     <input type='submit' id='submitComment' value='Post' />
     <input type='button' id='cancelComment' value='Cancel' />
     </form>
     </div>
     <div id='comments'>
     <?php
     // Put comment div
     echo $commentDivContent;
     ?>
     </div>
     </div>
     </body>
     </html>
     <?php endif; } ?>