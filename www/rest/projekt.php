<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 07.05.18
 * Time: 02:16
 */

require "../../lib/inc.all.php";
global $nonce;
AuthHandler::getInstance()->requireAuth();

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
            $ret = $projektHandler->updateMetaData($_POST);
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
    DBConnector::getInstance()->logAppend($logId, "result", $ret);
    DBConnector::getInstance()->logAppend($logId, "msgs", $msgs);
    
    if (isset($projektHandler))
        DBConnector::getInstance()->logAppend($logId, "projekt_id", $projektHandler->getID());
}

$msgs[] = print_r($_POST, true);

$result = [];
$result["msgs"] = $msgs;
$result["ret"] = ($ret !== false);
$result["target"] = $target;
//if ($altTarget !== false)
//    $result["altTarget"] = $altTarget;
$result["forceClose"] = true;
//$result["_REQUEST"] = $_REQUEST;
//$result["_FILES"] = $_FILES;

header("Content-Type: text/json; charset=UTF-8");
echo json_encode($result);
exit;
