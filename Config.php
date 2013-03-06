<?php
namespace Dota2Draft;

require 'Settings.php';

class Config {	
	private $SteamApiKey;
	private $Types;

	public static function Instance()
	{
		static $instance;
		if ($instance == null) {
			$instance = new Config();
		}
		return $instance;
	}
	
	private function __construct()
	{
		$this->SteamApiKey = STEAM_API_KEY;
		$this->Types = array('BanningDraft');
	}
	
	public function IsAllowedType($type) {
		return in_array($type, $this->Types);
	}
	
	public function GetSteamApiKey() {
		return $this->SteamApiKey;
	}
	
	public function GetDota2HeroesUrl() {
		return sprintf('http://api.steampowered.com/IEconDOTA2_570/GetHeroes/v0001/?language=en_us&key=%s', $this->GetSteamApiKey());
	}
}
?>