<form enctype="multipart/form-data" method="POST" action="importfile" class="content-form" id="import-form" target="submited-form">
	<label for="import-file">Name:</label>

	<input type="text" id="text-file" />
	<input type="button" value="Browse" id="select-file" />
	<input type="file" name="file" id="import-file" />
	<input type="submit" class="submit-button" id="submit-file" value="Import OPML" />
</form>
<iframe name="submited-form" id="submited-form"></iframe>