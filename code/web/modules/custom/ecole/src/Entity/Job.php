<?php

namespace Drupal\ecole\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Job entity.
 *
 * @ingroup ecole
 *
 * @ContentEntityType(
 *   id = "job",
 *   label = @Translation("Job"),
 *   handlers = {
 *     "storage" = "Drupal\ecole\JobStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ecole\JobListBuilder",
 *     "views_data" = "Drupal\ecole\Entity\JobViewsData",
 *     "translation" = "Drupal\ecole\JobTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\ecole\Form\JobForm",
 *       "add" = "Drupal\ecole\Form\JobForm",
 *       "edit" = "Drupal\ecole\Form\JobForm",
 *       "delete" = "Drupal\ecole\Form\JobDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ecole\JobHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ecole\JobAccessControlHandler",
 *   },
 *   base_table = "job",
 *   data_table = "job_field_data",
 *   revision_table = "job_revision",
 *   revision_data_table = "job_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer job entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/job/{job}",
 *     "add-form" = "/admin/structure/job/add",
 *     "edit-form" = "/admin/structure/job/{job}/edit",
 *     "delete-form" = "/admin/structure/job/{job}/delete",
 *     "version-history" = "/admin/structure/job/{job}/revisions",
 *     "revision" = "/admin/structure/job/{job}/revisions/{job_revision}/view",
 *     "revision_revert" = "/admin/structure/job/{job}/revisions/{job_revision}/revert",
 *     "revision_delete" = "/admin/structure/job/{job}/revisions/{job_revision}/delete",
 *     "translation_revert" = "/admin/structure/job/{job}/revisions/{job_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/job",
 *   },
 *   field_ui_base_route = "job.settings"
 * )
 */
class Job extends EditorialContentEntityBase implements JobInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly,
    // make the job owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Job entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Job entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Job is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);


    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Description.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

	$fields['jobdate'] = BaseFieldDefinition::create('datetime')
	->setLabel(t('Date'))
	->setDescription(t('Date.'))
	->setRevisionable(TRUE)
	->setSettings([
		'datetime_type' => 'date'
	])
	->setDefaultValue('')
	->setDisplayOptions('view', [
		'label' => 'above',
		'type' => 'datetime_default',
		'settings' => [
		'format_type' => 'medium',
		],
		'weight' => 20,
	])
	->setDisplayOptions('form', [
		'type' => 'datetime',
		'weight' => 20,
	])
	->setDisplayConfigurable('form', TRUE)
	->setDisplayConfigurable('view', TRUE);


    $fields['salary'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Salary'))
      ->setDescription(t('Job salary.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
	  
	$fields['salary'] = BaseFieldDefinition::create('integer')
			->setLabel(t('Salary'))
			->setDescription(t('Job salary.'))
			->setDisplayOptions('view', array(
				'label' => 'above',
				'weight' => 30,
			))
			->setDisplayOptions('form', array(
				'weight' => 30,
			))
			->setDisplayConfigurable('form', true)
			->setDisplayConfigurable('view', true);
			

    $fields['town'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Town'))
      ->setDescription(t('Job localization.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 40,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 40,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

	  
	  
	  
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
