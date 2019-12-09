<?php
function custom_model_completed_proposals_all()
{
	$output = "";
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('approval_status', 3);
	$query->orderBy('actual_completion_date', 'DESC');
	//$query->condition('is_completed', 1);
	$result = $query->execute();
	
	//var_dump($custom_model_abstract);die;
	if ($result->rowCount() == 0)
	{
		$output .= "Work has been completed for the following custom model. We welcome your contributions." ."<hr>";

	} //$result->rowCount() == 0
	else
	{
		$output .= "Work has been completed for the following custom model. We welcome your contributions." . "<hr>";
		$preference_rows = array();
		$i = 1;
		while ($row = $result->fetchObject())
		{
			$proposal_id = $row->id;
			$approval_date = date("Y", $row->approval_date);
			$preference_rows[] = array(
				$i,
				l($row->project_title, "custom-model/custom-model-run/" . $row->id),
				$row->contributor_name,
				$row->university,
				$approval_date
			);
			$i++;
		} //$row = $result->fetchObject()
		$preference_header = array(
			'No',
			'Custom Model Project',
			'Contributor Name',
			'Institute',
			'Year'
		);
		$output .= theme('table', array(
			'header' => $preference_header,
			'rows' => $preference_rows
		));
	}
	return $output;
}
// eSim custom model display in progress proposals
function custom_model_progress_all()
{
	$page_content = "";
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('approval_status', 1);
	$query->condition('is_completed', 0);
	$result = $query->execute();
	if ($result->rowCount() == 0)
	{
		$page_content .= "Work is in progress for the following custom model under custom model Project<hr>";
	} //$result->rowCount() == 0
	else
	{
		$page_content .= "Work is in progress for the following custom model under custom model Project<hr>";
		$preference_rows = array();
		$i = 1;
		while ($row = $result->fetchObject())
		{
			$approval_date = date("Y", $row->approval_date);
			$preference_rows[] = array(
				$i,
				$row->project_title,
				$row->contributor_name,
				$row->university,
				$approval_date
			);
			$i++;
		} //$row = $result->fetchObject()
		$preference_header = array(
			'No',
			'custom model Project',
			'Contributor Name',
			'Institute',
			'Year'
		);
		$page_content .= theme('table', array(
			'header' => $preference_header,
			'rows' => $preference_rows
		));
	}
	return $page_content;
}