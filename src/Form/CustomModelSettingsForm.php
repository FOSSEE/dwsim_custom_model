<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\CustomModelSettingsForm.
 */

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\ConfigFormBase;

class CustomModelSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_settings_form';
  }
  protected function getEditableConfigNames() {
    return [
      'custom_model.settings',
    ];
  }
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $config = $this->config('custom_model.settings');
    $form['emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Bcc) Notification emails'),
      '#description' => t('Specify emails id for Bcc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('custom_model_emails', ''),
    ];
    $form['cc_emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Cc) Notification emails'),
      '#description' => t('Specify emails id for Cc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('custom_model_cc_emails', ''),
    ];
    $form['from_email'] = [
      '#type' => 'textfield',
      '#title' => t('Outgoing from email address'),
      '#description' => t('Email address to be display in the from field of all outgoing messages'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('custom_model_from_email', ''),
    ];
    $form['extensions']['resource_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions for uploading resource files'),
      '#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('resource_upload_extensions', ''),
    ];
    $form['extensions']['idea_proposal_resource_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions for uploading resource files during idea proposal'),
      '#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('idea_proposal_resource_upload_extensions', ''),
    ];
    $form['extensions']['abstract_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions for abstract'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('custom_model_abstract_upload_extensions', ''),
    ];
    $form['extensions']['custom_model_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed extensions for project files'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('custom_model_simulation_file', ''),
    ];
    $form['extensions']['custom_model_script_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed extensions for script files'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('custom_model_script_file', ''),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return parent::buildForm($form, $form_state);
    // return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('custom_model.settings')
    ->set('custom_model_emails', $form_state->getValue(['emails']))
    ->set('custom_model_cc_emails', $form_state->getValue(['cc_emails']))
    ->set('custom_model_from_email', $form_state->getValue(['from_email']))
    ->set('resource_upload_extensions', $form_state->getValue(['resource_upload']))
    ->set('idea_proposal_resource_upload_extensions', $form_state->getValue(['idea_proposal_resource_upload']))
    ->set('custom_model_abstract_upload_extensions', $form_state->getValue(['abstract_upload']))
    ->set('custom_model_simulation_file', $form_state->getValue(['custom_model_upload']))
    ->set('custom_model_script_file', $form_state->getValue(['custom_model_script_upload']))
    ->save();
    \Drupal::messenger()->addMessage(t('Settings updated'), 'status');
  }

}

