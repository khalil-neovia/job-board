<?php

namespace Drupal\ecole;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Job entity.
 *
 * @see \Drupal\ecole\Entity\Job.
 */
class JobAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ecole\Entity\JobInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished job entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published job entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit job entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete job entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add job entities');
  }


}
