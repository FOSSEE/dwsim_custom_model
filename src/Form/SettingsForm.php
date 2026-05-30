<?php

namespace Drupal\dwsim_custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings form — email addresses and allowed file extensions.
 * D7 equivalent: custom_model_settings_form() in settings.inc
 *
 * Note: variable_get/set replaced with Drupal 11 Config API.
 * Config object: dwsim_custom_model.settings
 */
class SettingsForm extends FormBase {

  public function getFormId() {
    return 'dwsim_custom_model_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load current config values.
    $config = \Drupal::config('dwsim_custom_model.settings');

    $form['emails'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('(Bcc) Notification emails'),
      '#description'   => $this->t('Comma-separated email addresses for BCC on all outgoing mail.'),
      '#size'          => 50,
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#default_value' => $config->get('bcc_emails') ?? '',
    ];

    $form['cc_emails'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('(Cc) Notification emails'),
      '#description'   => $this->t('Comma-separated email addresses for CC on all outgoing mail.'),
      '#size'          => 50,
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#default_value' => $config->get('cc_emails') ?? '',
    ];

    $form['from_email'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Outgoing from email address'),
      '#description'   => $this->t('Address shown in the From field of all outgoing messages.'),
      '#size'          => 50,
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#default_value' => $config->get('from_email') ?? '',
    ];

    $form['extensions'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Allowed file extensions'),
    ];

    $form['extensions']['resource_upload'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Resource file extensions'),
      '#description'   => $this->t('Comma-separated list WITHOUT spaces (e.g. pdf,doc,zip).'),
      '#size'          => 50,
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#default_value' => $config->get('resource_upload_extensions') ?? '',
    ];

    $form['extensions']['idea_proposal_resource_upload'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Idea proposal resource file extensions'),
      '#description'   => $this->t('Comma-separated list WITHOUT spaces.'),
      '#size'          => 50,
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#default_value' => $config->get('idea_proposal_resource_upload_extensions') ?? '',
    ];

    $form['extensions']['abstract_upload'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Abstract file extensions'),
      '#size'          => 50,
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#default_value' => $config->get('abstract_upload_extensions') ?? '',
    ];

    $form['extensions']['custom_model_upload'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Project file extensions'),
      '#size'          => 50,
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#default_value' => $config->get('simulation_file_extensions') ?? '',
    ];

    $form['extensions']['custom_model_script_upload'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Script file extensions'),
      '#size'          => 50,
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#default_value' => $config->get('script_file_extensions') ?? '',
    ];

    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Save Settings')];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // D11 config API replaces D7 variable_set().
    \Drupal::configFactory()->getEditable('dwsim_custom_model.settings')
      ->set('bcc_emails',                              $form_state->getValue('emails'))
      ->set('cc_emails',                               $form_state->getValue('cc_emails'))
      ->set('from_email',                              $form_state->getValue('from_email'))
      ->set('resource_upload_extensions',              $form_state->getValue('resource_upload'))
      ->set('idea_proposal_resource_upload_extensions',$form_state->getValue('idea_proposal_resource_upload'))
      ->set('abstract_upload_extensions',              $form_state->getValue('abstract_upload'))
      ->set('simulation_file_extensions',              $form_state->getValue('custom_model_upload'))
      ->set('script_file_extensions',                  $form_state->getValue('custom_model_script_upload'))
      ->save();

    $this->messenger()->addStatus($this->t('Settings saved.'));
  }

}
