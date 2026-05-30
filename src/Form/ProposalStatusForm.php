<?php

namespace Drupal\dwsim_custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dwsim_custom_model\Services\DwsimMailService;

/**
 * View proposal status and mark it as Completed.
 * D7 equivalent: custom_model_proposal_status_form() in manage_proposal.inc
 */
class ProposalStatusForm extends FormBase {

  public function getFormId() {
    return 'dwsim_custom_model_proposal_status_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
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

    $user_mail = \Drupal::entityTypeManager()->getStorage('user')->load($proposal->uid)?->getEmail() ?? '';

    $status_map = [0 => 'Pending', 1 => 'Approved', 2 => 'Dis-approved', 3 => 'Completed'];

    $form['contributor_name']       = ['#type' => 'item', '#title' => $this->t('Contributor'),    '#markup' => $proposal->name_title . ' ' . $proposal->contributor_name];
    $form['student_email_id']       = ['#type' => 'item', '#title' => $this->t('Email'),          '#markup' => $user_mail];
    $form['university']             = ['#type' => 'item', '#title' => $this->t('University'),     '#markup' => $proposal->university];
    $form['country']                = ['#type' => 'item', '#title' => $this->t('Country'),        '#markup' => $proposal->country];
    $form['project_title']          = ['#type' => 'item', '#title' => $this->t('Title'),          '#markup' => $proposal->project_title];
    $form['proposal_status_display']= ['#type' => 'item', '#title' => $this->t('Current Status'), '#markup' => $status_map[$proposal->approval_status] ?? 'Unknown'];

    // Show "Mark as Completed" checkbox only when proposal is approved.
    if ($proposal->approval_status == 1) {
      $form['completed'] = [
        '#type'        => 'checkbox',
        '#title'       => $this->t('Mark as Completed'),
        '#description' => $this->t('Check if the user has provided all required files.'),
      ];
    }

    // Show disapproval reason if dis-approved.
    if ($proposal->approval_status == 2) {
      $form['disapproval_reason'] = [
        '#type'   => 'item',
        '#title'  => $this->t('Reason for disapproval'),
        '#markup' => $proposal->dissapproval_reason,
      ];
    }

    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['cancel'] = [
      '#type'  => 'link',
      '#title' => $this->t('Cancel'),
      '#url'   => \Drupal\Core\Url::fromRoute('dwsim_custom_model.manage_proposal.all'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id      = $form_state->getValue('proposal_id');
    $db      = \Drupal::database();
    $current = \Drupal::currentUser();

    $proposal = $db->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    // Mark as completed.
    if ($form_state->getValue('completed') == 1) {
      $db->query(
        "UPDATE {custom_model_proposal}
         SET approval_status = :status, actual_completion_date = :date
         WHERE id = :id",
        [':status' => 3, ':date' => \Drupal::time()->getRequestTime(), ':id' => $id]
      );

      // TODO: D7 called CreateReadmeFileCustomModel($proposal_id) here.
      // Port that function to a D11 service before final cutover.

      $recipient = \Drupal::entityTypeManager()->getStorage('user')->load($proposal->uid)?->getEmail() ?? '';
      $mailer    = new DwsimMailService();
      $mailer->send('custom_model_proposal_completed', $recipient, [
        'proposal_id' => $id,
        'user_id'     => $proposal->uid,
      ], $current->getEmail());

      $this->messenger()->addStatus($this->t('Proposal marked as completed. User notified.'));
    }

    $form_state->setRedirect('dwsim_custom_model.manage_proposal');
  }

}
