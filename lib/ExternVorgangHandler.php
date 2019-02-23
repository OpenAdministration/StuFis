<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 22.02.19
 * Time: 19:40
 */

class ExternVorgangHandler
	extends FormHandlerInterface{
	
	private $id;
	
	public function __construct($id){
		$this->id = $id;
	}
	
	
	public static function initStaticVars(){
		// TODO: Implement initStaticVars() method.
	}
	
	public static function getStateStringFromName($statename){
		// TODO: Implement getStateStringFromName() method.
	}
	
	public function updateSavedData($data){
		// TODO: Implement updateSavedData() method.
	}
	
	public function state_change($stateName, $etag){
		// TODO: Implement method and use etag :/
		switch ($stateName){
			case "instructed":
			case "payed":
			case "booked":
				$colName = "state_$stateName";
			break;
			default:
				ErrorHandler::_errorExit("Wrong State $stateName in External");
			break;
		}
		$newEtag = randomstring();
		//TODO: also Version number tracking?
		DBConnector::getInstance()->dbUpdate(
			"extern_data",
			["id" => $this->id, "etag" => $etag],
			[
				$colName =>
					DBConnector::getInstance()->getUser()["fullname"] . ";" . date_create()->format(DateTime::ATOM),
				"etag" => $newEtag
			]
		);
	}
	
	public function setState($stateName){
		// TODO: Implement setState() method.
	}
	
	public function state_change_possible($nextState){
		//FIXME
		return true;
	}
	
	public function getStateString(){
		//FIXME
		return "I have no fucking state";
	}
	
	public function getNextPossibleStates(){
		// TODO: Implement getNextPossibleStates() method.
	}
	
	public function getID(){
		// TODO: Implement getID() method.
	}
	
	public function render(){
		// TODO: Implement render() method.
	}
	
}