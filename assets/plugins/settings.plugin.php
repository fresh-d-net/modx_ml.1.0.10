<?php
$result = "
	SELECT
                 `setting_value`
        FROM
		`" . $this->dbConfig['table_prefix'] . "system_settings`
		WHERE
		`setting_name`='shop_settings_bonus_percent'
";

$sql = $this->db->query($result);

while ($myrow = mysql_fetch_row($sql)) {
	$percent = $myrow[0];
}

$e = &$this->Event;

$output = "";

if ($e->name == 'OnSiteSettingsRender') {

	$settingsArr = !empty($settings) ? explode('||', $settings) : array('Example custom setting~custom_st_example');

	$fname = !empty($pname) ? $pname : 'Дополнительные настройки';

	$output .= '</td></tr></table></div><div style="display: block;" class="tab-page" id="tabPage8"><h2 class="tab">' . $fname . '</h2><script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage8" ) );</script><table border="0" cellpadding="3" cellspacing="0"><tbody>';

	foreach ($settingsArr as $key => $st_row) {

		$st_label_arr = explode('~', $st_row);

		$custom_st_label = trim($st_label_arr[0]);

		$custom_st_name = isset($st_label_arr[1]) ? $st_label_arr[1] : 'custom_st';

		$custom_st_value = isset($st_label_arr[1]) && isset($this->config[$st_label_arr[1]]) ? trim($this->config[$st_label_arr[1]]) : '';

		$output .= '<tr><td class="warning" nowrap="">' . $custom_st_label . '</td>
        <td><input type="text" value="' . $custom_st_value . '" name="' . $custom_st_name . '" style="width: 350px;" onchange="documentDirty=true;" /></td></tr><tr><td colspan="2"><div class="split"/></td></tr>';

	}
	$output .= '</tbody></table>';

}
$e->output($output);
?>
