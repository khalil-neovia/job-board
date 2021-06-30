<?php

namespace Drupal\ecole\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\ecole\Entity\JobInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class JobController.
 *
 *  Returns responses for Job routes.
 */
class JobController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Job revision.
   *
   * @param int $job_revision
   *   The Job revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($job_revision) {
    $job = $this->entityTypeManager()->getStorage('job')
      ->loadRevision($job_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('job');

    return $view_builder->view($job);
  }

  /**
   * Page title callback for a Job revision.
   *
   * @param int $job_revision
   *   The Job revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($job_revision) {
    $job = $this->entityTypeManager()->getStorage('job')
      ->loadRevision($job_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $job->label(),
      '%date' => $this->dateFormatter->format($job->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Job.
   *
   * @param \Drupal\ecole\Entity\JobInterface $job
   *   A Job object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(JobInterface $job) {
    $account = $this->currentUser();
    $job_storage = $this->entityTypeManager()->getStorage('job');

    $langcode = $job->language()->getId();
    $langname = $job->language()->getName();
    $languages = $job->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $job->label()]) : $this->t('Revisions for %title', ['%title' => $job->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all job revisions") || $account->hasPermission('administer job entities')));
    $delete_permission = (($account->hasPermission("delete all job revisions") || $account->hasPermission('administer job entities')));

    $rows = [];

    $vids = $job_storage->revisionIds($job);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\ecole\JobInterface $revision */
      $revision = $job_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $job->getRevisionId()) {
          $link = $this->l($date, new Url('entity.job.revision', [
            'job' => $job->id(),
            'job_revision' => $vid,
          ]));
        }
        else {
          $link = $job->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.job.translation_revert', [
                'job' => $job->id(),
                'job_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.job.revision_revert', [
                'job' => $job->id(),
                'job_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.job.revision_delete', [
                'job' => $job->id(),
                'job_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['job_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }
  public function print_job($value,$output_format=0){
		$node_storage = \Drupal::entityTypeManager()->getStorage('job');
		$entity = $node_storage->load($value);
		$field_list=['name'=>"Job Name",'description'=> "Job Description", 'jobdate'=>"Start Date", 'salary'=>"Salary", 'town'=>"Location"];
		if($output_format==0){
			$output="Job n°".$entity->id()." :<ul>";
			foreach($field_list as $key=>$my_value){
				if(isset($entity->get($key)->value)){
					$output.="<li><strong>".
					$my_value.
					"</strong>: ".
					$entity->get($key)->value .
					"</li>";
				}
			}
			$output.="</ul>";	
		}
		elseif($output_format==1){
			$output="Job n°".$entity->id()." :";
			foreach($field_list as $key=>$my_value){
				if(isset($entity->get($key)->value)){
					$output.=$my_value.": ".$entity->get($key)->value.". ";
				}
			}
			
		}
		else{
			$output="";	
		}

		return($output);
  }

  
  public function manageentities() {

	$query = \Drupal::entityQuery('job');


	// Use conditions to get a list of published articles.
	$node_ids = $query->execute();
	$output="<h3>Use Case: Searching all jobs:</h3>";
	foreach($node_ids as $key => $value){
		
		$output.="<p>".$this->print_job($value)."</p>";

	}
	$duration='9 day';
	$output.="<h3>Use Case: Searching within a period of ".$duration."(s):</h3>";

	$date = new DrupalDateTime($duration);
	$date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
	$formatted = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
	
	$query = \Drupal::entityQuery('job')
	->condition('jobdate', $formatted, '<=')
	->execute();
  
	foreach($query as $key => $value){
		
		$output.="<p>".$this->print_job($value)."</p>";

	}
	$output.="<h3>Use Case: Searching a range of salary:</h3>";
	
	$query = \Drupal::entityQuery('job')
	->condition('salary', 2000, '>=')
	->condition('salary', 2800, '<=')
	->execute();	
	
	foreach($query as $key => $value){
		
		$output.="<p>".$this->print_job($value)."</p>";

	}
	
	//$query->condition('title', '%drupal%','LIKE');
	$output.="<h3>Use Case: Searching a specefic text:</h3>";
	
	$keywords="preparation";
	$query = \Drupal::entityQuery('job')
	//->condition('description', "preparation", 'LIKE')
	//->fieldCondition('field_sku', 'value','%'. $keywords .'%', 'LIKE') 
	//->fieldCondition('description', 'value','%'. $keywords .'%', 'LIKE')
	//->condition('description', 'value','%'. $keywords .'%', 'LIKE')
	->condition('description.value','%'. $keywords .'%', 'LIKE')
	->execute();	
	
	foreach($query as $key => $value){
		
		$output.="<p>".$this->print_job($value)."</p>";

	}
	
	
	
	return array(
	  '#type' => 'markup',
	  '#markup' => $output,
	);
  }
  public function search_for_job($keywords){
	$query = \Drupal::entityQuery('job');
  	$group = $query->orConditionGroup()
	->condition('name.value','%'. $keywords .'%', 'LIKE')
	->condition('town.value','%'. $keywords .'%', 'LIKE');
  	
  	$result=$query->condition($group)->execute();	
	
  	return($result);
  
  }
  
}
