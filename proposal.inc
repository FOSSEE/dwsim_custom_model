<?php
// $Id$
/*
Approval Status :
0 - Pending
1 - Approved
2 - Dis-Approved
3 - Completed
Solution Status :
0 - Pending
1 - Approved
2 - Dis-Approved
Solution Display :
0 - No
1 - Yes


is_completed
0 - in progress
1 - Completed
Tables :
custom_model_solution : approval_status
0 - Pending
1 - Approved
2 - Disapproved (delete it)
*/
function custom_model_proposal_form($form, &$form_state, $no_js_use = FALSE)
{
	global $user;
	/************************ start approve book details ************************/
	if ($user->uid == 0)
	{
		$msg = drupal_set_message(t('It is mandatory to login on this website to access the custom model proposal form. If you are new user please create a new account first.'), 'error');
		drupal_goto('user/login', array('query' => drupal_get_destination()));
		return $msg;
	} //$user->uid == 0
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('uid', $user->uid);
	$query->orderBy('id', 'DESC');
	$query->range(0, 1);
	$proposal_q = $query->execute();
	$proposal_data = $proposal_q->fetchObject();
	if ($proposal_data)
	{
		if ($proposal_data->approval_status == 0 || $proposal_data->approval_status == 1)
		{
			drupal_set_message(t('We have already received your proposal.'), 'status');
			drupal_goto('');
			return;
		} //$proposal_data->approval_status == 0 || $proposal_data->approval_status == 1
	} //$proposal_data
	$form['#attributes'] = array(
		'enctype' => "multipart/form-data"
	);
	$form['name_title'] = array(
		'#type' => 'select',
		'#title' => t('Title'),
		'#options' => array(
			'Dr' => 'Dr',
			'Prof' => 'Prof',
			'Mr' => 'Mr',
			'Mrs' => 'Mrs',
			'Ms' => 'Ms'
		),
		'#required' => TRUE
	);
	$form['contributor_name'] = array(
		'#type' => 'textfield',
		'#title' => t('Name of the contributor'),
		'#size' => 250,
		'#attributes' => array(
			'placeholder' => t('Enter your full name.....')
		),
		'#maxlength' => 250,
		'#required' => TRUE
	);
	$form['contributor_contact_no'] = array(
		'#type' => 'textfield',
		'#title' => t('Contact No.'),
		'#size' => 10,
		'#attributes' => array(
			'placeholder' => t('Enter your contact number')
		),
		'#maxlength' => 250,
		'#required' => TRUE
	);
	$form['contributor_email_id'] = array(
		'#type' => 'textfield',
		'#title' => t('Email'),
		'#size' => 30,
		'#value' => $user->mail,
		'#disabled' => TRUE
	);
	$form['university'] = array(
		'#type' => 'textfield',
		'#title' => t('University / Institute / Organisation'),
		'#size' => 80,
		'#maxlength' => 200,
		'#required' => TRUE,
		'#attributes' => array(
			'placeholder' => 'Insert full name of your institute/ university.... '
		)
	);
	$form['department'] = array(
		'#type' => 'select',
		'#title' => t('Department / Branch'),
		'#options' => _cm_list_of_departments(),
		'#required' => TRUE
	);
	$form['other_department'] = array(
		'#type' => 'textfield',
		'#title' => t('Department/Branch name not in list'),
		'#size' => 100,
		'#attributes' => array(
			'placeholder' => t('Enter your department name')
		),
		'#states' => array(
			'visible' => array(
				':input[name="department"]' => array(
					'value' => 'Others'
				)
			)
		),
	);
	$form['country'] = array(
		'#type' => 'select',
		'#title' => t('Country'),
		'#options' => array(
			'India' => 'India',
			'Others' => 'Others'
		),
		'#required' => TRUE,
		'#tree' => TRUE,
		'#validated' => TRUE
	);
	$form['other_country'] = array(
		'#type' => 'textfield',
		'#title' => t('Other than India'),
		'#size' => 100,
		'#attributes' => array(
			'placeholder' => t('Enter your country name')
		),
		'#states' => array(
			'visible' => array(
				':input[name="country"]' => array(
					'value' => 'Others'
				)
			)
		)
	);
	$form['other_state'] = array(
		'#type' => 'textfield',
		'#title' => t('State other than India'),
		'#size' => 100,
		'#attributes' => array(
			'placeholder' => t('Enter your state/region name')
		),
		'#states' => array(
			'visible' => array(
				':input[name="country"]' => array(
					'value' => 'Others'
				)
			)
		)
	);
	$form['other_city'] = array(
		'#type' => 'textfield',
		'#title' => t('City other than India'),
		'#size' => 100,
		'#attributes' => array(
			'placeholder' => t('Enter your city name')
		),
		'#states' => array(
			'visible' => array(
				':input[name="country"]' => array(
					'value' => 'Others'
				)
			)
		)
	);
	$form['all_state'] = array(
		'#type' => 'select',
		'#title' => t('State'),
		'#options' => _cm_list_of_states(),
		'#validated' => TRUE,
		'#states' => array(
			'visible' => array(
				':input[name="country"]' => array(
					'value' => 'India'
				)
			)
		)
	);
	$form['city'] = array(
		'#type' => 'select',
		'#title' => t('City'),
		'#options' => _cm_list_of_cities(),
		'#states' => array(
			'visible' => array(
				':input[name="country"]' => array(
					'value' => 'India'
				)
			)
		)
	);
	$form['pincode'] = array(
		'#type' => 'textfield',
		'#title' => t('Pincode'),
		'#size' => 6,
		'#required' => TRUE
	);
	/***************************************************************************/
	$form['hr'] = array(
		'#type' => 'item',
		'#markup' => '<hr>'
	);
	$form['version'] = array(
		'#type' => 'select',
		'#title' => t('DWSIM Version'),
		'#options' => _list_of_software_versions(),
		'#required' => TRUE
	);
	$form['project_title'] = array(
		'#type' => 'textarea',
		'#title' => t('Title of the Custom Model'),
		'#size' => 250,
		'#description' => t('Maximum character limit is 250'),
		'#required' => TRUE
	);
	$form['script_used'] = array(
		'#type' => 'select',
		'#title' => t('Script used to create the Custom Model'),
		'#options' => array(
			'Scilab' => 'Scilab',
			'IronPython' => 'IronPython'
		),
		'#required' => TRUE,
	);
	$form['samplefile'] = array(
		'#type' => 'fieldset',
		'#title' => t('Upload Abstract of Custom Model'),
		'#collapsible' => FALSE,
		'#collapsed' => FALSE
	);
	$form['samplefile']['samplefilepath'] = array(
		'#type' => 'file',
		//'#title' => t('Upload circuit diagram'),
		'#size' => 48,
		'#description' => t('Upload a document of about 1-2 pages explaining the custom model you intend to create)') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('resource_upload_extensions', '') . '</span>'
	);
	$form['term_condition'] = array(
		'#type' => 'checkboxes',
		'#title' => t('Terms And Conditions'),
		'#options' => array(
			'status' => t('<a href="https://dwsim.fossee.in/custom-model/term-and-conditions" target="_blank">I agree to the Terms and Conditions</a>')
		),
		'#required' => TRUE
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit')
	);
	return $form;
}
function custom_model_proposal_form_validate($form, &$form_state)
{
	$project_title = $form_state['values']['project_title'];
	/*$proposar_name = $form_state['values']['name_title'] . ' ' . $form_state['values']['contributor_name'];
	$directory_name = _df_dir_name($project_title, $proposar_name);*/
	//var_dump($directory_name);die;
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('project_title', $project_title);
	$query->condition(db_or()->condition('approval_status',1)->condition('approval_status',3)); 
	$result = $query->execute()->rowCount();
	//var_dump($result);die;
	if ($result >= 1)
	{
		form_set_error('project_title', t('Project title name already exists'));
		return;
	}
	if ($form_state['values']['term_condition'] == '1')
	{
		form_set_error('term_condition', t('Please check the terms and conditions'));
		// $form_state['values']['country'] = $form_state['values']['other_country'];
	} //$form_state['values']['term_condition'] == '1'
	if ($form_state['values']['country'] == 'Others')
	{
		if ($form_state['values']['other_country'] == '')
		{
			form_set_error('other_country', t('Enter country name'));
			// $form_state['values']['country'] = $form_state['values']['other_country'];
		} //$form_state['values']['other_country'] == ''
		else
		{
			$form_state['values']['country'] = $form_state['values']['other_country'];
		}
		if ($form_state['values']['other_state'] == '')
		{
			form_set_error('other_state', t('Enter state name'));
			// $form_state['values']['country'] = $form_state['values']['other_country'];
		} //$form_state['values']['other_state'] == ''
		else
		{
			$form_state['values']['all_state'] = $form_state['values']['other_state'];
		}
		if ($form_state['values']['other_city'] == '')
		{
			form_set_error('other_city', t('Enter city name'));
			// $form_state['values']['country'] = $form_state['values']['other_country'];
		} //$form_state['values']['other_city'] == ''
		else
		{
			$form_state['values']['city'] = $form_state['values']['other_city'];
		}
	} //$form_state['values']['country'] == 'Others'
	else
	{
		if ($form_state['values']['country'] == '0')
		{
			form_set_error('country', t('Select country name'));
			// $form_state['values']['country'] = $form_state['values']['other_country'];
		} //$form_state['values']['country'] == ''
		if ($form_state['values']['all_state'] == '0')
		{
			form_set_error('all_state', t('Select state name'));
			// $form_state['values']['country'] = $form_state['values']['other_country'];
		} //$form_state['values']['all_state'] == ''
		if ($form_state['values']['city'] == '0')
		{
			form_set_error('city', t('Select city name'));
			// $form_state['values']['country'] = $form_state['values']['other_country'];
		} //$form_state['values']['city'] == ''
	}
	//Validation for project title
	$form_state['values']['project_title'] = trim($form_state['values']['project_title']);
	if ($form_state['values']['project_title'] != '')
	{
		if (strlen($form_state['values']['project_title']) > 250)
		{
			form_set_error('project_title', t('Maximum charater limit is 250 charaters only, please check the length of the project title'));
		} //strlen($form_state['values']['project_title']) > 250
		else if (strlen($form_state['values']['project_title']) < 10)
		{
			form_set_error('project_title', t('Minimum charater limit is 10 charaters, please check the length of the project title'));
		} //strlen($form_state['values']['project_title']) < 10
	} //$form_state['values']['project_title'] != ''
	else
	{
		form_set_error('project_title', t('Project title shoud not be empty'));
	}
if (isset($_FILES['files']))
	{
		/* check if atleast one source or result file is uploaded */
		if (!($_FILES['files']['name']['samplefilepath']))
			form_set_error('samplefilepath', t('Please upload the abstract'));
		/* check for valid filename extensions */
		foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
		{
			if ($file_name)
			{
				$allowed_extensions_str = variable_get('resource_upload_extensions', '');
				$allowed_extensions = explode(',', $allowed_extensions_str);
				$fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
				$temp_extension = end($fnames);
				if (!in_array($temp_extension, $allowed_extensions))
					form_set_error($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
				if ($_FILES['files']['size'][$file_form_name] <= 0)
					form_set_error($file_form_name, t('File size cannot be zero.'));
				/* check if valid file name */
				if (!custom_model_check_valid_filename($_FILES['files']['name'][$file_form_name]))
					form_set_error($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
			} //$file_name
		} //$_FILES['files']['name'] as $file_form_name => $file_name
	} 
	return $form_state;
}
function custom_model_proposal_form_submit($form, &$form_state)
{
	global $user;
	$root_path = custom_model_path();
	if (!$user->uid)
	{
		drupal_set_message('It is mandatory to login on this website to access the proposal form', 'error');
		return;
	} //!$user->uid
	/*if ($form_state['values']['version'] == 'Old version')
	{
		$form_state['values']['version'] = trim($form_state['values']['older']);
	} *///$form_state['values']['version'] == 'Old version'
	/* inserting the user proposal */
	if($form_state['values']['department'] == 'Others') {
		$form_state['values']['department'] =$form_state['values']['other_department'];
	}
	if ($form_state['values']['country'] == 'Others')
	{
		$form_state['values']['country'] = $form_state['values']['other_country'];
		$form_state['values']['all_state'] = $form_state['values']['other_state'];
		$form_state['values']['city'] = $form_state['values']['other_city'];
		//$form_state['values']['pincode'] = $form_state['values']['other_pincode'];
	}
	$v = $form_state["values"];
	$project_title = trim($v['project_title']);
	$proposar_name = $v['name_title'] . ' ' . $v['contributor_name'];
	$university = $v['university'];
	$directory_name = _cm_dir_name($project_title, $proposar_name);
	$result = "INSERT INTO {custom_model_proposal} 
    (
    uid, 
    approver_uid,
    name_title, 
    contributor_name,
    contact_no,
    university,
    department,
    city, 
    pincode, 
    state, 
    country,
    version,
    project_title, 
    script_used,
    directory_name,
    samplefilepath,
    approval_status,
    is_completed, 
    dissapproval_reason,
    creation_date, 
    approval_date
    ) VALUES
    (
    :uid, 
    :approver_uid, 
    :name_title, 
    :contributor_name, 
    :contact_no,
    :university, 
    :department,
    :city, 
    :pincode, 
    :state,  
    :country,
    :version,
    :project_title, 
    :script_used,
    :directory_name,
    :samplefilepath,
    :approval_status,
    :is_completed, 
    :dissapproval_reason,
    :creation_date, 
    :approval_date
    )";
	$args = array(
		":uid" => $user->uid,
		":approver_uid" => 0,
		":name_title" => $v['name_title'],
		":contributor_name" => trim($v['contributor_name']),
		":contact_no" => $v['contributor_contact_no'],
		":university" => $v['university'],
		":department" => $form_state['values']['department'],
		":city" => $v['city'],
		":pincode" => $v['pincode'],
		":state" => $v['all_state'],
		":country" => $v['country'],
		":version" => $v['version'],
		":project_title" => $v['project_title'],
		":script_used" => $v['script_used'],
		":directory_name" => $directory_name,
		":samplefilepath" => "",
		":approval_status" => 0,
		":is_completed" => 0,
		":dissapproval_reason" => "NULL",
		":creation_date" => time(),
		":approval_date" => 0
		);
	//	var_dump($args);die;
	//var_dump($result);die;
	$result1 = db_query($result, $args, array(
		'return' => Database::RETURN_INSERT_ID
	));
//var_dump($args);die;

	$dest_path = $directory_name . '/';
	$dest_path1 = $root_path . $dest_path;
	//var_dump($dest_path1);die;	
	if (!is_dir($root_path . $dest_path))
		mkdir($root_path . $dest_path);
	/* uploading files */
	foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
	{
		if ($file_name)
		{
			/* checking file type */
			//$file_type = 'S';
			if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
			{
				 drupal_set_message(t("Error uploading file. File !filename already exists.", array('!filename' => $_FILES['files']['name'][$file_form_name])), 'error');
				//unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
			} //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
			/* uploading file */
			if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
			{
				$query = "UPDATE {custom_model_proposal} SET samplefilepath = :samplefilepath WHERE id = :id";
				$args = array(
					":samplefilepath" => $_FILES['files']['name'][$file_form_name],
					":id" => $result1
				);
				
				$updateresult = db_query($query, $args);
				//var_dump($args);die;

				drupal_set_message($file_name . ' uploaded successfully.', 'status');
			} //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
			else
			{
				drupal_set_message('Error uploading file : ' . $dest_path . '/' . $file_name, 'error');
			}
		} //$file_name
	} //$_FILES['files']['name'] as $file_form_name => $file_name
	if (!$result1)
	{
		drupal_set_message(t('Error receiving your proposal. Please try again.'), 'error');
		return;
	} //!$proposal_id
	/* sending email */
	$email_to = $user->mail;
	$form = variable_get('custom_model_from_email', '');
	$bcc = variable_get('custom_model_emails', '');
	$cc = variable_get('custom_model_cc_emails', '');
	$params['custom_model_proposal_received']['result1'] = $result1;
	$params['custom_model_proposal_received']['user_id'] = $user->uid;
	$params['custom_model_proposal_received']['headers'] = array(
		'From' => $form,
		'MIME-Version' => '1.0',
		'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
		'Content-Transfer-Encoding' => '8Bit',
		'X-Mailer' => 'Drupal',
		'Cc' => $cc,
		'Bcc' => $bcc
	);
	if (!drupal_mail('custom_model', 'custom_model_proposal_received', $email_to, user_preferred_language($user), $params, $form, TRUE))
		drupal_set_message('Error sending email message.', 'error');
	drupal_set_message(t('We have received your DWSIM Custom Model proposal. We will get back to you soon.'), 'status');
	drupal_goto('');
}