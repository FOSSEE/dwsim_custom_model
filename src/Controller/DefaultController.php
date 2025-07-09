<?php /**
 * @file
 * Contains \Drupal\custom_model\Controller\DefaultController.
 */

namespace Drupal\custom_model\Controller;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Service;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Default controller for the custom_model module.
 */
class DefaultController extends ControllerBase {

  public function custom_model_proposal_pending() {
    /* get pending proposals to be approved */
    $pending_rows = [];
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('approval_status', 0);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();

    // var_dump($pending_data);die;
//     while ($pending_data = $pending_q->fetchObject()) {

//       $approval_url = Link::fromTextAndUrl('Approve', Url::fromRoute('custom_model.proposal_approval_form',['id'=>$pending_data->id]))->toString();
//       // 
//         $edit_url =  Link::fromTextAndUrl('Edit', Url::fromRoute('custom_model.proposal_edit_form',['id'=>$pending_data->id]))->toString();

//         $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));

//       $pending_rows[$pending_data->id] = [
//         date('d-m-Y', $pending_data->creation_date),

//         // l($pending_data->name_title . ' ' . $pending_data->contributor_name, 'user/' . $pending_data->uid),
//         // $pending_data->project_title,

//         Link::fromTextAndUrl($pending_data->name_title . ' ' . $pending_data->contributor_name,
//         Url::fromUri('internal:/user/' . $pending_data->uid)
//         )->toString(),
//         $pending_data->project_title,
//         // l('Approve', 'custom-model/manage-proposal/approve/' . $pending_data->id) . ' | ' . l('Edit', 'custom-model/manage-proposal/edit/' . $pending_data->id),
       
//         $pending_data->department,
//         $mainLink 


	
// // @linkApprove' => $approval_url, @linkReject' => $edit_url;
//       // $mainLink = t('$approval_url | $edit_url'),
//       ];
//     }
//     // var_dump($pending_data);die;
//     //$pending_data = $pending_q->fetchObject()
// 	/* check if there are any pending proposals */
//     // if (!$pending_rows) {
//     //   \Drupal::messenger()->addMessage(t('There are no pending proposals.'), 'status');
//     //   return '';
//     // } 
while ($pending_data = $pending_q->fetchObject()) {
  $name = isset($pending_data->name_title) ? (string) $pending_data->name_title : '';
  $contributor = isset($pending_data->contributor_name) ? (string) $pending_data->contributor_name : '';
  $title = isset($pending_data->project_title) ? (string) $pending_data->project_title : '';
  $department = isset($pending_data->department) ? (string) $pending_data->department : '';

  $approval_url = Link::fromTextAndUrl('Approve', Url::fromRoute('custom_model.proposal_approval_form', ['id' => $pending_data->id]))->toString();
  $edit_url = Link::fromTextAndUrl('Edit', Url::fromRoute('custom_model.proposal_edit_form', ['id' => $pending_data->id]))->toString();

  $mainLink = t('@linkApprove | @linkReject', ['@linkApprove' => $approval_url, '@linkReject' => $edit_url]);

  $pending_rows[$pending_data->id] = [
    date('d-m-Y', $pending_data->creation_date),
    Link::fromTextAndUrl($name . ' ' . $contributor, Url::fromUri('internal:/user/' . $pending_data->uid))->toString(),
    $title,
    $department,
    $mainLink,
  ];
}

//     //!$pending_rows
$pending_header = [
  'Date of Submission',
  'Contributor Name',
  'Title of the Custom Model',
  'Department',
  'Action',
];
   
//$output = theme_table($pending_header, $pending_rows);
    $output = [
      '#type' => 'table',
      '#header' => $pending_header,
      '#rows' => $pending_rows,
    ];
    // var_dump($output);die;
    return $output;
  }


public function custom_model_proposal_all()
{
	/* get pending proposals to be approved */
	$proposal_rows = array();
	$query = \Drupal::database()->select('custom_model_proposal');
	$query->fields('custom_model_proposal');
	$query->orderBy('id', 'DESC');
	$proposal_q = $query->execute();
	while ($proposal_data = $proposal_q->fetchObject())
	{
		$approval_status = '';
		switch ($proposal_data->approval_status)
		{
			case 0:
				$approval_status = 'Pending';
				break;
			case 1:
				$approval_status = 'Approved';
				break;
			case 2:
				$approval_status = 'Dis-approved';
				break;
			case 3:
				$approval_status = 'Completed';
				break;
			default:
				$approval_status = 'Unknown';
				break;
		} //$proposal_data->approval_status
		if ($proposal_data->actual_completion_date == 0)
		{
			$actual_completion_date = "Not Completed";
		} //$proposal_data->actual_completion_date == 0
		else
		{
			$actual_completion_date = date('d-m-Y', $proposal_data->actual_completion_date);
		}
		$proposal_rows[] = [
			date('d-m-Y', $proposal_data->creation_date),
			// l($proposal_data->contributor_name, 'user/' . $proposal_data->uid),
      Link::fromTextAndUrl($proposal_data->name_title . ' ' . $proposal_data->contributor_name,Url::fromUri('internal:/user/' . $proposal_data->uid)
      )->toString(),
			$proposal_data->project_title,
			$actual_completion_date,
			$approval_status,
			// l('Status', 'custom-model/manage-proposal/status/' . $proposal_data->id) . ' | ' . l('Edit', 'custom-model/manage-proposal/edit/' . $proposal_data->id)
      Link::fromTextAndUrl('Status', Url::fromRoute('custom_model.proposal_status_form',['id'=>$proposal_data->id]))->toString(),
      $edit_url =  Link::fromTextAndUrl('Edit', Url::fromRoute('custom_model.proposal_edit_form',['id'=>$proposal_data->id]))->toString(),
    ];
	} //$proposal_data = $proposal_q->fetchObject()
	/* check if there are any pending proposals */
	if (!$proposal_rows)
	{
		\Drupal::messenger()->addMessage(t('There are no proposals.'), 'status');
		return '';
	} //!$proposal_rows
	$proposal_header = [
		'Date of Submission',
		'Contributor Name',
		'Title of the Custom Model',
		'Date of Completion',
		'Status',
		'Action'
  ];
	$output = [
    '#theme' => 'table',
		'#header' => $proposal_header,
		'#rows' => $proposal_rows
  ];
	return $output;
}
 public function custom_model_idea_proposal_all() {
    /* get pending proposals to be approved */
    $proposal_rows = [];
    $query = \Drupal::database()->select('custom_model_idea_proposal');
    $query->fields('custom_model_idea_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
      $approval_status = '';
      switch ($proposal_data->approval_status) {
        case 0:
          $approval_status = 'Pending';
          break;
        case 1:
          $approval_status = 'Approved';
          break;
        case 2:
          $approval_status = 'Dis-approved';
          break;
        case 3:
          $approval_status = 'Completed';
          break;
        default:
          $approval_status = 'Unknown';
          break;
      } //$proposal_data->approval_status
      if ($proposal_data->actual_completion_date == 0) {
        $actual_completion_date = "Not Completed";
      } //$proposal_data->actual_completion_date == 0
      else {
        $actual_completion_date = date('d-m-Y', $proposal_data->actual_completion_date);
      }
      // $idea_proposer_link = 
      Link::fromTextAndUrl(
        $proposal_data->idea_proposar_name,
        Url::fromUri('internal:/user/' . $proposal_data->uid)
      )->toString();
      
      $proposal_rows[] = [
        date('d-m-Y', $proposal_data->creation_date),
        // l($proposal_data->idea_proposar_name, 'user/' . $proposal_data->uid),
        // $proposal_data->project_title,
        // Generate the link for the idea proposer name.
        Link::fromTextAndUrl($proposal_data->idea_proposar_name . ' ' . $pending_data->contributor_name,Url::fromUri('internal:/user/' . $proposal_data->uid)
        )->toString(),
        
        
// Assign the project title (no changes required for plain text).
$project_title = $proposal_data->project_title,


       
        // Generate the "View" link.
 Link::fromTextAndUrl(
  'View',
  Url::fromUri('internal:/custom-model/manage-proposal/view-ideas/' . $proposal_data->id)
)->toString(),
      ];
    } //$proposal_data = $proposal_q->fetchObject()
	/* check if there are any pending proposals */
    // if (!$proposal_rows) {
    //   \Drupal::messenger()->addMessage(t('There are no proposals.'), 'status');
    //   return '';
    // } 
    //!$proposal_rows
    $proposal_header = [
      'Date of Submission',
      'Contributor Name',
      'Title of the Custom Model',
      'Action',
    ];
    $output = [
      '#type' => 'table',
      '#header' => $proposal_header,
      '#rows' => $proposal_rows,
    ];
    return $output;
  }

  
  
    public function dwsim_custom_model_approved_tab() {
      $page_content = [];
      $connection = \Drupal::database();
  
      // Query to fetch approved proposals.
      // $query = $connection->query("
      //   SELECT * 
      //   FROM custom_model_proposal 
      //   WHERE id NOT IN (SELECT proposal_id FROM custom_model_submitted_abstracts) 
      //   AND approval_status = 1 
      //   ORDER BY approval_date DESC
      // ");
      $query = $connection->query("
  SELECT * 
  FROM custom_model_proposal 
  WHERE approval_status = 1 
  ORDER BY approval_date DESC
");

      $result = $query->fetchAll();
  
      // Check if any results are returned.
      if (empty($result)) {
        // No proposals found.
        $page_content['message'] = [
          '#markup' => '<p>Approved Proposals under Custom Model Project: 0</p>',
        ];
      }
      else {
        // Proposals found.
        $page_content['message'] = [
          '#markup' => '<p>Approved Proposals under Custom Model Project: ' . count($result) . '</p><hr>',
        ];
  
        $proposal_rows = [];
        $i = 1;
  
        // Loop through the results to build table rows.
        foreach ($result as $row) {
          $approval_date = date("d-M-Y", $row->approval_date); // Convert timestamp to human-readable format.
  
          $proposal_rows[] = [
            $i,
            $row->project_title,
            $row->contributor_name,
            $row->university,
            $approval_date,
          ];
          $i++;
        }
  
        // Define table headers.
        $proposal_header = [
          'No',
          'Custom Model Project',
          'Contributor Name',
          'University / Institute',
          'Year of Completion',
        ];
  
        // Define the table render array.
        $page_content = [
          '#type' => 'table',
          '#header' => $proposal_header,
          '#rows' => $proposal_rows,
        ];
      }
  
      return $page_content;
    
  }
  
  public function dwsim_custom_model_uploaded_tab() {
    $page_content = [];
      // $connection = \Drupal::database();
      $result = \Drupal::database()->query("SELECT dfp.project_title, dfp.contributor_name, dfp.id, dfp.university, dfa.abstract_upload_date, dfa.abstract_approval_status from custom_model_proposal as dfp JOIN custom_model_submitted_abstracts as dfa on dfa.proposal_id = dfp.id where dfp.id in (select proposal_id from custom_model_submitted_abstracts) AND approval_status = 1");
      // Query to fetch approved proposals.
      // $query = $connection->query("
      //   SELECT * 
      //   FROM custom_model_proposal 
      //   WHERE id NOT IN (SELECT proposal_id FROM custom_model_submitted_abstracts) 
      //   AND approval_status = 1 
      //   ORDER BY approval_date DESC
      // ");
     

  // Execute the query and fetch the results.
  
      // $result = $query->fetchAll();
  
      // Check if any results are returned.
      if (empty($result)) {
        // No proposals found.
        $page_content['message'] = [
          '#markup' => '<p>Uploaded Proposals under Custom Model Project :2</p>',
          $page_content .= "Uploaded Proposals under Custom Model Project:2"
        ];
      }
      else {
        // Proposals found.
        $page_content['message'] = [
          '#markup' => '<p>Uploaded Proposals under Custom Model Project :2 . count($result) . </p>',
        ];
  
        $proposal_rows = [];
        $i = 1;
  
        // Loop through the results to build table rows.
        foreach ($result as $row) {
          $approval_date = date("d-M-Y", $row->approval_date); // Convert timestamp to human-readable format.
  
          $proposal_rows[] = [
            $i,
            $row->project_title,
            $row->contributor_name,
            $row->university,
            $approval_date,
          ];
          $i++;
        }
  
        // Define table headers.
        $proposal_header = [
          'No',
          'Custom Model Project',
          'Contributor Name',
          'University / Institute',
          'Year of Completion',
        ];
  
        // Define the table render array.
        $page_content = [
          '#type' => 'table',
          '#header' => $proposal_header,
          '#rows' => $proposal_rows,
        ];
      }
      return $page_content;
    }
  

  // public function custom_model_abstract() {
  //   $user = \Drupal::currentUser();
  //   $return_html = "";
  //   $proposal_data = \Drupal::service("custom_model_global")->custom_model_get_proposal();
  //   if (!$proposal_data) {
  //     // drupal_goto('');
      
  //    return;
  //   } //!$proposal_data
	// /* get experiment list */
  //   $query = \Drupal::database()->select('custom_model_submitted_abstracts');
  //   $query->fields('custom_model_submitted_abstracts');
  //   $query->condition('proposal_id', $proposal_data->id);
  //   $abstracts_q = $query->execute()->fetchObject();
  //   $query_pro = \Drupal::database()->select('custom_model_proposal');
  //   $query_pro->fields('custom_model_proposal');
  //   $query_pro->condition('id', $proposal_data->id);
  //   $abstracts_pro = $query_pro->execute()->fetchObject();
  //   $query_pdf = \Drupal::database()->select('custom_model_submitted_abstracts_file');
  //   $query_pdf->fields('custom_model_submitted_abstracts_file');
  //   $query_pdf->condition('proposal_id', $proposal_data->id);
  //   $query_pdf->condition('filetype', 'A');
  //   $abstracts_pdf = $query_pdf->execute()->fetchObject();
  //   if ($abstracts_pdf == TRUE) {
  //     if (!empty($abstracts_pdf->filename) && $abstracts_pdf->filename !== "NULL") {
  //       $abstract_filename = $abstracts_pdf->filename;
  //       //$abstract_filename = l($abstracts_pdf->filename, 'custom-model/download/project-file/' . $proposal_data->id);
  //     } //$abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != ""
  //     else {
  //       $abstract_filename = "File not uploaded";
  //     }
  //   } //$abstracts_pdf == TRUE
  //   else {
  //     $abstract_filename = "File not uploaded";
  //   }
  //   $query_process = \Drupal::database()->select('custom_model_submitted_abstracts_file');
  //   $query_process->fields('custom_model_submitted_abstracts_file');
  //   $query_process->condition('proposal_id', $proposal_data->id);
  //   $query_process->condition('filetype', 'S');
  //   $abstracts_query_process = $query_process->execute()->fetchObject();
  //   if ($abstracts_query_process == TRUE) {
  //     if ($abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != "") {
  //       $abstracts_query_process_filename = $abstracts_query_process->filename;
  //       //$abstracts_query_process_filename = l($abstracts_query_process->filename, 'custom-model/download/project-file/' . $proposal_data->id); 
  //     } //$abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != ""
  //     else {
  //       $abstracts_query_process_filename = "File not uploaded";
  //     }
  //     if ($abstracts_q->is_submitted == '') {
  //       // $url = l('Upload abstract', 'custom-model/abstract-code/upload');
  //       $url = Link::fromTextAndUrl('Upload abstract', Url::fromUri('internal:/custom-model/abstract-code/upload'))->toString();

  //     } //$abstracts_q->is_submitted == ''
  //     else {
  //       if ($abstracts_q->is_submitted == 1) {
  //         $url = "";
  //       } //$abstracts_q->is_submitted == 1
  //       else {
  //         if ($abstracts_q->is_submitted == 0) {
  //           // $url = l('Edit', 'custom-model/abstract-code/upload');
  //           $url = Link::fromTextAndUrl('Edit', Url::fromUri('internal:/custom-model/abstract-code/upload'))->toString();

  //         }
  //       }
  //     } //$abstracts_q->is_submitted == 0
  //   } //$abstracts_query_process == TRUE
  //   else {
  //     // $url = l('Upload abstract', 'custom-model/abstract-code/upload');
  //     $url = Link::fromTextAndUrl('Upload abstract', Url::fromUri('internal:/custom-model/abstract-code/upload'))->toString();
  //     $abstracts_query_process_filename = "File not uploaded";
  //   }
  //   $query_sc = \Drupal::database()->select('custom_model_submitted_abstracts_file');
  //   $query_sc->fields('custom_model_submitted_abstracts_file');
  //   $query_sc->condition('proposal_id', $proposal_data->id);
  //   $query_sc->condition('filetype', 'P');
  //   $abstracts_query_sc = $query_sc->execute()->fetchObject();
  //   if ($abstracts_query_sc == TRUE) {
  //     if ($abstracts_query_sc->filename != "NULL" || $abstracts_query_sc->filename != "") {
  //       $abstracts_query_sc_filename = $abstracts_query_sc->filename;
  //     } //$abstracts_query_sc->filename != "NULL" || $abstracts_query_sc->filename != ""
  //     else {
  //       $abstracts_query_sc_filename = "File not uploaded";
  //     }
  //     if ($abstracts_q->is_submitted == '') {
  //       // $url = l('Upload abstract', 'custom-model/abstract-code/upload');
  //       $url = Link::fromTextAndUrl('Upload abstract', Url::fromUri('internal:/custom-model/abstract-code/upload'))->toString();

  //     } //$abstracts_q->is_submitted == ''
  //     else {
  //       if ($abstracts_q->is_submitted == 1) {
  //         $url = "";
  //       } //$abstracts_q->is_submitted == 1
  //       else {
  //         if ($abstracts_q->is_submitted == 0) {
  //           // $url = l('Edit', 'custom-model/abstract-code/upload');
  //           $url = Link::fromTextAndUrl('Edit', Url::fromUri('internal:/custom-model/abstract-code/upload'))->toString();

  //         }
  //       }
  //     } //$abstracts_q->is_submitted == 0
  //   } //$abstracts_query_sc == TRUE
  //   else {
  //     // $url = l('Upload abstract', 'custom-model/abstract-code/upload');
  //     $url = Link::fromTextAndUrl('Upload abstract', Url::fromUri('internal:/custom-model/abstract-code/upload'))->toString();

  //     $abstracts_query_sc_filename = "File not uploaded";
  //   }
  //   // var_dump($abstract_filename);die;
    
    
  //     $return_html = '<strong>Contributor Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '<br /><br />';
  //     $return_html .= '<strong>Title of the Circuit Simulation Project:</strong><br />' . $proposal_data->project_title . '<br /><br />';
  //     $return_html .= '<strong>DWSIM version used:</strong><br />' . $proposal_data->version . '<br /><br />';
  //     $return_html .= '<strong>Uploaded Custom Model as DWSIM Simulation File:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
  //     $return_html .= '<strong>Uploaded scilab/ironpython script for the custom model:</strong><br />' . $abstracts_query_sc_filename . '<br /><br />';
  //     $return_html .= '<strong>Uploaded abstract of the project:</strong><br />' . $abstract_filename . '<br /><br />';
  //     $return_html .= $url . '<br />';
      
  //     // return new Response($return_html);
  //     return [
  //       '#type' => 'markup',
  //       '#markup' => $return_html,
  //       '#allowed_tags' => ['strong', 'br', 'a'],
  //     ];
  
  // }
    

public function custom_model_abstract() {
  $user = \Drupal::currentUser();
  $return_html = "";

  // Fetch proposal.
  $proposal_data = \Drupal::service('custom_model_global')->custom_model_get_proposal();
  if (!$proposal_data) {
    return [];
  }

  $db = \Drupal::database();

  // Fetch submission status.
  $abstracts_q = $db->select('custom_model_submitted_abstracts')
    ->fields('custom_model_submitted_abstracts')
    ->condition('proposal_id', $proposal_data->id)
    ->execute()
    ->fetchObject();

  // Abstract file (type A).
  $abstracts_pdf = $db->select('custom_model_submitted_abstracts_file')
    ->fields('custom_model_submitted_abstracts_file')
    ->condition('proposal_id', $proposal_data->id)
    ->condition('filetype', 'A')
    ->execute()
    ->fetchObject();

  if (!empty($abstracts_pdf->filename) && $abstracts_pdf->filename !== "NULL") {
    $abstract_filename = Link::fromTextAndUrl(
      $abstracts_pdf->filename,
      Url::fromUri('internal:/custom-model/download/project-file/' . $proposal_data->id)
    )->toString();
  } else {
    $abstract_filename = "File not uploaded";
  }

  // Simulation file (type S).
  $abstracts_query_process = $db->select('custom_model_submitted_abstracts_file')
    ->fields('custom_model_submitted_abstracts_file')
    ->condition('proposal_id', $proposal_data->id)
    ->condition('filetype', 'S')
    ->execute()
    ->fetchObject();

  if (!empty($abstracts_query_process->filename) && $abstracts_query_process->filename !== "NULL") {
    $abstracts_query_process_filename = $abstracts_query_process->filename;
  } else {
    $abstracts_query_process_filename = "File not uploaded";
  }

  // Script file (type P).
  $abstracts_query_sc = $db->select('custom_model_submitted_abstracts_file')
    ->fields('custom_model_submitted_abstracts_file')
    ->condition('proposal_id', $proposal_data->id)
    ->condition('filetype', 'P')
    ->execute()
    ->fetchObject();

  if (!empty($abstracts_query_sc->filename) && $abstracts_query_sc->filename !== "NULL") {
    $abstracts_query_sc_filename = $abstracts_query_sc->filename;
  } else {
    $abstracts_query_sc_filename = "File not uploaded";
  }

  // Upload/Edit link based on submission status.
  $url = '';
  if (isset($abstracts_q->is_submitted)) {
    if ($abstracts_q->is_submitted === '') {
      $url = Link::fromTextAndUrl('Upload abstract', Url::fromUri('internal:/custom-model/abstract-code/upload'))->toString();
    } elseif ($abstracts_q->is_submitted == 0) {
      $url = Link::fromTextAndUrl('Edit', Url::fromUri('internal:/custom-model/abstract-code/upload'))->toString();
    }
  } else {
    $url = Link::fromTextAndUrl('Upload abstract', Url::fromUri('internal:/custom-model/abstract-code/upload'))->toString();
  }

  // Build HTML.
  $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '<br /><br />';
  $return_html .= '<strong>Title of the Circuit Simulation Project:</strong><br />' . $proposal_data->project_title . '<br /><br />';
  $return_html .= '<strong>DWSIM version used:</strong><br />' . $proposal_data->version . '<br /><br />';
  $return_html .= '<strong>Uploaded Custom Model as DWSIM Simulation File:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
  $return_html .= '<strong>Uploaded scilab/ironpython script for the custom model:</strong><br />' . $abstracts_query_sc_filename . '<br /><br />';
  $return_html .= '<strong>Uploaded abstract of the project:</strong><br />' . $abstract_filename . '<br /><br />';
  $return_html .= $url . '<br />';

  // Return render array with allowed tags.
  return [
    '#type' => 'markup',
    '#markup' => $return_html,
    '#allowed_tags' => ['strong', 'br', 'a'],
  ];
}


  public function custom_model_download_completed_project() {
    $user = \Drupal::currentUser();
    // $id = arg(3);
    $root_path = \Drupal::service("custom_model_global")->custom_model_path();
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('id', $id);
    $custom_model_q = $query->execute();
    $custom_model_data = $custom_model_q->fetchObject();
    $CIRCUITSIMULATION_PATH = $custom_model_data->directory_name . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('id', $id);
    $custom_model_udc_q = $query->execute();
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('id', $id);
    $query = \Drupal::database()->select('custom_model_submitted_abstracts_file');
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
      \Drupal::messenger()->addMessage("There are circuit simulation project in this proposal to download", 'error');
      // drupal_goto('circuit-simulation-project/full-download/project');
    }
  }

  
public function custom_model_download_full_project() {
  $user = \Drupal::currentUser();
  $route_match = \Drupal::routeMatch();
  $id = $route_match->getParameter('id');
  $root_path = \Drupal::service("custom_model_global")->custom_model_path(); // Assuming this is a global helper

  // Fetch proposal data
  $query = \Drupal::database()->select('custom_model_proposal', 'cmp');
  $query->fields('cmp');
  $query->condition('id', $id);
  $custom_model_data = $query->execute()->fetchObject();

  if (!$custom_model_data) {
    \Drupal::messenger()->addError(t('Invalid proposal ID.'));
    return new RedirectResponse('/circuit-simulation-project/full-download/project');
  }

  $project_dir = $custom_model_data->directory_name . '/project_files/';
  $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

  $zip = new \ZipArchive();
  if ($zip->open($zip_filename, \ZipArchive::CREATE) !== true) {
    \Drupal::messenger()->addError(t('Unable to create zip file.'));
    return new RedirectResponse('/circuit-simulation-project/full-download/project');
  }

  // Add project files to zip
  $query = \Drupal::database()->select('custom_model_submitted_abstracts_file', 'f');
  $query->fields('f');
  $query->condition('proposal_id', $id);
  $result = $query->execute();

  while ($file = $result->fetchObject()) {
    $file_path = $root_path . $custom_model_data->directory_name . '/project_files/' . $file->filepath;
    if (file_exists($file_path)) {
      $zip->addFile($file_path, str_replace(' ', '_', basename($file->filename)));
    }
  }

  $zip_file_count = $zip->numFiles;
  $zip->close();

  if ($zip_file_count > 0 && file_exists($zip_filename)) {
    $response = new BinaryFileResponse($zip_filename);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      str_replace(' ', '_', $custom_model_data->project_title) . '.zip'
    );

    // Delete the zip file after it's sent
    $response->deleteFileAfterSend(true);
    return $response;
  }
  else {
    \Drupal::messenger()->addError(t('There are no circuit simulation files in this proposal to download.'));
    return new RedirectResponse('/circuit-simulation-project/full-download/project/');
  }
}

  public function custom_model_completed_proposals_all() {
    $output = [];
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('approval_status', 3);
    $query->orderBy('actual_completion_date', 'DESC');
    //$query->condition('is_completed', 1);
    $result = $query->execute();

    
    //var_dump($custom_model_abstract);die;
    if (empty($result)) {
      $output ['message']= [
         '#markup' => '<p>Work has been completed for the following custom model. We welcome your contributions. . </p><hr>'
      ];
      
    }
    else{
      $output ['message']= [ '#markup' => '<p>Work has been completed for the following custom model. We welcome your contributions. </p>'];

   
   
    //$result->rowCount() == 0
   
      $preference_rows = [];
      $i = 1;
      while ($row = $result->fetchObject()) {
        $proposal_id = $row->id;
        $completion_date = date("Y", $row->actual_completion_date);
        $preference_rows[] = [
          $i,
          // l($row->project_title, "custom-model/custom-model-run/" . $row->id) . t("<br><strong>(Script used: ") . $row->script_used . t(")</strong>"),
          Link::fromTextAndUrl(
            $row->project_title,
            Url::fromUri('internal:/custom-model/custom-model-run/' . $row->id)
          )->toString(),
          
          // $markup = $link . $this->t('<br><strong>(Script used: @script)</strong>', ['@script' => $row->script_used]),
          $row->contributor_name,
          $row->university,
          $completion_date,
        ];
        $i++;
      } //$row = $result->fetchObject()
      $preference_header = [
        'No',
        'Custom Model Project',
        'Contributor Name',
        'University / Institute',
        'Year of Completion',
      ];
      $output =[
        '#type' => 'table',
        '#header' => $preference_header,
        '#rows' => $preference_rows,
      ];
    }
    return $output;
  }

  

 

/**
 * Custom function to show progress of custom model proposals.
 */
public function custom_model_progress_all() {
  $page_content = [];
  
  // Get the database connection
  $connection = Database::getConnection();
  
  // Create the select query
  $query = $connection->select('custom_model_proposal', 'cmp');
  $query->fields('cmp');
  $query->condition('approval_status', 1);
  $query->condition('is_completed', 0);
  $query->orderBy('approval_date', 'DESC');
  
  // Execute the query and get the results
  $result = $query->execute();

  // Check if no rows are returned
  // if ($result->fetchAll()) {
  //   $page_content ['#markup']= "Work is in progress for the following custom model under DWSIM Custom Model Project";
  // } else {
  //   // If results exist, start building the table
  //   $page_content ['#markup ']= "Work is in progress for the following custom model under DWSIM Custom Model Project";
    
  if (empty($result)) {
    $output['message'] = [
      '#type' => 'container',
      'text' => [
        '#markup' => t('Work has been completed for the following custom model. We welcome your contributions.'),
      ],
      'divider' => [
        '#markup' => '<hr>',
      ],
    ];
  } else {
    $output['message'] = [
      '#type' => 'container',
      'text' => [
        '#markup' => t('Work has been completed for the following custom model. We welcome your contributions.'),
      ],
    ];
  
  }
    $preference_rows = [];
    $i = 1; // Counter for row numbers

    // Iterate over each row of results
    foreach ($result as $row) {
      $approval_date = date("Y", $row->approval_date);
      $preference_rows[] = [
        $i,
        $row->project_title,
        $row->contributor_name,
        $row->university,
        $approval_date,
      ];
      $i++;
    // }

    // Define table headers
    $preference_header = [
      'No',
      'Custom Model Project',
      'Contributor Name',
      'University / Institute',
      'Year',
    ];
    
    // Render the table
    $page_content =  [
      '#type' =>'table',
      '#header' => $preference_header,
      '#rows' => $preference_rows,
    ];
  }
  
  return $page_content;
}

  
  public function custom_model_download_uploaded_file() {
    // $proposal_id = arg(3);
    $route_match = \Drupal::routeMatch();
    $proposal_id = (int) $route_match->getParameter('proposal_id');
    // var_dump($proposal_id);die;

    $root_path = \Drupal::service("custom_model_global")->custom_model_path();
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $result = $query->execute();
    $custom_model_uploaded_file = $result->fetchObject();
    $samplecodename = $custom_model_uploaded_file->samplefilepath;
     //var_dump($root_path . $samplecodename);die;
    ob_clean();
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header('Content-Type: application/pdf');
    header('Content-disposition: attachment; filename="' . $samplecodename . '"');
    header('Content-Length: ' . filesize($root_path . $samplecodename));
    header("Content-Transfer-Encoding: binary");
    header('Expires: 0');
    header('Pragma: no-cache');
    ob_clean();
    readfile($root_path . $samplecodename);
    //ob_end_flush();
    // var_dump($root_path . $custom_model_uploaded_file);die;

    //flush();
  }

  
  

  public function custom_model_download_idea_reference_file() {
    // $proposal_id = arg(3);
    $route_match = \Drupal::routeMatch();
  $proposal_id = $route_match->getParameter('proposal_id');
 
    $root_path = custom_model_ideas_files_path();
    $query = \Drupal::database()->select('custom_model_idea_proposal');
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

  public function custom_model_project_files() {
    // $proposal_id = arg(3);
    $route_match = \Drupal::routeMatch();
  $proposal_id = $route_match->getParameter('proposal_id');
 
    $root_path = \Drupal::service('custom_model_global')->custom_model_path();
    //var_dump($proposal_id);die;
    $query = \Drupal::database()->select('custom_model_submitted_abstracts_file');
    $query->fields('custom_model_submitted_abstracts_file');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('filetype', 'A');
    $result = $query->execute();
    $custom_model_project_files = $result->fetchObject();
    //var_dump($custom_model_project_files);die;
    $query1 = \Drupal::database()->select('custom_model_proposal');
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

  public function _list_custom_model_certificates() {
    $user = \Drupal::currentUser();
    $query_id = \Drupal::database()->query("SELECT id FROM custom_model_proposal WHERE approval_status=3 AND uid= :uid", [
      ':uid' => $user->uid
      ]);
    $exist_id = $query_id->fetchObject();
    if ($exist_id) {
      if ($exist_id->id) {
        if ($exist_id->id < 3) {
          \Drupal::messenger()->addMessage('<strong>You need to propose a <a href="https://dwsim.fossee.in/custom-model/proposal">Custom Model Project</a></strong>. If you have already proposed then you Custom Model is under reviewing process', 'status');
          return '';
        } //$exist_id->id < 3
        else {
          $search_rows = [];
          global $output;
          $output = '';
          $query3 = \Drupal::database()->query("SELECT id,project_title,contributor_name FROM custom_model_proposal WHERE approval_status=3 AND uid= :uid", [
            ':uid' => $user->uid
            ]);
          while ($search_data3 = $query3->fetchObject()) {
            if ($search_data3->id) {
              $search_rows[] = [
                $search_data3->project_title,
                $search_data3->contributor_name,
                l('Download Certificate', 'custom-model/certificates/generate-pdf/' . $search_data3->id),
              ];
            } //$search_data3->id
          } //$search_data3 = $query3->fetchObject()
          if ($search_rows) {
            $search_header = [
              'Project Title',
              'Contributor Name',
              'Download Certificates',
            ];
            $output = theme('table', [
              'header' => $search_header,
              'rows' => $search_rows,
            ]);
            return $output;
          } //$search_rows
          else {
            echo ("Error");
            return '';
          }
        }
      }
    } //$exist_id->id
    else {
      \Drupal::messenger()->addMessage('<strong>You need to propose a <a href="https://dwsim.fossee.in/custom-model/proposal">Custom Model Project</a></strong>. If you have already proposed then you Custom Model is under reviewing process', 'status');
      $page_content = "<span style='color:red;'> No certificate available </span>";
      return $page_content;
    }
  }

  public function verify_certificates($qr_code = 0) {
    $qr_code = arg(3);
    $route_match = \Drupal::routeMatch();
  $qr_code = $route_match->getParameter('qr_code');
 
    $page_content = "";
    if ($qr_code) {
      $page_content = verify_qrcode_fromdb($qr_code);
    } //$qr_code
    else {
      $verify_certificates_form = \Drupal::formBuilder()->getForm("verify_certificates_form");
      $page_content = \Drupal::service("renderer")->render($verify_certificates_form);
    }
    return $page_content;
  }

}
