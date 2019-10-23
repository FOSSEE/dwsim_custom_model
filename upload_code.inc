<?php
// $Id$
//A- abstract, S- Simulation, P- Script
function custom_model_abstract()
{
	global $user;
	$return_html = "";
	$proposal_data = custom_model_get_proposal();
	if (!$proposal_data)
	{
		drupal_goto('');
		return;
	} //!$proposal_data
	/* get experiment list */
	$query = db_select('custom_model_submitted_abstracts');
	$query->fields('custom_model_submitted_abstracts');
	$query->condition('proposal_id', $proposal_data->id);
	$abstracts_q = $query->execute()->fetchObject();
	$query_pro = db_select('custom_model_proposal');
	$query_pro->fields('custom_model_proposal');
	$query_pro->condition('id', $proposal_data->id);
	$abstracts_pro = $query_pro->execute()->fetchObject();
	$query_pdf = db_select('custom_model_submitted_abstracts_file');
	$query_pdf->fields('custom_model_submitted_abstracts_file');
	$query_pdf->condition('proposal_id', $proposal_data->id);
	$query_pdf->condition('filetype', 'A');
	$abstracts_pdf = $query_pdf->execute()->fetchObject();
	if ($abstracts_pdf == TRUE)
	{
		if ($abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != "")
		{
			$abstract_filename = $abstracts_pdf->filename;
			//$abstract_filename = l($abstracts_pdf->filename, 'custom-model/download/project-file/' . $proposal_data->id);
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
	$query_process->condition('proposal_id', $proposal_data->id);
	$query_process->condition('filetype', 'S');
	$abstracts_query_process = $query_process->execute()->fetchObject();
	if ($abstracts_query_process == TRUE)
	{
		if ($abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != "")
		{
			$abstracts_query_process_filename = $abstracts_query_process->filename;
			//$abstracts_query_process_filename = l($abstracts_query_process->filename, 'custom-model/download/project-file/' . $proposal_data->id); 
		} //$abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != ""
		else
		{
			$abstracts_query_process_filename = "File not uploaded";
		}
		if ($abstracts_q->is_submitted == '')
		{
			$url = l('Upload abstract', 'custom-model/abstract-code/upload');
		} //$abstracts_q->is_submitted == ''
		else if ($abstracts_q->is_submitted == 1)
		{
			$url = "";
		} //$abstracts_q->is_submitted == 1
		else if ($abstracts_q->is_submitted == 0)
		{
			$url = l('Edit', 'custom-model/abstract-code/upload');
		} //$abstracts_q->is_submitted == 0
	} //$abstracts_query_process == TRUE
	else
	{
		$url = l('Upload abstract', 'custom-model/abstract-code/upload');
		$abstracts_query_process_filename = "File not uploaded";
	}
	$query_sc = db_select('custom_model_submitted_abstracts_file');
	$query_sc->fields('custom_model_submitted_abstracts_file');
	$query_sc->condition('proposal_id', $proposal_data->id);
	$query_sc->condition('filetype', 'P');
	$abstracts_query_sc = $query_sc->execute()->fetchObject();
	if ($abstracts_query_sc == TRUE)
	{
		if ($abstracts_query_sc->filename != "NULL" || $abstracts_query_sc->filename != "")
		{
			$abstracts_query_sc_filename = $abstracts_query_sc->filename;
		} //$abstracts_query_sc->filename != "NULL" || $abstracts_query_sc->filename != ""
		else
		{
			$abstracts_query_sc_filename = "File not uploaded";
		}
		if ($abstracts_q->is_submitted == '')
		{
			$url = l('Upload abstract', 'custom-model/abstract-code/upload');
		} //$abstracts_q->is_submitted == ''
		else if ($abstracts_q->is_submitted == 1)
		{
			$url = "";
		} //$abstracts_q->is_submitted == 1
		else if ($abstracts_q->is_submitted == 0)
		{
			$url = l('Edit', 'custom-model/abstract-code/upload');
		} //$abstracts_q->is_submitted == 0
	} //$abstracts_query_sc == TRUE
	else
	{
		$url = l('Upload abstract', 'custom-model/abstract-code/upload');
		$abstracts_query_sc_filename = "File not uploaded";
	}
	$return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '<br /><br />';
	$return_html .= '<strong>Title of the Circuit Simulation Project:</strong><br />' . $proposal_data->project_title . '<br /><br />';
	$return_html .= '<strong>DWSIM version used:</strong><br />' . $proposal_data->version . '<br /><br />';
	$return_html .= '<strong>Uploaded Custom Model as DWSIM Simulation File:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
	$return_html .= '<strong>Uploaded scilab/ironpython script for the custom model:</strong><br />' . $abstracts_query_sc_filename . '<br /><br />';
	$return_html .= '<strong>Uploaded abstract of the project:</strong><br />' . $abstract_filename . '<br /><br />';
	$return_html .= $url . '<br />';
	return $return_html;
}
function custom_model_upload_abstract_code_form($form, &$form_state)
{
	global $user;
	$form['#attributes'] = array(
		'enctype' => "multipart/form-data"
	);
	/* get current proposal */
	//$proposal_id = (int) arg(3);
	$uid = $user->uid;
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('uid', $uid);
	$query->condition('approval_status', '1');
	$proposal_q = $query->execute();
	if ($proposal_q)
	{
		if ($proposal_data = $proposal_q->fetchObject())
		{
			/* everything ok */
		} //$proposal_data = $proposal_q->fetchObject()
		else
		{
			drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
			drupal_goto('custom-model/abstract-code');
			return;
		}
	} //$proposal_q
	else
	{
		drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
		drupal_goto('custom-model/abstract-code');
		return;
	}
	$query = db_select('custom_model_submitted_abstracts');
	$query->fields('custom_model_submitted_abstracts');
	$query->condition('proposal_id', $proposal_data->id);
	$abstracts_q = $query->execute()->fetchObject();
	if ($abstracts_q)
	{
		if ($abstracts_q->is_submitted == 1)
		{
			drupal_set_message(t('You have already submited your project files, hence you can not upload more code, for any query please write to us.'), 'error', $repeat = FALSE);
			drupal_goto('custom-model/abstract-code');
			//return;
		} //$abstracts_q->is_submitted == 1
	} //$abstracts_q->is_submitted == 1
	$form['project_title'] = array(
		'#type' => 'item',
		'#markup' => $proposal_data->project_title,
		'#title' => t('Title of the Circuit Simulation Project')
	);
	$form['contributor_name'] = array(
		'#type' => 'item',
		'#markup' => $proposal_data->contributor_name,
		'#title' => t('Contributor Name')
	);
	$form['dwsim_version'] =array(
		'#type' => 'item',
		'#markup' => $proposal_data->version,
		'#title' => t('DWSIM version used')
	);
	$existing_uploaded_S_file = default_value_for_uploaded_files("S", $proposal_data->id);
	if (!$existing_uploaded_S_file)
	{
		$existing_uploaded_S_file = new stdClass();
		$existing_uploaded_S_file->filename = "No file uploaded";
	} //!$existing_uploaded_S_file
	$form['upload_custom_model_simulation_file'] = array(
		'#type' => 'file',
		'#title' => t('Upload the Custom Model as DWSIM Simulation File'),
		'#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_S_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('custom_model_simulation_file', '') . '</span>'
	);// Upload simulation file
	$existing_uploaded_P_file = default_value_for_uploaded_files("P", $proposal_data->id);
	if (!$existing_uploaded_P_file)
	{
		$existing_uploaded_P_file = new stdClass();
		$existing_uploaded_P_file->filename = "No file uploaded";
	} //!$existing_uploaded_P_file
	$form['upload_custom_model_script_file'] = array(
		'#type' => 'file',
		'#title' => t('Upload the scilab/ironpython script for the custom model'),
		'#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_P_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('custom_model_script_file', '') . '</span>'
	);
	$existing_uploaded_A_file = default_value_for_uploaded_files("A", $proposal_data->id);
	if (!$existing_uploaded_A_file)
	{
		$existing_uploaded_A_file = new stdClass();
		$existing_uploaded_A_file->filename = "No file uploaded";
	} //!$existing_uploaded_A_file
	$form['upload_an_abstract'] = array(
		'#type' => 'file',
		'#title' => t('Upload an abstract of the project.') ,
		'#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_A_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('custom_model_abstract_upload_extensions', '') . '</span>'
	));
	
	$form['prop_id'] = array(
		'#type' => 'hidden',
		'#value' => $proposal_data->id
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit'),
		'#submit' => array(
			'custom_model_upload_abstract_code_form_submit'
		)
	);
	$form['cancel'] = array(
		'#type' => 'item',
		'#markup' => l(t('Cancel'), 'custom-model/abstract-code')
	);
	return $form;
}

///////////////////////////////////////////////////////////////
function custom_model_upload_abstract_code_form_validate($form, &$form_state)
{
	if (isset($_FILES['files']))
	{
		/* check if file is uploaded */
		$existing_uploaded_A_file = default_value_for_uploaded_files("A", $form_state['values']['prop_id']);
		$existing_uploaded_S_file = default_value_for_uploaded_files("S", $form_state['values']['prop_id']);
		$existing_uploaded_P_file = default_value_for_uploaded_files("P", $form_state['values']['prop_id']);
		if (!$existing_uploaded_S_file)
		{
			if (!($_FILES['files']['name']['upload_custom_model_simulation_file']))
				form_set_error('upload_custom_model_simulation_file', t('Please upload the file.'));
		} //!$existing_uploaded_S_file
		if (!$existing_uploaded_P_file)
		{
			if (!($_FILES['files']['name']['upload_custom_model_script_file']))
				form_set_error('upload_custom_model_script_file', t('Please upload the file.'));
		} //!$existing_uploaded_S_file
		if (!$existing_uploaded_A_file)
		{
			if (!($_FILES['files']['name']['upload_an_abstract']))
				form_set_error('upload_an_abstract', t('Please upload the file.'));
		} //!$existing_uploaded_A_file
		/* check for valid filename extensions */
		if ($_FILES['files']['name']['upload_custom_model_script_file'] || $_FILES['files']['name']['upload_an_abstract'] || $_FILES['files']['name']['upload_custom_model_simulation_file'])
		{
			foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
			{
				if ($file_name)
				{
					/* checking file type */
					if (strstr($file_form_name, 'upload_custom_model_simulation_file'))
						$file_type = 'S';
					else if (strstr($file_form_name, 'upload_custom_model_script_file'))
						$file_type = 'P';
					else if (strstr($file_form_name, 'upload_an_abstract'))
						$file_type = 'A';
					else
						$file_type = 'U';
					$allowed_extensions_str = '';
					switch ($file_type)
					{
						case 'S':
							$allowed_extensions_str = variable_get('custom_model_simulation_file', '');
							break;
						case 'A':
							$allowed_extensions_str = variable_get('custom_model_abstract_upload_extensions', '');
							break;
						case 'P':
							$allowed_extensions_str = variable_get('custom_model_script_file', '');
							break;
					} //$file_type
					$allowed_extensions = explode(',', $allowed_extensions_str);
					$tmp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
					$temp_extension = end($tmp_ext);
					if (!in_array($temp_extension, $allowed_extensions))
						form_set_error($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
					if ($_FILES['files']['size'][$file_form_name] <= 0)
						form_set_error($file_form_name, t('File size cannot be zero.'));
					/* check if valid file name */
					if (!custom_model_check_valid_filename($_FILES['files']['name'][$file_form_name]))
						form_set_error($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
				} //$file_name
			} //$_FILES['files']['name'] as $file_form_name => $file_name
		} //$_FILES['files']['name'] as $file_form_name => $file_name
	} //isset($_FILES['files'])
	// drupal_add_js('jQuery(document).ready(function () { alert("Hello!"); });', 'inline');
	// drupal_static_reset('drupal_add_js') ;
}
function custom_model_upload_abstract_code_form_submit($form, &$form_state)
{
	global $user;
	$v = $form_state['values'];
	$root_path = custom_model_path();
	$proposal_data = custom_model_get_proposal();
	$proposal_id = $proposal_data->id;
	if (!$proposal_data)
	{
		drupal_goto('');
		return;
	} //!$proposal_data
	$proposal_id = $proposal_data->id;
	$proposal_directory = $proposal_data->directory_name;
	/* create proposal folder if not present */
	//$dest_path = $proposal_directory . '/';
	$dest_path_project_files = $proposal_directory . '/project_files/';
	if (!is_dir($root_path . $dest_path_project_files))
		mkdir($root_path . $dest_path_project_files);
	$proposal_id = $proposal_data->id;
	$query_s = "SELECT * FROM {custom_model_submitted_abstracts} WHERE proposal_id = :proposal_id";
	$args_s = array(
		":proposal_id" => $proposal_id
	);
	$query_s_result = db_query($query_s, $args_s)->fetchObject();
	if (!$query_s_result)
	{
		/* creating solution database entry */
		$query = "INSERT INTO {custom_model_submitted_abstracts} (
	proposal_id,
	approver_uid,
	abstract_approval_status,
	abstract_upload_date,
	abstract_approval_date,
	is_submitted) VALUES (:proposal_id, :approver_uid, :abstract_approval_status,:abstract_upload_date, :abstract_approval_date, :is_submitted)";
		$args = array(
			":proposal_id" => $proposal_id,
			":approver_uid" => 0,
			":abstract_approval_status" => 0,
			":abstract_upload_date" => time(),
			":abstract_approval_date" => 0,
			":is_submitted" => 1
		);
		$submitted_abstract_id = db_query($query, $args, array(
			'return' => Database::RETURN_INSERT_ID
		));
		$query1 = "UPDATE {custom_model_proposal} SET is_submitted = :is_submitted WHERE id = :id";
		$args1 = array(
			":is_submitted" => 1,
			":id" => $proposal_id
		);
		db_query($query1,$args1);
		drupal_set_message('Abstract uploaded successfully.', 'status');
	} //!$query_s_result
	else
	{
		$query = "UPDATE {custom_model_submitted_abstracts} SET 

	
	abstract_upload_date =:abstract_upload_date,
	is_submitted= :is_submitted 
	WHERE proposal_id = :proposal_id
	";
		$args = array(
			
			":abstract_upload_date" => time(),
			":is_submitted" => 1,
			":proposal_id" => $proposal_id
		);
		$submitted_abstract_id = db_query($query, $args, array(
			'return' => Database::RETURN_INSERT_ID
		));
		$query1 = "UPDATE {custom_model_proposal} SET is_submitted = :is_submitted WHERE id = :id";
		$args1 = array(
			":is_submitted" => 1,
			":id" => $proposal_id
		);
		db_query($query1,$args1);
		drupal_set_message('Abstract updated successfully.', 'status');
	}
	foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
	{
		if ($file_name)
		{
			/* checking file type */
			if (strstr($file_form_name, 'upload_custom_model_simulation_file'))
			{
				$file_type = 'S';
			} //strstr($file_form_name, 'upload_custom_model_simulation_file')
			else if (strstr($file_form_name, 'upload_custom_model_script_file'))
			{
				$file_type = 'P';
			}
			else if (strstr($file_form_name, 'upload_an_abstract'))
			{
				$file_type = 'A';
			} 
			else
			{
				$file_type = 'U';
			}
			switch ($file_type)
			{
				case 'S':
					if (file_exists($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]))
					{
						//unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
						drupal_set_message(t("File !filename already exists hence overwirtten the exisitng file ", array(
							'!filename' => $_FILES['files']['name'][$file_form_name]
						)), 'error');
					} //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
					else if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]))
					{
						/* for uploaded files making an entry in the database */
						$query_ab_f = "SELECT * FROM custom_model_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
						$args_ab_f = array(
							":proposal_id" => $proposal_id,
							":filetype" => $file_type
						);
						$query_ab_f_result = db_query($query_ab_f, $args_ab_f)->fetchObject();
						if (!$query_ab_f_result)
						{
							$query = "INSERT INTO {custom_model_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
							$args = array(
								":submitted_abstract_id" => $submitted_abstract_id,
								":proposal_id" => $proposal_id,
								":uid" => $user->uid,
								":approvar_uid" => 0,
								":filename" => $_FILES['files']['name'][$file_form_name],
								":filepath" => $_FILES['files']['name'][$file_form_name],
								":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
								":filesize" => $_FILES['files']['size'][$file_form_name],
								":filetype" => $file_type,
								":timestamp" => time()
							);
							db_query($query, $args);
							drupal_set_message($file_name . ' uploaded successfully.', 'status');
						} //!$query_ab_f_result
						else
						{
							unlink($root_path . $dest_path_project_files . $query_ab_f_result->filename);
							$query = "UPDATE {custom_model_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
							$args = array(
								":filename" => $_FILES['files']['name'][$file_form_name],
								":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
								":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
								":filesize" => $_FILES['files']['size'][$file_form_name],
								":timestamp" => time(),
								":proposal_id" => $proposal_id,
								":filetype" => $file_type
							);
							db_query($query, $args);
							drupal_set_message($file_name . ' file updated successfully.', 'status');
						}
					} //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					else
					{
						drupal_set_message('Error uploading file : ' . $dest_path_project_files . $file_name, 'error');
					}
					break;
				case 'P':
					if (file_exists($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]))
					{
						//unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
						drupal_set_message(t("File !filename already exists hence overwirtten the exisitng file ", array(
							'!filename' => $_FILES['files']['name'][$file_form_name]
						)), 'error');
					} //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
					else if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]))
					{
						/* for uploaded files making an entry in the database */
						$query_ab_f = "SELECT * FROM custom_model_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
						$args_ab_f = array(
							":proposal_id" => $proposal_id,
							":filetype" => $file_type
						);
						$query_ab_f_result = db_query($query_ab_f, $args_ab_f)->fetchObject();
						if (!$query_ab_f_result)
						{
							$query = "INSERT INTO {custom_model_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
							$args = array(
								":submitted_abstract_id" => $submitted_abstract_id,
								":proposal_id" => $proposal_id,
								":uid" => $user->uid,
								":approvar_uid" => 0,
								":filename" => $_FILES['files']['name'][$file_form_name],
								":filepath" => $_FILES['files']['name'][$file_form_name],
								":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
								":filesize" => $_FILES['files']['size'][$file_form_name],
								":filetype" => $file_type,
								":timestamp" => time()
							);
							db_query($query, $args);
							drupal_set_message($file_name . ' uploaded successfully.', 'status');
						} //!$query_ab_f_result
						else
						{
							unlink($root_path . $dest_path_project_files . $query_ab_f_result->filename);
							$query = "UPDATE {custom_model_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
							$args = array(
								":filename" => $_FILES['files']['name'][$file_form_name],
								":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
								":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
								":filesize" => $_FILES['files']['size'][$file_form_name],
								":timestamp" => time(),
								":proposal_id" => $proposal_id,
								":filetype" => $file_type
							);
							db_query($query, $args);
							drupal_set_message($file_name . ' file updated successfully.', 'status');
						}
					} //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					else
					{
						drupal_set_message('Error uploading file : ' . $dest_path_project_files . $file_name, 'error');
					}
					break;
				case 'A':
					if (file_exists($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]))
					{
						//unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
						drupal_set_message(t("File !filename already exists hence overwirtten the exisitng file ", array(
							'!filename' => $_FILES['files']['name'][$file_form_name]
						)), 'error');
					} //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
					else if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]))
					{
						/* for uploaded files making an entry in the database */
						$query_ab_f = "SELECT * FROM custom_model_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
						$args_ab_f = array(
							":proposal_id" => $proposal_id,
							":filetype" => $file_type
						);
						$query_ab_f_result = db_query($query_ab_f, $args_ab_f)->fetchObject();
						if (!$query_ab_f_result)
						{
							$query = "INSERT INTO {custom_model_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
							$args = array(
								":submitted_abstract_id" => $submitted_abstract_id,
								":proposal_id" => $proposal_id,
								":uid" => $user->uid,
								":approvar_uid" => 0,
								":filename" => $_FILES['files']['name'][$file_form_name],
								":filepath" => $_FILES['files']['name'][$file_form_name],
								":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
								":filesize" => $_FILES['files']['size'][$file_form_name],
								":filetype" => $file_type,
								":timestamp" => time()
							);
							db_query($query, $args);
							drupal_set_message($file_name . ' uploaded successfully.', 'status');
						} //!$query_ab_f_result
						else
						{
							unlink($root_path . $dest_path_project_files . $query_ab_f_result->filename);
							$query = "UPDATE {custom_model_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
							$args = array(
								":filename" => $_FILES['files']['name'][$file_form_name],
								":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
								":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
								":filesize" => $_FILES['files']['size'][$file_form_name],
								":timestamp" => time(),
								":proposal_id" => $proposal_id,
								":filetype" => $file_type
							);
							db_query($query, $args);
							drupal_set_message($file_name . ' file updated successfully.', 'status');
						}
					} //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					else
					{
						drupal_set_message('Error uploading file : ' . $dest_path_project_files . $file_name, 'error');
					}
					break;
			} //$file_type
		} //$file_name
	} //$_FILES['files']['name'] as $file_form_name => $file_name
	/* sending email */
	$email_to = $user->mail;
	$from = variable_get('custom_model_from_email', '');
	$bcc = variable_get('custom_model_emails', '');
	$cc = variable_get('custom_model_cc_emails', '');
	$params['abstract_uploaded']['proposal_id'] = $proposal_id;
	$params['abstract_uploaded']['submitted_abstract_id'] = $submitted_abstract_id;
	$params['abstract_uploaded']['user_id'] = $user->uid;
	$params['abstract_uploaded']['headers'] = array(
		'From' => $from,
		'MIME-Version' => '1.0',
		'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
		'Content-Transfer-Encoding' => '8Bit',
		'X-Mailer' => 'Drupal',
		'Cc' => $cc,
		'Bcc' => $bcc
	);
	if (!drupal_mail('custom_model', 'abstract_uploaded', $email_to, language_default(), $params, $from, TRUE))
		drupal_set_message('Error sending email message.', 'error');
	drupal_goto('custom-model/abstract-code');
}
function default_value_for_uploaded_files($filetype, $proposal_id)
{
	$query = db_select('custom_model_submitted_abstracts_file');
	$query->fields('custom_model_submitted_abstracts_file');
	$query->condition('proposal_id', $proposal_id);
	$selected_files_array = "";
	if ($filetype == "A")
	{
		$query->condition('filetype', $filetype);
		$filetype_q = $query->execute()->fetchObject();
		return $filetype_q;
	} //$filetype == "A" A-abstract
	elseif ($filetype == "P")
	{
		$query->condition('filetype', $filetype);
		$filetype_q = $query->execute()->fetchObject();
		return $filetype_q;
	}// P - Script file
	elseif ($filetype == "S")
	{
		$query->condition('filetype', $filetype);
		$filetype_q = $query->execute()->fetchObject();
		return $filetype_q;
	} //S - Simulation file
	else
	{
		return;
	}
	return;
}