<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\CustomModelProposalForm.
 */

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomModelProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_proposal_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    /************************ start approve book details ************************/
    if ($user->isAnonymous()) {
      // $msg = \Drupal::messenger()->addError(t('This is an error message, red in color'));
      $url = Link::fromTextAndUrl(t('login'), Url::fromRoute('user.page'))->toString();
      $msg = \Drupal::messenger()->addmessage(t('It is mandatory to ' . Link::fromTextAndUrl(t('login'), Url::fromRoute('user.page'))->toString() . ' on this website to access the lab proposal form. If you are new user please create a new account first.'));
      // return $msg;

      $response = new RedirectResponse(Url::fromRoute('user.page')->toString());

  $response->send();
  return $msg;
    } //$user->uid == 0
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('uid', $user->id());
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if ($proposal_data) {
      if ($proposal_data->approval_status == 0 || $proposal_data->approval_status == 1) {
        \Drupal::messenger()->addMessage(t('We have already received your proposal.'), 'status');
        // drupal_goto('');
        return;
      } //$proposal_data->approval_status == 0 || $proposal_data->approval_status == 1
    } //$proposal_data
    $form['#attributes'] = [
      'enctype' => "multipart/form-data"
      ];
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
    ];
    $form['contributor_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the contributor'),
      '#size' => 250,
      '#attributes' => [
        'placeholder' => t('Enter your full name.....')
        ],
      '#maxlength' => 250,
      '#required' => TRUE,
    ];
    $form['contributor_contact_no'] = [
      '#type' => 'textfield',
      '#title' => t('Contact No.'),
      '#size' => 10,
      '#attributes' => [
        'placeholder' => t('Enter your contact number')
        ],
      '#maxlength' => 250,
      '#required' => TRUE,
    ];
    $form['contributor_email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 30,
      '#value' => $user ? $user->getEmail() : '', 
      '#disabled' => TRUE,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University / Institute / Organisation'),
      '#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Insert full name of your institute/ university.... '
        ],
    ];
    $form['department'] = [
      '#type' => 'select',
      '#title' => t('Department / Branch'),
      '#options' => _cm_list_of_departments(),
      '#required' => TRUE,
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
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      '#size' => 100,
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
      '#options' =>_cm_list_of_states(),
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
      '#size' => 6,
      '#required' => TRUE,
    ];
    /***************************************************************************/
    $form['hr'] = [
      '#type' => 'item',
      '#markup' => '<hr>',
    ];
    $form['version'] = [
      '#type' => 'select',
      '#title' => t('DWSIM Version'),
      '#options' => _list_of_software_versions(),
      '#required' => TRUE,
    ];
    $form['project_title'] = [
      '#type' => 'textarea',
      '#title' => t('Title of the Custom Model'),
      '#size' => 250,
      '#description' => t('Maximum character limit is 250'),
      '#required' => TRUE,
    ];
    $form['reference'] = [
      '#type' => 'textfield',
      '#title' => t('Reference'),
      '#size' => 10000,
      '#attributes' => [
        'placeholder' => 'Enter Reference'
        ],
      '#required' => TRUE,
    ];
    $form['script_used'] = [
      '#type' => 'select',
      '#title' => t('Script used to create the Custom Model'),
      '#options' => [
        'Scilab' => 'Scilab',
        'IronPython' => 'IronPython',
      ],
      '#required' => TRUE,
    ];
    $form['samplefile'] = [
      '#type' => 'fieldset',
      '#title' => t('Upload Abstract of Custom Model'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['samplefile']['samplefilepath'] = [
      '#type' => 'file',
      //'#title' => t('Upload circuit diagram'),
		'#size' => 48,
      '#description' => $this->t('Upload a document of about 1-2 pages explaining 
      the custom model you intend to create') . '<br />' . $this->t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('custom_model.settings')->get('resource_upload_extensions', '') . '</span>',
     

    ];
    $form['term_condition'] = [
      '#type' => 'checkboxes',
      '#title' => t('Terms And Conditions'),
      '#options' => [
        'status' => t('<a href="https://dwsim.fossee.in/custom-model/term-and-conditions" target="_blank">I agree to the Terms and Conditions</a>')
        ],
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $project_title = $form_state->getValue(['project_title']);
    /*$proposar_name = $form_state['values']['name_title'] . ' ' . $form_state['values']['contributor_name'];
	$directory_name = _df_dir_name($project_title, $proposar_name);*/
    //var_dump($directory_name);die;
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('project_title', $project_title);
    // $query->condition(db_or()->condition('approval_status', 1)->condition('approval_status', 3));
    // $result = $query->execute()->rowCount();
    //var_dump($result);die;
    if ($result >= 1) {
      $form_state->setErrorByName('project_title', t('Project title name already exists'));
      return;
    }
    if ($form_state->getValue(['term_condition']) == '1') {
      $form_state->setErrorByName('term_condition', t('Please check the terms and conditions'));
      // $form_state['values']['country'] = $form_state['values']['other_country'];
    } //$form_state['values']['term_condition'] == '1'
    if ($form_state->getValue([
      'country'
      ]) == 'Others') {
      if ($form_state->getValue(['other_country']) == '') {
        $form_state->setErrorByName('other_country', t('Enter country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_country'] == ''
      else {
        $form_state->setValue(['country'], $form_state->getValue([
          'other_country'
          ]));
      }
      if ($form_state->getValue(['other_state']) == '') {
        $form_state->setErrorByName('other_state', t('Enter state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_state'] == ''
      else {
        $form_state->setValue(['all_state'], $form_state->getValue([
          'other_state'
          ]));
      }
      if ($form_state->getValue(['other_city']) == '') {
        $form_state->setErrorByName('other_city', t('Enter city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_city'] == ''
      else {
        $form_state->setValue(['city'], $form_state->getValue(['other_city']));
      }
    } //$form_state['values']['country'] == 'Others'
    else {
      if ($form_state->getValue(['country']) == '0') {
        $form_state->setErrorByName('country', t('Select country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['country'] == ''
      if ($form_state->getValue([
        'all_state'
        ]) == '0') {
        $form_state->setErrorByName('all_state', t('Select state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['all_state'] == ''
      if ($form_state->getValue([
        'city'
        ]) == '0') {
        $form_state->setErrorByName('city', t('Select city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['city'] == ''
    }
    //Validation for project title
    $form_state->setValue(['project_title'], trim($form_state->getValue([
      'project_title'
      ])));
    if ($form_state->getValue(['project_title']) != '') {
      if (strlen($form_state->getValue(['project_title'])) > 250) {
        $form_state->setErrorByName('project_title', t('Maximum charater limit is 250 charaters only, please check the length of the project title'));
      } //strlen($form_state['values']['project_title']) > 250
      else {
        if (strlen($form_state->getValue(['project_title'])) < 10) {
          $form_state->setErrorByName('project_title', t('Minimum charater limit is 10 charaters, please check the length of the project title'));
        }
      } //strlen($form_state['values']['project_title']) < 10
    } //$form_state['values']['project_title'] != ''
    else {
      $form_state->setErrorByName('project_title', t('Project title shoud not be empty'));
    }
    if (isset($_FILES['files'])) {
      /* check if atleast one source or result file is uploaded */
      if (!($_FILES['files']['name']['samplefilepath'])) {
        $form_state->setErrorByName('samplefilepath', t('Please upload the abstract'));
      }
      /* check for valid filename extensions */
    //   foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
    //     if ($file_name) {
    //       $allowed_extensions_str = \Drupal::config('custom_model.settings')->get('resource_upload_extensions', '');
    //       $allowed_extensions = explode(',', $allowed_extensions_str);
    //       $fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
    //       $temp_extension = end($fnames);
    //       if (!in_array($temp_extension, $allowed_extensions)) {
    //         $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
    //       }
    //       if ($_FILES['files']['size'][$file_form_name] <= 0) {
    //         $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
    //       }
    //       /* check if valid file name */
    //       if (!custom_model_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
    //         $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
    //       }
    //     } //$file_name
    //   } //$_FILES['files']['name'] as $file_form_name => $file_name
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        if (strstr($file_form_name, 'syllabus')) {
          $file_type = 'S';
        }
        else {
          $file_type = 'U';
        }
        $allowed_extensions_str = '';
        switch ($file_type) {
          case 'S':
            $allowed_extensions_str = \Drupal::config('custom_model.settings')->get('lab_migration_syllabus_file_extensions');
            break;
        } //$file_type
        $allowed_extensions = explode(',', $allowed_extensions_str);
        $allowd_file = strtolower($_FILES['files']['name'][$file_form_name]);
        $allowd_files = explode('.', $allowd_file);
        $temp_extension = end($allowd_files);
        // if (!in_array($temp_extension, $allowed_extensions)) {
        //   $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
        // }
        if ($_FILES['files']['size'][$file_form_name] <= 0) {
          $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
        }
      }
    }
    }
    return $form_state;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();

    if (!$user->id()) {
      \Drupal::messenger()->addmessage('It is mandatory to login on this website to access the proposal form');
      return;
    }

  
	/*if ($form_state['values']['version'] == 'Old version')
	{
		$form_state['values']['version'] = trim($form_state['values']['older']);
	} *///$form_state['values']['version'] == 'Old version'
	/* inserting the user proposal */
    if ($form_state->getValue(['department']) == 'Others') {
      $form_state->setValue(['department'], $form_state->getValue(['other_department']));
    }
    if ($form_state->getValue(['country']) == 'Others') {
      $form_state->setValue(['country'], $form_state->getValue(['other_country']));
      $form_state->setValue(['all_state'], $form_state->getValue(['other_state']));
      $form_state->setValue(['city'], $form_state->getValue(['other_city']));
      //$form_state['values']['pincode'] = $form_state['values']['other_pincode'];
    }

    $v = $form_state->getValues();
    $project_title = trim($v['project_title']);
    $proposar_name = $v['name_title'] . ' ' . $v['contributor_name'];
    $university = $v['university'];
    
    $directory_name = _cm_dir_name($project_title, $proposar_name);
    // var_dump($root_path); die;
    
    $result = "INSERT INTO {custom_model_proposal} 
    (
    uid, 
    approver_uid,
    name_title, 
    contributor_name,
    contact_no,
    university,
    department,
    city, 
    pincode, 
    state, 
    country,
    version,
    project_title, 
    script_used,
    directory_name,
    samplefilepath,
    approval_status,
    is_completed, 
    dissapproval_reason,
    creation_date, 
    approval_date
    ) VALUES
    (
    :uid, 
    :approver_uid, 
    :name_title, 
    :contributor_name, 
    :contact_no,
    :university, 
    :department,
    :city, 
    :pincode, 
    :state,  
    :country,
    :version,
    :project_title, 
    :script_used,
    :directory_name,
    :samplefilepath,
    :approval_status,
    :is_completed, 
    :dissapproval_reason,
    :creation_date, 
    :approval_date
    )";
    $args = [
      // 'uid' =>  $user->get('uid')->value,
      'uid' => \Drupal::currentUser()->id(), 

      'approver_uid' => 0,
      'name_title' => $v['name_title'],
      'contributor_name' => trim($v['contributor_name']),
      'contact_no' => $v['contributor_contact_no'],
      'university' => $v['university'],
      'department' => $form_state->getValue(['department']),
      'city' => $v['city'],
      'pincode' => $v['pincode'],
      'state'=> $v['all_state'],
      'country' => $v['country'],
      'version' => $v['version'],
      'project_title' => $v['project_title'],
      'script_used' => $v['script_used'],
      'directory_name' => $directory_name,
      'samplefilepath' => "",
      'approval_status' => 0,
      'is_completed' => 0,
      'dissapproval_reason' => "NULL",
      'creation_date' => time(),
      'approval_date' => 0,
    ];
    	// var_dump($args);die;
    //var_dump($result);die;
    // var_dump($root_path);die;	

    $result1 = \Drupal::database()->query($result, $args, [
      'return' => Database::RETURN_INSERT_ID
      ]);
    //var_dump($args);die;

    $root_path = \Drupal::service('custom_model_global')->custom_model_path();
    $dest_path = $directory_name . '/';
    $dest_path1 = $root_path . $dest_path;
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    // var_dump($root_path);die;	
    /* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        //$file_type = 'S';
        if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          \Drupal::messenger()->addMessage(t("Error uploading file. File !filename already exists.", [
            '!filename' => $_FILES['files']['name'][$file_form_name]
            ]), 'error');
          //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
        } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
			/* uploading file */
        if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          $query = "UPDATE {custom_model_proposal} SET samplefilepath = :samplefilepath WHERE id = :id";
          $args = [
            ":samplefilepath" => $_FILES['files']['name'][$file_form_name],
            ":id" => $result1,
          ];

          $updateresult = \Drupal::database()->query($query, $args);
          //var_dump($args);die;

          \Drupal::messenger()->addMessage($file_name . ' uploaded successfully.', 'status');
        } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
        else {
          \Drupal::messenger()->addMessage('Error uploading file : ' . $dest_path . '/' . $file_name, 'error');
        }
      } //$file_name
    } //$_FILES['files']['name'] as $file_form_name => $file_name
    if (!$result1) {
      \Drupal::messenger()->addMessage(t('Error receiving your proposal. Please try again.'), 'error');
      return;
    } 
    //!$proposal_id
	/* sending email */
    // $email_to = $user->mail;
    // $form = variable_get('custom_model_from_email', '');
    // $bcc = variable_get('custom_model_emails', '');
    // $cc = variable_get('custom_model_cc_emails', '');
    // $params['custom_model_proposal_received']['result1'] = $result1;
    // $params['custom_model_proposal_received']['user_id'] = $user->uid;
    // $params['custom_model_proposal_received']['headers'] = [
    //   'From' => $form,
    //   'MIME-Version' => '1.0',
    //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    //   'Content-Transfer-Encoding' => '8Bit',
    //   'X-Mailer' => 'Drupal',
    //   'Cc' => $cc,
    //   'Bcc' => $bcc,
    // ];
    // if (!drupal_mail('custom_model', 'custom_model_proposal_received', $email_to, user_preferred_language($user), $params, $form, TRUE)) {
    //   \Drupal::messenger()->addMessage('Error sending email message.', 'error');
    // }
    \Drupal::messenger()->addMessage(t('We have received your DWSIM Custom Model proposal. We will get back to you soon.'), 'status');
    // drupal_goto('');
    $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  
    // Send the redirect response
      $response->send();
  }

}

