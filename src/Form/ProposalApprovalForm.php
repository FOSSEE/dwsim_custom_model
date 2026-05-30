<?php

namespace Drupal\dwsim_custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dwsim_custom_model\Services\DwsimMailService;

/**
 * Approve or disapprove a Custom Model proposal.
 * D7 equivalent: custom_model_proposal_approval_form() in manage_proposal.inc
 */
class ProposalApprovalForm extends FormBase {

  public function getFormId() {
    return 'dwsim_custom_model_proposal_approval_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    // Load the proposal record for the given ID.
    $proposal = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$proposal) {
      $this->messenger()->addError($this->t('Invalid proposal selected.'));
      return $form;
    }

    $form['proposal_id'] = ['#type' => 'hidden', '#value' => $id];

    // Show proposal info as read-only fields.
    $user_mail = \Drupal::entityTypeManager()->getStorage('user')->load($proposal->uid)?->getEmail() ?? '';
    $form['contributor_name']     = ['#type' => 'item', '#title' => $this->t('Contributor'), '#markup' => $proposal->name_title . ' ' . $proposal->contributor_name];
    $form['student_email_id']     = ['#type' => 'item', '#title' => $this->t('Email'),       '#markup' => $user_mail];
    $form['university']           = ['#type' => 'item', '#title' => $this->t('University'),  '#markup' => $proposal->university];
    $form['project_title']        = ['#type' => 'item', '#title' => $this->t('Title'),       '#markup' => $proposal->project_title];

    $form['approval'] = [
      '#type'     => 'radios',
      '#title'    => $this->t('Select an action'),
      '#options'  => ['1' => $this->t('Approve'), '2' => $this->t('Disapprove')],
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type'        => 'textarea',
      '#title'       => $this->t('Reason for disapproval'),
      '#states'      => [
        'visible' => [':input[name="approval"]' => ['value' => '2']],
      ],
      '#attributes'  => ['placeholder' => $this->t('Minimum 30 characters')],
    ];

    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['cancel'] = [
      '#type'   => 'link',
      '#title'  => $this->t('Cancel'),
      '#url'    => \Drupal\Core\Url::fromRoute('dwsim_custom_model.manage_proposal'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('approval') == 2 && empty(trim($form_state->getValue('message')))) {
      $form_state->setErrorByName('message', $this->t('Reason for disapproval cannot be empty.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id       = $form_state->getValue('proposal_id');
    $action   = $form_state->getValue('approval');
    $db       = \Drupal::database();
    $current  = \Drupal::currentUser();
    $mailer   = new DwsimMailService();

    $proposal = $db->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    $recipient = \Drupal::entityTypeManager()->getStorage('user')->load($proposal->uid)?->getEmail() ?? '';

    if ($action == 1) {
      // Mark as approved and notify the contributor.
      $db->update('custom_model_proposal')
        ->fields(['approver_uid' => $current->id(), 'approval_date' => \Drupal::time()->getRequestTime(), 'approval_status' => 1])
        ->condition('id', $id)
        ->execute();

      $mailer->send('custom_model_proposal_approved', $recipient, [
        'proposal_id' => $id,
        'user_id'     => $proposal->uid,
      ], $current->getEmail());

      $this->messenger()->addStatus($this->t('Proposal #@id approved. User notified.', ['@id' => $id]));

    } elseif ($action == 2) {
      // Mark as disapproved, save reason, and notify the contributor.
      $db->update('custom_model_proposal')
        ->fields([
          'approver_uid'        => $current->id(),
          'approval_date'       => \Drupal::time()->getRequestTime(),
          'approval_status'     => 2,
          'dissapproval_reason' => $form_state->getValue('message'),
        ])
        ->condition('id', $id)
        ->execute();

      $mailer->send('custom_model_proposal_disapproved', $recipient, [
        'proposal_id' => $id,
        'user_id'     => $proposal->uid,
      ], $current->getEmail());

      $this->messenger()->addError($this->t('Proposal #@id disapproved. User notified.', ['@id' => $id]));
    }

    $form_state->setRedirect('dwsim_custom_model.manage_proposal');
  }

}
