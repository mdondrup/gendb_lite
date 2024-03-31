<?php
namespace Drupal\gendb_lite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gendb_lite\GenDBRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;


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
      $this->t('Name'),
      $this->t('Unique name'),
      $this->t('Feature type'),
    ];

    $entries = $this->repository->load();

    foreach ($entries as $entry) {
      // Sanitize each entry.
      $rows[] = array_map('Drupal\Component\Utility\Html::escape', (array) $entry);
    }
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }
  
 
}
