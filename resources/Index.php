<?php
namespace Dota2Draft;

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException;

/**
 * Home
 * @namespace Dota2Draft
 * @uri /
 */
class HomeResource extends Resource {
    
	/**
     * @method GET
     * @return str
     */
    function getHome() {
		return file_get_contents('templates/index.html');
	}
}
?>