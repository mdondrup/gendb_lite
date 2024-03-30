<?php
namespace Drupal\gendb_lite\Controller;

use Drupal\Core\Controller\ControllerBase;


 function gene_count() {

  $sql = "SELECT COUNT(DISTINCT f.feature_id) FROM chado.feature AS f LEFT JOIN chado.f_type AS t ON f.type_id = t.type_id WHERE t.type = 'gene'";
  $query = \Drupal::database()->query($sql);
  
  
  return($query->fetch()->count);
}


/**
 * Provides route responses for the Example module.
 */
class ExampleController extends ControllerBase {


  
  /** 
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function myPage() {
    return [
      '#markup' => 'Checking for genes in Chado...</br> Found '. gene_count() .' genes.',
    ];
  }

}
