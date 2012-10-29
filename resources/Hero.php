<?php
namespace Dota2Draft;

require_once 'Database.php';
require_once 'Config.php';

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException;

/**
 * Hero
 * @namespace Dota2Draft
 * @uri /hero
 * @uri /hero/:id
 */
class HeroResource extends Resource {
    
	/**
     * @method GET
     * @param  str $id
     * @return str
     */
	function GetHero($id = null) {
		$database = Database::Instance();
		$headers = array('content-type' => 'application/json');
		
		if($id) {
			//Find match
			$stmt = $database->prepare('SELECT id, name, localized_name FROM d2draft_heroes AS heroes WHERE id=? LIMIT 0,1');
			$stmt->bind_param('s', $id);
			$stmt->execute();
			$stmt->store_result();

			if($stmt->num_rows > 0) { //Match found
				$stmt->bind_result($id, $name, $localizedName);
				$stmt->fetch();
				
				$response = json_encode(array('id' => $id,
                                      'name' => $name,
                                      'localized_name' => $localizedName));
				return new Response(Response::OK, $response, $headers);
			} else { //Match not found
				$response = json_encode(array('error' => 'Hero not found.'));
				return new Response(Response::NOTFOUND, $response, $headers);
			}
		} else { //No ID specified
			$response = json_encode(array('error' => 'Please specify an ID.'));
			return new Response(Response::NOTFOUND, $response, $headers);
		}
	}
	
	/**
     * @method PUT
     * @return str
     */
	function UpdateHeroes() {
		$config = Config::Instance();
		$database = Database::Instance();
		$headers = array('content-type' => 'application/json');
	
		$heroes = json_decode(file_get_contents($config->GetDota2HeroesUrl()));
		if(!$heroes) {
			$response = json_encode(array('error' => 'Steam servers not responding.'));
			return new Response(Response::SERVICEUNAVAILABLE, $response, $headers);
		}
		
		$database->query('DELETE FROM d2draft_heroes');
		$stmt = $database->prepare('INSERT INTO d2draft_heroes (id, name, localized_name) VALUES(?, ?, ?);');
		$stmt->bind_param('sss', $id, $name, $localizedName);
		foreach($heroes->result->heroes as $hero) {
			$id = $hero->id;
			$name = $hero->name;
			$localizedName = $hero->localized_name;
			$stmt->execute();
		}
		$stmt->close();
		
		$response = json_encode(array('error' => 'Heroes list updated.'));
		return new Response(Response::CREATED, $response, $headers);
	}
}
?>