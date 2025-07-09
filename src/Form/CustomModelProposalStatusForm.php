<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\CustomModelProposalStatusForm.
 */

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Session\AccountProxy;
class CustomModelProposalStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_proposal_status_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();
    $proposal_id = (int) $route_match->getParameter('id');
    
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
        // drupal_goto('custom-model/manage-proposal');
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
      // drupal_goto('custom-model/manage-proposal');
      return;
    }
    $form['contributor_name'] = [
      '#type' => 'item',
      // '#markup' => l($proposal_data->name_title . ' ' . $proposal_data->contributor_name, 'user/' . $proposal_data->uid),
      '#markup' => Link::fromTextAndUrl($proposal_data->name_title . ' ' . $proposal_data->contributor_name,Url::fromUri('internal:/user/' . $proposal_data->uid)
      )->toString(),
    
      '#title' => t('Student name'),
    ];
    $form['student_email_id'] = [
      '#title' => t('Student Email'),
      '#type' => 'item',
      // '#markup' => User::load($proposal_data->uid)->getEmail(),
    //  '#markup' => \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid)->getEmail(),
      '#title' => t('Email'),
    ];
    /*$form['month_year_of_degree'] = array(
		'#type' => 'date_popup',
		'#title' => t('Month and year of award of degree'),
		'#date_label_position' => '',
		'#description' => '',
		'#default_value' => $proposal_data->month_year_of_degree,
		'#date_format' => 'M-Y',
		'#date_increment' => 0,
		'#date_year_range' => '1960:+0',
		'#datepicker_options' => array(
			'maxDate' => 0
		),
		'#disabled' => TRUE
	);*/
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University/Institute'),
    ];
    $form['country'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->country,
      '#title' => t('Country'),
    ];
    $form['all_state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => t('State'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => t('City'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => t('Pincode/Postal code'),
    ];
    $form['project_guide_name'] = [
      '#type' => 'item',
      '#title' => t('Project guide'),
      '#markup' => $proposal_data->project_guide_name,
    ];
    $form['project_guide_email_id'] = [
      '#type' => 'item',
      '#title' => t('Project guide email'),
      '#markup' => $proposal_data->project_guide_email_id,
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Custom Model'),
    ];

    $proposal_status = '';
    switch ($proposal_data->approval_status) {
      case 0:
        $proposal_status = t('Pending');
        break;
      case 1:
        $proposal_status = t('Approved');
        break;
      case 2:
        $proposal_status = t('Dis-approved');
        break;
      case 3:
        $proposal_status = t('Completed');
        break;
      default:
        $proposal_status = t('Unkown');
        break;
    }
    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => t('Proposal Status'),
    ];
    if ($proposal_data->approval_status == 0) {
      $form['approve'] = [
        '#type' => 'item',
        // '#markup' => l('Click here', 'custom-model/manage-proposal/approve/' . $proposal_id),
        '#title' => t('Approve'),
      ];
    } //$proposal_data->approval_status == 0
    if ($proposal_data->approval_status == 1) {
      $form['completed'] = [
        '#type' => 'checkbox',
        '#title' => t('Completed'),
        '#description' => t('Check if user has provided all the required files and pdfs.'),
      ];
    } //$proposal_data->approval_status == 1
    if ($proposal_data->approval_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->message,
        '#title' => t('Reason for disapproval'),
      ];
    } //$proposal_data->approval_status == 2
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      // '#markup' => l(t('Cancel'), 'custom-model/manage-proposal/all'),
      '#markup' => Link::fromTextAndUrl(
  $this->t('Cancel'),
  Url::fromUri('internal:/custom-model/manage-proposal/all'))->toString(),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
      $proposal_id = (int) \Drupal::routeMatch()->getParameter('id'); // ✅ Needed!
      $user = \Drupal::currentUser();
    
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {custom_model_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    // if ($proposal_q) {
    //   if ($proposal_data = $proposal_q->fetchObject()) {
    //     /* everything ok */
    //   } //$proposal_data = $proposal_q->fetchObject()
    //   else {
    //     \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
    //     // drupal_goto('custom-model/manage-proposal');
    //     return;
    //   }
    // } //$proposal_q
    // else {
    //   \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
    //   // drupal_goto('custom-model/manage-proposal');
    //   return;
    // }
    /* set the book status to completed */
    if ($form_state->getValue(['completed']) == 1) {
      $up_query = "UPDATE custom_model_proposal SET approval_status = :approval_status , actual_completion_date = :expected_completion_date WHERE id = :proposal_id";
      $args = [
        ":approval_status" => '3',
        ":proposal_id" => $proposal_id,
        ":expected_completion_date" => time(),
      ];
      $result = \Drupal::database()->query($up_query, $args);
      // CreateReadmeFileCustomModel($proposal_id);
      // if (!$result) {
      //   \Drupal::messenger()->addMessage('Error in update status', 'error');
      //   return;
      // } //!$result
		/* sending email */
      // $user_data = User::load($proposal_data->uid);
      // $email_to = $user_data->mail;
      // $from = variable_get('custom_model_from_email', '');
      // $bcc = $user->mail . ', ' . variable_get('custom_model_emails', '');
      // $cc = variable_get('custom_model_cc_emails', '');
      // $params['custom_model_proposal_completed']['proposal_id'] = $proposal_id;
      // $params['custom_model_proposal_completed']['user_id'] = $proposal_data->uid;
      // $params['custom_model_proposal_completed']['headers'] = [
      //   'From' => $from,
      //   'MIME-Version' => '1.0',
      //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      //   'Content-Transfer-Encoding' => '8Bit',
      //   'X-Mailer' => 'Drupal',
      //   'Cc' => $cc,
      //   'Bcc' => $bcc,
      // ];
      // if (!drupal_mail('custom_model', 'custom_model_proposal_completed', $email_to, language_default(), $params, $from, TRUE)) {
      //   \Drupal::messenger()->addMessage('Error sending email message.', 'error');
      // }
      \Drupal::messenger()->addMessage('Congratulations! Custom Model proposal has been marked as completed. User has been notified of the completion.', 'status');
    }
    // drupal_goto('custom-model/manage-proposal');
    // RedirectResponse('lab-migration/manage-proposal');
    $response = new RedirectResponse(Url::fromRoute('custom_model.proposal_all')->toString());
  
    // //   // Send the redirect response
      $response->send();
  

    return;

  }

}

