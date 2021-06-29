<?php

namespace Drupal\ecole;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface JobStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Job revision IDs for a specific Job.
   *
   * @param \Drupal\ecole\Entity\JobInterface $entity
   *   The Job entity.
   *
   * @return int[]
   *   Job revision IDs (in ascending order).
   */
  public function revisionIds(JobInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Job author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Job revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\ecole\Entity\JobInterface $entity
   *   The Job entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(JobInterface $entity);

  /**
   * Unsets the language for all Job with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
