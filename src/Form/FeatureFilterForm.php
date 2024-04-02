<?php
namespace Drupal\gendb_lite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;

/**
 * Provides the form for filter Students.
 */
class FeatureFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gendb_lite_feature_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      //Get previous parameter values to fill in 
	  $fname = \Drupal::request()->query->get('fname');
      $funame = \Drupal::request()->query->get('funame');
      $ftype = \Drupal::request()->query->get('ftype');

    $form['filters'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Filter'),
        '#open'  => true,
    ];

   $form['filters']['fname'] = [
       '#title'         => 'Gene name',
       '#type'          => 'search',
	   '#description' => 'Enter any part of a gene name to search for',
    '#default_value' => $fname
    ];
    
    $form['filters']['funame'] = [
        '#title'         => 'Unique name',
        '#type'          => 'search',
        '#description' => 'Enter any part of unique gene name to search for',
        '#default_value' => $funame

		
    ];
      $form['filters']['ftype'] = [
        '#title'         => 'Type',
        '#type'          => 'search',
        '#description' => 'Restrict feature type',
        '#default_value' => $ftype

		
    ];

    $form['filters']['actions'] = [
        '#type'       => 'actions'
    ];

    $form['filters']['actions']['submit'] = [
        '#type'  => 'submit',
        '#value' => $this->t('Filter')
		
    ];
   
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
     // there's no error state here, if nothing is entered, diplay all
      
     /** 
	 if ( $form_state->getValue('fname') == "") {
		$form_state->setErrorByName('from', $this->t(''));
     }elseif( $form_state->getValue('marks') == ""){
		 $form_state->setErrorByName('marks', $this->t(''));
	 }
     */
	 
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array & $form, FormStateInterface $form_state) {	  
	  $field = $form_state->getValues();
	  $fname = $field["fname"];
   $funame = $field["funame"];
   $ftype =$field["ftype"];
    $url = \Drupal\Core\Url::fromRoute('gendb_lite.list')
          ->setRouteParameters(array('fname'=>$fname,'funame'=>$funame, 'ftype' => $ftype));
    $form_state->setRedirectUrl($url); 
  }

}
