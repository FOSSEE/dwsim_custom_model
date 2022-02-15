<?php

//Proposal tab for reviewing approved proposals
function dwsim_custom_model_approved_tab()
{
	$page_content = "";
	$result = db_query("SELECT * from custom_model_proposal where id not in (select proposal_id from custom_model_submitted_abstracts) AND approval_status = 1 order by approval_date desc");
	if ($result->rowCount() == 0)
	{
		$page_content .= "Approved Proposals under Custom Model Project: " . $result->rowCount() . "<hr>";
	} //$result->rowCount() == 0
	else
	{
		$page_content .= "Approved Proposals under Custom Model Project: " . $result->rowCount() . "<hr>";
		$preference_rows = array();
		$i = 1;
		while ($row = $result->fetchObject())
		{
			$approval_date = date("d-M-Y", $row->approval_date);
			$preference_rows[] = array(
				$i,
				$row->project_title,
				$row->contributor_name,
				$row->university,
				$approval_date
			);
			$i++;
		} 
		$preference_header = array(
				'No',
				'Custom Model Project',
				'Contributor Name',
				'University / Institute',
				'Year of Completion'
			);
		$page_content .= theme('table', array(
			'header' => $preference_header,
			'rows' => $preference_rows
		));
	}
	return $page_content;
}


///////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////

//Proposal tab for reviewing Code submission of the uploaded proposals  

function dwsim_custom_model_uploaded_tab()
{
	$page_content = "";
	$result = db_query("SELECT dfp.project_title, dfp.contributor_name, dfp.id, dfp.university, dfa.abstract_upload_date, dfa.abstract_approval_status from custom_model_proposal as dfp JOIN custom_model_submitted_abstracts as dfa on dfa.proposal_id = dfp.id where dfp.id in (select proposal_id from custom_model_submitted_abstracts) AND approval_status = 1");

	if ($result->rowCount() == 0)
	{
		$page_content .= "Uploaded Proposals under Custom Model Project: " . $result->rowCount() . "<hr>";
	}
	else
	{
		$page_content .= "Uploaded Proposals under Custom Model Project: " . $result->rowCount() . "<hr>";
		$preference_rows = array();
		$i = 1;
		while ($row = $result->fetchObject())
		{
			$abstract_upload_date = date("d-M-Y", $row->abstract_upload_date);
			$preference_rows[] = array(
				$i,
				$row->project_title,
				$row->contributor_name,
				$row->university,
				$abstract_upload_date
			);
			$i++;
		}
		$preference_header = array(
				'No',
				'Custom Model Project',
				'Contributor Name',
				'University / Institute',
				'Year of Completion'
			);
		$page_content .= theme('table', array(
			'header' => $preference_header,
			'rows' => $preference_rows
		));
	}
	return $page_content;
}


///////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////

//Proposal tab for reviewing Completed proposals

function dwsim_custom_model_completed_tab_form($form, $form_state)
{
	$options_first = _custom_model_details_year_wise();
	$selected = isset($form_state['values']['howmany_select']) ? $form_state['values']['howmany_select'] : key($options_first);
	$form = array();
	$form['howmany_select'] = array(
	'#title' => t('Sorting projects according to year:'),
    '#type' => 'select',
    '#options' => _custom_model_details_year_wise(),
    /*'#options' => array(
    	'Please select...' => 'Please select...',
    	'2017' => '2017',
    	'2018' => '2018', 
    	'2019' => '2019', 
    	'2020' => '2020', 
    	'2021' => '2021'),*/
    '#default_value' => $selected,
    '#ajax' => array(
      'callback' => 'ajax_example_autocheckboxes_callback',
    ),
    '#suffix' => '<div id="ajax-selected-custom-model"></div>'

	  );
	return $form;
}


function ajax_example_autocheckboxes_callback($form, $form_state)
{
	$commands = array();
	$custom_model_project_default_value = $form_state['values']['howmany_select'];
	if ($custom_model_project_default_value != 0)
	{
		$form['howmany_select']['#options'] =  array('Please select...' => 'Please select...');
		$commands[] = ajax_command_html('#ajax-selected-custom-model', _custom_model_details($custom_model_project_default_value));
		$commands[] = ajax_command_replace('#ajax_selected_custom_model_action', drupal_render($form['howmany_select']));
		
	} 
	else
	{
		$form['howmany_select']['#options'] =  array('Please select...' => 'Please select...');
		$commands[] = ajax_command_data('#ajax_selected_custom_model', 'form_state_value_select', $form_state['values']['howmany_select']);
	}
	return array(
		'#type' => 'ajax',
		'#commands' => $commands
	);
}


function _custom_model_details_year_wise()
  {
    $custom_model_years = array(
		'0' => 'Please select...'
	);
    $result = db_query("SELECT from_unixtime(actual_completion_date, '%Y ') as Year from custom_model_proposal WHERE approval_status = 3 ORDER BY Year ASC");
    while ($year_wise_list_data = $result->fetchObject())
      {
        $custom_model_years[$year_wise_list_data->Year] = $year_wise_list_data->Year;
      }
    return $custom_model_years;
  }


function _custom_model_details($custom_model_proposal_id)
{
	$output = "";
	$result = db_query("SELECT * from {custom_model_proposal} WHERE approval_status = 3 and from_unixtime(actual_completion_date, '%Y') = :year",
		array(
							':year' => $custom_model_proposal_id)
				 );
	
	if ($result->rowCount() == 0)
	{
		$output .= "Work has been completed for the following custom model Project:" . $result->rowCount() . "<hr>";

	} 
	else
	{
		$output .= "Work has been completed for the following custom model Project:" . $result->rowCount() . "<hr>";
			$preference_rows = array();
			$i = 1;
			while ($row = $result->fetchObject())
			{
				$proposal_id = $row->id;
				$completion_date = date("Y", $row->actual_completion_date);
				$preference_rows[] = array(
					$i,
					l($row->project_title, "custom-model/custom-model-run/" . $row->id) . t("<br><strong>(Script used: ") . $row->script_used . t(")</strong>"),
					$row->contributor_name,
					$row->university,
					$completion_date
				);
				$i++;
			} //$row = $result->fetchObject()
			$preference_header = array(
				'No',
				'Custom Model Project',
				'Contributor Name',
				'University / Institute',
				'Year of Completion'
			);
			$output .= theme('table', array(
				'header' => $preference_header,
				'rows' => $preference_rows
			));
		}
		return $output;
	}