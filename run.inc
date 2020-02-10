<?php
function custom_model_run_form($form, &$form_state)
{
	$options_first = _list_of_custom_model();
	$url_custom_model_id = (int) arg(2);
	$custom_model_data = _custom_model_information($url_custom_model_id);
	if ($custom_model_data == 'Not found') {
		$url_custom_model_id = '';
	} //$custom_model_data == 'Not found'
	if (!$url_custom_model_id) {
		$selected = isset($form_state['values']['custom_model']) ? $form_state['values']['custom_model'] : key($options_first);
	} //!$url_custom_model_id
	elseif ($url_custom_model_id == '') {
		$selected = 0;
	} //$url_custom_model_id == ''
	else {
		$selected = $url_custom_model_id;
	}
	$form = array();
	$form['custom_model'] = array(
		'#type' => 'select',
		'#title' => t('Title of the Custom Model'),
		'#options' => _list_of_custom_model(),
		'#default_value' => $selected,
		'#ajax' => array(
			'callback' => 'custom_model_project_details_callback'
		)
	);
	if (!$url_custom_model_id) {
		$form['custom_model_details'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax_custom_model_details"></div>'
		);
		$form['selected_custom_model'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax_selected_custom_model"></div>'
		);
	} //!$url_custom_model_id
	else {
		$custom_model_default_value = $url_custom_model_id;
		$form['custom_model_details'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax_custom_model_details">' . _custom_model_details($custom_model_default_value) . '</div>'
		);
		$form['selected_custom_model'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax_selected_custom_model">' . l('Download Abstract', 'custom-model/download/project-file/' . $custom_model_default_value) . '<br>' . l('Download Custom Model', 'custom-model/full-download/project/' . $custom_model_default_value) . '</div>'
		);
	}
	return $form;
}
function custom_model_project_details_callback($form, $form_state)
{
	$commands = array();
	$custom_model_default_value = $form_state['values']['custom_model'];
	if ($custom_model_default_value != 0) {
		$form['custom_model_details']['#markup'] = _custom_model_details($custom_model_default_value);
		$custom_model_details = _custom_model_information($custom_model_default_value);
		$provider = user_load($custom_model_details->uid);
		if ($custom_model_details->uid > 0) {
			$commands[] = ajax_command_html('#ajax_selected_custom_model',l('Download Abstract', 'custom-model/download/project-file/' . $custom_model_default_value) . '<br>' . l('Download Custom Model', 'custom-model/full-download/project/' . $custom_model_default_value));
		} //$custom_model_details->uid > 0
		else {
			$commands[] = ajax_command_html('#ajax_selected_custom_model', '');
			$commands[] = ajax_command_html('#ajax_selected_custom_model_dwsim', '');
		}
		$commands[] = ajax_command_html('#ajax_custom_model_details', _custom_model_details($custom_model_default_value));
	} //$custom_model_default_value != 0
	else {
		// $form['lab_experiment_list']['#options'] = _ajax_get_experiment_list();
		//  $commands[] = ajax_command_replace('#ajax_selected_experiment', drupal_render($form['lab_experiment_list']));
		$commands[] = ajax_command_html('#ajax_custom_model_details', '');
		$commands[] = ajax_command_html('#ajax_selected_custom_model', '');
		$commands[] = ajax_command_html('#ajax_selected_custom_model_dwsim', '');
		$commands[] = ajax_command_data('#ajax_selected_custom_model', 'form_state_value_select', $form_state['values']['custom_model']);
	}
	return array(
		'#type' => 'ajax',
		'#commands' => $commands
	);
}
function bootstrap_table_format($headers, $rows)
{
	$thead = "";
	$tbody = "";
	foreach ($headers as $header) {
		$thead .= "<th>{$header}</th>";
	} //$headers as $header
	foreach ($rows as $row) {
		$tbody .= "<tr>";
		foreach ($row as $data) {
			$tbody .= "<td>{$data}</td>";
		} //$row as $data
		$tbody .= "</tr>";
	} //$rows as $row
	$table = "
            <table class='table table-bordered table-hover' style='margin-left:-140px'>
                <thead>{$thead}</thead>
                <tbody>{$tbody}</tbody>
            </table>
        ";
	return $table;
}
/*****************************************************/
function _list_of_custom_model()
{
	$custom_model_titles = array(
		'0' => 'Please select...'
	);
	//$lab_titles_q = db_query("SELECT * FROM {custom_model_proposal} WHERE solution_display = 1 ORDER BY lab_title ASC");
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('approval_status', 3);
	$query->orderBy('project_title', 'ASC');
	$custom_model_titles_q = $query->execute();
	while ($custom_model_titles_data = $custom_model_titles_q->fetchObject()) {
		$custom_model_titles[$custom_model_titles_data->id] = $custom_model_titles_data->project_title . ' (Proposed by ' . $custom_model_titles_data->name_title . ' ' . $custom_model_titles_data->contributor_name . ')';
	} //$custom_model_titles_data = $custom_model_titles_q->fetchObject()
	return $custom_model_titles;
}
function _custom_model_information($proposal_id)
{
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('id', $proposal_id);
	$query->condition('approval_status', 3);
	$custom_model_q = $query->execute();
	$custom_model_data = $custom_model_q->fetchObject();
	if ($custom_model_data) {
		return $custom_model_data;
	} //$custom_model_data
	else {
		return 'Not found';
	}
}
function _custom_model_details($custom_model_default_value)
{
	$custom_model_details = _custom_model_information($custom_model_default_value);
	if ($custom_model_default_value != 0) {
		$form['custom_model_details']['#markup'] = '<span style="color: rgb(128, 0, 0);"><strong>About the Custom Model</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Proposer Name:</strong> ' . $custom_model_details->name_title . ' ' . $custom_model_details->contributor_name . '</li>' . '<li><strong>Title of the Custom Model:</strong> ' . l($custom_model_details->project_title,'custom-model/full-download/project/' . $custom_model_default_value) . '</li>' . '<li><strong>University:</strong> ' . $custom_model_details->university . '</li></ul>';
		$details = $form['custom_model_details']['#markup'];
		return $details;
	} //$custom_model_default_value != 0
}