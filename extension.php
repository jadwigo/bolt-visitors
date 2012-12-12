<?php
// Visitors Extension for Bolt, by Lodewijk Evers

namespace Visitors;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
* Info block for Visitors Extension.
*/
function info()
{

  $data = array(
    'name' => "Visitors",
    'description' => "An extension to remember authenticated visitors on your bolt.cm site",
    'author' => "Lodewijk Evers",
    'link' => "https://github.com/jadwigo/bolt-visitors",
    'version' => "0.1",
    'required_bolt_version' => "0.7.10",
    'highest_bolt_version' => "0.7.10",
    'type' => "General",
    'first_releasedate' => "2012-12-12",
    'latest_releasedate' => "2012-12-12",
    'dependancies' => "",
    'priority' => 10
  );

  return $data;

}

/**
 * Initialize Visitors. Called during bootstrap phase.
 *
 * Checks if a visitor is known, and loads the associated visitor
 * Also handles the routing for login, logout and view
 */
function init($app)
{

  $yamlparser = new \Symfony\Component\Yaml\Parser();
  $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

  // Make sure a '$basepath' is set
  if (isset($config['basepath'])) {
      $basepath = $config['basepath'];
  } else {
      $basepath = "visitors";
  }

  // View visitor page
  $app->get("/{$basepath}", '\Visitors\view')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitors');
  
  // View visitor page (again)
  $app->get("/{$basepath}/view", '\Visitors\view')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitorsview');

  // Login to visitor page
  $app->get("/{$basepath}/login", '\Visitors\login')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitorslogin');
  
  // Logout from visitor page
  $app->get("/{$basepath}/logout", '\Visitors\logout')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitorslogout');
  
  // View visitor page
  $app->get("/{$basepath}/view", '\Visitors\view')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitorsview');

}

/**
 * Login visitor page
 *
 * Prepare the visitor login from hybridauth
 */
function login(Silex\Application $app) {
  $title = "login page";
  $markup = '';
  return \Visitors\page($app, 'login', $title, $markup);
}

/**
 * Logout visitor page
 *
 * Remove / Reset a visitor session
 */
function logout(Silex\Application $app) {
  $title = "logout page";
  $markup = '';
  return \Visitors\page($app, 'logout', $title, $markup);
}

/**
 * View visitor page
 *
 * View the current visitor
 */
function view(Silex\Application $app) {
  $title = "view page";
  $markup = '';
  return \Visitors\page($app, 'view', $title, $markup);
}

/**
 * Output the results in the default template
 */
function page(Silex\Application $app, $type, $title, $markup) {

  // Make sure jQuery is included
  $app['extensions']->addJquery();

  // Add javascript file only on the visitors pages
  $app['extensions']->addJavascript($app['paths']['app'] . "extensions/Visitors/assets/visitors.js");

  $template = 'base.twig';
 
  $body = $app['twig']->render($template, array('title' => $title, 'markup' => $markup));

  return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));
}