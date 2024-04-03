<?php
namespace Drupal\gendb_lite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gendb_lite\GenDBRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Routing;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
   * Returns a simple welcome page. Displays the number of different features per type.
   *
   * @return array
   *   A simple renderable array.
   */
  public function myPage() {
      $content=[];
      $headers = ['Feature type', 'Count'];
      $rows = [];
      
      $content['message'] =
          ['#markup' => 'Number of features types in the Chado DB...</br>'];
      $entries = $this->repository->countFeatureTypes();
      foreach ($entries as $entry) {
          // Sanitize each entry.
          $entry = array_map('Drupal\Component\Utility\Html::escape', (array) $entry);
          $rows[] = $entry;        
      }
      
      $content['table'] =
          [
              '#type' => 'table',
              '#header' => $headers,
              '#rows' => $rows,
              '#empty' => $this->t('No features found'),
          ];
      return $content;
  }

	public function featureList() {
  
      $content = [];
      //Get parameter value while submitting filter form  
	  $fname = \Drupal::request()->query->get('fname');
  	   $funame = \Drupal::request()->query->get('funame');
   $ftype = \Drupal::request()->query->get('ftype');

  
	
	  //====load filter controller
	  $content['form'] = $this->formBuilder()->getForm('Drupal\gendb_lite\Form\FeatureFilterForm');    

       $content['message'] =
           [
      '#markup' => $this->t('Generate a list of all entries in the database.'),
    ];

    $rows = [];
    $headers = [
      [ 'data' => $this->t('Name') , 'field' => 'f.name'],
      [ 'data' => $this->t('Unique name'),'field' => 'f.uniquename'],
      [ 'data' => $this->t('Feature type'), 'field' => 't.type']
    ];

    $entries = $this->repository->load(
        ['f.name' => ['value' => $fname, 'operator' => 'contains'],
        'f.uniquename' =>  ['value' => $funame, 'operator' => 'contains'],
        't.type' => ['value'=>$ftype, 'operator' => 'LIKE' ]],
        $headers,);

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


  /**
  * Display basig info and sequence for any feature (albeit the name) 
  */



  
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
        unset($entry['feature_id']);
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

    $seqs = $this->repository->getSeq($id);

    foreach ($seqs as $srcname => $myseq) {
     $form['sequence_'.$srcname] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sequence from Alignment against '. $srcname),      
      '#disabled' => 'disabled',
      '#value' => $myseq,      
     ];
    } 
    $content['form'] = $form;
    
    return $content;
      
  }

  /**
  * A simple default controller method that should work with most chado tables.
  * Lists all fields from the storage table for a given object_id.
  *
  */


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
