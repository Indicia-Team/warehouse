<div class="termlist">
<?php echo $table ?>
<br />
Notes:
<ul>
<li>All Users must have an associated 'Person' - in order to create a new user the 'Person' must exist first.</li>
<li>In order to be on the list of potential users, the person must have an email address.</li>
</ul>
<form action="<?php echo url::site(); ?>person/create_from_user">
<input type="submit" value="New person" />
</form>
</div>