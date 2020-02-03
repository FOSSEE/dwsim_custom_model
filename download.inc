<?php
// $Id$
function custom_model_download_uploaded_file()
{
	$proposal_id = arg(3);
	$root_path = custom_model_path();
	$query = db_select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->condition('id', $proposal_id);
	$query->range(0, 1);
	$result = $query->execute();
	$custom_model_uploaded_file = $result->fetchObject();
	$samplecodename = $custom_model_uploaded_file->samplefilepath;
			ob_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header('Content-Type: application/pdf');
            header('Content-disposition: attachment; filename="' . $samplecodename . '"');
            header('Content-Length: ' . filesize($root_path . $custom_model_uploaded_file->directory_name . '/' . $samplecodename));
            header("Content-Transfer-Encoding: binary");
            header('Expires: 0');
            header('Pragma: no-cache');
            ob_clean();
            readfile($root_path . $custom_model_uploaded_file->directory_name . '/' . $samplecodename);
	        //ob_end_flush();
           //flush();
}
function custom_model_project_files() {
	$proposal_id = arg(3);
	$root_path = custom_model_path();
	//var_dump($proposal_id);die;
	$query = db_select('custom_model_submitted_abstracts_file');
	$query->fields('custom_model_submitted_abstracts_file');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();
	$custom_model_project_files = $result->fetchObject();
	//var_dump($custom_model_project_files);die;
	$query1 = db_select('custom_model_proposal');
	$query1->fields('custom_model_proposal');
	$query1->condition('id', $proposal_id);
	$result1 = $query1->execute();
	$custom_model = $result1->fetchObject();
	$directory_name = $custom_model->directory_name . '/project_files/';
	$samplecodename = $custom_model_project_files->filename;
	ob_clean();
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Type: application/pdf");
	header('Content-disposition: attachment; filename="' . $samplecodename . '"');
	header("Content-Length: " . filesize($root_path . $directory_name . $samplecodename));
	header("Content-Transfer-Encoding: binary");
	header("Expires: 0");
	header("Pragma: no-cache");
	ob_clean();
	readfile($root_path . $directory_name . $samplecodename);
	//ob_end_flush();
	//ob_clean();
}
function custom_model_download_idea_reference_file()
{
	$proposal_id = arg(3);
	$root_path = custom_model_ideas_files_path();
	$query = db_select('custom_model_idea_proposal');
	$query->fields('custom_model_idea_proposal');
	$query->condition('id', $proposal_id);
	$query->range(0, 1);
	$result = $query->execute();
	$uploaded_idea_reference_file = $result->fetchObject();
	$samplecodename = $uploaded_idea_reference_file->reference_file;
	//var_dump($root_path . $custom_model_uploaded_file->directory_name . '/' . $samplecodename);die;
			ob_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename="' . $samplecodename . '"');
            header('Content-Length: ' . filesize($root_path . $uploaded_idea_reference_file->directory_name . '/' . $samplecodename));
            header("Content-Transfer-Encoding: binary");
            header('Expires: 0');
            header('Pragma: no-cache');
            ob_clean();
            readfile($root_path . $uploaded_idea_reference_file->directory_name . '/' . $samplecodename);
	        //ob_end_flush();
           //flush();
}