<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 16.07.18
 * Time: 16:21
 */

class ChatHandler{
	// MEMBER =========================================================
	/**
	 * last lead comments
	 * @var array
	 */
    private $comments;
    
    /**
     * maximum comments id
     * @var integer
     */
    private $max_comment_id = 0;
    
    /**
     * maximum comments id - posted
     * @var integer
     */
    private $post_last_id = 0;
    
    /**
     * keep this values in filter
     * @var array
     */
    private $keep = ['0', '1'];
    
    /**
     * 
     * @var DBConnector
     */
    private $db;
    
    /**
     * access group
     * @var string
     */
    private $group;
    
    /**
     * access group_id
     * @var string
     */
    private $group_id;
    
    /**
     * username
     * @var string 
     */
    private $user;
    
    /**
     * userfullname
     * @var string
     */
    private $userfullname;

    /**
     * is error
     * @var array
     */
    private $error = NULL;
    
    /**
     * array map
     * @var array
     */
    private $colors = [
    	//own comment color
    	'owner' => [['DCDCDC', '000']],
    	'default' => [['CCCCCC', '000']],
    	//private message
    	'-1' => [['DDDDDD', '000']],
    	//normal comments color, map
    	'0' => [['C7CAC3', '000'], ['AEB2A8', '000'], ['989C90', '000'], ['84887C', '000'], ['BAC4A5', '000']],
    	//system message
    	'1' => [['5BC0DE', 'fff']],
    	//admin message
    	'2' => [['AA3939', '000'], ['801515', 'fff'], ['D46A6A', 'fff'], ['550000', 'fff'], ['FFAAAA', '000']],
    	//finanzen message
    	'3' => [['0D58A6', 'fff'], ['094480', 'fff'], ['306DAB', 'fff'], ['063465', 'fff'], ['5286BC', 'fff']],
    ];
    
    private $classMap = [
    	'-1' => 'chat-private',
    ];
    
    // STATIC MEMBER ==================================================
    
    private static $validateMap = [
    	'action' => [
    		'action' => ['regex',
    			'pattern' => '/^(newcomment|gethistory)$/',
    			'error' => 'Access Denied.',
    		],
    	],
    	'newcomment' => [
    		'action' => ['regex',
    			'pattern' => '/^(newcomment)$/',
    			'error' => 'Access Denied.',
    		],
    		'target_id' => ['integer',
    			'min' => '1',
    			'error' => 'Access Denied.',
    		],
    		'target' => ['regex',
    			'pattern' => '/^[a-zA-Z0-9]+$/',
    			'maxlength' => 63,
    			'error' => 'Access Denied.',
    		],
    		'text' => [ 'text',
    			'maxlength' => '4000',
    			'empty',
    			'htmlspecialchars',
    			'error' => 'Access Denied.',
    		],
    		'type' => ['regex',
    			'pattern' => '/^(-1|0|2|3)$/',
    			'maxlength' => 63,
    			'error' => 'Access Denied.',
    		],
    	],
    	'gethistory' => [
    		'action' => ['regex',
    			'pattern' => '/^(gethistory)$/',
    			'error' => 'Access Denied.',
    		],
    		'target_id' => ['integer',
    			'min' => '1',
    			'error' => 'Access Denied.',
    		],
    		'target' => ['regex',
    			'pattern' => '/^[a-zA-Z0-9]+$/',
    			'maxlength' => 63,
    			'error' => 'Access Denied.',
    		],
    		'last' => ['integer',
    			'min' => '0',
    			'error' => 'Access Denied.',
    		],
    	],
    	
    ];
    
    // CONSTRUCTOR ====================================================
    
    /**
     * class constructor
     * @param string 	$group
     * @param int 		$group_id
     * @param string 	$user
     */
    public function __construct($group, $group_id, $user = NULL, $userfullname = NULL){
    	$this->db = DBConnector::getInstance();
    	$this->group = $group;
    	$this->group_id = $group_id;
    	/* @var $auth AuthHandler */
    	$auth = AUTH_HANDLER;
    	$this->user = ($user)? $user : $auth::getInstance()->getUsername();
    	$this->userfullname = ($userfullname)? $userfullname : $auth::getInstance()->getUserFullName();
    	
    }
    
    // FRONTEND - GUI =================================================
    
    /**
     * TODO admin comment button 
     * @param integer $group_id		group identifier
     * @param string  $group		group
     * @param string  $user			user name + identifier
     * @param array $button		button definitions
     */
    public static function renderChatPanel($group, $group_id, $user, $buttons = [['label' => 'Senden', 'color' => 'success', 'type' => '0']]){
		if (!$group_id) return; ?>
			<div class="panel panel-default chat-panel">
				<input type="hidden" name="nononce" value="<?= strrev($GLOBALS["nonce"]); ?>">
	        	<input type="hidden" name="nonce" value="<?= $GLOBALS["nonce"]; ?>">
				<div class="panel-heading">Kommentare/Nachrichten</div>
				<div class="panel-body chat">
					<div class="new-chat-comment"
                         data-url="<?= URIBASE . 'rest/chat'; ?>"
                         data-target_id="<?= $group_id; ?>"
                         data-target="<?= $group; ?>">
                        <?php if (count($buttons)>0){ ?>
						<div class="chat-container chat-right">
							<span class="chat-time">Jetzt gerade</span>
							<label for="new-comment_<?php $tid = substr(base64_encode(sha1(mt_rand())),0,16); echo $tid;?>">
								<?= htmlspecialchars($user); ?>
							</label>
							<textarea id="new-comment_<?= $tid; ?>" class="chat-textarea form-control col-xs-10" rows="3"></textarea>
							<?php 
								foreach($buttons as $btn){ 
									?>
									<button type="button" style="margin: 0 0 5px 8px;"
											data-type="<?= $btn['type'] ?>"
											<?= (isset($btn['hover-title']))? 'title="'.$btn['hover-title'].'"': ''; ?>
											class="btn btn-<?= $btn['color']; ?> pull-right chat-submit"><?=
												$btn['label'];
											?></button>
								<?php }
							?>
							<div class="clearfix"></div>
						</div>
						<?php } ?>
					</div>
					<div class="clearfix"></div>
					<div class="chat-section">
						<div class="chat-loading"><div>Der Chat läd gerade...</div><div class="planespinner"><div class="rotating-plane"></div></div></div>
						<div class="chat-no-comments">Keine Komentare vorhanden</div>
						<div class="clearfix"></div>
					</div>
				</div>
			</div>
    	<?php 
    }
    
    // LOAD CHAT COMMENTS =============================================
    
    /**
     * load chat comments to instance
     * @param string $group
     * @param unknown $group_id
     * @param boolean $sort
     * @param number $incremental
     */
    public function _loadComments($group, $group_id, $sort = true, $incremental = 0){
    	$this->comments = $this->db->dbFetchAll(
    		"comments",
            [DBConnector::FETCH_ASSOC],
    		[],
    		["target" => $group, "target_id" => $group_id, 'id' => ['>', $incremental]],
    		[],
    		["timestamp" => $sort, 'id' => $sort]
    	);
    }
    
    /**
     * load chat comments to instance
     * @param boolean $sort
     * @param number $incremental
     * @param string $group
     * @param integer $group_id
     */
    public function loadComments($sort = true, $incremental = 0, $group = NULL, $group_id = NULL){
    	$this->_loadComments(($group)?$group:$this->group, ($group_id)?$group_id:$this->group_id, $sort, $incremental);
    }
    
    /**
     * add color and position information to comments
     * @param string $user
     * @param array $colors
     */
    public function _commentStyle($user, $colors){
    	$map = [];
    	foreach($this->colors as $k => $styleInfo){
    		$map[$k.''] = [
    			'user' => [],
    			'color-position' => 0,
    			'color-count' => count($this->colors[$k]),
    		];
    	}
    	foreach ($this->comments as $k => $c){
    		//position
    		if ($user == $c['creator']){
    			$this->comments[$k]['pos'] = 'right';
    		} elseif($c['type']=='1') {
    			$this->comments[$k]['pos'] = 'middle';
    		} else {
    			$this->comments[$k]['pos'] = 'left';
    		}
    		//color + border color
    		$colorKey = 'default';
    		if(array_key_exists($c['type'], $map)){
    			$colorKey = $c['type'];
    		}
			if ($c['type'] != 2 && $c['type'] != 3 && $c['creator'] == $user){
				$colorKey = 'owner';
			}
			//------------
			$cc = $c['creator'];
			if (!isset($map[$colorKey]['user'][$cc])) {
				$map[$colorKey]['user'][$cc] = $this->colors[$colorKey][($map[$colorKey]['color-position']%$map[$colorKey]['color-count'])];
				$map[$colorKey]['color-position'] = $map[$colorKey]['color-position'] + 1;
			}
			$this->comments[$k]['color'] = $map[$colorKey]['user'][$cc];
    		//extra class
    		if (isset($this->classMap[$c['type']])){
    			$this->comments[$k]['class'] = $this->classMap[$c['type']];
    		}
    		// ==========================
    		$this->max_comment_id = max($this->max_comment_id, $c['id'], $this->post_last_id);
    	}
    	$this->max_comment_id = max($this->max_comment_id, $this->post_last_id);
    }
    
    /**
     * add color information to comments
     * @param string $user
     * @param array $colors
     */
    public function commentStyle(){
    	$this->_commentStyle($this->user, $this->colors);
    }
    
    /**
     * unset not required comment information
     * @param array $keep unset types not in this array
     */
    public function filterComments($keep = NULL){
    	$kp = ($keep)? $keep : $this->keep;
    	$this->max_comment_id = 0;
    	$count = 0;
    	foreach ($this->comments as $k => $c){
    		if (!in_array($c['type'], $kp)){
    			$count++;
    			unset($this->comments[$k]);
    		} else {
    			if ($this->comments[$k]['type']==-1){
    				$this->comments[$k]['text'] = $this->decryptMessage($this->comments[$k]['text']);
    			}
    			$count++;
    			$this->max_comment_id = max($this->max_comment_id, $c['id'], $this->post_last_id);
    			unset($this->comments[$k]['id']); 
				unset($this->comments[$k]['target']); 
				unset($this->comments[$k]['target_id']);
				unset($this->comments[$k]['type']);
				unset($this->comments[$k]['creator']);
				$this->comments[$k]['count'] = $count;
				$count = 0;
    		}
    	}
    	$this->max_comment_id = max($this->max_comment_id, $this->post_last_id);
    }
    
    /**
     * set keep array
     * @param array $keep
     */
    public function setKeep($keep){
    	if ($keep && is_array($keep)){
    		$this->keep = $keep;
    	}
    }
    
    /**
     * return the $comments
     * @return array
     */
    public function getComments() {
    	return $this->comments;
    }
    
    /**
     * return the $max_comment_id
     * @return array
     */
    public function getMaxCommentId() {
    	return $this->max_comment_id;
    }
    
    // CREATE COMMENTS ================================================
    
    /**
     * create chat entry
     * @param string $group
     * @param integer $group_id
     * @param string $timestamp
     * @param string $creator
     * @param string $creator_alias
     * @param string $text
     * @param integer $type
     */
    public function _createComment($group, $group_id, $timestamp, $creator, $creator_alias, $text, $type){
    	try {
    		$this->db->dbInsert('comments', [
    			'target' 	=> mb_substr($group, 0, 63),
    			'target_id' => intval($group_id),
    			'timestamp' => mb_substr($timestamp, 0, 20),
    			'creator' 	=> mb_substr($creator, 0, 127),
    			'creator_alias' => mb_substr($creator_alias, 0, 255),
    			'text' 		=> ($type=='-1')? ($this->encryptMessage(mb_substr($text, 0, 45000))) : (mb_substr($text, 0, 60000)),
    			'type' 		=> $type,
    		]);
    	} catch (Exception $e){
    		$this->error = "Couln't create comment entry";
    		ErrorHandler::_errorLog('CHAT - Insert Error:' . $this->db->getPdo()->errorInfo()[2]);
    	}
    	
    }
    
    /**
     * create chat entry
     * @param string $text
     * @param integer $type
     * @param string $group
     * @param integer $group_id
     */
    public function createComment($text, $type, $group = NULL, $group_id = NULL){
    	$this->_createComment((($group)?$group:$this->group), (($group_id)?$group_id:$this->group_id), date_create()->format('Y-m-d H:i:s'), $this->user, $this->userfullname, $text, $type);
    }
    
    // VALIDATOR ======================================================
    
    /**
     * set error message -> may use this for ACL
     * @param unknown $msg
     */
    public function setErrorMessage($msg){
    	if (!$this->error && is_string($msg) && $msg){
    		$this->error = $msg;
    	}
    }
    
    /**
     * return error state
     * @return boolean
     */
    public function isError(){
    	return (is_string($this->error) && $this->error);
    }
    
    /**
     * return error message
     * @return string
     */
    public function getError(){
    	if ($this->error){
    		return $this->error;
    	} else {
    		return '';
    	}
    }
    
    /**
     * reset the error
     */
    public function resetError(){
    	 $this->error = NULL;
    }
    
    /**
     * validate post data
     * @param array string
     */
    public function validatePost($post){
    	$vali = new Validator();
    	$vali->validateMap($post, self::$validateMap['action'], true);
    	if($vali->getIsError()){
    		$this->error = $vali->getLastErrorMsg();
    		return NULL;
    	}
    	$vali->validateMap($post, self::$validateMap[$vali->getFiltered('action')], true);
    	if($vali->getIsError()){
    		$this->error = $vali->getLastErrorMsg();
    		return NULL;
    	}
    	return $vali->getFiltered();
    }
    
    // JSON HANDLER ===================================================
    
    /**
     * return json response
     */
    public function answerJson($json){
    	http_response_code($json['code']);
    	header("Content-Type: application/json");
    	echo json_encode($json, JSON_HEX_QUOT | JSON_HEX_TAG);
    }
    
    /**
     * return erro json response
     */
    public function answerError(){
    	if($this->error){
    		$this->answerJson([
    			'success' => false,
    			'code' => 403,
    			'msg' => $this->error,
    		]);
    	}
    }
    
    /**
     * please note this function DOES NOT contain an ACL
     * use this function as reference how to anser chat calls
     * check 
     * 		target
     * 		target_id 
     * 		type (if 'action' is set to 'newcomment')
     */
    public function answerAll($post) {
    	if (!$post || !is_array($post) || !isset($post['action'])){
    		$this->error = 'Action Denied.';
    		return $this->answerError();
    	}
    	$post = $this->validatePost($post);
    	if ($this->error){
    		return $this->answerError();
    	}
    	switch ($post['action']){
    		case 'newcomment': {
    			$this->createComment($post['text'], $post['type'], $post['target'], $post['target_id']);
    			$this->answerJson([
    				'success' => true,
    				'code' => 200,
    				'msg' => 'created',
    			]);
    		} break;
    		case 'gethistory': {
    			$this->post_last_id = $post['last'];
    			$this->loadComments(true, $post['last'], $post['target'], $post['target_id']);
    			$this->commentStyle();
    			$this->filterComments();
    			$this->answerJson([
    				'success' => true,
    				'code' => 200,
    				'data' => $this->comments,
    				'last' => $this->max_comment_id,
    			]);
    		} break;
    		default: {
    			ErrorHandler::_errorLog('Chat: Error: Unhandles Action passed Validation: '.$post['action']);
    		} break;
    	}
    }
    
    // crypto =======================================
    
    /**
     * create crypto keys
     * RSA @ 4096
     */
    private function createKeys(){
    	//check files exists
    	if (!file_exists(__DIR__.'/secret_private.php')||filesize(__DIR__.'/secret_private.php') == 0){
	    	$config = array(
	    		"digest_alg" => "sha512",
	    		"private_key_bits" => 4096,
	    		"private_key_type" => OPENSSL_KEYTYPE_RSA,
	    	);
	    	$res = openssl_pkey_new($config);
	    	openssl_pkey_export($res, $privKey);
	    	$this->textToFile(__DIR__.'/secret_private.php', $privKey);
	    	$pubKey = openssl_pkey_get_details($res);
	    	$pubKey = $pubKey["key"];
	    	$this->textToFile(__DIR__.'/secret_public.php', $pubKey);
    	}
    }
    
    /**
     * create file with text
     * mace shure you can write on given location
     * @param string $file
     * @param text $text
     * @param string $permission
     */
    private function textToFile($file, $text, $permission = '0400'){
    	$text = base64_encode($text);
    	
    	//create file content
    	$key_file_content = "<?php //* -------------------------------------------------------- *\n";
    	$key_file_content .= "// Must include code to stop this file being accessed directly\n";
    	$key_file_content .= "if(!defined('FINANRANTRAGUI_FW_SI')) die(); \n";
    	$key_file_content .= "//* -------------------------------------------------------- *\n";
    	$key_file_content .= '$key = \''.$text."';\n ?>";
    	
    	//create file
    	$handle = fopen ($file, 'w');
    	fwrite ($handle, $key_file_content);
    	fclose ($handle);
    	chmod($file, 0400);
    }
    
    /**
     * get text/key by key
     * @param string $type
     * @return string
     */
    private function getKey($type = 'public'){
    	switch ($type){
    		case 'public': {
    			return $this->_getKey(__DIR__.'/secret_public.php');
    		} break;
    		case 'private': {
    			return $this->_getKey(__DIR__.'/secret_private.php');
    		} break;
    		default: {
    			return '';
    		}
    	}
    }
    
    /**
     * encrypt chat message
     * @param string $text
     * @return string
     */
    private function encryptMessage($text){
    	if ($text == '') return '';
    	$this->createKeys();
    	return $this->_encryptMessage($text, $this->getKey('public'));
    }
    
    /**
     * decrypt chat message
     * @param string $text
     * @return string
     */
    private function decryptMessage($text){
    	if ($text == '') return '';
    	$this->createKeys();
    	return $this->_decryptMessage($text, $this->getKey('private'));
    }
    
    /**
     * get text/key from file
     * @param string $filename
     * @return string
     */
    private function _getKey($filename){
    	require $filename;
    	return base64_decode($key);
    }
    
    /**
     * encrypt chat message by key
     * @param string $text
     * @param string $key
     * @return string
     */
    private function _encryptMessage($text, $key){
    	openssl_public_encrypt($text, $encrypted, $key);
    	return base64_encode($encrypted);
    }
    
    /**
     * decrypt chat message by key
     * @param string $encrypted
     * @param string $key
     * @return string
     */
    private function _decryptMessage($encrypted, $key){
    	openssl_private_decrypt(base64_decode($encrypted), $decrypted, $key);
    	if ($decrypted === NULL) return '<strong><i>! Corrupted message. !</i></strong>';
    	return $decrypted;
    }
}