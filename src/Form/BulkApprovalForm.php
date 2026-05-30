<?php

namespace Drupal\dwsim_custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\dwsim_custom_model\Services\DwsimMailService;

/**
 * Bulk approve or send back uploaded Custom Model code submissions.
 * D7 equivalent: custom_model_abstract_submission_bulk_approval_form()
 */
class BulkApprovalForm extends FormBase {

  public function getFormId() {
    return 'dwsim_custom_model_bulk_approval_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // List all approved proposals in the dropdown.
    $options = [0 => $this->t('-- Select a proposal --')];
    $proposals = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('approval_status', 1)
      ->orderBy('id', 'DESC')
      ->execute()
      ->fetchAll();

    foreach ($proposals as $p) {
      $options[$p->id] = '#' . $p->id . ' — ' . $p->project_title . ' (' . $p->contributor_name . ')';
    }

    $form['custom_model_proposals'] = [
      '#type'    => 'select',
      '#title'   => $this->t('Select a proposal'),
      '#options' => $options,
      '#ajax'    => [
        'callback' => '::proposalSelectCallback',
        'wrapper'  => 'ajax-selected-custom-model',
        'event'    => 'change',
      ],
      '#suffix' => '<div id="ajax-selected-custom-model"></div>',
    ];

    // Action dropdown shown after a proposal is picked.
    $form['custom_model_actions'] = [
      '#type'    => 'select',
      '#title'   => $this->t('Action'),
      '#options' => [
        0 => $this->t('-- Select action --'),
        1 => $this->t('Approve uploaded code'),
        2 => $this->t('Send back for revision'),
      ],
      '#prefix' => '<div id="ajax-selected-custom-model-action">',
      '#suffix' => '</div>',
    ];

    $form['message'] = [
      '#type'        => 'textarea',
      '#title'       => $this->t('Reason for revision (if sending back)'),
      '#attributes'  => ['placeholder' => $this->t('Minimum 30 characters required')],
    ];

    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];

    return $form;
  }

  /**
   * AJAX callback — shows proposal details when a proposal is selected from the dropdown.
   * D7 equivalent: ajax_bulk_custom_model_abstract_details_callback()
   */
  public function proposalSelectCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $selected = $form_state->getValue('custom_model_proposals');

    if ($selected != 0) {
      // Load and display proposal details in the AJAX area.
      $details = $this->buildProposalDetails($selected);
      $response->addCommand(new HtmlCommand('#ajax-selected-custom-model', $details));
    } else {
      $response->addCommand(new HtmlCommand('#ajax-selected-custom-model', ''));
    }

    return $response;
  }

  /**
   * Returns a short HTML summary of a proposal for the AJAX area.
   */
  private function buildProposalDetails($proposal_id) {
    $proposal = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal) {
      return $this->t('Proposal not found.');
    }

    return '<strong>' . $this->t('Title') . ':</strong> ' . $proposal->project_title . '<br>'
      . '<strong>' . $this->t('Contributor') . ':</strong> ' . $proposal->contributor_name . '<br>'
      . '<strong>' . $this->t('University') . ':</strong> ' . $proposal->university;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = $form_state->getValue('custom_model_proposals');
    $action      = $form_state->getValue('custom_model_actions');
    $message     = $form_state->getValue('message');
    $current     = \Drupal::currentUser();
    $db          = \Drupal::database();

    if (!$proposal_id) {
      $this->messenger()->addError($this->t('Please select a proposal.'));
      return;
    }

    // Fetch the proposal and the contributor's email.
    $proposal  = $db->select('custom_model_proposal', 'p')->fields('p')->condition('id', $proposal_id)->execute()->fetchObject();
    $user_mail = \Drupal::entityTypeManager()->getStorage('user')->load($proposal->uid)?->getEmail() ?? '';
    $mailer    = new DwsimMailService();

    if ($action == 1) {
      // Approve all abstract and file records for this proposal.
      $abstracts = $db->select('custom_model_submitted_abstracts', 'a')
        ->fields('a')
        ->condition('proposal_id', $proposal_id)
        ->execute()
        ->fetchAll();

      foreach ($abstracts as $abstract) {
        $db->query("UPDATE {custom_model_submitted_abstracts} SET abstract_approval_status = 1, is_submitted = 1, approver_uid = :uid WHERE id = :id",
          [':uid' => $current->id(), ':id' => $abstract->id]);
        $db->query("UPDATE {custom_model_submitted_abstracts_file} SET file_approval_status = 1, approvar_uid = :uid WHERE submitted_abstract_id = :id",
          [':uid' => $current->id(), ':id' => $abstract->id]);
      }

      $subject = $this->t('[!site] Your Custom Model has been approved', ['!site' => \Drupal::config('system.site')->get('name')]);
      $body    = [$this->t("Dear @name,\n\nYour uploaded abstract for the Custom Model has been approved.\n\nTitle: @title\n\nBest Wishes,\nFOSSEE, IIT Bombay",
        ['@name' => $proposal->contributor_name, '@title' => $proposal->project_title])];

      $mailer->sendStandard($user_mail, $subject, $body);
      $this->messenger()->addStatus($this->t('Proposal #@id approved.', ['@id' => $proposal_id]));

    } elseif ($action == 2) {
      // Check that the reason is long enough before sending back.
      if (strlen(trim($message)) <= 30) {
        $this->messenger()->addError($this->t('Please mention the reason for resubmission (minimum 30 characters).'));
        return;
      }

      // Reset abstract and file records to pending (is_submitted = 0).
      $abstracts = $db->select('custom_model_submitted_abstracts', 'a')
        ->fields('a')
        ->condition('proposal_id', $proposal_id)
        ->execute()
        ->fetchAll();

      foreach ($abstracts as $abstract) {
        $db->query("UPDATE {custom_model_submitted_abstracts} SET abstract_approval_status = 0, is_submitted = 0, approver_uid = :uid WHERE id = :id",
          [':uid' => $current->id(), ':id' => $abstract->id]);
        $db->query("UPDATE {custom_model_submitted_abstracts_file} SET file_approval_status = 0, approvar_uid = :uid WHERE submitted_abstract_id = :id",
          [':uid' => $current->id(), ':id' => $abstract->id]);
      }

      $subject = $this->t('[!site] Your Custom Model submission requires revision', ['!site' => \Drupal::config('system.site')->get('name')]);
      $body    = [$this->t("Dear @name,\n\nYour submission requires revision.\n\nReason: @reason\n\nBest Wishes,\nFOSSEE, IIT Bombay",
        ['@name' => $proposal->contributor_name, '@reason' => $message])];

      $mailer->sendStandard($user_mail, $subject, $body);
      $this->messenger()->addStatus($this->t('Proposal #@id sent back for revision.', ['@id' => $proposal_id]));
    }

    $form_state->setRedirect('dwsim_custom_model.abstract_submission_bulk');
  }

}
