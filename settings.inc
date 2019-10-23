<?php
// $Id$
function custom_model_settings_form($form, $form_state)
{
	$form['emails'] = array(
		'#type' => 'textfield',
		'#title' => t('(Bcc) Notification emails'),
		'#description' => t('Specify emails id for Bcc option of mail system with comma separated'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('custom_model_emails', '')
	);
	$form['cc_emails'] = array(
		'#type' => 'textfield',
		'#title' => t('(Cc) Notification emails'),
		'#description' => t('Specify emails id for Cc option of mail system with comma separated'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('custom_model_cc_emails', '')
	);
	$form['from_email'] = array(
		'#type' => 'textfield',
		'#title' => t('Outgoing from email address'),
		'#description' => t('Email address to be display in the from field of all outgoing messages'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('custom_model_from_email', '')
	);
	$form['extensions']['resource_upload'] = array(
		'#type' => 'textfield',
		'#title' => t('Allowed file extensions for uploading resource files'),
		'#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('resource_upload_extensions', '')
	);
	$form['extensions']['abstract_upload'] = array(
		'#type' => 'textfield',
		'#title' => t('Allowed file extensions for abstract'),
		'#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('custom_model_abstract_upload_extensions', '')
	);
	$form['extensions']['custom_model_upload'] = array(
		'#type' => 'textfield',
		'#title' => t('Allowed extensions for project files'),
		'#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('custom_model_simulation_file', '')
	);
	$form['extensions']['custom_model_script_upload'] = array(
		'#type' => 'textfield',
		'#title' => t('Allowed extensions for project files'),
		'#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('custom_model_script_file', '')
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit')
	);
	return $form;
}
function custom_model_settings_form_validate($form, &$form_state)
{
	return;
}
function custom_model_settings_form_submit($form, &$form_state)
{
	variable_set('custom_model_emails', $form_state['values']['emails']);
	variable_set('custom_model_cc_emails', $form_state['values']['cc_emails']);
	variable_set('custom_model_from_email', $form_state['values']['from_email']);
	variable_set('resource_upload_extensions', $form_state['values']['resource_upload']);
	variable_set('custom_model_abstract_upload_extensions', $form_state['values']['abstract_upload']);
	variable_set('custom_model_simulation_file', $form_state['values']['custom_model_upload']);
	variable_set('custom_model_script_file', $form_state['values']['custom_model_script_upload']);
	drupal_set_message(t('Settings updated'), 'status');
}