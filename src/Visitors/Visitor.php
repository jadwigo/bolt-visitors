<?php

namespace Visitors;

use Bolt;
use Silex;

/**
 * The visitor handles the database storage of known visitors
 */
class Visitor
{
    var $visitor;
    private $provider;
    private $profile;
    private $db;
    private $config;
    private $prefix;
    private $session;

    public function __construct(Silex\Application $app)
    {
        $this->config = $app['config'];
        $this->db = $app['db'];
        $this->session = $app['session'];
        $this->prefix = isset($this->config['general']['database']['prefix']) ? $this->config['general']['database']['prefix'] : "bolt_";
        // Make sure prefix ends in '_'. Prefixes without '_' are lame..
        if ($this->prefix[ strlen($this->prefix)-1 ] != "_") {
            $this->prefix .= "_";
        }
    }

    // check if visitors table exists - if not create it
    // CREATE TABLE 'bolt_visitors' ('id' INTEGER PRIMARY KEY NOT NULL, 'username' VARCHAR(64), 'provider' VARCHAR(64), 'providerdata' TEXT)

    public function setProvider($provider) {
        $this->provider = $provider;
    }

    public function setProfile($profile) {
        $this->profile = $profile;
    }

    public function checkExisting() {
        $visitor_raw = false;
        if($this->profile->displayName) {
            $sql = "SELECT * from " . $this->prefix ."visitors WHERE username = :displayname AND provider = :providername";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue("displayname", $this->profile->displayName);
            $stmt->bindValue("providername", $this->provider);
            $stmt->execute();
            $visitor_raw = $stmt->fetch();
        }
        if($visitor_raw && $visitor_raw['id']>0) {
            $this->visitor = $visitor_raw;
            $this->profile = unserialize($this->visitor['providerdata']);
            return $this->visitor;
        } else {
            return false;
        }
    }

    // load existing visitor by id
    public function load_by_id($visitor_id) {
        $sql = "SELECT * from " . $this->prefix ."visitors WHERE id = :visitorid";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue("visitorid", $visitor_id);
        $stmt->execute();
        $visitors = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->visitor = array_shift($visitors);
        $this->profile = unserialize($this->visitor['providerdata']);
        return $this->visitor;
    }

    // save new visitor
    public function save() {
        $serialized = serialize($this->profile);
        // id is set to autoincrement, so let the DB handle it
        $tablename =  $this->prefix ."visitors";
        $content = array(
            'username' => $this->profile->displayName, 
            'provider' => $this->provider, 
            'providerdata' => $serialized
        );
        $res = $this->db->insert($tablename, $content);
        $id = $this->db->lastInsertId();
        return $id;
    }

    // update existing visitor
    public function update() {
        $tablename =  $this->prefix ."visitors";
        $serialized = serialize($this->profile);
        $content = array(
            'username' => $this->visitor['username'], 
            'provider' => $this->provider, 
            'providerdata' => $serialized
        );
        return $this->db->update($tablename, $content, array('id' => $this->visitor['id']));
    }

    // delete visitor
    // TODO: fix this if needed
    public function delete($visitor_id = null) {
        //$this->db->delete($this->visitor);
    }

}