<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\DwsimCustomModelCompletedTabForm.
 */

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;

class DwsimCustomModelCompletedTabForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dwsim_custom_model_completed_tab_form';
  }

  
  
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = $this->_custom_model_details_year_wise();
    $selected = !$form_state->getValue(['howmany_select']) ? $form_state->getValue(['howmany_select']) : key($options_first);
    $form = [];
    $form['howmany_select'] = [
      '#title' => $this->t('Sorting projects according to year:'),
      '#type' => 'select',
      '#options' => $this->_custom_model_details_year_wise(),
      /*'#options' => array(
    	'Please select...' => 'Please select...',
    	'2017' => '2017',
    	'2018' => '2018', 
    	'2019' => '2019', 
    	'2020' => '2020', 
    	'2021' => '2021',
      '2022' => '2022',
      '2023' => '2023'
      ),*/
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajax_example_autocheckboxes_callback',
      'wrapper' => 'ajax-selected-year-wrapper',
        
        ],
        ];
        $form['ajax_selected_year']['selected-year-wrapper'] = [
          '#type' => 'markup',
          '#markup' => 
            
              $this->t('Work has been completed for the following custom model Project:'), 
             
        ];
        $form['ajax-selected-year-wrapper'] = [
          '#type' => 'container',
          '#attributes' => ['id' => 'ajax-selected-year-wrapper'],
        ];
      $rendered_table = \Drupal::service('renderer')->render($this->_custom_model_details($form_state->getValue(['howmany_select'])));

      $form['ajax-selected-year-wrapper'][''] = [
          '#type' => 'markup',
          '#markup' =>  $rendered_table
      ];
        // $form['ajax_selected_year'] = [
        //   '#markup' => '<div id="ajax-selected-year-wrapper">', // This is the wrapper where the updated content will be placed.
        //   '#prefix' => '<div id="ajax-selected-year-wrapper">', // Optional: You can add any markup before.
        //   '#suffix' => '</div>', // Optional: You can add any markup after.
        // ];

        // $form['ajax_selected_year'] = [
        //   '#type' => 'container',
        //   '#attributes' => ['id' => 'ajax_selected_year_wrapper'],
        // ];
       
      $year_options = $this->_custom_model_details_year_wise();
      $selected_year = $form_state->getValue('howmany_select') ?? '0';
    
      $form['howmany_select'] = [
        '#title' => $this->t('Sorting projects according to year:'),
        '#type' => 'select',
        '#options' => $this->_custom_model_details_year_wise(),
        '#default_value' => $form_state->getValue('howmany_select') ?? '0',
        '#ajax' => [
          'callback' => '::ajax_example_autocheckboxes_callback',
          'wrapper' => 'ajax-selected-year-wrapper',
          
        ],
      ];
      
      $form['ajax-selected-year-wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax-selected-year-wrapper'],
      ];
      
      // Only show table if year is selected
      $selected_year = $form_state->getValue('howmany_select') ?? '0';
      if ($selected_year !== '0') {
        $form['ajax-selected-year-wrapper'] += $this->_custom_model_details1($selected_year);
      }
      
      return $form;
    
    
  }
  public function ajax_example_autocheckboxes_callback(array &$form, FormStateInterface $form_state) {
    return $form['ajax-selected-year-wrapper'];
  }
  
  public function _custom_model_details_year_wise() {
    $custom_model_years = ['0' => $this->t('Please select...')];
  
    $result = \Drupal::database()->query("SELECT DISTINCT FROM_UNIXTIME(actual_completion_date, '%Y') AS year FROM custom_model_proposal WHERE approval_status = 3 ORDER BY year DESC");
  
    foreach ($result as $record) {
      $year = $record->year;
      $custom_model_years[$year] = $year;
    }
  
    return $custom_model_years;
  }
   

 public function _custom_model_details($custom_model_proposal_id) {
  $output = [];

  // Query the database.
  $connection = \Drupal::database();
  $query = $connection->select('custom_model_proposal', 'cmp')
    ->fields('cmp')
    ->condition('approval_status', 3);

  $result = $query->execute();

  // Initialize table rows and headers.
  $preference_rows = [];
  $preference_header = [
    'No',
    'Custom Model Project',
    'Contributor Name',
    'University / Institute',
    'Year of Completion',
  ];

  if ($result === 0) {
    // No results found.
    $output['message'] = [
      '#markup' => Markup::create('<p>Work has been completed for the following custom model. We welcome your contributions.</p><hr>'),
    ];
  } else {
    // Process the results.
    $i = 1;
    foreach ($result as $row) {
      $completion_date = date('Y', $row->actual_completion_date);

      $project_link = Link::fromTextAndUrl($row->project_title, Url::fromRoute('custom_model.run_form', ['custom_model_id' => $row->id]))
        ->toString();

      $preference_rows[] = [
        $i,
        Markup::create($project_link . '<br><strong>(Script used: ' . $row->script_used . ')</strong>'),
        $row->contributor_name,
        $row->university,
        $completion_date,
      ];
      $i++;
    }

    // Add the message and the table to the output.
    $output['message'] = [
      '#markup' => Markup::create('<p>Work has been completed for the following custom model.</p><hr>'),
    ];

    $output['table'] = [
      '#type' => 'table',
      '#header' => $preference_header,
      '#rows' => $preference_rows,
      '#attributes' => ['class' => ['custom-model-details']],
    ];
  }

  return $output;
}

public function _custom_model_details1($year) {
  if ($year == '0') {
    return ['#markup' => $this->t('Please select a year to view completed custom model projects.')];
  }

  $query = \Drupal::database()->select('custom_model_proposal', 'cmp');
  $query->fields('cmp');
  $query->condition('approval_status', 3);
  $query->where("FROM_UNIXTIME(actual_completion_date, '%Y') = :year", [':year' => $year]);
  $results = $query->execute()->fetchAll();

  if (empty($results)) {
    return ['#markup' => $this->t('No completed custom model projects found for the selected year.')];
  }

  $rows = [];
  $i = count($results);

  foreach ($results as $row) {
    $link = Link::fromTextAndUrl(
      $row->project_title,
      Url::fromUri('internal:/custom-model/custom-model-run/' . $row->id)
    )->toString();

    $rows[] = [
      $i,
      Markup::create($link . '<br><strong>(' . $this->t('Script used: @script', ['@script' => $row->script_used]) . ')</strong>'),
      $row->contributor_name,
      $row->university,
      date('Y', $row->actual_completion_date),
    ];
    $i--;
  }

  $header = [
    $this->t('No'),
    $this->t('Custom Model Project'),
    $this->t('Contributor Name'),
    $this->t('University / Institute'),
    $this->t('Year of Completion'),
  ];

  return [
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => ['class' => ['custom-model-details']],
  ];
}

  

  private function custom_model_completed_proposals_all_list($year ="") {
    $output = [];
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('approval_status', 3);
    $query->orderBy('actual_completion_date', 'DESC');
    $query->condition('is_completed', 1);
    $result = $query->execute();

    
    //var_dump($custom_model_abstract);die;
    if (empty($result)) {
      $output ['message']= [
         '#markup' => '<p>Work has been completed for the following custom model. </p><hr>'
      ];
      
    }
    else{
      $output ['message']= [ '#markup' => '<p>Work has been completed for the following custom model </p>'];

   
   
    //$result->rowCount() == 0
   
      $preference_rows = [];
      $i = 1;
      while ($row = $result->fetchObject()) {
        $proposal_id = $row->id;
        $completion_date = date("Y", $row->actual_completion_date);
        $preference_rows[] = [
          $i,
          // l($row->project_title, "custom-model/custom-model-run/" . $row->id) . t("<br><strong>(Script used: ") . $row->script_used . t(")</strong>"),
         $link = Link::fromTextAndUrl(
            $row->project_title,
            Url::fromUri('internal:/custom-model/custom-model-run/' . $row->id)
          )->toString(),
          
          
          // $markup = $link . $this->t('<br><strong>(Script used: @script)</strong>', ['@script' => $row->script_used]),
          $row->contributor_name,
          $row->university,
          $completion_date,
        ];
        $i++;
      } //$row = $result->fetchObject()
      $preference_header = [
        'No',
        'Custom Model Project',
        'Contributor Name',
        'University / Institute',
        'Year of Completion',
      ];
      $output =[
        '#type' => 'table',
        '#header' => $preference_header,
        '#rows' => $preference_rows,
      ];
    }
    //$output = "test";
    return $output;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

  }

}

