<?php

namespace Drupal\dwsim_custom_model\Services;

/**
 * Reusable mail service for dwsim_custom_model module.
 *
 * Centralises repeated drupal_mail() calls and header array
 * construction found across manage_proposal.inc,
 * abstract_submission_bulk_approval.inc, and upload_code.inc.
 *
 * Usage (procedural, from any .inc file):
 *   $mailer = new \Drupal\dwsim_custom_model\Services\DwsimMailService();
 *   $mailer->send('custom_model_proposal_approved', $email_to, [
 *     'proposal_id' => $proposal_id,
 *     'user_id'     => $uid,
 *   ]);
 */
class DwsimMailService {

  /**
   * Sends a dwsim_custom_model mail using drupal_mail().
   *
   * Builds the standard MIME headers from module variables and
   * delegates to the existing hook_mail() in email.inc.
   *
   * @param string $key       Mail key matching a case in custom_model_mail().
   * @param string $to        Recipient email address.
   * @param array  $data      Key-value data passed under $params[$key].
   * @param string $bcc_extra Additional BCC addresses (comma-separated).
   *
   * @return bool  TRUE if drupal_mail() reported success.
   */
  public function send($key, $to, array $data, $bcc_extra = '') {
    global $user;

    $from = variable_get('custom_model_from_email', '');
    $cc   = variable_get('custom_model_cc_emails', '');

    // Build BCC: module setting + optional caller-supplied address.
    $bcc_base = variable_get('custom_model_emails', '');
    $bcc = $bcc_extra ? trim($bcc_extra . ', ' . $bcc_base, ', ') : $bcc_base;

    // Common mail headers.
    $data['headers'] = $this->buildHeaders($from, $cc, $bcc);

    $params[$key] = $data;

    $result = drupal_mail('custom_model', $key, $to, language_default(), $params, $from, TRUE);

    if (!$result) {
      drupal_set_message(t('Error sending email message.'), 'error');
    }

    return (bool) $result;
  }

  /**
   * Sends a plain "standard" mail (subject/body provided by caller).
   *
   * Matches the 'standard' case in hook_mail().
   *
   * @param string $to      Recipient email address.
   * @param string $subject Mail subject string.
   * @param array  $body    Mail body array (Drupal convention).
   *
   * @return bool
   */
  public function sendStandard($to, $subject, array $body) {
    $from = variable_get('custom_model_from_email', '');
    $cc   = variable_get('custom_model_cc_emails', '');
    $bcc  = variable_get('custom_model_emails', '');

    $params['standard']['subject'] = $subject;
    $params['standard']['body']    = $body;
    $params['standard']['headers'] = $this->buildHeaders($from, $cc, $bcc);

    $result = drupal_mail('custom_model', 'standard', $to, language_default(), $params, $from, TRUE);

    if (!$result) {
      drupal_set_message(t('Error sending email message.'), 'error');
    }

    return (bool) $result;
  }

  /**
   * Builds the standard MIME headers array used across all mail sends.
   *
   * @param string $from  Sender address.
   * @param string $cc    CC address(es).
   * @param string $bcc   BCC address(es).
   *
   * @return array
   */
  public function buildHeaders($from, $cc = '', $bcc = '') {
    return [
      'From'                     => $from,
      'MIME-Version'             => '1.0',
      'Content-Type'             => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer'                 => 'Drupal',
      'Cc'                       => $cc,
      'Bcc'                      => $bcc,
    ];
  }

}
