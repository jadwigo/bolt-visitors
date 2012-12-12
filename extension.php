<?php
// Visitors Extension for Bolt, by Lodewijk Evers

namespace Visitors;

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
 */
function init($app)
{

    // Make sure jQuery is included
    $app['extensions']->addJquery();

    // Add javascript file
    $app['extensions']->addJavascript($app['paths']['app'] . "extensions/Visitors/assets/visitors.js");


}




