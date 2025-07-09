<?php

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;

class DwsimCustomModelCompletedTabForm extends FormBase {

  public function getFormId() {
    return 'dwsim_custom_model_completed_tab_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $year_options = $this->_custom_model_details_year_wise();
    $selected_year = $form_state->getValue('howmany_select') ?? '0';

    $form['howmany_select'] = [
      '#title' => $this->t('Sorting projects according to year:'),
      '#type' => 'select',
      '#options' => $year_options,
      '#default_value' => $selected_year,
      '#ajax' => [
        'callback' => '::ajax_example_autocheckboxes_callback',
        'wrapper' => 'ajax-selected-year-wrapper',
      ],
    ];

    $form['ajax-selected-year-wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax-selected-year-wrapper'],
    ];

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
    $i = 1;

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
      $i++;
    }

    $header = [
      $this->t('No'),
      $this->t('Custom Model Project'),
      $this->t('Contributor Name'),
      $this->t('University / Institute'),
      $this->t('Year of Completion'),
    ];

    return [
      'message' => [
        '#markup' => '<p>Work has been completed for the following custom model Project: ' . count($rows) . '</p><hr>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['class' => ['custom-model-details', 'table', 'table-striped', 'table-bordered']],
      ],
    ];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submission required for this form.
  }
}
