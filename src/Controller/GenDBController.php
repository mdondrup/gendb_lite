<?php
namespace Drupal\gendb_lite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gendb_lite\GenDBRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;





/**
* Counts the number of gene entries in Chado DB, should be move to utility functions
*/

 function gene_count() {
  $sql = "SELECT COUNT(DISTINCT f.feature_id) FROM chado.feature AS f LEFT JOIN chado.f_type AS t ON f.type_id = t.type_id WHERE t.type = 'gene'";
  $query = \Drupal::database()->query($sql); 
  return($query->fetch()->count);
}


/**
 * Provides route responses for the Example module.
 */
class GenDBController extends ControllerBase {
    
 /**
   * The repository for our specialized queries.
 */

 protected $repository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = new static($container->get('gendb_lite.repository'));
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }

  /**
   * Construct a new controller.
   *
   * @param \Drupal\gendb_lite\GenDBRepository $repository
   *   The repository service.
   */
  public function __construct(GenDBRepository $repository) {
    $this->repository = $repository;
  }
 
      
  /** 
   * Returns a simple welcome page. Counts the genes
   *
   * @return array
   *   A simple renderable array.
   */
  public function myPage() {
    return [
      '#markup' => 'Counting genes in Chado...</br> Found '. gene_count() .' genes.',
    ];
  }

  public function featureList() {
       $content = [];

    $content['message'] = [
      '#markup' => $this->t('Generate a list of all entries in the database. There is no filter in the query.'),
    ];

    $rows = [];
    $headers = [
      [ 'data' => $this->t('Name') , 'field' => 'f.name'],
      [ 'data' => $this->t('Unique name'),'field' => 'f.uniquename'],
       [ 'data' => $this->t('Feature type'), 'field' => 't.type']
    ];

    $entries = $this->repository->load([], $headers);

    foreach ($entries as $entry) {
      // Sanitize each entry.
        $entry = array_map('Drupal\Component\Utility\Html::escape', (array) $entry);
        $url = Url::fromRoute('gendb_lite.gene', ['id' => $entry['feature_id']]);
       
        $entry['name'] = Link::fromTextAndUrl($entry['name'], $url);
        unset ($entry['feature_id']);
        $rows[] = $entry;
        
    }
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
    ];
 // Add our pager element so the user can choose which pagination to see.
    // This will add a '?page=1' fragment to the links to subsequent pages.
    $content['pager'] = [
      '#type' => 'pager',
      '#weight' => 10,
    ];

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

function hook__form_FORM_ID_alter(&$form,\Drupal\Core\Form\FormStateInterface 
$form_state, $form_id) {
//output your form structure to know what to target in the form array($form[])
#kint( $form['title']);
$form['title']['#disabled'] = TRUE;

}

  
  public function geneInfo(string $id) {

      $content = [];
       $rows = [];
    $headers = [
      [ 'data' => $this->t('Name') , 'field' => 'f.name'],
      [ 'data' => $this->t('Unique name'),'field' => 'f.uniquename'],
      [ 'data' => $this->t('Feature type'), 'field' => 't.type'],
      ['feature_id']
    ];

      
      $content['#plain_text'] = 'Looking up gene_id ' . $id;
      $entry = $this->repository->getFeatureInfoById($id);
      // Sanitize each entry.
        $entry = array_map('Drupal\Component\Utility\Html::escape', (array) $entry);
        #$url = Url::fromRoute('gendb_lite.gene', ['id' => $entry['feature_id']]);
       
        #$entry['name'] = Link::fromTextAndUrl($entry['name'], $url);
        
        $rows[] = $entry;
        
    
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
    ];

    $form = [];    
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This basic example shows a single text input element and a submit button'),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      
      '#disabled' => 'disabled',
      '#value' => $entry['name'],
      
    ];
    $content['form'] = $form;
        
      return $content;
      
  }

  
 
}
