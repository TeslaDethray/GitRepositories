<?php

require_once __DIR__ . '/vendor/php-github-api/vendor/autoload.php';

/**
 * Is the go-between between this app and the PHP Github API
 *
 * @author SaraMcCutcheon <tesladethray at gmail dot com>
 */
class apiHandler {
  
  protected $client;
  protected $paginator;
  protected $result;

  /**
    * Class constructor
    */
  public function __construct() {
    $this->client = new \Github\Client(
      new \Github\HttpClient\CachedHttpClient(array('cache_dir' => __DIR__ . '/cache/'))
    );
  }

  /**
    * Sets $result and $paginator
    *
    * @param string $apiName the name of the API to use
    * @param string $which the entity to be retrieved from the API
    * @param mixed $parameters parameters for the API entity
    * 
    * @return array results from the first page of the paginated query
    */
  public function requestData($apiName, $which, $parameters = array()) {
    if(!is_array($parameters)) $parameters = array($parameters);
    $api = $this->client->api($apiName);
    $this->paginator = new Github\ResultPager($this->client);
    $this->result = $this->paginator->fetch($api, $which, $parameters);
    return $this->result;
  }

  /**
    * Sets $paginator to the next page, returns that next page
    *
    * @return array results from the next page of the paginated query
    */
  public function nextPage() {
    return $this->paginator->fetchNext();
  }

  /**
    * Checks whether the $paginator has another page after the current one
    *
    * @return boolean returns true if there is at least one more page
    */
  public function hasMorePages() {
    return $this->paginator->hasNext();
  }

}

