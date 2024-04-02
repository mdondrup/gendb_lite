<?php

namespace Drupal\gendb_lite;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Repository for database-related helper methods for our example.
 *
 * This repository is a service named 'dbtng_example.repository'. You can see
 * how the service is defined in dbtng_example/dbtng_example.services.yml.
 *
 * For projects where there are many specialized queries, it can be useful to
 * group them into 'repositories' of queries. We can also architect this
 * repository to be a service, so that it gathers the database connections it
 * needs. This way other classes which use the repository don't need to concern
 * themselves with database connections, only with business logic.
 *
 * This repository demonstrates basic CRUD behaviors, and also has an advanced
 * query which performs a join with the user table.
 *
 * @ingroup dbtng_example
 */
class GenDBRepository {

    use MessengerTrait;
    use StringTranslationTrait;

    /**
     * The database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $connection;

    /**
     * Construct a repository object.
     *
     * @param \Drupal\Core\Database\Connection $connection
     *   The database connection.
     * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
     *   The translation service.
     * @param \Drupal\Core\Messenger\MessengerInterface $messenger
     *   The messenger service.
     */
    public function __construct(Connection $connection, TranslationInterface $translation, MessengerInterface $messenger) {
        $this->connection = $connection;
        $this->setStringTranslation($translation);
        $this->setMessenger($messenger);
    }


    /**
     * Read from the database using a filter array.
     *
     * The standard function to perform reads for static queries is
     * Connection::query().
     *
     * Connection::query() uses an SQL query with placeholders and arguments as
     * parameters.
     *
     * Drupal DBTNG provides an abstracted interface that will work with a wide
     * variety of database engines.
     *
     * The following is a query which uses a string literal SQL query. The
     * placeholders will be substituted with the values in the array. Placeholders
     * are marked with a colon ':'. Table names are marked with braces, so that
     * Drupal's' multisite feature can add prefixes as needed.
     *
     * @code
     *   // SELECT * FROM {dbtng_example} WHERE uid = 0 AND name = 'John'
     *   \Drupal::database()->query(
     *     "SELECT * FROM {dbtng_example} WHERE uid = :uid and name = :name",
     *     [':uid' => 0, ':name' => 'John']
     *   )->execute();
     * @endcode
     *
     * For more dynamic queries, Drupal provides Connection::select() API method,
     * so there are several ways to perform the same SQL query. See the
     * @link http://drupal.org/node/310075 handbook page on dynamic queries. @endlink
     * @code
     *   // SELECT * FROM {dbtng_example} WHERE uid = 0 AND name = 'John'
     *   \Drupal::database()->select('dbtng_example')
     *     ->fields('dbtng_example')
     *     ->condition('uid', 0)
     *     ->condition('name', 'John')
     *     ->execute();
     * @endcode
     *
     * Here is select() with named placeholders:
     * @code
     *   // SELECT * FROM {dbtng_example} WHERE uid = 0 AND name = 'John'
     *   $arguments = array(':name' => 'John', ':uid' => 0);
     *   \Drupal::database()->select('dbtng_example')
     *     ->fields('dbtng_example')
     *     ->where('uid = :uid AND name = :name', $arguments)
     *     ->execute();
     * @endcode
     *
     * Conditions are stacked and evaluated as AND and OR depending on the type of
     * query. For more information, read the conditional queries handbook page at:
     * http://drupal.org/node/310086
     *
     * The condition argument is an 'equal' evaluation by default, but this can be
     * altered:
     * @code
     *   // SELECT * FROM {dbtng_example} WHERE age > 18
     *   \Drupal::database()->select('dbtng_example')
     *     ->fields('dbtng_example')
     *     ->condition('age', 18, '>')
     *     ->execute();
     * @endcode
     *
     * @param array $entry
     *   An array containing all the fields used to search the entries in the
     *   table.
     *
     * @return object
     *   An object containing the loaded entries if found.
     *
     * @see Drupal\Core\Database\Connection::select()
     */
    public function load(array $entry = [], array $header = []) {
        // Read all the fields from the dbtng_example table.
        $select = $this->connection
            ->select('chado.feature', 'f')->extend('Drupal\Core\Database\Query\TableSortExtender')
            ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);

        $select->join('chado.f_type', 't', 'f.type_id = t.type_id');

        $select->addField('f', 'name');
        $select->addField('f', 'uniquename');
        $select->addField('t', 'type', 'type');
        $select->addField('f', 'feature_id');

        // Add each field and value as a condition to this query.
        $operator = '='; // default operator 
        foreach ($entry as $field => $value) {
            if (is_array($value)) {
                $operator = $value['operator'];
                $value = $value['value'];
            }
            if ($value == '') {
                continue; ## don't add a filter for empty values
            }
            if ($operator == 'contains') {
                $value = "%$value%";
                $operator = 'LIKE';
            }
            $select->condition($field, $value, $operator);
        }
        $select->orderByHeader($header);
        // Return the result in object format.
        return $select->distinct()->execute()->fetchAll();
    }

    public function getFeatureInfoById(int $id) {

        $select = $this->connection
            ->select('chado.feature', 'f');
        $select->join('chado.f_type', 't', 'f.type_id = t.type_id');
        $select->join('chado.organism', 'o', 'f.organism_id = o.organism_id');
        $select->addField('f', 'name');
        $select->addField('f', 'residues');

        $select->addField('f', 'uniquename');
        $select->addField('t', 'type', 'type');

        $select->addField('f', 'feature_id');
        $select->addField('o', 'genus');
        $select->addField('o', 'species');
        $select->addField('o', 'organism_id');
        $select->condition('f.feature_id', $id);
        return $select->execute()->fetch();
    }
    
    /*
     * Table taken from: https://www.biophp.org/minitools/sequence_manipulation_and_data/
     * But allows to preserve lower case
     */

    function Complement($seq){

        $comptable =  [
            'A' => 'T',
            'T' => 'A',
            'G' => 'C',
            'C' => 'G',
            'Y' => 'R',
            'W' => 'W',
            'K' => 'M',
            'M' => 'K',
            'D' => 'H',
            'V' => 'B',
            'H' => 'D',
            'B' => 'V',
        ];
        foreach ($comptable as $key => $value) {
	        $comptable[strtolower($key)] = strtolower($value);
        }
    
        $seq = strtr($seq, $comptable);
       
        return $seq;
    }




    public function spliceSeq(string $seq, int $fid, int $sid) {

        $select = $this->connection
            ->select('chado.featureloc', 'l');

        $select->fields('l');
        $select->condition('feature_id', $fid);
        $select->condition('srcfeature_id', $sid);
        $ranges = $select->execute()->fetchAll();

        $subseq = '';

        foreach ($ranges as $range) {
            // suseq is 0-based
            $fmin = (int) $range->fmin; $fmax = (int) $range->fmax;

            $substr = substr($seq, $fmin, $fmax - $fmin);

            if ($range->strand < 0){
                $substr = strrev($this->Complement($substr));

            }
            $subseq .= $substr;

        }

        return $subseq;

    }


    /**
     * Retrieve the sequence recursively from the source feature
     *
     **/
    public function getSeqRecursive(int $id) {
        // get the residues, if any
        $select = $this->connection
            ->select('chado.feature', 'f');
        $select->addField('f', 'residues');
        $select->condition('f.feature_id', $id);
        $entry = $select->execute()->fetchAssoc();
        #var_dump($entry);
        $residues = $entry['residues'];
        // Check if this feature has a featureloc entry
        $select = $this->connection
            ->select('chado.featureloc', 'l');
        $select->fields('l');
        $select->condition('l.feature_id', $id)->distinct()
        ->orderBy('l.srcfeature_id');
        $entries =  $select->execute()->fetchAll();
        // if there are no sourcefeatures, we are done and can return the residues
        if (count($entries) === 0) {
            // list is empty.
            return [$id => $residues];
        }
        // We need to get the sequence of the source feature, hope it has residues
        $retarray = [];
        foreach ($entries as $entry) {

            if ($entry->srcfeature_id) {
                $srcseqs = $this->getSeqRecursive($entry->srcfeature_id);
                // splice the sequences, one by one
                foreach ($srcseqs as $sid => $sseq) {
                    $myseq = $this->spliceSeq($sseq, $id, $sid);
                    $retarray[$sid] = $myseq;
                }
            } else {
                $retarray[$id] = $residues;

            }
        }

        return $retarray;



    }


    public function defaultLookup(string $chadotype, int $id) {

        $select = $this->connection
            ->select('chado.'.$chadotype, 't')
            ->fields('t')
            ->condition('t.'.$chadotype.'_id', $id);
        return $select->execute()->fetch();

    }
    
    /**
	 * Counts the number of feature entries in Chado DB,
  */
 
 public function countFeatureTypes() {
     
     
     $sql = "SELECT type,count(type)  FROM chado.f_type
         GROUP BY type ORDER BY type;";
     
     $query = \Drupal::database()->query($sql); 
     return($query->fetchAll());
     
    }   


}
