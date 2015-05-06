#!/usr/bin/env php
<?php

  require_once __DIR__ . '/apiHandler.php';
  require_once __DIR__ . '/config/config.php';

  $repos = new apiHandler();
  $contributors = new apiHandler();

  $parameters = array('wordpress', array('language' => 'php', 'start_page' => REPO_START_PAGE));
  $result = $repos->requestData('repo', 'find', $parameters);

  $repositories = array();

  while($repos->hasMorePages() && (count($repositories) < 100)) {
    foreach($result['items'] as $repo) {
      $repositories[$repo['id']] = $repo;
      $repositories[$repo['id']]['contributors'] = requestContributors($repo['full_name']);
    }
    $result = $repos->nextPage();
  }

  function requestContributors($repo_fullname) {
    global $contributors;
    $contributor_parameters = explode('/', $repo_fullname);
    $contributor_parameters[] = true;
    $contributor_results = $contributors->requestData('repo', 'contributors', $contributor_parameters);

    while($contributors->hasMorePages()) {
      $contributor_results = array_merge($contributor_results, $contributors->nextPage());
    }

    return $contributor_results;
  }
  
  die(print_r($repositories, true));
