<?php

namespace Drupal\dwsim_custom_model\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * All page callbacks for the dwsim_custom_model module live here.
 *
 * Grouped by section — each section maps to the original D7 .inc file.
 * Sections: Manage Proposal | Review Tabs | Bulk Approval | Downloads | Certificates
 */
class DefaultController extends ControllerBase {

  // ---------------------------------------------------------------------------
  // MANAGE PROPOSAL — LISTING PAGES
  // from manage_proposal.inc
  // ---------------------------------------------------------------------------

  /**
   * Shows pending proposals (approval_status = 0).
   * D7 equivalent: custom_model_proposal_pending()
   */
  public function pending() {
    $results = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('approval_status', 0)
      ->orderBy('id', 'DESC')
      ->execute()
      ->fetchAll();

    $rows = [];
    foreach ($results as $row) {
      $rows[] = [
        date('d-m-Y', $row->creation_date),
        Link::fromTextAndUrl(
          $row->name_title . ' ' . $row->contributor_name,
          Url::fromRoute('entity.user.canonical', ['user' => $row->uid])
        )->toString(),
        $row->project_title,
        Link::fromTextAndUrl($this->t('Approve'), Url::fromRoute('dwsim_custom_model.manage_proposal.approve', ['id' => $row->id]))->toString()
          . ' | '
          . Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('dwsim_custom_model.manage_proposal.edit', ['id' => $row->id]))->toString(),
      ];
    }

    if (empty($rows)) {
      $this->messenger()->addStatus($this->t('There are no pending proposals.'));
    }

    return [
      '#theme'  => 'table',
      '#header' => ['Date of Submission', 'Contributor Name', 'Title of the Custom Model', 'Action'],
      '#rows'   => $rows,
      '#empty'  => $this->t('No pending proposals.'),
      '#cache'  => ['max-age' => 0],
    ];
  }

  /**
   * Shows all proposals regardless of status.
   * D7 equivalent: custom_model_proposal_all()
   */
  public function all() {
    $results = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->orderBy('id', 'DESC')
      ->execute()
      ->fetchAll();

    $status_map = [0 => 'Pending', 1 => 'Approved', 2 => 'Dis-approved', 3 => 'Completed'];

    $rows = [];
    foreach ($results as $row) {
      $completion = $row->actual_completion_date
        ? date('d-m-Y', $row->actual_completion_date)
        : 'Not Completed';

      $rows[] = [
        date('d-m-Y', $row->creation_date),
        Link::fromTextAndUrl($row->contributor_name, Url::fromRoute('entity.user.canonical', ['user' => $row->uid]))->toString(),
        $row->project_title,
        $completion,
        $status_map[$row->approval_status] ?? 'Unknown',
        Link::fromTextAndUrl($this->t('Status'), Url::fromRoute('dwsim_custom_model.manage_proposal.status', ['id' => $row->id]))->toString()
          . ' | '
          . Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('dwsim_custom_model.manage_proposal.edit', ['id' => $row->id]))->toString(),
      ];
    }

    if (empty($rows)) {
      $this->messenger()->addStatus($this->t('There are no proposals.'));
    }

    return [
      '#theme'  => 'table',
      '#header' => ['Date of Submission', 'Contributor Name', 'Title', 'Date of Completion', 'Status', 'Action'],
      '#rows'   => $rows,
      '#empty'  => $this->t('No proposals found.'),
      '#cache'  => ['max-age' => 0],
    ];
  }

  /**
   * Shows all idea proposals.
   * D7 equivalent: custom_model_idea_proposal_all()
   */
  public function ideaAll() {
    $results = \Drupal::database()
      ->select('custom_model_idea_proposal', 'p')
      ->fields('p')
      ->orderBy('id', 'DESC')
      ->execute()
      ->fetchAll();

    $rows = [];
    foreach ($results as $row) {
      $rows[] = [
        date('d-m-Y', $row->creation_date),
        Link::fromTextAndUrl($row->idea_proposar_name, Url::fromRoute('entity.user.canonical', ['user' => $row->uid]))->toString(),
        $row->project_title,
        Link::fromTextAndUrl($this->t('View'), Url::fromRoute('dwsim_custom_model.manage_proposal.view_ideas', ['id' => $row->id]))->toString(),
      ];
    }

    if (empty($rows)) {
      $this->messenger()->addStatus($this->t('There are no idea proposals.'));
    }

    return [
      '#theme'  => 'table',
      '#header' => ['Date of Submission', 'Contributor Name', 'Title of the Custom Model', 'Action'],
      '#rows'   => $rows,
      '#empty'  => $this->t('No idea proposals found.'),
      '#cache'  => ['max-age' => 0],
    ];
  }

  // ---------------------------------------------------------------------------
  // PROPOSAL REVIEW TABS
  // from proposals_review_tab.inc
  // ---------------------------------------------------------------------------

  /**
   * Lists approved proposals that have not uploaded code yet.
   * D7 equivalent: dwsim_custom_model_approved_tab()
   */
  public function approved() {
    $results = \Drupal::database()->query(
      "SELECT * FROM {custom_model_proposal}
       WHERE id NOT IN (SELECT proposal_id FROM {custom_model_submitted_abstracts})
       AND approval_status = 1
       ORDER BY approval_date DESC"
    )->fetchAll();

    $rows = [];
    $i = 1;
    foreach ($results as $row) {
      $rows[] = [$i++, $row->project_title, $row->contributor_name, $row->university, date('d-M-Y', $row->approval_date)];
    }

    return [
      '#markup' => $this->t('Approved Proposals under Custom Model Project: @count', ['@count' => count($rows)]) . '<hr>',
      'table' => [
        '#theme'  => 'table',
        '#header' => ['No', 'Custom Model Project', 'Contributor Name', 'University / Institute', 'Approval Date'],
        '#rows'   => $rows,
        '#empty'  => $this->t('No approved proposals.'),
        '#cache'  => ['max-age' => 0],
      ],
    ];
  }

  /**
   * Lists approved proposals that have uploaded their code/abstract.
   * D7 equivalent: dwsim_custom_model_uploaded_tab()
   */
  public function uploaded() {
    $results = \Drupal::database()->query(
      "SELECT dfp.project_title, dfp.contributor_name, dfp.id, dfp.university,
              dfa.abstract_upload_date, dfa.abstract_approval_status
       FROM {custom_model_proposal} dfp
       JOIN {custom_model_submitted_abstracts} dfa ON dfa.proposal_id = dfp.id
       WHERE dfp.id IN (SELECT proposal_id FROM {custom_model_submitted_abstracts})
       AND dfp.approval_status = 1"
    )->fetchAll();

    $rows = [];
    $i = 1;
    foreach ($results as $row) {
      $rows[] = [$i++, $row->project_title, $row->contributor_name, $row->university, date('d-M-Y', $row->abstract_upload_date)];
    }

    return [
      '#markup' => $this->t('Uploaded Proposals under Custom Model Project: @count', ['@count' => count($rows)]) . '<hr>',
      'table' => [
        '#theme'  => 'table',
        '#header' => ['No', 'Custom Model Project', 'Contributor Name', 'University / Institute', 'Upload Date'],
        '#rows'   => $rows,
        '#empty'  => $this->t('No uploaded proposals.'),
        '#cache'  => ['max-age' => 0],
      ],
    ];
  }

  /**
   * Lists completed proposals filtered by year.
   * D7 equivalent: _custom_model_details()
   */
  public function completedByYear($year) {
    $results = \Drupal::database()->query(
      "SELECT * FROM {custom_model_proposal}
       WHERE approval_status = 3
       AND FROM_UNIXTIME(actual_completion_date, '%Y') = :year",
      [':year' => $year]
    )->fetchAll();

    $rows = [];
    $i = 1;
    foreach ($results as $row) {
      $rows[] = [$i++, $row->project_title, $row->contributor_name, $row->university, date('Y', $row->actual_completion_date)];
    }

    return [
      '#markup' => $this->t('Completed Custom Model Projects: @count', ['@count' => count($rows)]) . '<hr>',
      'table' => [
        '#theme'  => 'table',
        '#header' => ['No', 'Custom Model Project', 'Contributor Name', 'University / Institute', 'Year of Completion'],
        '#rows'   => $rows,
        '#empty'  => $this->t('No completed projects for this year.'),
        '#cache'  => ['max-age' => 0],
      ],
    ];
  }

  /**
   * Public listing of all completed custom models.
   */
  public function completedAll() {
    $results = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('approval_status', 3)
      ->orderBy('actual_completion_date', 'DESC')
      ->execute()
      ->fetchAll();

    $rows = [];
    $i = 1;
    foreach ($results as $row) {
      $rows[] = [$i++, $row->project_title, $row->contributor_name, $row->university, date('d-M-Y', $row->actual_completion_date)];
    }

    return [
      '#theme'  => 'table',
      '#header' => ['No', 'Custom Model Project', 'Contributor Name', 'University / Institute', 'Completion Date'],
      '#rows'   => $rows,
      '#empty'  => $this->t('No completed custom models yet.'),
      '#cache'  => ['max-age' => 0],
    ];
  }

  /**
   * Lists approved proposals that are still in progress (not yet completed).
   */
  public function inProgress() {
    $results = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('approval_status', 1)
      ->orderBy('approval_date', 'DESC')
      ->execute()
      ->fetchAll();

    $rows = [];
    $i = 1;
    foreach ($results as $row) {
      $rows[] = [$i++, $row->project_title, $row->contributor_name, $row->university, date('d-M-Y', $row->approval_date)];
    }

    return [
      '#theme'  => 'table',
      '#header' => ['No', 'Custom Model Project', 'Contributor Name', 'University / Institute', 'Approval Date'],
      '#rows'   => $rows,
      '#empty'  => $this->t('No custom models currently in progress.'),
      '#cache'  => ['max-age' => 0],
    ];
  }

  // ---------------------------------------------------------------------------
  // BULK APPROVAL
  // from abstract_submission_bulk_approval.inc
  // ---------------------------------------------------------------------------

  /**
   * Loads the bulk approval form for abstract submissions.
   * D7 equivalent: page callback for custom-model/abstract-approval/bulk
   */
  public function submissionBulk() {
    return \Drupal::formBuilder()->getForm('\Drupal\dwsim_custom_model\Form\BulkApprovalForm');
  }

  /**
   * Loads the bulk approval form for code submissions.
   * D7 equivalent: page callback for custom-model/code-approval/bulk
   */
  public function codeBulk() {
    return \Drupal::formBuilder()->getForm('\Drupal\dwsim_custom_model\Form\BulkApprovalForm');
  }

  // ---------------------------------------------------------------------------
  // FILE DOWNLOADS
  // from download.inc and full_download.inc
  // ---------------------------------------------------------------------------

  /**
   * Returns the root path where custom model files are stored on the server.
   * D7 equivalent: custom_model_path()
   */
  private function rootPath() {
    return \Drupal::config('dwsim_custom_model.settings')->get('root_path')
      ?: \Drupal::root() . '/sites/default/files/custom_model/';
  }

  /**
   * Sends the resource/abstract file attached to a proposal as a download.
   * D7 equivalent: custom_model_download_uploaded_file()
   */
  public function resourceFile($id) {
    $proposal = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('id', $id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $file_path = $this->rootPath() . $proposal->directory_name . '/' . $proposal->samplefilepath;
    $response  = new BinaryFileResponse($file_path);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($proposal->samplefilepath));
    return $response;
  }

  /**
   * Sends the project file (filetype A) for a proposal as a download.
   * D7 equivalent: custom_model_project_files()
   */
  public function projectFile($id) {
    $file = \Drupal::database()
      ->select('custom_model_submitted_abstracts_file', 'f')
      ->fields('f')
      ->condition('proposal_id', $id)
      ->condition('filetype', 'A')
      ->execute()
      ->fetchObject();

    $proposal = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$file || !$proposal) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $file_path = $this->rootPath() . $proposal->directory_name . '/project_files/' . $file->filename;
    $response  = new BinaryFileResponse($file_path);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->filename);
    return $response;
  }

  /**
   * Zips all project files for a proposal and sends it as a download.
   * D7 equivalent: custom_model_download_full_project()
   */
  public function fullProject($id) {
    $root     = $this->rootPath();
    $proposal = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$proposal) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // Create a temporary zip file on the server.
    $zip_filename = $root . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);

    $files = \Drupal::database()
      ->select('custom_model_submitted_abstracts_file', 'f')
      ->fields('f')
      ->condition('proposal_id', $id)
      ->execute()
      ->fetchAll();

    foreach ($files as $file) {
      $zip->addFile(
        $root . $proposal->directory_name . '/project_files/' . $file->filepath,
        $proposal->directory_name . '/' . str_replace(' ', '_', basename($file->filename))
      );
    }

    $count = $zip->numFiles;
    $zip->close();

    if ($count === 0) {
      $this->messenger()->addError($this->t('No project files available to download.'));
      return $this->redirect('dwsim_custom_model.completed');
    }

    // Stream the zip to the browser and delete the temp file after sending.
    $response = new BinaryFileResponse($zip_filename);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, str_replace(' ', '_', $proposal->project_title) . '.zip');
    $response->deleteFileAfterSend(TRUE);
    return $response;
  }

  // ---------------------------------------------------------------------------
  // CERTIFICATES
  // from pdf/list_custom_model_certificate.inc, pdf/verify_certificates.inc
  // ---------------------------------------------------------------------------

  /**
   * Lists all completed proposals (certificates issued).
   * D7 equivalent: _list_custom_model_certificates()
   */
  public function certificateList() {
    $results = \Drupal::database()
      ->select('custom_model_proposal', 'p')
      ->fields('p')
      ->condition('approval_status', 3)
      ->orderBy('actual_completion_date', 'DESC')
      ->execute()
      ->fetchAll();

    $rows = [];
    $i = 1;
    foreach ($results as $row) {
      $rows[] = [$i++, $row->contributor_name, $row->project_title, $row->university, date('d-M-Y', $row->actual_completion_date)];
    }

    return [
      '#theme'  => 'table',
      '#header' => ['No', 'Contributor', 'Custom Model Title', 'University / Institute', 'Completion Date'],
      '#rows'   => $rows,
      '#empty'  => $this->t('No certificates issued yet.'),
      '#cache'  => ['max-age' => 0],
    ];
  }

  /**
   * Certificate verification page — form is handled via routing (_form).
   * D7 equivalent: verify_certificates()
   */
  public function certificateVerify() {
    return [
      '#markup' => $this->t('Use the form below to verify a Custom Model certificate.'),
      '#cache'  => ['max-age' => 0],
    ];
  }

  /**
   * PDF certificate generation.
   * D7 equivalent: generate_pdf() in pdf/cert_new.inc
   * Note: FPDF logic stays in cert_new.inc until a D11 PDF library is set up.
   */
  public function generatePdf() {
    return [
      '#markup' => $this->t('Certificate generation is handled by the PDF form.'),
      '#cache'  => ['max-age' => 0],
    ];
  }

}
