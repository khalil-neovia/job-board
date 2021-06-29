<?php

namespace Drupal\ecole;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\ecole\Entity\JobInterface;

/**
 * Defines the storage handler class for Job entities.
 *
 * This extends the base storage class, adding required special handling for
 * Job entities.
 *
 * @ingroup ecole
 */
class JobStorage extends SqlContentEntityStorage implements JobStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(JobInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {job_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {job_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(JobInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {job_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('job_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
