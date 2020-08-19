<?php
/**
 * Implementation of hook_mail().
 */
function custom_model_mail($key, &$message, $params)
{
	global $user;
	$language = $message['language'];
	//$language = user_preferred_language($user);
	switch ($key)
	{
		case 'custom_model_proposal_received':
			/* initializing data */
			$query = db_select('custom_model_proposal');
			$query->fields('custom_model_proposal');
			$query->condition('id', $params['custom_model_proposal_received']['result1']);
			$query->range(0, 1);
			$proposal_data = $query->execute()->fetchObject();
			$user = user_load($params['custom_model_proposal_received']['user_id']);
			$message['headers'] = $params['custom_model_proposal_received']['headers'];
			$message['subject'] = t('[!site_name][Custom Model] Your DWSIM Custom Model proposal has been received', array(
				'!site_name' => variable_get('site_name', '')
			), array(
				'language' => $language->language
			));
			$message['body'] = array(
				'body' => t('
Dear ' . $proposal_data->contributor_name . ',

We have received your DWSIM Custom Model proposal with the following details:

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '
Email: ' . $user->mail . '
University/Institute: ' . $proposal_data->university . '
Department/Branch: ' . $proposal_data->department . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '
Country: ' . $proposal_data->country . '
Project Title: ' . $proposal_data->project_title . '

Your proposal is under review. You will soon receive an email when same has been approved/disapproved.

Best Wishes,

!site_name Team,
FOSSEE, IIT Bombay', array(
					'!site_name' => variable_get('site_name', ''),
					'!user_name' => $user->name
				), array(
					'language' => $language->language
				))
			);
			break;
		case 'custom_model_proposal_approved':
			/* initializing data */
			$url = 'http://dwsim.fossee.in/custom-model/abstract-code';
			$query = db_select('custom_model_proposal');
			$query->fields('custom_model_proposal');
			$query->condition('id', $params['custom_model_proposal_approved']['proposal_id']);
			$query->range(0, 1);
			$proposal_data = $query->execute()->fetchObject();
			$user_data = user_load($params['custom_model_proposal_approved']['user_id']);
			$message['headers'] = $params['custom_model_proposal_approved']['headers'];
			$message['subject'] = t('[!site_name][Custom Model] Your DWSIM Custom Model proposal has been approved', array(
				'!site_name' => variable_get('site_name', '')
			), array(
				'language' => $language->language
			));
			$message['body'] = array(
				'body' => t('
Dear ' . $proposal_data->contributor_name . ',

Your DWSIM Custom Model proposal with the following details has been approved:

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '
Email: ' . $user_data->mail . '
University/Institute: ' . $proposal_data->university . '
Department/Branch: ' . $proposal_data->department . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '
Country: ' . $proposal_data->country . '
Project Title: ' . $proposal_data->project_title . '

You can upload your abstract and the completed simulation at: ' . $url . '
Best Wishes,

!site_name Team,
FOSSEE, IIT Bombay', array(
					'!site_name' => variable_get('site_name', ''),
					'!user_name' => $user_data->name
				), array(
					'language' => $language->language
				))
			);
			break;
		case 'custom_model_proposal_disapproved':
			/* initializing data */
			$query = db_select('custom_model_proposal');
			$query->fields('custom_model_proposal');
			$query->condition('id', $params['custom_model_proposal_disapproved']['proposal_id']);
			$query->range(0, 1);
			$proposal_data = $query->execute()->fetchObject();
			$user_data = user_load($params['custom_model_proposal_disapproved']['user_id']);
			//$user = user_load($params['custom_model_proposal']['name']);
			$message['headers'] = $params['custom_model_proposal_disapproved']['headers'];
			$message['subject'] = t('[!site_name][Custom Model] Your DWSIM Custom Model proposal has been disapproved', array(
				'!site_name' => variable_get('site_name', '')
			), array(
				'language' => $language->language
			));
			$message['body'] = array(
				'body' => t('
Dear ' . $proposal_data->contributor_name . ',

We regret to inform you that your DWSIM Custom Model proposal with following details have been disapproved:

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '
Email: ' . $user_data->mail . '
University/Institute: ' . $proposal_data->university . '
Department/Branch: ' . $proposal_data->department . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '
Country: ' . $proposal_data->country . '
Project Guide: ' . $project_guide_name . '
Project Guide Email: ' . $project_guide_email_id . '
Project Title: ' . $proposal_data->project_title . '

Reason for rejection: ' . $proposal_data->dissapproval_reason . '


You are welcome to submit a new proposal.

Best Wishes,

!site_name Team,
FOSSEE, IIT Bombay', array(
					'!site_name' => variable_get('site_name', ''),
					'!user_name' => $user_data->name
				), array(
					'language' => $language->language
				))
			);
			break;
		case 'custom_model_proposal_completed':
			/* initializing data */
			$query = db_select('custom_model_proposal');
			$query->fields('custom_model_proposal');
			$query->condition('id', $params['custom_model_proposal_completed']['proposal_id']);
			$query->range(0, 1);
			$proposal_data = $query->execute()->fetchObject();
			$user_data = user_load($params['custom_model_proposal_completed']['user_id']);
			$message['headers'] = $params['custom_model_proposal_completed']['headers'];
			$message['subject'] = t('[!site_name][Custom Model] Your DWSIM Custom Model proposal has been completed', array(
				'!site_name' => variable_get('site_name', '')
			), array(
				'language' => $language->language
			));
			$message['body'] = array(
				'body' => t('
Dear ' . $proposal_data->contributor_name . ',

Your DWSIM Custom Model and abstract on the following process have been completed successfully.

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '
Email: ' . $user_data->mail . '
University/Institute: ' . $proposal_data->university . '
Department/Branch: ' . $proposal_data->department . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '
Country: ' . $proposal_data->country . '
Project Title: ' . $proposal_data->project_title . '

Best Wishes,

!site_name Team,
FOSSEE, IIT Bombay', array(
					'!site_name' => variable_get('site_name', ''),
					'!user_name' => $user_data->name
				), array(
					'language' => $language->language
				))
			);
			break;
		case 'custom_model_proposal_deleted':
			/* initializing data */
			$query = db_select('custom_model_proposal');
			$query->fields('custom_model_proposal');
			$query->condition('id', $params['custom_model_proposal_deleted']['proposal_id']);
			$query->range(0, 1);
			$proposal_data = $query->execute()->fetchObject();
			$user_data = user_load($params['custom_model_proposal_deleted']['user_id']);
			$message['headers'] = $params['custom_model_proposal_deleted']['headers'];
			$message['subject'] = t('[!site_name][Custom Model] Your DWSIM Custom Model proposal has been deleted', array(
				'!site_name' => variable_get('site_name', '')
			), array(
				'language' => $language->language
			));
			$message['body'] = array(
				'body' => t('
Dear ' . $proposal_data->contributor_name . ',

Your DWSIM Custom Model proposal with the following details has been deleted :

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '
Email: ' . $user_data->mail . '
University/Institute: ' . $proposal_data->university . '
Department/Branch: ' . $proposal_data->department . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '
Country: ' . $proposal_data->country . '
Project Title: ' . $proposal_data->project_title . '

You can propose a new DWSIM Custom Model.

Best Wishes,

!site_name Team,
FOSSEE, IIT Bombay', array(
					'!site_name' => variable_get('site_name', ''),
					'!user_name' => $user_data->name
				), array(
					'language' => $language->language
				))
			);
			break;
		/**************************************************************/
		case 'abstract_uploaded':
			$query = db_select('custom_model_proposal');
			$query->fields('custom_model_proposal');
			$query->condition('id', $params['abstract_uploaded']['proposal_id']);
			$query->range(0, 1);
			$proposal_data = $query->execute()->fetchObject();
			$user = user_load($params['abstract_uploaded']['user_id']);
			$message['subject'] = t('[!site_name][Custom Model] You have uploaded DWSIM Custom Model project files', array(
				'!site_name' => variable_get('site_name', '')
			), array(
				'language' => $language->language
			));
			$message['headers'] = $params['abstract_uploaded']['headers'];
			$message['body'] = array(
				'body' => t('
Dear ' . $proposal_data->contributor_name . ',

You have uploaded the following Abstract and Project files:

Project Title: ' . $proposal_data->project_title . '

The abstract and project files are under review. You will be notified when it has been approved.

Best Wishes,

!site_name Team,
FOSSEE, IIT Bombay', array(
					'!site_name' => variable_get('site_name', ''),
					'!user_name' => $user->name
				), array(
					'language' => $language->language
				))
			);
			break;
		case 'standard':
			$message['subject'] = $params['standard']['subject'];
			$message['body'] = $params['standard']['body'];
			$message['headers'] = $params['standard']['headers'];
			break;
	} //$key
}