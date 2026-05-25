<?php

namespace Drupal\dwsim_custom_model\Services;

/**
 * Reusable AJAX helper for dwsim_custom_model module.
 *
 * Wraps common Drupal 7 ajax_command_* calls to avoid
 * duplicate callback code across .inc files.
 *
 * Usage (procedural, from any .inc file):
 *   $helper = new \Drupal\dwsim_custom_model\Services\DwsimAjaxHelper();
 *   $commands = $helper->htmlCommand('#my-div', $html);
 *   return $helper->ajaxResponse($commands);
 */
class DwsimAjaxHelper {

  /**
   * Returns an ajax_command_html entry for a given selector and markup.
   *
   * @param string $selector  CSS selector to target.
   * @param string $html      HTML markup to insert.
   *
   * @return array
   */
  public function htmlCommand($selector, $html) {
    return ajax_command_html($selector, $html);
  }

  /**
   * Returns an ajax_command_replace entry for a given selector and markup.
   *
   * @param string $selector  CSS selector to replace.
   * @param string $html      HTML markup to replace with.
   *
   * @return array
   */
  public function replaceCommand($selector, $html) {
    return ajax_command_replace($selector, $html);
  }

  /**
   * Returns an ajax_command_data entry to attach arbitrary data to an element.
   *
   * @param string $selector  CSS selector to target.
   * @param string $name      Data attribute name.
   * @param mixed  $value     Data value.
   *
   * @return array
   */
  public function dataCommand($selector, $name, $value) {
    return ajax_command_data($selector, $name, $value);
  }

  /**
   * Builds a standard Drupal AJAX response array from a commands list.
   *
   * @param array $commands  Array of ajax_command_* entries.
   *
   * @return array
   */
  public function ajaxResponse(array $commands) {
    return [
      '#type'     => 'ajax',
      '#commands' => $commands,
    ];
  }

  /**
   * Convenience: replace a target element with rendered form element markup.
   *
   * Common pattern used in bulk approval AJAX callbacks.
   *
   * @param string $selector      CSS selector to replace.
   * @param array  $form_element  Drupal form element array to render.
   *
   * @return array  ajax_command_replace entry.
   */
  public function replaceFormElement($selector, array $form_element) {
    return ajax_command_replace($selector, drupal_render($form_element));
  }

}
