<?php

function custom_model_abstract_bulk_approval_form($form, &$form_state)
{
	$options_first = _bulk_list_of_custom_model_proposals();
	$selected = isset($form_state['values']['custom_model_proposals']) ? $form_state['values']['custom_model_proposals'] : key($options_first);
	$form = array();
	$form['custom_model_proposals'] = array(
		'#type' => 'select',
		'#title' => t('Title of the Custom Model'),
		'#options' => _bulk_list_of_custom_model_proposals(),
		'#default_value' => $selected,
		'#ajax' => array(
			
			'callback' => 'ajax_bulk_custom_model_abstract_details_callback'
		),
		'#suffix' => '<div id="ajax_selected_custom_model"></div><div id="ajax_selected_custom_model_pdf"></div>'
	);
	$form['custom_model_actions'] = array(
		'#type' => 'select',
		'#title' => t('Please select action for Custom Model'),
		'#options' => _bulk_list_custom_model_actions(),
		'#default_value' => 0,
		'#prefix' => '<div id="ajax_selected_custom_model_action" style="color:red;">',
		'#suffix' => '</div>',
		'#states' => array(
			'invisible' => array(
				':input[name="custom_model_proposals"]' => array(
					'value' => 0
				)
			)
		)
	);
	$form['message'] = array(
		'#type' => 'textarea',
		'#title' => t('Please specify the reason for marking resubmit/disapproval'),
		'#prefix' => '<div id= "message_submit">',
		'#states' => array(
			'visible' => array(
				array(
					':input[name="custom_model_actions"]' => array(
						'value' => 3
					)
				),
				'or',
				array(
					':input[name="custom_model_actions"]' => array(
						'value' => 2
					)
				)
			)
		)
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit'),
	);
	//var_dump('expression');die;
	return $form;
}
function ajax_bulk_custom_model_abstract_details_callback($form, $form_state)
{
	$commands = array();
	$custom_model_proposals_default_value = $form_state['values']['custom_model_proposals'];
	if ($custom_model_proposals_default_value != 0)
	{
		$commands[] = ajax_command_html('#ajax_selected_custom_model', _custom_model_details($custom_model_proposals_default_value));
		$form['custom_model_actions']['#options'] = _bulk_list_custom_model_actions();
		$commands[] = ajax_command_replace('#ajax_selected_custom_model_action', drupal_render($form['custom_model_actions']));
	} //$custom_model_proposals_default_value != 0
	else
	{
		$commands[] = ajax_command_html('#ajax_selected_custom_model', '');
		$commands[] = ajax_command_data('#ajax_selected_custom_model', 'form_state_value_select', $form_state['values']['custom_model_proposals']);
	}
	return array(
		'#type' => 'ajax',
		'#commands' => $commands
	);
}
/************************************************************/
function custom_model_abstract_bulk_approval_form_submit($form, &$form_state)
{
	global $user;
	$msg = '';
	$root_path = custom_model_path();
	//var_dump($root_path);die;
	if ($form_state['clicked_button']['#value'] == 'Submit')
	{
		if ($form_state['values']['custom_model_proposals'])
		// custom_model_abstract_del_lab_pdf($form_state['values']['custom_model_proposals']);
			if (user_access('custom model bulk manage submission'))
			{
				$query = db_select('custom_model_proposal');
				$query->fields('custom_model_proposal');
				$query->condition('id', $form_state['values']['custom_model_proposals']);
				$user_query = $query->execute();
				$user_info = $user_query->fetchObject();
				$user_data = user_load($user_info->uid);
				if ($form_state['values']['custom_model_actions'] == 1)
				{
					// approving entire project //
					$query = db_select('custom_model_submitted_abstracts');
					$query->fields('custom_model_submitted_abstracts');
					$query->condition('proposal_id', $form_state['values']['custom_model_proposals']);
					$abstracts_q = $query->execute();
					$experiment_list = '';
					while ($abstract_data = $abstracts_q->fetchObject())
					{
						db_query("UPDATE {custom_model_submitted_abstracts} SET abstract_approval_status = 1, is_submitted = 1, approver_uid = :approver_uid WHERE id = :id", array(
							':approver_uid' => $user->uid,
							':id' => $abstract_data->id
						));
						db_query("UPDATE {custom_model_submitted_abstracts_file} SET file_approval_status = 1, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", array(
							':approver_uid' => $user->uid,
							':submitted_abstract_id' => $abstract_data->id
						));
					} //$abstract_data = $abstracts_q->fetchObject()
					drupal_set_message(t('Approved Custom Model project.'), 'status');
					// email 
					$email_subject = t('[!site_name][Custom Model] Your uploaded Custom Model have been approved', array(
						'!site_name' => variable_get('site_name', '')
					));
					$email_body = array(
						0 => t('

Dear ' . $user_info->contributor_name .',

Your uploaded abstract for the Custom Model has been approved:

Title of Custom Model  : ' . $user_info->project_title . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
							'!site_name' => variable_get('site_name', ''),
							'!user_name' => $user_data->name
						))
					);
					/** sending email when everything done **/
					$email_to = $user_data->mail;
					$from = variable_get('custom_model_from_email', '');
					$bcc = variable_get('custom_model_emails', '');
					$cc = variable_get('custom_model_cc_emails', '');
					$params['standard']['subject'] = $email_subject;
					$params['standard']['body'] = $email_body;
					$params['standard']['headers'] = array(
						'From' => $from,
						'MIME-Version' => '1.0',
						'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
						'Content-Transfer-Encoding' => '8Bit',
						'X-Mailer' => 'Drupal',
						'Cc' => $cc,
						'Bcc' => $bcc
					);
					if (!drupal_mail('custom_model', 'standard', $email_to, language_default(), $params, $from, TRUE))
					{
						$msg = drupal_set_message('Error sending email message.', 'error');
					} //!drupal_mail('custom_model', 'standard', $email_to, language_default(), $params, $from, TRUE)
				} //$form_state['values']['custom_model_actions'] == 1
				elseif ($form_state['values']['custom_model_actions'] == 2)
				{
					if (strlen(trim($form_state['values']['message'])) <= 30)
					{
						form_set_error('message', t(''));
						$msg = drupal_set_message("Please mention the reason for resubmission. Minimum 30 character required", 'error');
						return $msg;
					}
					//pending review entire project 
					$query = db_select('custom_model_submitted_abstracts');
					$query->fields('custom_model_submitted_abstracts');
					$query->condition('proposal_id', $form_state['values']['custom_model_proposals']);
					$abstracts_q = $query->execute();
					$experiment_list = '';
					while ($abstract_data = $abstracts_q->fetchObject())
					{
						db_query("UPDATE {custom_model_submitted_abstracts} SET abstract_approval_status = 0, is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", array(
							':approver_uid' => $user->uid,
							':id' => $abstract_data->id
						));
						db_query("UPDATE {custom_model_proposal} SET is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", array(
							':approver_uid' => $user->uid,
							':id' => $abstract_data->proposal_id
						));
						db_query("UPDATE {custom_model_submitted_abstracts_file} SET file_approval_status = 0, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", array(
							':approver_uid' => $user->uid,
							':submitted_abstract_id' => $abstract_data->id
						));
					} //$abstract_data = $abstracts_q->fetchObject()
					drupal_set_message(t('Resubmit the project files'), 'status');
					// email 
					$email_subject = t('[!site_name][Custom Model] Your uploaded Custom Model have been marked as pending', array(
						'!site_name' => variable_get('site_name', '')
					));
					$email_body = array(
						0 => t('

Dear ' . $user_info->contributor_name .',

Kindly resubmit the project files for the project : ' . $user_info->project_title . '.

Reason for dis-approval: ' . $form_state['values']['message'] . '
 

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
							'!site_name' => variable_get('site_name', ''),
							'!user_name' => $user_data->name
						))
					);
					/** sending email when everything done **/
					$email_to = $user_data->mail;
					$from = variable_get('custom_model_from_email', '');
					$bcc = variable_get('custom_model_emails', '');
					$cc = variable_get('custom_model_cc_emails', '');
					$params['standard']['subject'] = $email_subject;
					$params['standard']['body'] = $email_body;
					$params['standard']['headers'] = array(
						'From' => $from,
						'MIME-Version' => '1.0',
						'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
						'Content-Transfer-Encoding' => '8Bit',
						'X-Mailer' => 'Drupal',
						'Cc' => $cc,
						'Bcc' => $bcc
					);
					if (!drupal_mail('custom_model', 'standard', $email_to, language_default(), $params, $from, TRUE))
					{
						drupal_set_message('Error sending email message.', 'error');
					} //!drupal_mail('custom_model', 'standard', $email_to, language_default(), $params, $from, TRUE)
				} //$form_state['values']['custom_model_actions'] == 2
				elseif ($form_state['values']['custom_model_actions'] == 3) //disapprove and delete entire Custom Model
				{
					if (strlen(trim($form_state['values']['message'])) <= 30)
					{
						form_set_error('message', t(''));
						$msg = drupal_set_message("Please mention the reason for disapproval. Minimum 30 character required", 'error');
						return $msg;
					} //strlen(trim($form_state['values']['message'])) <= 30
					if (!user_access('custom model bulk delete abstract'))
					{
						$msg = drupal_set_message(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Lab.'), 'error');
						return $msg;
					} //!user_access('custom_model bulk delete code')
					if (custom_model_abstract_delete_project($form_state['values']['custom_model_proposals'])) //////
					{
						drupal_set_message(t('Dis-Approved and Deleted Entire Custom Model project.'), 'status');
						$email_subject = t('[!site_name][Custom Model] Your uploaded Custom Model have been marked as dis-approved', array(
						'!site_name' => variable_get('site_name', '')
					));
					$email_body = array(
						0 => t('

Dear ' . $user_info->contributor_name .',

Your uploaded Custom Model files for the Custom Model Title : ' . $user_info->project_title . ' have been marked as dis-approved.

Reason for dis-approval: ' . $form_state['values']['message'] . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
					'!site_name' => variable_get('site_name', ''),
					'!user_name' => $user_data->name
					))
					);
					$email_to = $user_data->mail;
					$from = variable_get('custom_model_from_email', '');
					$bcc = variable_get('custom_model_emails', '');
					$cc = variable_get('custom_model_cc_emails', '');
					$params['standard']['subject'] = $email_subject;
					$params['standard']['body'] = $email_body;
					$params['standard']['headers'] = array(
						'From' => $from,
						'MIME-Version' => '1.0',
						'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
						'Content-Transfer-Encoding' => '8Bit',
						'X-Mailer' => 'Drupal',
						'Cc' => $cc,
						'Bcc' => $bcc
					);
					if (!drupal_mail('custom_model', 'standard', $email_to, language_default(), $params, $from, TRUE))
					{
						drupal_set_message('Error sending email message.', 'error');
					}
					} //custom_model_abstract_delete_project($form_state['values']['custom_model_proposals'])
					else
					{
						drupal_set_message(t('Error Dis-Approving and Deleting Entire Custom Model.'), 'error');
					}
					// email
			} //user_access('custom_model project bulk manage code')
		return $msg;
	} //$form_state['clicked_button']['#value'] == 'Submit'
}
}
/**********************************************************/
function _bulk_list_of_custom_model_proposals()
{
	$project_titles = array(
		'0' => 'Please select...'
	);
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('is_submitted', 1);
	$query->condition('approval_status', 1);
	$query->orderBy('project_title', 'ASC');
	$project_titles_q = $query->execute();
	while ($project_titles_data = $project_titles_q->fetchObject())
	{
		$project_titles[$project_titles_data->id] = $project_titles_data->project_title . ' (Proposed by ' . $project_titles_data->contributor_name . ')';
	} //$project_titles_data = $project_titles_q->fetchObject()
	return $project_titles;
}
function _bulk_list_custom_model_actions()
{
	$custom_model_actions = array(
		0 => 'Please select...'
	);
	$custom_model_actions[1] = 'Approve Entire Custom Model';
	$custom_model_actions[2] = 'Resubmit Project files';
	$custom_model_actions[3] = 'Dis-Approve Entire Custom Model (This will delete Custom Model)';
	//$custom_model_actions[4] = 'Delete Entire Custom Model Including Proposal';
	return $custom_model_actions;
}
function _custom_model_details($custom_model_proposal_id)
{
	$return_html = "";
	$query_pro = db_select('custom_model_proposal');
	$query_pro->fields('custom_model_proposal');
	$query_pro->condition('id', $custom_model_proposal_id);
	$abstracts_pro = $query_pro->execute()->fetchObject();
	//var_dump($abstracts_pro);die;
	$query_pdf = db_select('custom_model_submitted_abstracts_file');
	$query_pdf->fields('custom_model_submitted_abstracts_file');
	$query_pdf->condition('proposal_id', $custom_model_proposal_id);
	$query_pdf->condition('filetype', 'A');
	$abstracts_pdf = $query_pdf->execute()->fetchObject();
	if ($abstracts_pdf == TRUE)
	{
		if ($abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != "")
		{
			$abstract_filename = $abstracts_pdf->filename;
		} //$abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != ""
		else
		{
			$abstract_filename = "File not uploaded";
		}
	} //$abstracts_pdf == TRUE
	else
	{
		$abstract_filename = "File not uploaded";
	}
	$query_process = db_select('custom_model_submitted_abstracts_file');
	$query_process->fields('custom_model_submitted_abstracts_file');
	$query_process->condition('proposal_id', $custom_model_proposal_id);
	$query_process->condition('filetype', 'S');
	$abstracts_query_process = $query_process->execute()->fetchObject();
	$query = db_select('custom_model_submitted_abstracts');
	$query->fields('custom_model_submitted_abstracts');
	$query->condition('proposal_id', $custom_model_proposal_id);
	$abstracts_q = $query->execute()->fetchObject();
	if ($abstracts_q)
	{
		if ($abstracts_q->is_submitted == 0)
		{
		} //$abstracts_q->is_submitted == 0
	} //$abstracts_q
	//var_dump($abstracts_query_process);die;
	if ($abstracts_query_process == TRUE)
	{
		if ($abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != "")
		{
			$abstracts_query_process_filename = $abstracts_query_process->filename;
		} //$abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != ""
		else
		{
			$abstracts_query_process_filename = "File not uploaded";
		}
	} //$abstracts_query_process == TRUE
	$query_script = db_select('custom_model_submitted_abstracts_file');
	$query_script->fields('custom_model_submitted_abstracts_file');
	$query_script->condition('proposal_id', $custom_model_proposal_id);
	$query_script->condition('filetype', 'P');
	$abstracts_query_script = $query_script->execute()->fetchObject();
	$query = db_select('custom_model_submitted_abstracts');
	$query->fields('custom_model_submitted_abstracts');
	$query->condition('proposal_id', $custom_model_proposal_id);
	$abstracts_q = $query->execute()->fetchObject();
	if ($abstracts_q)
	{
		if ($abstracts_q->is_submitted == 0)
		{
		} //$abstracts_q->is_submitted == 0
	} //$abstracts_q
	//var_dump($abstracts_query_script);die;
	if ($abstracts_query_script == TRUE)
	{
		if ($abstracts_query_script->filename != "NULL" || $abstracts_query_script->filename != "")
		{
			$abstracts_query_script_filename = $abstracts_query_script->filename;
		} //$abstracts_query_script->filename != "NULL" || $abstracts_query_script->filename != ""
		else
		{
			$abstracts_query_script_filename = "File not uploaded";
		}
	}
	else
	{
		$url = l('Upload abstract', 'custom-model/abstract-code/upload');
		$abstracts_query_process_filename = "File not uploaded";
	}
	$download_custom_model = l('Download Custom Model','custom-model/full-download/project/'.$custom_model_proposal_id);
	$return_html .= '<strong>Proposer Name:</strong><br />' . $abstracts_pro->name_title . ' ' . $abstracts_pro->contributor_name . '<br /><br />';
	$return_html .= '<strong>Title of the Custom Model:</strong><br />' . $abstracts_pro->project_title . '<br /><br />';
	$return_html .= '<strong>Uploaded an abstract (brief outline) of the project:</strong><br />' . $abstract_filename . '<br /><br />';
	$return_html .= '<strong>Uploaded Custom Model as DWSIM Simulation File:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
	$return_html .= '<strong>Uploaded script file:</strong><br />' . $abstracts_query_script_filename . '<br /><br />';
	$return_html .= $download_custom_model;
	return $return_html;
}