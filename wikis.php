<?php
$wiki_details = array(
	'starcraft' => array('name' => 'StarCraft: Brood War', 'type' => 'full'),
	'starcraft2' => array('name' => 'StarCraft II', 'type' => 'full'),
	'dota2' => array('name' => 'Dota 2', 'type' => 'full'),
	'hearthstone' => array('name' => 'Hearthstone', 'type' => 'full'),
	'heroes' => array('name' => 'Heroes', 'type' => 'full'),
	'smash' => array('name' => 'Smash Bros', 'type' => 'full'),
	'counterstrike' => array('name' => 'Counter-Strike', 'type' => 'full'),
	'overwatch' => array('name' => 'Overwatch', 'type' => 'full'),
	'commons' => array('name' => 'Commons', 'type' => 'full'),
	'warcraft' => array('name' => 'Warcraft', 'type' => 'alpha'),
	'fighters' => array('name' => 'Fighting Games', 'type' => 'alpha'),
	'rocketleague' => array('name' => 'Rocket League', 'type' => 'full'),
	'clashroyale' => array('name' => 'Clash Royale', 'type' => 'alpha'),
	'crossfire' => array('name' => 'CrossFire', 'type' => 'alpha'),
	'battlerite' => array('name' => 'Battlerite', 'type' => 'alpha'),
	'teamfortress' => array('name' => 'Team Fortress', 'type' => 'alpha'),
	'trackmania' => array('name' => 'TrackMania', 'type' => 'alpha'),
	'diabotical' => array('name' => 'Diabotical', 'type' => 'alpha'),
	'leagueoflegends' => array('name' => 'League of Legends', 'type' => 'alpha'),
	'worldofwarcraft' => array('name' => 'World of Warcraft', 'type' => 'alpha'),
	'fifa' => array('name' => 'FIFA', 'type' => 'alpha')
);

$default_wikis = array_keys(array_filter($wiki_details, function($v) { return $v['type'] == 'full'; }));