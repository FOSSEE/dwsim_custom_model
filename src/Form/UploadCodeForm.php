<?php

namespace Drupal\dwsim_custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dwsim_custom_model\Services\DwsimMailService;

/**
 * Code and abstract upload form for approved contributors.
 * D7 equivalent: custom_model_upload_abstract_code_form() in upload_code.inc
 */
class UploadCodeForm extends FormBase {

  public function getFormId() {
    return 'dwsim_custom_model_upload_code_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current = \Drupal::currentUser();

    // Load the contributor's approved proposal.
    $proposal = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('uid', $current->id())
      ->condition('approval_status', 1)
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal) {
      $this->messenger()->addError($this->t('No approved proposal found for your account.'));
      return $form;
    }

    $form['proposal_id'] = ['#type' => 'hidden', '#value' => $proposal->id];

    $form['#attributes'] = ['enctype' => 'multipart/form-data'];

    $form['project_title'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Custom Model Title'),
      '#markup' => $proposal->project_title,
    ];

    // Abstract PDF upload.
    $form['abstract'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Upload Abstract (PDF)'),
    ];
    $form['abstract']['abstract_file'] = [
      '#type'  => 'file',
      '#title' => $this->t('Abstract PDF'),
    ];

    // Project files upload.
    $form['project_files'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Upload Project Files'),
    ];
    $form['project_files']['project_file'] = [
      '#type'  => 'file',
      '#title' => $this->t('Project file (zip/simulation file)'),
    ];

    // Script file upload.
    $form['script_files'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Upload Script File'),
    ];
    $form['script_files']['script_file'] = [
      '#type'  => 'file',
      '#title' => $this->t('Script file'),
    ];

    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Upload')];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current     = \Drupal::currentUser();
    $proposal_id = $form_state->getValue('proposal_id');
    $db          = \Drupal::database();

    // Insert submitted abstract record.
    $submitted_abstract_id = $db->insert('custom_model_submitted_abstracts')
      ->fields([
        'proposal_id'          => $proposal_id,
        'uid'                  => $current->id(),
        'abstract_upload_date' => \Drupal::time()->getRequestTime(),
        'abstract_approval_status' => 0,
        'is_submitted'         => 1,
      ])
      ->execute();

    // File move logic to be ported separately.
    // In D11 use \Drupal\Core\File\FileSystemInterface::move()
    // with 'public://custom_model/' as the base URI.

    // Send upload confirmation email.
    $user_email = \Drupal::entityTypeManager()->getStorage('user')->load($current->id())?->getEmail() ?? '';
    $mailer = new DwsimMailService();
    $mailer->send('abstract_uploaded', $user_email, [
      'proposal_id'           => $proposal_id,
      'submitted_abstract_id' => $submitted_abstract_id,
      'user_id'               => $current->id(),
    ]);

    $this->messenger()->addStatus($this->t('Files uploaded successfully.'));
    $form_state->setRedirect('dwsim_custom_model.abstract_code');
  }

}
