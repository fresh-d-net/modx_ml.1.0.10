<form name="parser" action="#" onsubmit="getform('/manager/index.php', document.getElementById('parser'), save_config_ok);">
	<input type="hidden" id="a" name="a" value="[+modulea+]" />
	<input type="hidden" id="id" name="id" value="[+moduleid+]" />
	<input type="hidden" id="action" name="act" value="import" />

	<div class="">
		<label>Вставить файл<input type="file" name="file" value="Вставить файл"></label>
	</div>

	<p>или</p>

	<div class="">
		<label>Указать адрес файла<input type="text" name="url" value="" placeholder="url к файлу"></label>
	</div>

	<input type="submit">
</form>