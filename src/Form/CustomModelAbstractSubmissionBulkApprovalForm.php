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
  $current_user = \Drupal::currentUser();
  $database = \Drupal::database();
  $messenger = \Drupal::messenger();
  $mailManager = \Drupal::service('plugin.manager.mail');
  $language = \Drupal::languageManager()->getDefaultLanguage();

  $proposal_id = $form_state->getValue('custom_model_proposals');
  $action = $form_state->getValue('custom_model_actions');
  $message_text = trim($form_state->getValue('message'));

  if (!$proposal_id || !$current_user->hasPermission('custom model bulk manage submission')) {
    $messenger->addError(t('Access denied or invalid proposal.'));
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

  $user = User::load($proposal->uid);

  if ($action == 1) {
    // Approve
    $abstracts = $database->select('custom_model_submitted_abstracts', 'a')
      ->fields('a')
      ->condition('proposal_id', $proposal_id)
      ->execute();

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
    $params['subject'] = t('[!site_name][Custom Model] Your uploaded Custom Model has been approved', ['!site_name' => \Drupal::config('system.site')->get('name')]);
    $params['body'][] = t("
Dear @name,

Your uploaded abstract for the Custom Model has been approved:

Title of Custom Model: @title

Best Wishes,
@site Team,
FOSSEE, IIT Bombay", [
      '@name' => $proposal->contributor_name,
      '@title' => $proposal->project_title,
      '@site' => \Drupal::config('system.site')->get('name'),
    ]);

  } elseif ($action == 2) {
    // Resubmit (Pending)
    if (strlen($message_text) < 30) {
      $form_state->setErrorByName('message', t('Please mention the reason for resubmission. Minimum 30 characters required.'));
      return;
    }

    $abstracts = $database->select('custom_model_submitted_abstracts', 'a')
      ->fields('a')
      ->condition('proposal_id', $proposal_id)
      ->execute();

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

    $params['subject'] = t('[!site_name][Custom Model] Your uploaded Custom Model has been marked as pending', ['!site_name' => \Drupal::config('system.site')->get('name')]);
    $params['body'][] = t("
Dear @name,

Kindly resubmit the project files for the project: @title.

Reason for dis-approval: @reason

Best Wishes,
@site Team,
FOSSEE, IIT Bombay", [
      '@name' => $proposal->contributor_name,
      '@title' => $proposal->project_title,
      '@reason' => $message_text,
      '@site' => \Drupal::config('system.site')->get('name'),
    ]);

  } elseif ($action == 3) {
    // Disapprove/Delete
    if (strlen($message_text) < 30) {
      $form_state->setErrorByName('message', t('Please mention the reason for disapproval. Minimum 30 characters required.'));
      return;
    }

    if (!$current_user->hasPermission('custom model bulk delete abstract')) {
      $messenger->addError(t('You do not have permission to delete this Custom Model project.'));
      return;
    }

    if (custom_model_abstract_delete_project($proposal_id)) {
      $messenger->addStatus(t('Disapproved and Deleted Entire Custom Model project.'));

      $params['subject'] = t('[!site_name][Custom Model] Your uploaded Custom Model has been marked as dis-approved', ['!site_name' => \Drupal::config('system.site')->get('name')]);
      $params['body'][] = t("
Dear @name,

Your uploaded Custom Model files for the Custom Model Title: @title have been marked as dis-approved.

Reason for dis-approval: @reason

Best Wishes,
@site Team,
FOSSEE, IIT Bombay", [
        '@name' => $proposal->contributor_name,
        '@title' => $proposal->project_title,
        '@reason' => $message_text,
        '@site' => \Drupal::config('system.site')->get('name'),
      ]);
    } else {
      $messenger->addError(t('Error deleting the Custom Model project.'));
    }
  }

  // Send email
  if (!empty($params)) {
    $mailManager->mail('custom_model', 'standard', $user->getEmail(), $language->getId(), [
      'subject' => $params['subject'],
      'body' => $params['body'],
    ], NULL, TRUE);
  }
}

  }


