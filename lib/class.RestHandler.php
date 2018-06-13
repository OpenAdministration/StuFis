<?php
/**
 * FRAMEWORK JsonHandler
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
include_once dirname(__FILE__).'/class._JsonController.php';
class RestHandler extends JsonController {
	
	// ================================================================================================
	
	/**
	 * private class constructor
	 * implements singleton pattern
	 */
	public function __construct(){
		$this->json_result = [];
	}
	
	// ================================================================================================
	/**
	 * Created by PhpStorm.
	 * User: konsul
	 * Date: 07.05.18
	 * Time: 02:16
	 */
	public function handlePost($routeInfo = NULL){
		global $nonce;
		$ret = false;
		$msgs = [];
		$projektHandler = null;
		$dbret = false;
		try{
			$logId = DBConnector::getInstance()->logThisAction($_POST);
			DBConnector::getInstance()->logAppend($logId, "username", AuthHandler::getInstance()->getUsername());
		
			if (!isset($_POST["action"]))
				throw new ActionNotSetException("Es wurde keine Aktion übertragen");
			if ($_POST["nonce"] !== $nonce)
				throw new OldFormException("{$_POST["nonce"]} Veraltetes Formular: {$GLOBALS['nonce']}");
		
			if (DBConnector::getInstance()->dbBegin() === false)
				throw new PDOException("cannot start DB transaction");
		
			switch ($_POST["action"]){
				case "create":
					$projektHandler = ProjektHandler::createNewProjekt($_POST);
					if ($projektHandler !== null)
						$ret = true;
					break;
				case "changeState":
					if (!isset($_POST["id"]) || !is_numeric($_POST["id"])){
						throw new IdNotSetException("ID nicht gesetzt.");
					}
					$projektHandler = new ProjektHandler([$_POST["id"]]);
					$ret = $projektHandler->setState($_POST["newState"]);
					break;
				case "update":
					if (!isset($_POST["id"]) || !is_numeric($_POST["id"])){
						throw new IdNotSetException("ID nicht gesetzt.");
					}
					$projektHandler = new ProjektHandler([$_POST["id"]]);
					$ret = $projektHandler->updateSavedData($_POST);
					break;
				default:
					throw new ActionNotSetException("Unbekannte Aktion verlangt!");
			}
		}catch (ActionNotSetException $exception){
			$ret = false;
			$msgs[] = $exception->getMessage();
		}catch (IdNotSetException $exception){
			$ret = false;
			$msgs[] = $exception->getMessage();
		}catch (WrongVersionException $exception){
			$ret = false;
			$msgs[] = $exception->getMessage();
		}catch (IllegalStateException $exception){
			$ret = false;
			$msgs[] = "In diesen Status darf nicht gewechselt werden!";
			$msgs[] = $exception->getMessage();
		}catch (OldFormException $exception){
			$ret = false;
			$msgs[] = "Bitte lade das Projekt neu!";
			$msgs[] = $exception->getMessage();
		}catch (InvalidDataException $exception){
			$ret = false;
			$msgs[] = $exception->getMessage();
		}catch (PDOException $exception){
			$ret = false;
			$msgs[] = $exception->getMessage();
		}catch (IllegalTransitionException $exception){
			$ret = false;
			$msgs[] = $exception->getMessage();
		}finally{
			if ($ret)
				$dbret = DBConnector::getInstance()->dbCommit();
			if ($ret === false || $dbret === false){
				DBConnector::getInstance()->dbRollBack();
				$msgs[] = "DB Rollback";
				$target = "./";
			}else{
				$msgs[] = "Daten erfolgreich gespeichert!";
				$msgs[] = $ret;
				$target = $GLOBALS["URIBASE"] . "projekt/" . $projektHandler->getID();
			}
			if (isset($logId)){
				DBConnector::getInstance()->logAppend($logId, "result", $ret);
				DBConnector::getInstance()->logAppend($logId, "msgs", $msgs);
			}else{
				$msgs[] = "Logging nicht möglich :(";
			}
			 
			if (isset($projektHandler))
				DBConnector::getInstance()->logAppend($logId, "projekt_id", $projektHandler->getID());
		}
		
		$msgs[] = print_r($_POST, true);
		
		$this->json_result["msgs"] = $msgs;
		$this->json_result["ret"] = ($ret !== false);
		$this->json_result["target"] = $target;
		//if ($altTarget !== false)
		//    $result["altTarget"] = $altTarget;
		$this->json_result["forceClose"] = true;
		//$result["_REQUEST"] = $_REQUEST;
		//$result["_FILES"] = $_FILES;
		$this->print_json_result(true);
	}
}