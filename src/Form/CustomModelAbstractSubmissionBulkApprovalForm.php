<?php

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a form for bulk abstract approval in the Custom Model module.
 */
class CustomModelAbstractSubmissionBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_abstract_submission_bulk_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = $this->_bulk_list_of_custom_model_proposals();
    $selected = $form_state->getValue('custom_model_proposals') ?? key($options);

    // $form['custom_model_proposals'] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Title of the Custom Model'),
    //   '#options' => $options,
    //   '#default_value' => $selected,
    //   '#ajax' => [
    //     'callback' => [$this, 'ajaxCustomModelDetailsCallback'],
    //     'wrapper' => 'ajax-selected-custom-model-wrapper',
    //   ],
    //   '#suffix' => '<div id="ajax-selected-custom-model-wrapper"><div id="ajax_selected_custom_model"></div><div id="ajax_selected_custom_model_pdf"></div></div>',
    // ];

    $form['custom_model_proposals'] = [
      '#type' => 'select',
      '#title' => t('Title of the custom model'),
      '#options' => $this->_bulk_list_of_custom_model_proposals(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajax_bulk_custom_model_abstract_details_callback',
        'wrapper' => 'ajax_selected_custom_model'
        ],
    ];
    $form['update_custom_model'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_selected_custom_model'],
      '#states' => [
        'invisible' => [
          ':input[name="custom_model_proposals"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    $form['update_custom_model']['cm_details'] = [
      '#type' => 'markup',
      '#markup' => $this->_custom_model_details($form_state->getValue('custom_model_proposals')),
      '#states' => [
        'invisible' => [
          ':input[name="custom_model_proposals"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    $form['custom_model_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for Custom Model project'),
      '#options' => $this->_bulk_list_custom_model_actions(),
      '#default_value' => 0,
      '#states' => [
        'invisible' => [
          ':input[name="custom_model_proposals"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    
    
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Please specify the reason for marking resubmit/disapproval'),
      '#prefix' => '<div id="message_submit">',
      '#states' => [
        'visible' => [
          [
            ':input[name="custom_model_actions"]' => ['value' => 2],
          ],
          'or',
          [
            ':input[name="custom_model_actions"]' => ['value' => 3],
          ],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }
  public function ajaxProposalCallback(array &$form, FormStateInterface $form_state) {
    return $form['proposal_wrapper'];
  }
  
  function ajax_bulk_custom_model_abstract_details_callback(array &$form, FormStateInterface $form_state) {
    return $form['update_custom_model'];
  }

  
/**
 * Returns the custom model proposal details HTML.
 */
function _custom_model_details($custom_model_proposal_id) {
  $database = \Drupal::database();
  $return_html = '';

  // Fetch proposal data.
  $abstracts_pro = $database->select('custom_model_proposal', 'cmp')
    ->fields('cmp')
    ->condition('id', $custom_model_proposal_id)
    ->execute()
    ->fetchObject();

  // Abstract file (filetype A).
  $abstracts_pdf = $database->select('custom_model_submitted_abstracts_file', 'pdf')
    ->fields('pdf')
    ->condition('proposal_id', $custom_model_proposal_id)
    ->condition('filetype', 'A')
    ->execute()
    ->fetchObject();

  $abstract_filename = (!empty($abstracts_pdf) && !empty($abstracts_pdf->filename) && $abstracts_pdf->filename !== "NULL") 
    ? $abstracts_pdf->filename 
    : 'File not uploaded';

  // DWSIM Simulation file (filetype S).
  $abstracts_process = $database->select('custom_model_submitted_abstracts_file', 'proc')
    ->fields('proc')
    ->condition('proposal_id', $custom_model_proposal_id)
    ->condition('filetype', 'S')
    ->execute()
    ->fetchObject();

  $process_filename = (!empty($abstracts_process) && !empty($abstracts_process->filename) && $abstracts_process->filename !== "NULL")
    ? $abstracts_process->filename 
    : 'File not uploaded';

  // Script file (filetype P).
  $abstracts_script = $database->select('custom_model_submitted_abstracts_file', 'script')
    ->fields('script')
    ->condition('proposal_id', $custom_model_proposal_id)
    ->condition('filetype', 'P')
    ->execute()
    ->fetchObject();

  $script_filename = (!empty($abstracts_script) && !empty($abstracts_script->filename) && $abstracts_script->filename !== "NULL")
    ? $abstracts_script->filename 
    : 'File not uploaded';

  // Download link for full project.
  $download_url = Url::fromUserInput('/custom-model/full-download/project/' . $custom_model_proposal_id);
  $download_link = Link::fromTextAndUrl('Download Custom Model', $download_url)->toString();

  // Compose output.
  $return_html .= '<strong>Proposer Name:</strong><br />' . $abstracts_pro->name_title . ' ' . $abstracts_pro->contributor_name . '<br /><br />';
  $return_html .= '<strong>Title of the Custom Model:</strong><br />' . $abstracts_pro->project_title . '<br /><br />';
  $return_html .= '<strong>Uploaded an abstract (brief outline) of the project:</strong><br />' . $abstract_filename . '<br /><br />';
  $return_html .= '<strong>Uploaded Custom Model as DWSIM Simulation File:</strong><br />' . $process_filename . '<br /><br />';
  $return_html .= '<strong>Uploaded script file:</strong><br />' . $script_filename . '<br /><br />';
  $return_html .= $download_link;

  return $return_html;
}

  /**
   * AJAX callback for updating details.
   */
  public function ajaxCustomModelDetailsCallback(array &$form, FormStateInterface $form_state) {
    return $form['ajax-selected-custom-model-wrapper'];
    // $response = new AjaxResponse();
    // $proposal_id = $form_state->getValue('custom_model_proposals');

    // if ($proposal_id != 0) {
    //   $html = _custom_model_details($proposal_id);
    //   $response->addCommand(new HtmlCommand('#ajax_selected_custom_model', $html));

    //   // Re-render action select box with updated options if needed.
    //   $form['custom_model_actions']['#options'] = this->_bulk_list_custom_model_actions();
    //   $actions_rendered = \Drupal::service('renderer')->render($form['custom_model_actions']);
    //   $response->addCommand(new ReplaceCommand('#ajax_selected_custom_model_action', $actions_rendered));
    // }
    // else {
    //   $response->addCommand(new HtmlCommand('#ajax_selected_custom_model', ''));
    // }

    // return $response;
  }


  function _bulk_list_custom_model_actions() {
    return [
      0 => 'Please select...',
       1 => 'Approve Entire Custom Model',
      2 => 'Resubmit Project files',
      3 => 'Dis-Approve Entire Custom Model (This will delete Custom Model)',
    ];
  }
  function custom_model_abstract_delete_project($proposal_id) {
  $status = TRUE;
  $root_path = \Drupal::service("custom_model_global")->custom_model_path();
  $database = \Drupal::database();

  $query = $database->select('custom_model_proposal', 'cmp');
  $query->fields('cmp');
  $query->condition('id', $proposal_id);
  $proposal_q = $query->execute();
  $proposal_data = $proposal_q->fetchObject();

  if (!$proposal_data) {
    \Drupal::messenger()->addMessage('Invalid Custom Model Project.', 'error');
    return FALSE;
  }

  $query = $database->select('custom_model_submitted_abstracts_file', 'cmsaf');
  $query->fields('cmsaf');
  $query->condition('proposal_id', $proposal_id);
  $abstract_q = $query->execute();

  $dir_project_files = $root_path . $proposal_data->directory_name . '/project_files';

  while ($abstract_data = $abstract_q->fetchObject()) {
    if (is_dir($dir_project_files)) {
      unlink($root_path . $proposal_data->directory_name . '/project_files/' . $abstract_data->filepath);
    }
    else {
      \Drupal::messenger()->addMessage('Invalid Custom Model project abstract.', 'error');
    }

    $database->delete('custom_model_submitted_abstracts_file')
      ->condition('proposal_id', $proposal_id)
      ->execute();
  }

  $res = \Drupal::service("custom_model_global")->cm_rrmdir($root_path . $proposal_data->directory_name . '/project_files');

  $dir_path_udc = $root_path . $proposal_data->directory_name;
  if (is_dir($dir_path_udc)) {
    unlink($root_path . $proposal_data->samplefilepath);
    $res = \Drupal::service("custom_model_global")->cm_rrmdir($dir_path_udc);
  }

  $database->delete('custom_model_submitted_abstracts')
    ->condition('proposal_id', $proposal_id)
    ->execute();

  $database->delete('custom_model_proposal')
    ->condition('id', $proposal_data->id)
    ->execute();

  return $status;
}
  
  
function _bulk_list_of_custom_model_proposals() {
    $project_titles = [
      '0' => 'Please select...',
    ];
  
    $database = \Drupal::database();
  
    $query = $database->select('custom_model_proposal', 'cmp')
      ->fields('cmp')
      ->condition('is_submitted', 1)
      ->condition('approval_status', 1)
      ->orderBy('project_title', 'ASC');
  
    $results = $query->execute()->fetchAll();
  
    foreach ($results as $record) {
      $project_titles[$record->id] = $record->project_title . ' (Proposed by ' . $record->contributor_name . ')';
    }
  
    return $project_titles;
  }
  /**
   * {@inheritdoc}
   */
    
public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
      $user = \Drupal::currentUser();

  $database = \Drupal::database();
  $messenger = \Drupal::messenger();
  $mailManager = \Drupal::service('plugin.manager.mail');
  $language = \Drupal::languageManager()->getDefaultLanguage();

  $proposal_id = $form_state->getValue('custom_model_proposals');
  $action = $form_state->getValue('custom_model_actions');
  $message_text = trim($form_state->getValue('message'));

if (!$proposal_id || !$user->hasPermission('custom model bulk manage submission'))
  {    $messenger->addError(t('Access denied or invalid proposal.'));
    return;
  }

  // Load proposal and user.
  $proposal = $database->select('custom_model_proposal', 'cmp')
    ->fields('cmp')
    ->condition('id', $proposal_id)
    ->execute()
    ->fetchObject();

  if (!$proposal) {
    $messenger->addError(t('Proposal not found.'));
    return;
  }

  // var_dump($proposal_id);die;
  $user = User::load($proposal->uid);

  if ($action == 1) {
    // Approve
    $abstracts = $database->select('custom_model_submitted_abstracts', 'a')
      ->fields('a')
      ->condition('proposal_id', $proposal_id)
      ->execute();
$current_user = \Drupal::currentUser();
    foreach ($abstracts as $abstract) {
      $database->update('custom_model_submitted_abstracts')
        ->fields([
          'abstract_approval_status' => 1,
          'is_submitted' => 1,
          'approver_uid' => $current_user->id(),
        ])
        ->condition('id', $abstract->id)
        ->execute();

      $database->update('custom_model_submitted_abstracts_file')
        ->fields([
          'file_approval_status' => 1,
          'approvar_uid' => $current_user->id(),
        ])
        ->condition('submitted_abstract_id', $abstract->id)
        ->execute();
    }

    $messenger->addStatus(t('Approved Custom Model project.'));

    // Email


// Load user  
// Mail for abstarct-approval
$user = User::load($proposal->uid);
$email_to = $user ? $user->getEmail() : '';
// $email_to = $user->getEmail();

// Load config
$config = \Drupal::config('custom_model.settings');

$from = $config->get('custom_model_from_email');
$bcc_config = $config->get('custom_model_emails');
$cc = $config->get('custom_model_cc_emails');

// Build BCC
$bcc = $email_to;
if (!empty($bcc_config)) {
  $bcc .= ', ' . $bcc_config;
}

// Mail params
$params['abstract_approval'] = [
  'proposal_id' => $proposal_id,
  'user_id' => $proposal_data->uid,
];

// Send mail
$mailManager = \Drupal::service('plugin.manager.mail');

$langcode = $user->getPreferredLangcode();

$result = $mailManager->mail(
  'custom_model',
  'abstract_approval',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

// Handle failure
if (!$result['result']) {
  \Drupal::messenger()->addMessage(t(' Sending email message.'));
}
  else {
    \Drupal::messenger()->addStatus('Email sent successfully.');
  }

  }
   elseif ($action == 2) {
    // Resubmit (Pending)
    if (strlen($message_text) < 30) {
      $form_state->setErrorByName('message', t('Please mention the reason for resubmission. Minimum 30 characters required.'));
      return;
    }

    $abstracts = $database->select('custom_model_submitted_abstracts', 'a')
      ->fields('a')
      ->condition('proposal_id', $proposal_id)
      ->execute();

$current_user = \Drupal::currentUser();

    foreach ($abstracts as $abstract) {
      $database->update('custom_model_submitted_abstracts')
        ->fields([
          'abstract_approval_status' => 0,
          'is_submitted' => 0,
          'approver_uid' => $current_user->id(),
        ])
        ->condition('id', $abstract->id)
        ->execute();

      $database->update('custom_model_proposal')
        ->fields([
          'is_submitted' => 0,
          'approver_uid' => $current_user->id(),
        ])
        ->condition('id', $abstract->proposal_id)
        ->execute();

      $database->update('custom_model_submitted_abstracts_file')
        ->fields([
          'file_approval_status' => 0,
          'approvar_uid' => $current_user->id(),
        ])
        ->condition('submitted_abstract_id', $abstract->id)
        ->execute();
    }

    $messenger->addStatus(t('Resubmit the project files'));



    // Email function for resubmmit abstract

    $user = User::load($proposal->uid);
$email_to = $user ? $user->getEmail() : '';
// $email_to = $user->getEmail();

// Load config
$config = \Drupal::config('custom_model.settings');

$from = $config->get('custom_model_from_email');
$bcc_config = $config->get('custom_model_emails');
$cc = $config->get('custom_model_cc_emails');

// Build BCC
$bcc = $email_to;
if (!empty($bcc_config)) {
  $bcc .= ', ' . $bcc_config;
}

// Mail params
$params['abstract_resubmit'] = [
  'proposal_id' => $proposal_id,
  'user_id' => $proposal_data->uid,
];

// Send mail
$mailManager = \Drupal::service('plugin.manager.mail');

$langcode = $user->getPreferredLangcode();

$result = $mailManager->mail(
  'custom_model',
  'abstract_resubmit',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

// Handle failure
if (!$result['result']) {
  \Drupal::messenger()->addMessage(t(' Sending email message.'));
}
  else {
    \Drupal::messenger()->addStatus('Email sent successfully.');
  }

    } elseif ($action == 3) {
    // Disapprove/Delete
    if (strlen($message_text) < 30) {
      $form_state->setErrorByName('message', t('Please mention the reason for disapproval. Minimum 30 characters required.'));
      return;
    }

if (!$proposal_id || !$user->hasPermission('custom model bulk manage submission'))
{      $messenger->addError(t('You do not have permission to delete this Custom Model project.'));
      return;
    }

    $abstracts = $database->select('custom_model_submitted_abstracts', 'a')
      ->fields('a')
      ->condition('proposal_id', $proposal_id)
      ->execute();
$current_user = \Drupal::currentUser();
    foreach ($abstracts as $abstract) {
      $database->update('custom_model_submitted_abstracts')
        ->fields([
          'abstract_approval_status' => 2,
          'is_submitted' => 1,
          'approver_uid' => $current_user->id(),
        ])
        ->condition('id', $abstract->id)
        ->execute();

      $database->update('custom_model_submitted_abstracts_file')
        ->fields([
          'file_approval_status' => 1,
          'approvar_uid' => $current_user->id(),
        ])
        ->condition('submitted_abstract_id', $abstract->id)
        ->execute();
    }

    $messenger->addStatus(t('Disaaproved Custom Model project.'));



    // Email function for disapproved abstract

    $user = User::load($proposal->uid);
$email_to = $user ? $user->getEmail() : '';
// $email_to = $user->getEmail();

// Load config
$config = \Drupal::config('custom_model.settings');

$from = $config->get('custom_model_from_email');
$bcc_config = $config->get('custom_model_emails');
$cc = $config->get('custom_model_cc_emails');

// Build BCC
$bcc = $email_to;
if (!empty($bcc_config)) {
  $bcc .= ', ' . $bcc_config;
}

// Mail params
$params['abstract_disapproval'] = [
  'proposal_id' => $proposal_id,
  'user_id' => $proposal_data->uid,
];

// Send mail
$mailManager = \Drupal::service('plugin.manager.mail');

$langcode = $user->getPreferredLangcode();

$result = $mailManager->mail(
  'custom_model',
  'abstract_disapproval',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

// Handle failure
if (!$result['result']) {
  \Drupal::messenger()->addMessage(t(' Sending email message.'));
}
else {
  \Drupal::messenger()->addMessage('Error disapproving and deleting solution. Please contact administrator.', 'error');
}
    }
\Drupal::messenger()->addMessage('Updated successfully.', 'status');

$response = new \Symfony\Component\HttpFoundation\RedirectResponse(
  \Drupal\Core\Url::fromUserInput('/lab-migration/code-approval')->toString()
);
return $response;  }
}


