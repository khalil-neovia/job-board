<?php

namespace Drupal\ecole\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ecole\Controller\JobController;

/**
 * Class JobsearchForm.
 */
class JobsearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jobsearch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['job_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Job Title'),
	  '#required' => true,
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //// Display result.
    //foreach ($form_state->getValues() as $key => $value) {
    //  \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
    //}
	$job = new JobController() ;
	$job_list=$job->search_for_job($form_state->getValues()["job_title"]);
	
	
	if(count($job_list)>0){
		foreach ($job_list as $key => $value) {
			//\Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
			\Drupal::messenger()->addMessage($job->print_job($value,1));	  
		}		
	}
	else{
		\Drupal::messenger()->addMessage("No results found.");
	}
  }

}
