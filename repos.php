#!/usr/bin/env php
<?php

require_once __DIR__ . '/githubApi.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/db.php';

$db = new db();
$repos = new githubApi();
$contributors = new githubApi();
$repositories = array();
$projects_query_data = array();
$contributors_query_data = array();

$parameters = array('wordpress', array('language' => 'php', 'start_page' => REPO_START_PAGE));
$result = $repos->requestData('repo', 'find', $parameters);

do {
  foreach($result['repositories'] as $id => $repo) {
    $repositories[] = array_merge(
      $repo, 
      array('contributors' => requestContributors($contributors, $repo['owner'], $repo['name']))
    );
    if (haveEnoughRepos($repositories)) break;
  }
  $result = $repos->nextPage();
} while($repos->hasMorePages() && !haveEnoughRepos($repositories));

foreach($repositories as $id => $repository) {
  $projects_query_data[] = implodeDataForQuery(array(
    'id' => $id,
    'name' => $repository['name'],
    'description' => $repository['description'],
    'owner' => $repository['owner'],
    'homepage' => $repository['homepage'],
    'watchers' => $repository['watchers'],
    'forks' => $repository['forks'],
    'stargazers' => $repository['followers']
  ));
  foreach($repository['contributors'] as $contributor) {
    $contributors_query_data[] = implodeDataForQuery(array(
      'id' => isset($contributor['id']) ? $contributor['id'] : 0, 
      'contributor_name' => isset($contributor['login']) ? $contributor['login'] : $contributor['name'], 
      'project_id' => $id
    ));
  }
}

initializeTables($db);
$db->query('INSERT INTO `projects` (`id`, `name`, `description`, `owner`, `homepage`, `watchers`, `forks`, `stargazers`) VALUES ' . implode(', ', $projects_query_data) . ';');
$db->query('INSERT INTO `project_contributors` (`id`, `contributor_name`, `project_id`) VALUES ' . implode(', ', $contributors_query_data) . ';');


function implodeDataForQuery($array) {
  return '("' . implode('", "', $array) . '")';
}

function initializeTables($db) {
  $db->query("DROP TABLE `projects`;");
  $db->query("CREATE TABLE IF NOT EXISTS `projects` (
    `id` int(11) NOT NULL,
    `name` varchar(244) NOT NULL,
    `description` text NOT NULL,
    `owner` varchar(244) NOT NULL,
    `homepage` varchar(244) NOT NULL,
    `watchers` int(11) NOT NULL,
    `forks` int(11) NOT NULL,
    `stargazers` int(11) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
  $db->query("DROP TABLE `project_contributors`;");
  $db->query("CREATE TABLE IF NOT EXISTS `project_contributors` (
    `id` int(11) NOT NULL,
    `contributor_name` varchar(244) NOT NULL,
    `project_id` int(11) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1;");
}

function haveEnoughRepos($repo_array) {
  return count($repo_array) >= NUM_REPOS;
}

function requestContributors($contrib_handler, $repo_owner, $repo_name) {
  $contributor_parameters = array($repo_owner, $repo_name, true);
  $contributor_results = $contrib_handler->requestData('repo', 'contributors', $contributor_parameters);

  while($contrib_handler->hasMorePages()) {
    $contributor_results = array_merge($contributor_results, $contrib_handler->nextPage());
  }

  return $contributor_results;
}
