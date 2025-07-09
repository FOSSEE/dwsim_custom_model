<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\CustomModelProposalEditForm.
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

class CustomModelProposalEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_proposal_edit_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();
$proposal_id = (int) $route_match->getParameter('id');

    //$proposal_q = \Drupal::database()->query("SELECT * FROM {custom_model_proposal} WHERE id = %d", $proposal_id);
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
        $url = Url::fromUri('internal:/custom-model/manage-proposal/edit/')->toString();

        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
      // drupal_goto('custom-model/manage-proposal');
      $url = Url::fromUri('internal:/custom-model/manage-proposal/edit/')->toString();
      return;
    }
    $user_data = User::load($proposal_data->uid);
    $form['name_title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
        'Mr' => 'Mr',
        'Mrs' => 'Mrs',
        'Ms' => 'Ms',
      ],
      '#required' => TRUE,
      '#default_value' => $proposal_data->name_title,
    ];
    $form['contributor_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Proposer'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->contributor_name,
    ];
    $form['contributor_contact_no'] = [
      '#type' => 'textfield',
      '#title' => t('Contact No.'),
      '#default_value' => $proposal_data->contact_no,
    ];
    // $form['student_email_id'] = [
    //   '#type' => 'item',
    //   '#title' => t('Email'),
    //   '#markup' => $user_data->mail,
    // ];
    $form['contributor_email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 30,
      '#value' => $user ? $user->getEmail() : '', 
      '#disabled' => TRUE,
    ];
    $form['month_year_of_degree'] = [
      '#type' => 'date_popup',
      '#title' => t('Month and year of award of degree'),
      '#date_label_position' => '',
      '#description' => '',
      '#default_value' => $proposal_data->month_year_of_degree,
      '#date_format' => 'M-Y',
      '#date_increment' => 0,
      '#date_year_range' => '1960:+0',
      '#datepicker_options' => [
        'maxDate' => 0
        ],
      '#required' => TRUE,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University/Institute'),
      '#size' => 200,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#default_value' => $proposal_data->university,
    ];
    $form['department'] = [
      '#type' => 'textfield',
      '#title' => t('Department / Branch'),
      '#options' => _cm_list_of_departments(),
      '#default_value' => $proposal_data->department,
    ];
    $form['other_department'] = [
      '#type' => 'textfield',
      '#title' => t('Department/Branch name not in list'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your department name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="department"]' => [
            'value' => 'Others'
            ]
          ]
        ],
      '#default_value' => $proposal_data->other_department,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#default_value' => $proposal_data->country,
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      '#size' => 100,
      '#default_value' => $proposal_data->country,
      '#attributes' => [
        'placeholder' => t('Enter your country name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => t('State other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your state/region name')
        ],
      '#default_value' => $proposal_data->state,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => _cm_list_of_states(),
      '#default_value' => $proposal_data->state,
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#options' => _cm_list_of_cities(),
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#default_value' => $proposal_data->pincode,
      '#attributes' => [
        'placeholder' => 'Insert pincode of your city/ village....'
        ],
    ];
    $form['version'] = [
      '#type' => 'textfield',
      '#title' => t('DWSIM Version'),
      '#options' => _list_of_software_versions(),
      '#default_value' => $proposal_data->version,
    ];
    $form['project_title'] = [
      '#type' => 'textarea',
      '#title' => t('Title of the Custom Model'),
      '#size' => 300,
      '#maxlength' => 350,
      '#required' => TRUE,
      '#default_value' => $proposal_data->project_title,
    ];
    $form['reference'] = [
      '#type' => 'textfield',
      '#title' => t('Reference'),
      '#size' => 10000,
      '#attributes' => [
        'placeholder' => 'Enter Reference'
        ],
      '#default_value' => $proposal_data->reference,
    ];
    $form['script_used'] = [
      '#type' => 'textfield',
      '#title' => t('Script used to create the Custom Model'),
      /*'#options' => array(
			'Scilab' => 'Scilab',
			'IronPython' => 'IronPython'
		),*/
      '#default_value' => $proposal_data->script_used,
    ];
    /*$form['samplefile'] = array(
		'#type' => 'fieldset',
		'#title' => t('Upload Abstract of Custom Model'),
		'#collapsible' => FALSE,
		'#collapsed' => FALSE
	);*/
    $form/*['samplefile']*/['samplefilepath'] = [
      '#type' => 'textfield',
      '#title' => t('Uploaded Abstract of Custom Model'),
      //'#title' => t('Upload circuit diagram'),
		'#default_value' => $proposal_data->samplefilepath,
    ];

    $form['delete_proposal'] = [
      '#type' => 'checkbox',
      '#title' => t('Delete Proposal'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      // '#markup' => l(t('Cancel'), 'custom-model/manage-proposal'),
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'),
        Url::fromUri('internal:/custom-model/manage-proposal/all'))->toString(),
          ];
    
    return $form;
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
        return new RedirectResponse('/custom-model/manage-proposal/all');
        // return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
      // drupal_goto('custom-model/manage-proposal');
      return new RedirectResponse('/custom-model/manage-proposal/all');

      return;
    }
    /* delete proposal */
    if ($form_state->getValue(['delete_proposal']) == 1) {
      /* sending email */
      // $user_data = User::load($proposal_data->uid);
      // $email_to = $user_data->mail;
      // $from = variable_get('custom_model_from_email', '');
      // $bcc = variable_get('custom_model_emails', '');
      // $cc = variable_get('custom_model_cc_emails', '');
      // $params['custom_model_proposal_deleted']['proposal_id'] = $proposal_id;
      // $params['custom_model_proposal_deleted']['user_id'] = $proposal_data->uid;
      // $params['custom_model_proposal_deleted']['headers'] = [
      //   'From' => $from,
      //   'MIME-Version' => '1.0',
      //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      //   'Content-Transfer-Encoding' => '8Bit',
      //   'X-Mailer' => 'Drupal',
      //   'Cc' => $cc,
      //   'Bcc' => $bcc,
      // ];
      // if (!drupal_mail('custom_model', 'custom_model_proposal_deleted', $email_to, user_preferred_language($user), $params, $from, TRUE)) {
      //   \Drupal::messenger()->addMessage('Error sending email message.', 'error');
      // }
      \Drupal::messenger()->addMessage(t('Custom model proposal has been deleted.'), 'status');
      if (\Drupal::service("custom_model_global")->cm_rrmdir_project($proposal_id) == TRUE) {
        $query = \Drupal::database()->delete('custom_model_proposal');
        $query->condition('id', $proposal_id);
        $num_deleted = $query->execute();
        \Drupal::messenger()->addMessage(t('Proposal Deleted'), 'status');
        // drupal_goto('custom-model/manage-proposal');
        return new RedirectResponse('/custom-model/manage-proposal/all');

        return;
      } //rrmdir_project($proposal_id) == TRUE
    } //$form_state['values']['delete_proposal'] == 1
	/* update proposal */
    $v = $form_state->getValues();
    $project_title = $v['project_title'];
    $proposar_name = $v['name_title'] . ' ' . $v['contributor_name'];
    $university = $v['university'];
    $directory_names = _cm_dir_name($project_title, $proposar_name);
    if (cm_RenameDir($proposal_id, $directory_names)) {
      $directory_name = $directory_names;
    } //LM_RenameDir($proposal_id, $directory_names)
    else {
      return;
    }
    $str = substr($proposal_data->samplefilepath, strrpos($proposal_data->samplefilepath, '/'));
    $resource_file = ltrim($str, '/');
    $samplefilepath = $directory_name . '/' . $resource_file;
    $query = "UPDATE custom_model_proposal SET 
				name_title=:name_title,
				contributor_name=:contributor_name,
				university=:university,
				city=:city,
				pincode=:pincode,
				state=:state,
				project_title=:project_title,
				directory_name=:directory_name ,
				samplefilepath=:samplefilepath
				WHERE id=:proposal_id";
    $args = [
      ':name_title' => $v['name_title'],
      ':contributor_name' => $v['contributor_name'],
      ':university' => $v['university'],
      ':city' => $v['city'],
      ':pincode' => $v['pincode'],
      ':state' => $v['all_state'],
      ':project_title' => $project_title,
      ':directory_name' => $directory_name,
      ':samplefilepath' => $samplefilepath,
      ':proposal_id' => $proposal_id,
    ];
    $result = \Drupal::database()->query($query, $args);
    \Drupal::messenger()->addMessage(t('Proposal Updated'), 'status');
        // RedirectResponse('lab-migration/manage-proposal');
        $response = new RedirectResponse(Url::fromRoute('custom_model.proposal_all')->toString());
  
        // //   // Send the redirect response
          $response->send();
      
  }
  

}

