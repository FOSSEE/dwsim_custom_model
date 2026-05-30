<?php

namespace Drupal\dwsim_custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dwsim_custom_model\Services\DwsimMailService;

/**
 * Edit an existing Custom Model proposal (admin side).
 * D7 equivalent: custom_model_proposal_edit_form() in manage_proposal.inc
 */
class ProposalEditForm extends FormBase {

  public function getFormId() {
    return 'dwsim_custom_model_proposal_edit_form';
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

    $form['name_title'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Title'),
      '#options'       => ['Dr' => 'Dr', 'Prof' => 'Prof', 'Mr' => 'Mr', 'Mrs' => 'Mrs', 'Ms' => 'Ms'],
      '#required'      => TRUE,
      '#default_value' => $proposal->name_title,
    ];

    $form['contributor_name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Name of the Proposer'),
      '#maxlength'     => 50,
      '#required'      => TRUE,
      '#default_value' => $proposal->contributor_name,
    ];

    $form['contributor_contact_no'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Contact No.'),
      '#default_value' => $proposal->contact_no,
    ];

    $form['university'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('University / Institute / Organisation'),
      '#maxlength'     => 200,
      '#required'      => TRUE,
      '#default_value' => $proposal->university,
    ];

    $form['project_title'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Title of the Custom Model'),
      '#required'      => TRUE,
      '#default_value' => $proposal->project_title,
    ];

    $form['delete_proposal'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Delete this proposal'),
      '#description' => $this->t('Check to permanently delete this proposal and notify the user.'),
    ];

    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Save')];
    $form['cancel'] = [
      '#type'  => 'link',
      '#title' => $this->t('Cancel'),
      '#url'   => \Drupal\Core\Url::fromRoute('dwsim_custom_model.manage_proposal'),
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

    // Handle delete.
    if ($form_state->getValue('delete_proposal') == 1) {
      $recipient = \Drupal::entityTypeManager()->getStorage('user')->load($proposal->uid)?->getEmail() ?? '';
      $mailer    = new DwsimMailService();
      $mailer->send('custom_model_proposal_deleted', $recipient, [
        'proposal_id' => $id,
        'user_id'     => $proposal->uid,
      ]);

      $db->delete('custom_model_proposal')->condition('id', $id)->execute();
      $this->messenger()->addStatus($this->t('Proposal #@id deleted.', ['@id' => $id]));
      $form_state->setRedirect('dwsim_custom_model.manage_proposal');
      return;
    }

    // Update proposal fields.
    $db->update('custom_model_proposal')
      ->fields([
        'name_title'       => $form_state->getValue('name_title'),
        'contributor_name' => $form_state->getValue('contributor_name'),
        'contact_no'       => $form_state->getValue('contributor_contact_no'),
        'university'       => $form_state->getValue('university'),
        'project_title'    => $form_state->getValue('project_title'),
      ])
      ->condition('id', $id)
      ->execute();

    $this->messenger()->addStatus($this->t('Proposal #@id updated.', ['@id' => $id]));
    $form_state->setRedirect('dwsim_custom_model.manage_proposal');
  }

}
