<?php
namespace Dota2Draft;

require_once 'Database.php';
require_once 'Config.php';

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException;

/**
 * Match
 * @namespace Dota2Draft
 * @uri /match
 * @uri /match/:id
 */
class MatchResource extends Resource {
    
	/**
   * @method GET
   * @param  str $id
   * @return str
   */
  function getMatch($id = null) {
    $headers = array('content-type' => 'application/json');

    if($id) {
      $match = $this->getMatchDb($id);
      if(!$match) {
        $match = $this->getMatchDb($id, true);
      }
      
      if($match) {
        $response = json_encode($match);
        return new Response(Response::OK, $response, $headers);
      } else { //Match not found
        $response = json_encode(array('error' => 'Match not found.'));
        return new Response(Response::NOTFOUND, $response, $headers);
      }
    } else { //No ID specified
      $response = json_encode(array('error' => 'Please specify an ID.'));
      return new Response(Response::NOTFOUND, $response, $headers);
    }
  }
  
  function getMatchDb($id, $edit=false) {
    $database = Database::Instance();
    
    //Find match
    if($edit) {
      $stmt = $database->prepare('SELECT id, read_key, edit_key, type FROM d2draft_matches AS matches WHERE edit_key=? LIMIT 0,1');
    } else {
      $stmt = $database->prepare('SELECT id, read_key, type FROM d2draft_matches AS matches WHERE read_key=? LIMIT 0,1');
    }
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0) { //Match found
      if($edit) {
        $stmt->bind_result($matchId, $readKey, $editKey, $type);
      } else {
        $stmt->bind_result($matchId, $readKey, $type);
      }
      $stmt->fetch();
      $stmt->close();
      
      $response = array('readKey' => $readKey,
                        'type' => $type,
                        'heroes' => array());
                        
      if($edit) {
        $response['editKey'] = $editKey;
      }
      
      //Select heroes
      $stmt = $database->prepare('SELECT matchheroes.id AS id, heroes.id AS hero_id, heroes.name AS name, heroes.localized_name AS localized_name, matchheroes.status AS status  FROM d2draft_heroes AS heroes, d2draft_matches AS matches, d2draft_matchheroes AS matchheroes WHERE heroes.id = matchheroes.hero_id AND matchheroes.match_id = matches.id AND matches.id=?;');
      print_r($database->error);
      $stmt->bind_param('i', $matchId);
      $stmt->execute();
      $stmt->bind_result($matchHeroId, $heroId, $heroName, $heroLocalizedName, $status);
      while($stmt->fetch()) {
        $response['heroes'][] = array('id' => $heroId,
                                      'match_hero_id' => $matchHeroId,
                                      'name' => $heroName,
                                      'localized_name' => $heroLocalizedName,
                                      'status' => strtolower($status));
      }
      $stmt->close();
      
      return $response;
    }
    
    return null;
  }
	
	/**
   * @method POST
	 * @return str
   */
	function createMatch() {
		$config = Config::Instance();
		$database = Database::Instance();
		$headers = array('content-type' => 'application/json');
	
		$data = json_decode($this->request->data);
		if(!$config->IsAllowedType($data->type)) { //Malformed request
			$response = json_encode(array('error' => 'Game type not allowed.'));
			return new Response(Response::BADREQUEST, $response, $headers); 
		} else { //Create match
			$readKey = str_replace('/', '_', base64_encode(openssl_random_pseudo_bytes(32)));
			$editKey = str_replace('/', '_', base64_encode(openssl_random_pseudo_bytes(32)));
			$type = $data->type;
			
			$matchId = $this->CreateMatchDb($readKey, $editKey, $type);
			$response = array('readKey' => $readKey,
												'editKey' => $editKey,
											  'type' => $type);
			
			switch($type) {
				case 'BanningDraft':
					$result = $database->query('SELECT id, name, localized_name FROM d2draft_heroes ORDER BY RAND() LIMIT 0,24;');
					$stmt = $database->prepare('INSERT INTO d2draft_matchheroes (match_id, hero_id, status) VALUES(?, ?, "Pool");');
					$stmt->bind_param('ii', $matchId, $heroId);
					
					$response['heroes'] = array();
					while($hero = $result->fetch_object()) {
						$heroId = $hero->id;
						$response['heroes'][] = $hero;
						$stmt->execute();
					}
					
					$result->close();
					$stmt->close();
					
					break;
				default:
					break;
			}
			
			$response = json_encode($response);
			return new Response(Response::CREATED, $response, $headers); 
		}
	}
	
	private function CreateMatchDb($readKey, $editKey, $type) {
		$database = Database::Instance();
		
		$stmt = $database->prepare('INSERT INTO d2draft_matches (read_key, edit_key, type) VALUES(?, ?, ?);');
		$stmt->bind_param('sss', $readKey, $editKey, $type);
		$stmt->execute();
		$insertId = $stmt->insert_id;
		$stmt->close();
		
		return $insertId;
	}
}
?>