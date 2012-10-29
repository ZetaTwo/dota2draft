<?php
namespace Dota2Draft;

require_once 'Database.php';
require_once 'Config.php';
require_once 'pubnub/Pubnub.php';

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException;

/**
 * Match
 * @namespace Dota2Draft
 * @uri /matchhero
 * @uri /matchhero/:id
 */
class MatchHeroResource extends Resource {
  
  /**
   * @method GET
   * @param  str $id
   * @return str
   */
  function getMatchHero($id = null) {
    $headers = array('content-type' => 'application/json');
    

    if($id) {
      $matchhero = $this->getMatchHeroDb($id);
      if($matchhero) {           
        $response = json_encode($matchhero);
        return new Response(Response::OK, $response, $headers);
      } else {
        $response = json_encode(array('error' => 'Match hero not found.'));
        return new Response(Response::NOTFOUND, $response, $headers);
      }
    } else {
      $response = json_encode(array('error' => 'Please specify an ID.'));
      return new Response(Response::NOTFOUND, $response, $headers);
    }
  }
  
  function getMatchHeroDb($id) {
    $database = Database::Instance();
    $stmt = $database->prepare('SELECT id, status FROM d2draft_matchheroes AS matchheroes WHERE id=? LIMIT 0,1');
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0) { //Match found
      $stmt->bind_result($id, $status);
      $stmt->fetch();
      $stmt->close();
      
      return array('id' => $id,
                   'status' => $status);
    } else {
      return null;
    }
  }
  
  /**
   * @method POST
   * @param  str $id
   * @return str
   */
  function updateMatchHero($id = null) {
    $headers = array('content-type' => 'application/json');
    $database = Database::Instance();
    $data = json_decode($this->request->data);
    
    if($id) {
      //Update hero status
      $stmt = $database->prepare('UPDATE d2draft_matches AS matches, d2draft_matchheroes AS matchheroes SET matchheroes.status=? WHERE matches.edit_key=? AND matches.id = matchheroes.match_id AND matchheroes.id=?');
      $stmt->bind_param('sss', $data->status, $data->editKey, $id);
      $stmt->execute();
      $stmt->store_result();

      if($stmt->affected_rows > 0) { //Match hero updated
        $stmt->close();
        
        $matchhero = $this->getMatchHeroDb($id);
        
        //Publish action
        $stmt = $database->prepare('SELECT matches.read_key FROM d2draft_matches AS matches WHERE edit_key=? LIMIT 0,1;');
        $stmt->bind_param('s', $data->editKey);
        $stmt->execute();
        $stmt->bind_result($readKey);
        $stmt->fetch();
        $pubnub = new \Pubnub( 'pub-97a1a486-7b66-47fc-9f08-f611ee21bb9a', 'sub-31bdff4f-8afa-11e1-b98e-3955e0de91cc' );
        $pubnub->publish(array('channel' => 'dota2draft_' . $readKey, 'message' => $matchhero));
        
        //Return new status
        $response = json_encode($matchhero);
        return new Response(Response::OK, $response, $headers);
      } else {
        $response = json_encode(array('error' => 'Match hero not found.'));
        return new Response(Response::NOTFOUND, $response, $headers);
      }
    } else {
      $response = json_encode(array('error' => 'Please specify an ID.'));
      return new Response(Response::NOTFOUND, $response, $headers);
    }
  }
}
?>