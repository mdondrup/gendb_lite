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



  
  public function geneInfo(int $id) {

      $content = [];
       $rows = [];
    $headers = [
      [ 'data' => $this->t('Name') , 'field' => 'f.name'],
      [ 'data' => $this->t('Unique name'),'field' => 'f.uniquename'],
      [ 'data' => $this->t('Feature type'), 'field' => 't.type'],
      
      [ 'data' => $this->t('Organism')],
      ['feature_id']
    ];

      
      $content['#plain_text'] = 'Looking up gene_id ' . $id;
      $entry = $this->repository->getFeatureInfoById($id);
      // Sanitize each entry.
        $entry = array_map('Drupal\Component\Utility\Html::escape', (array) $entry);
        $url = Url::fromRoute('gendb_lite.default', ['chadotype' => 'organism', 'id' => $entry['organism_id']]);
        $residues = $entry['residues'];       
        $entry['organism'] = Link::fromTextAndUrl($entry['genus'].' '. $entry['species'], $url);
        unset($entry['genus']);
        unset($entry['species']);
        unset($entry['organism_id']);
        unset($entry['residues']);
        
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
      '#markup' => $this->t('Display feature information'),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      
      '#disabled' => 'disabled',
      '#value' => $entry['name'],      
    ];
   $form['residues'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Residues'),
      
      '#disabled' => 'disabled',
      '#value' => $residues,      
    ];
     $form['sequence'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sequence from Alignment'),
      
      '#disabled' => 'disabled',
      '#value' => var_export( $this->repository->getSeqRecursive($id), true),      
    ];
    $content['form'] = $form;
    
    return $content;
      
  }

  


  public function defaultController(string $chadotype, int $id) {

      $content = [];
      $rows = [];
      $content['#markup'] = 'Looking up Chado content: '. $chadotype . " ". $id;
      $entry = $this->repository->defaultLookup($chadotype, $id);

            $entry = array_map('Drupal\Component\Utility\Html::escape', (array) $entry);

      $rows[] = $entry;
      #$content['#plain_text'] = var_dump($entry);

      $content['table'] = [
          '#type' => 'table',
          '#header' => array_keys((array)$entry),
          '#rows' => $rows,
          '#empty' => $this->t('No entries available.'),
    ];
      
      return $content;
  }  
  
 
}
