<?php

/**
 * @file
 * Contains job.page.inc.
 *
 * Page callback for Job entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Job templates.
 *
 * Default template: job.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_job(array &$variables) {
  // Fetch Job Entity Object.
  $job = $variables['elements']['#job'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
