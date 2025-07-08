<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\CustomModelRunForm.
 */

 namespace Drupal\custom_model\Form;

 use Drupal\Core\Form\FormBase;
 use Drupal\Core\Form\FormStateInterface;
 use Drupal\Core\Ajax\AjaxResponse;
 use Drupal\Core\Ajax\HtmlCommand;
 use Drupal\Core\Url;
 use Drupal\Core\Link;
 use Drupal\Core\Database\Database;
 use Drupal\user\Entity\User;
 
 class CustomModelRunForm extends FormBase {
 
   public function getFormId() {
     return 'custom_model_run_form';
   }
 
   public function buildForm(array $form, FormStateInterface $form_state) {
     $route_match = \Drupal::routeMatch();
     $url_custom_model_id = (int) $route_match->getParameter('url_custom_model_id');
     $custom_model_data = $this->_custom_model_information($url_custom_model_id);
     if ($custom_model_data == 'Not found') {
       $url_custom_model_id = 0;
     }
 
     $selected = $form_state->getValue('custom_model') ?? $url_custom_model_id;
 
     $form['custom_model'] = [
       '#type' => 'select',
       '#title' => $this->t('Title of the Custom Model'),
       '#options' => $this->_list_of_custom_model(),
       '#default_value' => $selected,
       '#ajax' => [
         'callback' => '::custom_model_project_details_callback',
         'wrapper' => 'custom-model-details-wrapper',
       ],
     ];
 
     $form['custom_model_details_wrapper'] = [
       '#type' => 'container',
       '#attributes' => ['id' => 'custom-model-details-wrapper'],
     ];
 
     if ($selected && $selected != 0) {
       $form['custom_model_details_wrapper']['details'] = [
         '#type' => 'markup',
         '#markup' => $this->_custom_model_details($selected),
       ];
 
       $form['custom_model_details_wrapper']['links'] = [
         '#type' => 'markup',
         '#markup' => '<div id="ajax_selected_custom_model">' .
           Link::fromTextAndUrl(
             $this->t('Download Abstract'),
             Url::fromUri('internal:/custom-model/download/project-file/' . $selected)
           )->toString() .
           '<br>' .
           Link::fromTextAndUrl(
             $this->t('Download Custom Model'),
             Url::fromUri('internal:/custom-model/full-download/project/' . $selected)
           )->toString() .
           '</div>',
       ];
     }
 
     return $form;
   }
 
   public function custom_model_project_details_callback(array &$form, FormStateInterface $form_state) {
     return $form['custom_model_details_wrapper'];
   }
 
   public function _custom_model_details($custom_model_id) {
     $details = $this->_custom_model_information($custom_model_id);
     if (!$details) {
       return '';
     }
 
     return '<div><span style="color: #800000;"><strong>About the Custom Model</strong></span><br />' .
       '<ul>' .
       '<li><strong>Proposer Name:</strong> ' . $details->name_title . ' ' . $details->contributor_name . '</li>' .
       '<li><strong>Title of the Custom Model:</strong> ' . $details->project_title . '</li>' .
       '<li><strong>University:</strong> ' . $details->university . '</li>' .
       '</ul></div>';
   }
 
   public function _custom_model_information($proposal_id) {
     if (!$proposal_id) return 'Not found';
     $query = \Drupal::database()->select('custom_model_proposal', 'cmp')
       ->fields('cmp')
       ->condition('id', $proposal_id)
       ->condition('approval_status', 3);
     $result = $query->execute()->fetchObject();
     return $result ?: 'Not found';
   }
 
   public function _list_of_custom_model() {
     $options = ['0' => $this->t('Please select...')];
 
     $results = \Drupal::database()->select('custom_model_proposal', 'cmp')
       ->fields('cmp', ['id', 'project_title', 'name_title', 'contributor_name'])
       ->condition('approval_status', 3)
       ->orderBy('project_title', 'ASC')
       ->execute();
 
     foreach ($results as $row) {
       $options[$row->id] = $row->project_title . ' (' . $this->t('Proposed by @title @name', [
         '@title' => $row->name_title,
         '@name' => $row->contributor_name,
       ]) . ')';
     }
 
     return $options;
   }
 
   public function submitForm(array &$form, FormStateInterface $form_state) {
     // No submit handling required for this form.
   }
 }
 