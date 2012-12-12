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
    'version' => "0.3",
    'required_bolt_version' => "0.7.10",
    'highest_bolt_version' => "0.8.5",
    'type' => "General",
    'first_releasedate' => "2012-12-12",
    'latest_releasedate' => "2012-12-12",
    'dependencies' => "",
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
function init(Silex\Application $app)
{

  // get the extension configuration
  $config = \Visitors\loadconfig();
  $basepath = $config['basepath'];

  $recognizedvisitor = \Visitors\checkvisitor();
 
  $app['log']->add(\util::var_dump($recognizedvisitor, true));

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

  // Endpoint for hybridauth
  $app->match("/{$basepath}/endpoint", '\Visitors\endpoint')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitorsendpoint');

}

/**
 * Reuseable config
 */
function loadconfig() {
  // get the extension configuration
  $yamlparser = new \Symfony\Component\Yaml\Parser();
  $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

  // Make sure a $config['basepath'] is set
  if (!isset($config['basepath'])) {
      $config['basepath'] = "visitors";
  }

  // set an endpoint
  $config["base_url"] = $app['paths']['rooturl'] . $config['basepath'] . '/endpoint';
  
  return $config;
}

/**
 * Check who the visitor is
 *
 * TODO: make it actually recognize a visitor
 */
function checkvisitor() {
  return false;
}

/**
 * Login visitor page
 *
 * Prepare the visitor login from hybridauth
 */
function login(Silex\Application $app) {
  $title = "login page";

  // get the extension configuration
  $config = \Visitors\loadconfig();
  
  //$markup .= \util::var_dump($config, true);
 
  $provider = \util::get_var('provider', false);

  if($provider) {

    require_once( __DIR__."/hybridauth/hybridauth/Hybrid/Auth.php" );

    try{
      // initialize Hybrid_Auth with a given file
      $hybridauth = new \Hybrid_Auth( $config );

      // try to authenticate with the selected provider
      $adapter = $hybridauth->authenticate( $provider );

      // then grab the user profile 
      $user_profile = $adapter->getUserProfile();

      // TODO: check if user profile is known internally - and load it
      // TODO: create a new user profile if it does not exist yet - and load it

      // show us the money
      $markup .= \util::var_dump($user_profile, true);
    }
    catch( Exception $e ){
      echo "Error: please try again!";
      echo "Original error message: " . $e->getMessage();
    }
  } else {
    foreach($config['providers'] as $provider => $values) {
      if($values['enabled']==true) {
        $providers[] = '<li><a class="login '. $provider .'" href="/'.$config['basepath'].'/login?provider='. $provider .'">'. $provider .'</a></li>';
      }
    }
    $markup .= "<h2>Login with one of the following</h2>\n";
    $markup .= '<ul>'.join("\n", $providers)."</ul>\n";
  }

  // login the visitor
  $recognizedvisitor = \Visitors\checkvisitor();

  return \Visitors\page($app, 'login', $title, $markup);
}

/**
 * Hybrid auth endpoint
 */
function endpoint() {
  // get the extension configuration
  $config = \Visitors\loadconfig();

  require_once( __DIR__."/hybridauth/hybridauth/Hybrid/Auth.php" );
  require_once( __DIR__."/hybridauth/hybridauth/Hybrid/Endpoint.php" ); 

  \Hybrid_Endpoint::process();

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
  $template = 'base.twig';
 
  $body = $app['twig']->render($template, array('title' => $title, 'markup' => $markup));

  return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));
}