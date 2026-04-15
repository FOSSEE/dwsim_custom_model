<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\CustomModelProposalApprovalForm.
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
use Drupal\Core\Session\AccountProxy;

class CustomModelProposalApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_proposal_approval_form';
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
        $url = Url::fromUri('internal:/custom-model/manage-proposal/approve/')->toString();
        // \Drupal::service('request_stack')->getCurrentRequest()->query->set('destination', $url);
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
      // drupal_goto('custom-model/manage-proposal');
      $url = Url::fromUri('internal:/custom-model/manage-proposal/approve/')->toString();

      return;
    }
    if ($proposal_data->contact_no == "NULL" || $proposal_data->contact_no == "") {
      $contact_no = "Not Entered";
    } //$proposal_data->project_guide_email_id == NULL
    else {
      $contact_no = $proposal_data->contact_no;
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
      // '#markup' => User::load($proposal_data->uid)->mail,
      '#markup' => User::load($proposal_data->uid)->getEmail(),

      '#title' => t('Email'),
    ];
   
    $form['contributor_contact_no'] = [
      '#title' => t('Contact No.'),
      '#type' => 'item',
      '#markup' => $contact_no,
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University/Institute'),
    ];
    $form['department'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->department,
      '#title' => t('Department/Branch'),
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
    $form['version'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->version,
      '#title' => t('DWSIM Version used'),
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Custom Model'),
    ];
    $form['script_used'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->script_used,
      '#title' => t('Script used to create the Custom Model'),
    ];

if (!empty($proposal_data->samplefilepath) && $proposal_data->samplefilepath !== 'NULL') {
  $str = substr($proposal_data->samplefilepath, strrpos($proposal_data->samplefilepath, '/'));
  $resource_file = ltrim($str, '/');

  $form['samplefilepath'] = [
    '#type' => 'item',
    '#title' => $this->t('Uploaded Abstract of Custom Model'),
    '#markup' => Link::fromTextAndUrl(
      $resource_file,
      Url::fromUri('internal:/custom-model/download/resource-file/' . $proposal_id)
    )->toString(),
    
  ];
} else {
  $form['samplefilepath'] = [
    '#type' => 'item',
    '#title' => $this->t('Uploaded Abstract of Custom Model'),
    '#markup' => "Not uploaded<br><br>",
  ];
}

    $form['approval'] = [
      '#type' => 'radios',
      '#title' => t('Select an action on the Custom model proposal'),
      '#options' => [
        '1' => 'Approve',
        '2' => 'Disapprove',
      ],
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Reason for disapproval'),
      '#attributes' => [
        'placeholder' => t('Enter reason for disapproval in minimum 30 characters '),
        'cols' => 50,
        'rows' => 4,
      ],
      '#states' => [
        'visible' => [
          ':input[name="approval"]' => [
            'value' => '2'
            ]
          ]
        ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      // '#markup' => l(t('Cancel'), 'custom-model/manage-proposal'),
      '#markup' => Link::fromTextAndUrl(
  $this->t('Cancel'),
  Url::fromUri('internal:/custom-model/manage-proposal/pending'))->toString(),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['approval']) == 2) {
      if ($form_state->getValue(['message']) == '') {
        $form_state->setErrorByName('message', t('Reason for disapproval could not be empty'));
      } //$form_state['values']['message'] == ''
    } //$form_state['values']['approval'] == 2
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
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
        $url = Url::fromRoute('custom_model.proposal_pending')->toString();

        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
      // drupal_goto('custom-model/manage-proposal');
      $url = Url::fromRoute('custom_model.proposal_pending')->toString();
      // \Drupal::service('request_stack')->getCurrentRequest()->query->set('destination', $url); 
      return;
    }
    if ($form_state->getValue(['approval']) == 1) {
      $query = "UPDATE {custom_model_proposal} SET approver_uid = :uid, approval_date = :date, approval_status = 1 WHERE id = :proposal_id";
      $args = [
        ":uid" => $user->id(),
        ":date" => time(),
        ":proposal_id" => $proposal_id,
      ];
      \Drupal::database()->query($query, $args);

/* Sending email */

// Load proposal owner
$user = User::load($proposal_data->uid);
$email_to = $user->getEmail();

// Get config values
$config = \Drupal::config('custom_model.settings');

$from = $config->get('custom_model_from_email');
$bcc_config = $config->get('custom_model_emails');
$cc = $config->get('custom_model_cc_emails');

// Combine BCC (user email + config emails)
$bcc = $email_to;
if (!empty($bcc_config)) {
  $bcc .= ', ' . $bcc_config;
}

// Mail params (FLAT structure - important)
$params = [
  'proposal_id' => $proposal_id,
  'user_id' => $proposal_data->uid,
];

// Send mail
$result = \Drupal::service('plugin.manager.mail')->mail(
  'custom_model', // ✅ FIXED (must match module name)
  'custom_model_proposal_approved',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);// Headers
$headers = [
  'From' => $from,
  'MIME-Version' => '1.0',
  'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
  'Content-Transfer-Encoding' => '8Bit',
  'X-Mailer' => 'Drupal',
];

if (!empty($cc)) {
  $headers['Cc'] = $cc;
}
if (!empty($bcc)) {
  $headers['Bcc'] = $bcc;
}

// Send mail
$mailManager = \Drupal::service('plugin.manager.mail');

$langcode = $user->getPreferredLangcode();

$result = $mailManager->mail(
  'custom_mmodel', // keep same module key
  'custom_model_proposal_approved',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

// Check result
if (!$result['result']) {
  \Drupal::messenger()->addMessage(t('sending email message.'));
}

// Status message
\Drupal::messenger()->addStatus(
  t('Custom Model proposal No. @id approved. User has been notified of the approval.', [
    '@id' => $proposal_id
  ])
);

// Redirect (IMPORTANT: return, not send())
return new RedirectResponse(
  Url::fromRoute('custom_model.proposal_pending')->toString()
);
      // return;
    } //$form_state['values']['approval'] == 1
    else {
      if ($form_state->getValue(['approval']) == 2) {
        $query = "UPDATE {custom_model_proposal} SET approver_uid = :uid, approval_date = :date, approval_status = 2, dissapproval_reason = :dissapproval_reason WHERE id = :proposal_id";
        $args = [
          ":uid" => $user->uid,
          ":date" => time(),
          ":dissapproval_reason" => $form_state->getValue(['message']),
          ":proposal_id" => $proposal_id,
        ];
        $result = \Drupal::database()->query($query, $args);
/* Sending email */

// Load user
$user = User::load($proposal_data->uid);
$email_to = $user->getEmail();

// Load config
$config = \Drupal::config('custom_model.settings');

$from = $config->get('custom_model_from_email');
$bcc_config = $config->get('custom_model_emails');
$cc = $config->get('custom_model_cc_emails');

// Build BCC
$bcc = $email_to;
if (!empty($bcc_config)) {
  $bcc .= ', ' . $bcc_config;
}

// Mail params
$params['custom_model_proposal_disapproved'] = [
  'proposal_id' => $proposal_id,
  'user_id' => $proposal_data->uid,
];

// Send mail
$mailManager = \Drupal::service('plugin.manager.mail');

$langcode = $user->getPreferredLangcode();

$result = $mailManager->mail(
  'custom_model',
  'custom_model_proposal_disapproved',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

// Handle failure
if (!$result['result']) {
  \Drupal::messenger()->addMessage(t(' Sending email message.'));
}

// Status message
\Drupal::messenger()->addError(
  t('Custom Model proposal No. @id dis-approved. User has been notified of the dis-approval.', [
    '@id' => $proposal_id,
  ])
);

// Redirect (DO NOT use ->send())
return new RedirectResponse(
  Url::fromRoute('custom_model.proposal_pending')->toString()
);
      }
    }
  }


}

