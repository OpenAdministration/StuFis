<?php
/**
 * FRAMEWORK JsonHandler
 *
 * @package           Stura - Referat IT - ProtocolHelper
 * @category          framework
 * @author            michael g
 * @author            Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since             17.02.2018
 * @copyright         Copyright (C) 2018 - All rights reserved
 * @platform          PHP
 * @requirements      PHP 7.0 or higher
 */
include_once dirname(__FILE__) . '/class.JsonController.php';

class RestHandler extends JsonController{
    
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
     *
     * @param array $routeInfo
     */
    public function handlePost($routeInfo = null){
        global $nonce;
    
        if (!isset($_POST["nonce"]) || $_POST["nonce"] !== $nonce || isset($_POST["nononce"])){
            ErrorHandler::_renderError('Access Denied.', 403);
        }else{
            unset($_POST["nonce"]);
        }
    
        switch ($routeInfo['action']){
            case 'projekt':
                $this->handleProjekt($routeInfo);
                break;
            case 'auslagen':
                $this->handleAuslagen($routeInfo);
                break;
            case 'chat':
                $this->handleChat($routeInfo);
                break;
            case 'update-konto':
                $this->updateKonto($routeInfo);
                break;
            case "new-booking":
                $this->newBooking($routeInfo);
                break;
            case 'nononce':
            default:
                ErrorHandler::_errorExit('Unknown Action: ' . $routeInfo['action']);
                break;
        }
    }
    
    private function newBooking($routeInfo){
        
        if (!isset($_POST["zahlung"])
            || !is_array($_POST["zahlung"])
            || !isset($_POST["beleg"])
            || !is_array($_POST["beleg"])
            || !isset($_POST["booking-text"])
            || empty($_POST["booking-text"])
        ){
            $errorMsg = "Bitte stelle sicher, das du alle Felder ausgefüllt hast.";
        }
        
        $zahlung = $_POST["zahlung"];
        $beleg = $_POST["beleg"];
        
        if ((count($zahlung) === 1 && count($beleg) >= 1)
            || (count($beleg) === 1 && count($zahlung) >= 1)
        ){
            $errorMsg = "Es kann immer nur 1 Zahlung zu n Belegen oder 1 Beleg zu n Zahlungen zugeordnet werden. Andere Zuordnungen sind nicht möglich!";
        }
        
        if (isset($errorMsg)){
            JsonController::print_json([
                'success' => false,
                'status' => '500',
                'msg' => $errorMsg,
                'type' => 'modal',
                'subtype' => 'server-error',
                'reload' => 2000,
                'headline' => 'Unfolständige Datenübertragung',
                //'redirect' => URIBASE.'projekt/'.$this->projekt_id.'/auslagen/'.$this->auslagen_data['id'],
            ]);
        }
        
        
        JsonController::print_json([
            'success' => true,
            'status' => '200',
            'msg' => "Buchung wurde gespeichert",
            'type' => 'modal',
            'subtype' => 'server-success',
            //'reload' => 2000,
            'headline' => 'Erfolgreich gespeichert',
            //'redirect' => URIBASE.'projekt/'.$this->projekt_id.'/auslagen/'.$this->auslagen_data['id'],
        ]);
    }
    
    public function handleProjekt($routeInfo = null){
        $ret = false;
        $msgs = [];
        $projektHandler = null;
        $dbret = false;
        try{
            $logId = DBConnector::getInstance()->logThisAction($_POST);
            DBConnector::getInstance()->logAppend($logId, "username", (AUTH_HANDLER)::getInstance()->getUsername());
            
            if (!isset($_POST["action"]))
                throw new ActionNotSetException("Es wurde keine Aktion übertragen");
            
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
                    $projektHandler = new ProjektHandler(["pid" => $_POST["id"], "action" => "none"]);
                    $ret = $projektHandler->setState($_POST["newState"]);
                    break;
                case "update":
                    if (!isset($_POST["id"]) || !is_numeric($_POST["id"])){
                        throw new IdNotSetException("ID nicht gesetzt.");
                    }
                    $projektHandler = new ProjektHandler(["pid" => $_POST["id"], "action" => "edit"]);
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
                $msgs[] = "Deine Änderungen wurden nicht gespeichert (DB Rollback)";
                $target = "./";
            }else{
                $msgs[] = "Daten erfolgreich gespeichert!";
                $target = URIBASE . "projekt/" . $projektHandler->getID();
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
        if (DEV)
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
    
    /**
     * handle auslagen posts
     *
     * @param string $routeInfo
     */
    public function handleAuslagen($routeInfo = null){
        $func = '';
        if (isset($routeInfo['mfunction'])){
        }else if (isset($_POST['action'])){
            $routeInfo['mfunction'] = $_POST['action'];
        }else{
            ErrorHandler::_renderError('Unknown Action.', 404);
        }
    
        //validate
        $vali = new Validator();
        $validator_map = [];
        switch ($routeInfo['mfunction']){
            case 'updatecreate':
                $validator_map = [
                    'version' => ['integer',
                        'min' => '1',
                        'error' => 'Ungültige Versionsnummer.'
                    ],
                    'etag' => ['regex',
                        'pattern' => '/^(0|([a-f0-9]){32})$/',
                        'error' => 'Ungültige Version.'
                    ],
                    'projekt-id' => ['integer',
                        'min' => '1',
                        'error' => 'Ungültige Projekt ID.'
                    ],
                    'auslagen-id' => ['regex',
                        'pattern' => '/^(NEW|[1-9]\d*)$/',
                        'error' => 'Ungültige Auslagen ID.'
                    ],
                    'auslagen-name' => ['regex',
	                    'pattern' => '/^[a-zA-Z0-9\-_ :%$§\&\+\*\.!\?\/\\\[\]\'"#~()äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]*$/',
	                    'maxlength' => '255',
                        'empty',
                        'error' =>	'Ungültiger Auslagen name.'
                    ],
                    'zahlung-name' => ['regex',
	                    'pattern' => '/^[a-zA-Z0-9\-_ :%$§\&\+\*\.!\?\/\\\[\]\'"#~()äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]*$/',
	                    'maxlength' => '127',
                        'empty',
                        'error' =>	'Ungültiger Zahlungsempfänger.'
                    ],
                    'zahlung-iban' => ['regex',
                        'pattern' => '/^(([a-zA-Z]{2}\s?\d{2}\s?([0-9a-zA-Z]{4}\s?){4}[0-9a-zA-Z]{2})|([a-zA-Z0-9]{4}( ... ... )[a-zA-Z0-9]{2}))$/',
                        'maxlength' => '127',
                        'empty',
                        'error' => 'Ungültige Iban.'
                    ],
                    'zahlung-vwzk' => ['regex',
                        'pattern' => '/^[a-zA-Z0-9\-_,$§:;\/\\\\()!?& .\[\]%\'"#~\*\+äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]*$/',
                        'empty',
                        'maxlength' => '127',
                    ],
                    'belege' => ['array', 'optional',
                        'minlength' => 1,
                        'key' => ['regex',
                            'pattern' => '/^(new_)?(\d+)$/'
                        ],
                        'validator' => ['arraymap',
                            'required' => true,
                            'map' => [
                                'datum' => ['date',
                                    'empty',
                                    'format' => 'Y-m-d',
                                    'parse' => 'Y-m-d',
                                    'error' => 'Ungültiges Beleg Datum.'
                                ],
                                'beschreibung' => ['text',
                                    'strip',
                                    'trim',
                                ],
                                'posten' => ['array', 'optional',
                                    'minlength' => 1,
                                    'key' => ['regex',
                                        'pattern' => '/^(new_)?(\d+)$/'
                                    ],
                                    'validator' => ['arraymap',
                                        'required' => true,
                                        'map' => [
                                            'projekt-posten' => ['integer',
                                                'min' => '1',
                                                'error' => 'Invalid Projektposten ID.'
                                            ],
                                            'in' => ['float',
                                                'step' => '0.01',
                                                'format' => '2',
                                                'min' => '0',
                                                #'error' => 'Posten - Einnahmen: Ungültiger Wert'
                                            ],
                                            'out' => ['float',
                                                'step' => '0.01',
                                                'format' => '2',
                                                'min' => '0',
                                                #'error' => 'Posten - Ausgaben: Ungültiger Wert'
                                            ],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                ];
                break;
            case 'filedelete':
                $validator_map = [
                    'etag' => ['regex',
                        'pattern' => '/^(0|([a-f0-9]){32})$/',
                        'error' => 'Ungültige Version.'
                    ],
                    'projekt-id' => ['integer',
                        'min' => '1',
                        'error' => 'Ungültige Projekt ID.'
                    ],
                    'auslagen-id' => ['integer',
                        'min' => '1',
                        'error' => 'Ungültige Auslagen ID.'
                    ],
                    'fid' => ['integer',
                        'min' => '1',
                        'error' => 'Ungültige Datei ID.'
                    ],
                ];
                break;
            case 'state':
                $auslagen_states = [];
                $validator_map = [
                    'etag' => ['regex',
                        'pattern' => '/^(0|([a-f0-9]){32})$/',
                        'error' => 'Ungültige Version.'
                    ],
                    'projekt-id' => ['integer',
                        'min' => '1',
                        'error' => 'Ungültige Projekt ID.'
                    ],
                    'auslagen-id' => ['integer',
                        'min' => '1',
                        'error' => 'Ungültige Auslagen ID.'
                    ],
                    'state' => ['regex',
                        'pattern' => '/^(draft|wip|ok|instructed|booked|revocation|payed|ok-hv|ok-kv|ok-belege|revoked|rejected)$/',
                        'error' => 'Ungültiger Status.'
                    ],
                ];
                break;
            case 'belegpdf':
                $auslagen_states = [];
                $validator_map = [
                    'projekt-id' => ['integer',
                        'min' => '1',
                        'error' => 'Ungültige Projekt ID.'
                    ],
                    'auslagen-id' => ['integer',
                        'min' => '1',
                        'error' => 'Ungültige Auslagen ID.'
                    ],
                    'd' => ['integer', 'optional',
                        'min' => '0',
                        'max' => '1',
                        'error' => 'Ungültige Parameter.'
                    ],
                ];
                break;
            default:
                ErrorHandler::_renderError('Unknown Action.', 404);
                break;
        }
        $vali->validateMap($_POST, $validator_map, true);
        //return error if validation failed
        if ($vali->getIsError()){
            JsonController::print_json([
                'success' => false,
                'status' => '200',
                'msg' => $vali->getLastErrorMsg(),
                'type' => 'validator',
                'field' => $vali->getLastMapKey(),
            ]);
        }
        $validated = $vali->getFiltered();
    
        if ($routeInfo['mfunction'] == 'updatecreate'){
            //may add nonexisting arrays
            if (!isset($validated['belege'])){
                $validated['belege'] = [];
            }
            foreach ($validated['belege'] as $k => $v){
                if (!isset($v['posten'])){
                    $validated['belege'][$k]['posten'] = [];
                }
            }
            //check all values empty?
            $empty = ($validated['auslagen-id'] == 'NEW');
            $auslagen_test_empty = ['auslagen-name', 'zahlung-name', 'zahlung-iban', 'zahlung-vwzk', 'belege'];
            $belege_test_empty = ['datum', 'beschreibung', 'posten'];
            $posten_text_empty = ['out', 'in'];
            if ($empty) foreach ($auslagen_test_empty as $e){
                if (is_string($validated[$e]) && !!$validated[$e]
                    || is_array($validated[$e]) && count($validated[$e])){
                    $empty = false;
                    break;
                }
            }
            if ($empty) foreach ($validated['belege'] as $kb => $belege){
                foreach ($belege_test_empty as $e){
                    if (is_string($belege[$e]) && !!$belege[$e]
                        || is_array($belege[$e]) && count($belege[$e])){
                        $empty = false;
                        break 2;
                    }
                }
                foreach ($belege['posten'] as $posten){
                    foreach ($posten_text_empty as $e){
                        if (is_string($posten[$e]) && !!$posten[$e]
                            || is_array($posten[$e]) && count($posten[$e])){
                            $empty = false;
                            break 3;
                        }
                    }
                }
            
                //check file non empty
                $fileIdx = 'beleg_' . $kb;
                if (isset($_FILES[$fileIdx]['error']) && $_FILES[$fileIdx]['error'] === 0){
                    $empty = false;
                    break;
                }
            }
            //error reply
            if ($empty){
                JsonController::print_json([
                    'success' => false,
                    'status' => '200',
                    'msg' => 'Leere Auslagenerstattungen können nicht gespeichert werden.',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                ]);
            }
        }
        $routeInfo['pid'] = $validated['projekt-id'];
        if ($validated['auslagen-id'] != 'NEW'){
            $routeInfo['aid'] = $validated['auslagen-id'];
        }
        $routeInfo['validated'] = $validated;
        $routeInfo['action'] = 'post';
        //call auslagen handler
        $handler = new AuslagenHandler2($routeInfo);
        $handler->handlePost();
    
        //error reply
        if ($empty){
            JsonController::print_json([
                'success' => false,
                'status' => '200',
                'msg' => 'Der Posthandler hat die Anfrage nicht beantwortet.',
                'type' => 'modal',
                'subtype' => 'server-error',
            ]);
        }
    }
    
    private function handleChat($routeInfo){
    	$db = DBConnector::getInstance();
    	$chat = new ChatHandler(NULL, NULL);
   		$valid = $chat->validatePost($_POST);
   		$auth = (AUTH_HANDLER);
   		/* @var $auth AuthHandler */
   		$auth = $auth::getInstance();
    	if ($valid){
    		//access permission control
    		switch ($valid['target']){
    			case 'projekt': {
    				$r = [];
    				try{
    					$r = $db->dbFetchAll('projekte', [], ['projekte.id' => $valid['target_id']], [
							["type" => "left", "table" => "user", "on" => [["user.id", "projekte.creator_id"]]],
						]);
    				} catch (Exception $e){
    					ErrorHandler::_errorLog('RestHandler:  ' . $e->getMessage());
    					break;
    				}
    				if (!$r || count($r) == 0){
    					break;
    				}
    				// ACL --------------------------------
    				// action 
    				switch ($valid['action']){
    					case 'gethistory':
    						$map = ['0', '1'];
    						if ($auth->hasGroup('admin')) {
    							$map[] = '2';
    						}
    						if ($auth->hasGroup('ref-finanzen')) {
    							$map[] = '3';
    						}
    						if ($auth->hasGroup('ref-finanzen') || isset($r[0]['username']) && $r[0]['username'] == (AUTH_HANDLER)::getInstance()->getUsername()) {
    							$map[] = '-1';
    						}
    						$chat->setKeep($map);
    						break;
    					case 'newcomment':
    						if (!preg_match('/^(draft|wip|revoked|ok-by-hv|need-stura|done-hv|done-other|ok-by-stura)/', $r[0]['state'])){
    							break 2;
    						}
    						//switch type
    						switch ($valid['type']){
    							case '-1':
    								if (!$auth->hasGroup('ref-finanzen') && (!isset($r[0]['username']) || $r[0]['username'] != (AUTH_HANDLER)::getInstance()->getUsername())) {
    									break 3;
    								}
    								break;
    							case '0':
    								break;
    							case '1':
    								break 3;
    							case '2':
    								if (!$auth->hasGroup('admin')) {
    									break 3;
    								}
    								break;
    							case '3':
    								if (!$auth->hasGroup('ref-finanzen')) {
    									break 3;
    								}
    								break;
    							default: 
    								break 3;
    						}
    						break;
    					default: 
    						break 2;
    				}
    				// all ok -> handle all
    				$chat->answerAll($_POST);
    				die();
    			} break;
    			default:
	    			break;
    		}
    	}
    	$chat->setErrorMessage('Access Denied.');
    	$chat->answerError();
    	die();	
    }
    
    private function updateKonto($routeInfo){
        (AUTH_HANDLER)::getInstance()->requireGroup(HIBISCUSGROUP);
        
        $ret = true;
        if (!DBConnector::getInstance()->dbBegin()){
            ErrorHandler::_errorExit("Kann keine Verbindung zur SQL-Datenbank aufbauen. Bitte versuche es später erneut!");
        }
        
        //$newFormAnfangsbestand = HibiscusXMLRPCConnector::getInstance()->fetchFromHibiscusAnfangsbestand();
        
        $allZahlungen = HibiscusXMLRPCConnector::getInstance()->fetchFromHibiscus();
        if ($allZahlungen === false){
            JsonController::print_json([
                'success' => false,
                'status' => '500',
                'msg' => 'Konnte keine Verbindung mit Onlinebanking Service aufbauen',
                'type' => 'modal',
                'subtype' => 'server-error',
            ]);
        }
        $lastId = DBConnector::getInstance()->dbFetchAll("konto", ["id" => ["id", DBConnector::MAX]]);
        if (is_array($lastId)){
            $lastId = $lastId[0]["id"];
        }
        
        $inserted = 0;
        foreach ($allZahlungen as $zahlung){
            if ($lastId && $zahlung["id"] < $lastId)
                continue;
            $fields = [];
            $fields['id'] = $zahlung["id"];
            $fields['konto_id'] = $zahlung["konto_id"];
            $fields['date'] = $zahlung["datum"];
            $lastDate = $zahlung["datum"];
            $fields['type'] = $zahlung["art"];
            $fields['valuta'] = $zahlung["valuta"];
            $fields['primanota'] = $zahlung["primanota"];
            $fields['value'] = DBConnector::getInstance()->convertUserValueToDBValue($zahlung["betrag"], "money");
            $fields['empf_name'] = $zahlung["empfaenger_name"];
            $fields['empf_iban'] = $zahlung["empfaenger_konto"];
            $fields['empf_bic'] = $zahlung["empfaenger_blz"];
            $fields['saldo'] = $zahlung["saldo"];
            $fields['gvcode'] = $zahlung["gvcode"];
            $fields['zweck'] = $zahlung["zweck"];
            $fields['comment'] = $zahlung["kommentar"];
            $fields['customer_ref'] = $zahlung["customer_ref"];
            //$msgs[]= print_r($zahlung,true);
            DBConnector::getInstance()->dbInsert("konto", $fields);
            $inserted++;
        }
        
        $ret = DBConnector::getInstance()->dbCommit();
        
        if (!$ret){
            DBConnector::getInstance()->dbRollBack();
        }else{
            if ($inserted > 0){
                JsonController::print_json([
                    'success' => true,
                    'status' => '200',
                    'msg' => "$inserted neue Umsätze gefunden.",
                    'type' => 'modal',
                    'subtype' => 'server-success',
                ]);
            }else{
                JsonController::print_json([
                    'success' => false,
                    'status' => '200',
                    'msg' => 'Keine neuen Umsätze gefunden.',
                    'type' => 'modal',
                    'subtype' => 'server-error',
                ]);
            }
        }
    }
}