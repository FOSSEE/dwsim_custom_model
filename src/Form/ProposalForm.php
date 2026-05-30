<?php

namespace Drupal\dwsim_custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dwsim_custom_model\Services\DwsimMailService;

/**
 * Proposal submission form for contributors.
 * D7 equivalent: custom_model_proposal_form() in proposal.inc
 *
 * Note: File upload uses unmanaged files (same as D7).
 * Migration to Drupal managed files is a separate task.
 */
class ProposalForm extends FormBase {

  public function getFormId() {
    return 'dwsim_custom_model_proposal_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current = \Drupal::currentUser();

    // Redirect anonymous users to login.
    if ($current->isAnonymous()) {
      $this->messenger()->addError($this->t('Login is required to access the proposal form.'));
      return $this->redirect('user.login');
    }

    // Block if an active proposal already exists.
    $existing = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('uid', $current->id())
      ->condition('approval_status', [0, 1], 'IN')
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if ($existing) {
      $this->messenger()->addStatus($this->t('We have already received your proposal.'));
      return $this->redirect('<front>');
    }

    $form['#attributes'] = ['enctype' => 'multipart/form-data'];

    $form['name_title'] = [
      '#type'     => 'select',
      '#title'    => $this->t('Title'),
      '#options'  => ['Dr' => 'Dr', 'Prof' => 'Prof', 'Mr' => 'Mr', 'Mrs' => 'Mrs', 'Ms' => 'Ms'],
      '#required' => TRUE,
    ];

    $form['contributor_name'] = [
      '#type'       => 'textfield',
      '#title'      => $this->t('Name of the contributor'),
      '#maxlength'  => 250,
      '#required'   => TRUE,
      '#attributes' => ['placeholder' => $this->t('Enter your full name')],
    ];

    $form['contributor_contact_no'] = [
      '#type'       => 'textfield',
      '#title'      => $this->t('Contact No.'),
      '#maxlength'  => 250,
      '#required'   => TRUE,
      '#attributes' => ['placeholder' => $this->t('Enter your contact number')],
    ];

    // Email is pre-filled from account and disabled.
    $user_email = \Drupal::entityTypeManager()->getStorage('user')->load($current->id())?->getEmail() ?? '';
    $form['contributor_email_id'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Email'),
      '#value'    => $user_email,
      '#disabled' => TRUE,
    ];

    $form['university'] = [
      '#type'       => 'textfield',
      '#title'      => $this->t('University / Institute / Organisation'),
      '#maxlength'  => 200,
      '#required'   => TRUE,
      '#attributes' => ['placeholder' => $this->t('Full name of your institute')],
    ];

    $form['country'] = [
      '#type'     => 'select',
      '#title'    => $this->t('Country'),
      '#options'  => ['India' => 'India', 'Others' => 'Others'],
      '#required' => TRUE,
    ];

    $form['all_state'] = [
      '#type'    => 'textfield',
      '#title'   => $this->t('State'),
      '#size'    => 100,
    ];

    $form['city'] = [
      '#type'    => 'textfield',
      '#title'   => $this->t('City'),
      '#size'    => 100,
    ];

    $form['pincode'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Pincode'),
      '#size'     => 6,
      '#required' => TRUE,
    ];

    $form['version'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('DWSIM Version'),
      '#required' => TRUE,
    ];

    $form['project_title'] = [
      '#type'     => 'textarea',
      '#title'    => $this->t('Title of the Custom Model'),
      '#required' => TRUE,
    ];

    $form['reference'] = [
      '#type'       => 'textfield',
      '#title'      => $this->t('Reference'),
      '#required'   => TRUE,
      '#attributes' => ['placeholder' => 'Enter Reference'],
    ];

    $form['script_used'] = [
      '#type'     => 'select',
      '#title'    => $this->t('Script used to create the Custom Model'),
      '#options'  => ['Scilab' => 'Scilab', 'IronPython' => 'IronPython'],
      '#required' => TRUE,
    ];

    // Resource file upload (preserving D7 custom file handling).
    $form['samplefile'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Upload Abstract (PDF)'),
    ];
    $form['samplefile']['samplefile_upload'] = [
      '#type'  => 'file',
      '#title' => $this->t('Abstract file'),
    ];

    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit Proposal')];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current = \Drupal::currentUser();
    $db      = \Drupal::database();

    // Insert new proposal record.
    $proposal_id = $db->insert('custom_model_proposal')
      ->fields([
        'uid'               => $current->id(),
        'name_title'        => $form_state->getValue('name_title'),
        'contributor_name'  => $form_state->getValue('contributor_name'),
        'contact_no'        => $form_state->getValue('contributor_contact_no'),
        'university'        => $form_state->getValue('university'),
        'country'           => $form_state->getValue('country'),
        'state'             => $form_state->getValue('all_state'),
        'city'              => $form_state->getValue('city'),
        'pincode'           => $form_state->getValue('pincode'),
        'version'           => $form_state->getValue('version'),
        'project_title'     => $form_state->getValue('project_title'),
        'reference'         => $form_state->getValue('reference'),
        'script_used'       => $form_state->getValue('script_used'),
        'approval_status'   => 0,
        'creation_date'     => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    // Send confirmation email using the reusable mail service.
    $user_email = \Drupal::entityTypeManager()->getStorage('user')->load($current->id())?->getEmail() ?? '';
    $mailer = new DwsimMailService();
    $mailer->send('custom_model_proposal_received', $user_email, [
      'result1' => ['id' => $proposal_id],
      'user_id' => $current->id(),
    ]);

    $this->messenger()->addStatus($this->t('We have received your DWSIM Custom Model proposal. We will get back to you soon.'));
    $form_state->setRedirect('<front>');
  }

}
