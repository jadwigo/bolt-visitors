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
 * Also handles the routing
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

  // View account page
  $app->get("/{$basepath}", '\Visitors\view')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitors');

  // Login to account page
  $app->get("/{$basepath}/login", '\Visitors\login')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitorslogin');
  
  // Logout from account page
  $app->get("/{$basepath}/logout", '\Visitors\logout')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitorslogout');
  
  // View account page
  $app->get("/{$basepath}/view", '\Visitors\view')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitorsview');

}

/**
 * Login visitor page
 */
function login(Silex\Application $app) {
  $title = "login page";
  return page($app, 'login', $title);
}

/**
 * Logout visitor page
 */
function logout(Silex\Application $app) {
  $title = "logout page";
  return page($app, 'logout', $title);
}

/**
 * View visitor page
 */
function view(Silex\Application $app) {
  $title = "view page";
  return page($app, 'view', $title);
}

/**
 * Output stuff
 */
function page(Silex\Application $app, $type, $title) {

  // Make sure jQuery is included
  $app['extensions']->addJquery();

  // Add javascript file
  $app['extensions']->addJavascript($app['paths']['app'] . "extensions/Visitors/assets/visitors.js");

  $template = 'error.twig';
  $twigvars = array(
    'message' => $title,
    'class' => 'visitor',
    'code' => 'ok'
  );
  
  $body = $app['twig']->render($template, $twigvars);

  return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

}