<?php
// Visitors Extension for Bolt, by Lodewijk Evers

/**
 * TODO: check if old sessions linger and clean them up with a cron hook
 */

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
        'version' => "0.6",
        'required_bolt_version' => "0.7.10",
        'highest_bolt_version' => "0.8.5",
        'type' => "General",
        'first_releasedate' => "2012-12-12",
        'latest_releasedate' => "2013-01-08",
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

    // this would show the user in the debuglog, but it's actually just noise now
    // use {{ knownvisitor() }}, {{ visitor.id }} and {{ visitor.username }} in your templates instead
    //$app['log']->add(\util::var_dump($recognizedvisitor, true));

    $visitors_controller = $app['controllers_factory'];
    // View visitor page or redirect
    $visitors_controller
        ->match('', '\Visitors\view')
        ->bind('visitorsroot')
        ;
    $visitors_controller
        ->match('/', '\Visitors\view')
        ->bind('visitorsslash')
        ;
    $visitors_controller
        ->match('/view', '\Visitors\view')
        ->bind('visitorsview')
        ;

    // Login to visitor page
    $visitors_controller
        ->match('/login', '\Visitors\login')
        ->bind('visitorslogin')
        ;

    // Logout from visitor page
    $visitors_controller
        ->match('/logout', '\Visitors\logout')
        ->bind('visitorslogout')
        ;

    // Endpoint for hybridauth to authenticate with
    // Callbacks and keys are routed here
    $visitors_controller
        ->match('/endpoint', '\Visitors\endpoint')
        ->bind('visitorsendpoint')
        ;

    $app->mount("/{$basepath}", $visitors_controller);

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


    //$app['log']->add(\util::var_dump($config, true));
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

    $token = $app['session']->get('visitortoken');

    $current = $session->load($token);

    $visitor = new \Visitors\Visitor($app);
    $current_visitor = $visitor->load_by_id($current['visitor_id']);

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

    // login the visitor if available
    $recognizedvisitor = \Visitors\checkvisitor($app);

    if($recognizedvisitor) {
        // already logged in - show the account
        return redirect('homepage');
        //return redirect($config['basepath']);
        exit;
    }

    $provider = \util::get_var('provider', false);

    if($provider) {

        require_once( __DIR__."/hybridauth/hybridauth/Hybrid/Auth.php" );

        try{
            // initialize Hybrid_Auth with a given file

            // get the type early - because we might need to enable it
            $providertype = isset($config['providers'][$provider]['type'])?$config['providers'][$provider]['type']:$provider;

            // enable OpenID
            if($providertype=='OpenID' && $config['providers'][$provider]['enabled']==true) {
                $config['providers']['OpenID']['enabled']=true;
            }

            // initialize the authentication with the modified config
            $hybridauth = new \Hybrid_Auth( $config );

            if($providertype=='OpenID' && !empty($config['providers'][$provider]['openid_identifier'])) {
                // try to authenticate with the selected OpenID provider
                $providerurl = $config['providers'][$provider]['openid_identifier'];
                $adapter = $hybridauth->authenticate( $providertype, array("openid_identifier" => $providerurl));
            } else {
                // try to authenticate with the selected provider
                $adapter = $hybridauth->authenticate( $providertype );
            }
            // then grab the user profile
            $user_profile = $adapter->getUserProfile();

            if($user_profile) {
                $visitor = new \Visitors\Visitor($app);
                $visitor->setProvider( $provider );
                $visitor->setProfile( $user_profile );

                // check if user profile is known internally - and load it
                $known_visitor = $visitor->checkExisting();

                // create a new user profile if it does not exist yet - and load it
                if(!$known_visitor) {
                    $known_visitor = $visitor->save();
                }

                $session = new \Visitors\Session($app);
                $token = $session->login($known_visitor['id']);

                return redirect('homepage');
            }

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
            $label = !empty($values['label'])?$values['label']:$provider;

            $providers[] = '<div><a class="btn btn-large login '. $provider .'" href="/'.$config['basepath'].'/login?provider='. $provider .'">'. $label .'</a></div>';
        }
    }
    $markup .= '<div class="well">'."\n";
    $markup .= join("\n", $providers);
    $markup .= "</div>\n";

    $markup = new \Twig_Markup($markup, 'UTF-8');

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

    $logoutlink = new \Twig_Markup($logoutlink, 'UTF-8');

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

    $markup = new \Twig_Markup($markup, 'UTF-8');

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
        // TODO: This has some problems when the path is not initialized right
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