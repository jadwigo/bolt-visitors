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
    'version' => "0.5",
    'required_bolt_version' => "0.7.10",
    'highest_bolt_version' => "0.8.5",
    'type' => "General",
    'first_releasedate' => "2012-12-12",
    'latest_releasedate' => "2013-01-04",
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
  $config = \Visitors\loadconfig($app);
  $basepath = $config['basepath'];

  require_once __DIR__.'/src/Visitors/Visitor.php';
  require_once __DIR__.'/src/Visitors/Session.php';

  $recognizedvisitor = \Visitors\checkvisitor($app);

  // define twig functions and vars
  $app['twig']->addFunction('knownvisitor', new \Twig_Function_Function('Visitors\checkvisitor'));
  $app['twig']->addFunction('showvisitorlogin', new \Twig_Function_Function('Visitors\showvisitorlogin'));
  $app['twig']->addFunction('showvisitorlogout', new \Twig_Function_Function('Visitors\showvisitorlogout'));
  $app['twig']->addFunction('showvisitorprofile', new \Twig_Function_Function('Visitors\showvisitorprofile'));
  $app['twig']->addGlobal('visitor', $recognizedvisitor);

  $app['log']->add(\util::var_dump($recognizedvisitor, true));

  // View visitor page
  $app->get("/{$basepath}", '\Visitors\view')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitors');

  // for those people who add slashes to the end of the uri
  $app->get("/{$basepath}/", '\Visitors\view')
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

  // Endpoint for hybridauth to authenticate with
  // Callbacks and keys are routed here
  $app->match("/{$basepath}/endpoint", '\Visitors\endpoint')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('visitorsendpoint');

}

/**
 * Reuseable config
 */
function loadconfig(Silex\Application $app) {
  if(!$app) {
    global $app;
  }
  // get the extension configuration
  $yamlparser = new \Symfony\Component\Yaml\Parser();
  $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

  // Make sure a $config['basepath'] is set
  if (!isset($config['basepath'])) {
      $config['basepath'] = "visitors";
  }

  // set an endpoint for hybridauth to authenticate with
  $config["base_url"] = $app['paths']['rooturl'] . $config['basepath'] . '/endpoint';

  return $config;
}

/**
 * Check who the visitor is
 */
function checkvisitor(Silex\Application $app) {
  if(!$app) {
    global $app;
  }

  $session = new \Visitors\Session($app);
  //\util::var_dump($session);
  //$sessions = $session->active();
  //\util::var_dump($sessions);

  $token = $app['session']->get('visitortoken');
  //\util::var_dump($token);

  $current = $session->load($token);
  //\util::var_dump($current);

  $visitor = new \Visitors\Visitor($app);
  $current_visitor = $visitor->load_by_id($current['visitor_id']);
  //\util::var_dump($current_visitor);
  if($current_visitor) {
    return $current_visitor;
  }
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
  $config = \Visitors\loadconfig($app);

  // login the visitor if avaulable
  $recognizedvisitor = \Visitors\checkvisitor($app);

  if($recognizedvisitor) {
    // already logged in - show the account
    return redirect('homepage');
    //return redirect($config['basepath']);
    exit;
  }

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
      if($user_profile) {
        $visitor = new \Visitors\Visitor($app);
        $visitor->setProvider( $provider );
        $visitor->setProfile( $user_profile );

        $known_visitor = $visitor->checkExisting();

        // TODO: create a new user profile if it does not exist yet - and load it
        if(!$known_visitor) {
          $known_visitor = $visitor->save();
        }

        $session = new \Visitors\Session($app);
        $token = $session->login($known_visitor['id']);

        $app['session']->setFlash('info', '<p>You are logged in as '.$visitor->visitor['username'].' now</p>');
        
        return redirect('homepage');
      }


      // show us the money
      //$markup .= \util::var_dump($token, true);
      //$markup .= \util::var_dump($user_profile, true);
      //$markup .= \util::var_dump($known_visitor, true);
    }
    catch( Exception $e ){
      echo "Error: please try again!";
      echo "Original error message: " . $e->getMessage();
    }
  } else {
    $markup .= \Visitors\showvisitorlogin();
  }

  return \Visitors\page($app, 'login', $title, $markup);
}

/**
 * Returns a list of links to all enabled login options
 */
function showvisitorlogin() {
  // get the extension configuration
  if(!$config) {
    $config = \Visitors\loadconfig($app);
  }

  $markup = '';

  foreach($config['providers'] as $provider => $values) {
    if($values['enabled']==true) {
      $providers[] = '<div><a class="btn btn-large login '. $provider .'" href="/'.$config['basepath'].'/login?provider='. $provider .'">'. $provider .'</a></div>';
    }
  }
  $markup .= '<div class="well">'."\n";
  //$markup .= '<p class="text-info">Login with one of the following options.</p>'."\n";
  $markup .= join("\n", $providers);
  $markup .= "</div>\n";

  return $markup;
}

/**
 * Link to the logout page
 */
function showvisitorlogout() {
  // get the extension configuration
  if(!$config) {
    $config = \Visitors\loadconfig($app);
  }

  $logoutlink = '<div class="well">'."\n";
  $logoutlink .= '<a class="btn btn-small logout" href="/'.$config['basepath'].'/logout">Logout</a>';
  $logoutlink .= "</div>\n";
  return $logoutlink;
}

/**
 * Show the currently logged in visitor
 */
function showvisitorprofile() {
  // get the extension configuration
  if(!$config) {
    $config = \Visitors\loadconfig($app);
  }

  // login the visitor
  $recognizedvisitor = \Visitors\checkvisitor($app);

  if($recognizedvisitor) {
    $username = $recognizedvisitor['username'];
    $markup .= "<p>Hello $username.</p>";
    $markup .= \Visitors\showvisitorlogout();
  }

  return $markup;
}

/**
 * Hybrid auth endpoint
 *
 * This endpoint passes all login requests to hybridauth
 */
function endpoint(Silex\Application $app) {
  // get the extension configuration
  $config = \Visitors\loadconfig($app);

  require_once( __DIR__."/hybridauth/hybridauth/Hybrid/Auth.php" );
  require_once( __DIR__."/hybridauth/hybridauth/Hybrid/Endpoint.php" ); 

  \Hybrid_Endpoint::process();

}


/**
 * Logout visitor page
 *
 * Remove / Reset a visitor session
 *
 * TODO: kill the current session
 */
function logout(Silex\Application $app) {
  if(!$app) {
    global $app;
  }
  // get the extension configuration
  if(!$config) {
    $config = \Visitors\loadconfig($app);
  }

  $token = $app['session']->get('visitortoken');
  $session = new \Visitors\Session($app);
  $token = $session->clear($token);

  //return redirect($config['basepath'].'/login');
  return redirect('homepage');
  
}

/**
 * View visitor page
 *
 * View the current visitor
 */
function view(Silex\Application $app) {
  $markup = '';
  $config = \Visitors\loadconfig($app);

  // login the visitor
  $recognizedvisitor = \Visitors\checkvisitor($app);

  if($recognizedvisitor) {
    $markup = \Visitors\showvisitorprofile() ;
  } else {
    // go directly to login page
    return redirect($config['basepath'].'/login');
  }


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