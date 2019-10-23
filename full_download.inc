<?php

// $Id$
function custom_model_download_full_project()
{
	global $user;
	$id = arg(3);
	$root_path = custom_model_path();
	//var_dump($root_path);die;
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('id', $id);
	$custom_model_q = $query->execute();
	$custom_model_data = $custom_model_q->fetchObject();
	$CIRCUITSIMULATION_PATH = $custom_model_data->directory_name . '/';
	/* zip filename */
	$zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
	/* creating zip archive on the server */
	$zip = new ZipArchive();
	$zip->open($zip_filename, ZipArchive::CREATE);
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('id', $id);
	$custom_model_udc_q = $query->execute();
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('id', $id);
	$query = db_select('custom_model_submitted_abstracts_file');
	$query->fields('custom_model_submitted_abstracts_file');
	$query->condition('proposal_id', $id);
	$query_project_files = $query->execute();
	while ($project_files = $query_project_files->fetchObject()) {
		$zip->addFile($root_path . $CIRCUITSIMULATION_PATH . 'project_files/' . $project_files->filepath, $CIRCUITSIMULATION_PATH . str_replace(' ', '_', basename($project_files->filename)));
	}
	$zip_file_count = $zip->numFiles;
	$zip->close();
	if ($zip_file_count > 0) {
		if ($user->uid) {
			/* download zip file */
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $custom_model_data->project_title) . '.zip"');
			header('Content-Length: ' . filesize($zip_filename));
			ob_clean();
			readfile($zip_filename);
			unlink($zip_filename);
			/*flush();
			ob_end_flush();
			ob_clean();*/
			
		} //$user->uid
		else {
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $custom_model_data->project_title) . '.zip"');
			header('Content-Length: ' . filesize($zip_filename));
			header("Content-Transfer-Encoding: binary");
			header('Expires: 0');
			header('Pragma: no-cache');
			//ob_end_flush();
			ob_clean();
			//flush();
			readfile($zip_filename);
			unlink($zip_filename);
		}
	} //$zip_file_count > 0
	else {
		drupal_set_message("There are no circuit simulation project in this proposal to download", 'error');
		drupal_goto('circuit-simulation-project/full-download/project');
	}
}
function custom_model_download_completed_project()
{
		global $user;
	$id = arg(3);
	$root_path = custom_model_path();
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('id', $id);
	$custom_model_q = $query->execute();
	$custom_model_data = $custom_model_q->fetchObject();
	$CIRCUITSIMULATION_PATH = $custom_model_data->directory_name . '/';
	/* zip filename */
	$zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
	/* creating zip archive on the server */
	$zip = new ZipArchive();
	$zip->open($zip_filename, ZipArchive::CREATE);
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('id', $id);
	$custom_model_udc_q = $query->execute();
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('id', $id);
	$query = db_select('custom_model_submitted_abstracts_file');
	$query->fields('custom_model_submitted_abstracts_file');
	$query->condition('proposal_id', $id);
	$project_files = $query->execute();
	//var_dump($root_path . $CIRCUITSIMULATION_PATH . 'project_files/');die;
	while ($project_files = $project_files->fetchObject()) {
		$zip->addFile($root_path . $CIRCUITSIMULATION_PATH . 'project_files/' . $project_files->filepath, $CIRCUITSIMULATION_PATH . str_replace(' ', '_', basename($project_files->filename)));
	}
	$zip_file_count = $zip->numFiles;
	$zip->close();
	if ($zip_file_count > 0) {
		if ($user->uid) {
			/* download zip file */
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $custom_model_data->project_title) . '.zip"');
			header('Content-Length: ' . filesize($zip_filename));
			//ob_end_flush();
			ob_clean();
			//flush();
			readfile($zip_filename);
			unlink($zip_filename);
		} //$user->uid
		else {
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $custom_model_data->project_title) . '.zip"');
			header('Content-Length: ' . filesize($zip_filename));
			header("Content-Transfer-Encoding: binary");
			header('Expires: 0');
			header('Pragma: no-cache');
			//ob_end_flush();
			ob_clean();
			//flush();
			readfile($zip_filename);
			unlink($zip_filename);
		}
	} //$zip_file_count > 0
	else {
		drupal_set_message("There are circuit simulation project in this proposal to download", 'error');
		drupal_goto('circuit-simulation-project/full-download/project');
	}
}