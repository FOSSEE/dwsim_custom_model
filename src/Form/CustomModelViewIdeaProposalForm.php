<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\CustomModelViewIdeaProposalForm.
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


class CustomModelViewIdeaProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_view_idea_proposal_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('id');
    $query = \Drupal::database()->select('custom_model_idea_proposal');
    $query->fields('custom_model_idea_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
        // drupal_goto('custom-model/manage-proposal');
        

        $response = new RedirectResponse('/custom-model/manage-proposal/view-ideas/');
$response->send();
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
      // drupal_goto('custom-model/manage-proposal');
      $response = new RedirectResponse('/custom-model/manage-proposal/view-ideas/');
      $response->send();
      return;
    }
    if ($proposal_data->reference_link) {
      $reference_link = $proposal_data->reference_link;
    }
    else {
      $reference_link = 'None';
    }
    if ($proposal_data->reference_file) {
      // $reference_file = l($proposal_data->reference_file, 'custom-model/download/idea-reference-file/' . $proposal_data->id);
      $reference_file = Link::fromTextAndUrl(
        $proposal_data->reference_file,
        Url::fromUri('internal:/custom-model/download/idea-reference-file/' . $proposal_data->id)
      )->toString();

    }
    else {
      $reference_file = 'None';
    }
    // $form['contributor_name'] = [
    //   '#type' => 'item',
    //   '#markup' => l($proposal_data->name_title . ' ' . $proposal_data->idea_proposar_name, 'user/' . $proposal_data->uid),
    //   '#title' => t('Student name'),
    // ];
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $proposal_data->name_title . ' ' . $proposal_data->idea_proposar_name,
        Url::fromUri('internal:/user/' . $proposal_data->uid)
      )->toString(),
      '#title' => $this->t('Student name'),
    ];
    $form['student_email_id'] = [
      '#title' => t('Student Email'),
      '#type' => 'item',
      // '#markup' => User::load($proposal_data->uid)->getEmail(),
      '#title' => t('Email'),
    ];
    $form['student_email_id'] = [
      '#title' => $this->t('Student Email'),
      '#type' => 'item',
      // '#markup' => User::load($proposal_data->uid)->getEmail(),
    ];
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
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Custom Model'),
    ];

    $form['reference_link'] = [
      '#type' => 'item',
      '#markup' => $reference_link,
      '#title' => t('Any Reference Web Link'),
    ];
    $form['reference_file'] = [
      '#type' => 'item',
      '#markup' => $reference_file,
      '#title' => t('Any Reference File'),
    ];
   
    $form['cancel'] = [
      '#type' => 'markup',
      // '#markup' =>Link::fromTextAndUrl(t('Cancel'), 'lab-migration/manage-proposal'),
      '#markup' => Link::fromTextAndUrl(
  $this->t('Cancel'),
  Url::fromUri('internal:/custom-model/manage-proposal/idea-proposals'))->toString(),

    ];
    return $form;
  }
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

}
}

