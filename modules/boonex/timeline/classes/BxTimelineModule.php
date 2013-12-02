<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) BoonEx Pty Limited - http://www.boonex.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 * 
 * @defgroup    Timeline Timeline
 * @ingroup     DolphinModules
 *
 * @{
 */

bx_import('BxDolAcl');
bx_import('BxDolModule');

require_once( BX_DIRECTORY_PATH_PLUGINS . 'Services_JSON.php' );

define('BX_TIMELINE_TYPE_OWNER', 'owner');
define('BX_TIMELINE_TYPE_CONNECTIONS', 'connections');

define('BX_TIMELINE_HANDLER_TYPE_INSERT', 'insert');
define('BX_TIMELINE_HANDLER_TYPE_UPDATE', 'update');
define('BX_TIMELINE_HANDLER_TYPE_DELETE', 'delete');

define('BX_TIMELINE_FILTER_ALL', 'all');
define('BX_TIMELINE_FILTER_OWNER', 'owner');
define('BX_TIMELINE_FILTER_OTHER', 'other');

define('BX_TIMELINE_PARSE_TYPE_TEXT', 'text');
define('BX_TIMELINE_PARSE_TYPE_LINK', 'link');
define('BX_TIMELINE_PARSE_TYPE_PHOTO', 'photo');
define('BX_TIMELINE_PARSE_TYPE_SHARE', 'share');
define('BX_TIMELINE_PARSE_TYPE_DEFAULT', BX_TIMELINE_PARSE_TYPE_TEXT);


class BxTimelineModule extends BxDolModule
{
    var $_iOwnerId;
    var $_sJsPostObject;
    var $_sJsViewObject;
    var $_aPostElements;
    var $_sJsOutlineObject;

    var $_sDividerTemplate;
    var $_sBalloonTemplate;
    var $_sCmtPostTemplate;
    var $_sCmtViewTemplate;
    var $_sCmtTemplate;

    /**
     * Constructor
     */
    function __construct($aModule)
    {
        parent::BxDolModule($aModule);
        $this->_oConfig->init($this->_oDb);
        $this->_oTemplate->init();
        $this->_iOwnerId = 0;
    }

	/**
     * ACTION METHODS
     */
    public function actionPost()
    {
    	$sType = bx_process_input($_POST['type']);
	    $sMethod = 'getForm' . ucfirst($sType);
		if(!method_exists($this, $sMethod)) {
			$this->_echoResultJson(array());
        	return;
		}

        $this->_iOwnerId = bx_process_input(bx_get('owner_id'), BX_DATA_INT);
        if (!$this->isAllowedPost(true)) {
        	$this->_echoResultJson(array('msg' => bx_js_string(_t('_bx_timeline_txt_msg_not_allowed_post'))));
			return;
        }
        $aResult = $this->$sMethod();

        $this->_echoResultJson($aResult);
    }

	function actionDelete()
    {
        $this->_iOwnerId = bx_process_input(bx_get('owner_id'), BX_DATA_INT);
        if(!$this->isAllowedDelete(true)) {
            $this->_echoResultJson(array('code' => 1));
            return;
        }

        $iId = bx_process_input(bx_get('id'), BX_DATA_INT);
        $aEvent = $this->_oDb->getEvents(array('browse' => 'id', 'value' => $iId));

        if(!$this->_oDb->deleteEvent(array('id' => $iId))) {
        	$this->_echoResultJson(array('code' => 2));
            return;
        }

        $this->onDelete($aEvent);

        $this->_echoResultJson(array('code' => 0, 'id' => $iId));
    }

	public function actionShare()
    {
    	$iOwnerId = bx_process_input(bx_get('owner_id'), BX_DATA_INT);
    	$aContent = array(
    		'type' => bx_process_input(bx_get('type'), BX_DATA_TEXT),
    		'action' => bx_process_input(bx_get('action'), BX_DATA_TEXT),
    		'object_id' => bx_process_input(bx_get('object_id'), BX_DATA_INT),	
    	);

		$iId = $this->_oDb->insertEvent(array(
            'owner_id' => $iOwnerId,
            'type' => $this->_oConfig->getPrefix('common_post') . 'share',
            'action' => '',
        	'object_id' => $this->getUserId(),
        	'object_privacy_view' => $this->_oConfig->getPrivacyViewDefault(),
            'content' => serialize($aContent),
			'title' => '',
			'description' => ''
        ));

        if(!empty($iId)) {
        	$this->onShare($iId);

        	$this->_echoResultJson(array('msg' => _t('_bx_timeline_txt_msg_success_share')));
        	return;
        }

		$this->_echoResultJson(array('msg' => _t('_bx_timeline_txt_err_cannot_share')));
    }

    function actionGetPost()
    {
        $this->_oConfig->setJsMode(true);
        $this->_iOwnerId = bx_process_input(bx_get('owner_id'), BX_DATA_INT);

        $iEvent = bx_process_input(bx_get('id'), BX_DATA_INT);
        $aEvent = $this->_oDb->getEvents(array('browse' => 'id', 'value' => $iEvent));

        $this->_echoResultJson(array('item' => $this->_oTemplate->getPost($aEvent, array('type' => 'owner', 'owner_id' => $this->_iOwnerId))));
    }

	function actionGetPosts()
    {
        $this->_oConfig->setJsMode(true);

		$aParams = $this->_prepareParamsGet();
		list($sItems, $sLoadMore, $sBack) = $this->_oTemplate->getPosts($aParams);

		$this->_echoResultJson(array('items' => $sItems, 'load_more' => $sLoadMore, 'back' => $sBack));
    }

    public function actionGetPostForm($sType)
    {
    	$this->_iOwnerId = bx_process_input(bx_get('owner_id'), BX_DATA_INT);

    	$sMethod = 'getForm' . ucfirst($sType);
		if(!method_exists($this, $sMethod)) {
			$this->_echoResultJson(array());
        	return;
		}
    	$aResult = $this->$sMethod();

    	$this->_echoResultJson($aResult);
    }

    public function actionGetComments()
    {
    	$this->_iOwnerId = bx_process_input(bx_get('owner_id'), BX_DATA_INT);

    	$sSystem = bx_process_input(bx_get('system'), BX_DATA_TEXT);
    	$iId = bx_process_input(bx_get('id'), BX_DATA_INT);
    	$sComments = $this->_oTemplate->getComments($sSystem, $iId);

    	$this->_echoResultJson(array('content' => $sComments));
    }

    public function actionGetPostPopup()
    {
    	$iItemId = bx_process_input(bx_get('id'), BX_DATA_INT);
    	if(!$iItemId) {
    		$this->_echoResultJson(array());
    		return;
    	}

    	$this->_echoResultJson(array(
    		'popup' => $this->_oTemplate->getViewItemPopup($iItemId)
    	));
    }

    function actionRss($iOwnerId)
    {
    	list($sUserName) = $this->getUserInfo($iOwnerId);

    	$sRssCaption = _t('_bx_timeline_txt_rss_caption', $sUserName);
    	$sRssLink = $this->_oConfig->getViewUrl($iOwnerId);

    	$aParams = $this->_prepareParams('owner', $iOwnerId, 0, $this->_oConfig->getRssLength(), '', array(), 0);
        $aEvents = $this->_oDb->getEvents($aParams);

        $aRssData = array();
        foreach($aEvents as $aEvent) {
            if(empty($aEvent['title'])) continue;

            $aRssData[$aEvent['id']] = array(
               'UnitID' => $aEvent['id'],
               'UnitTitle' => $aEvent['title'],
               'UnitLink' => $this->_oConfig->getItemViewUrl($aEvent),
               'UnitDesc' => $aEvent['description'],
               'UnitDateTimeUTS' => $aEvent['date'],
            );
        }

        bx_import('BxDolRssFactory');
        $oRss = new BxDolRssFactory();

        header('Content-Type: text/html; charset=utf-8');
        echo $oRss->GenRssByData($aRssData, $sRssCaption, $sRssLink);
    }

	/**
     * SERVICE METHODS
     */
    public function serviceAddHandlers($sModuleUri = 'all')
    {
    	$this->_updateHandlers($sModuleUri, true);
    }

    public function serviceDeleteHandlers($sModuleUri = 'all')
    {
    	$this->_updateHandlers($sModuleUri, false);
    }

	function serviceGetActionsChecklist()
    {
        $aHandlers = $this->_oConfig->getHandlers();

        $aResults = array();
        foreach($aHandlers as $aHandler) {
        	if($aHandler['type'] != BX_TIMELINE_HANDLER_TYPE_INSERT)
        		continue;

            $aModule = $this->_oDb->getModuleByName($aHandler['module_name']);
            if(empty($aModule))
                $aModule['title'] = _t('_bx_timeline_alert_module_' . $aHandler['alert_unit']);

            $aResults[$aHandler['id']] = $aModule['title'] . ' (' . _t('_bx_timeline_alert_action_' . $aHandler['alert_action']) . ')';
        }

        asort($aResults);
        return $aResults;
    }

	public function serviceResponse($oAlert)
    {
    	bx_import('Response', $this->_aModule);
        $oResponse = new BxTimelineResponse($this);
        $oResponse->response($oAlert);
    }

    public function serviceGetBlockPost($iProfileId = 0)
    {
    	return $this->serviceGetBlockPostProfile($iProfileId);
    }

	public function serviceGetBlockPostProfile($iProfileId = 0)
	{
		if(!$iProfileId && bx_get('id') !== false) {
			$oProfile = BxDolProfile::getInstanceByContentAndType(bx_process_input(bx_get('id'), BX_DATA_INT), 'bx_persons');
			if(!empty($oProfile))
            	$iProfileId = $oProfile->id();
		}

        if (!$iProfileId)
            return array();

		$this->_iOwnerId = $iProfileId;
        list($sUserName, $sUserUrl) = $this->getUserInfo($iProfileId);

        if($this->_iOwnerId != $this->getUserId() && !$this->isAllowedPost())
            return array();

		bx_import('BxDolMenu');
		$oMenu = BxDolMenu::getObjectInstance($this->_oConfig->getObject('menu_post'));
		$oMenu->setMenuId('timeline-post-menu');
		$oMenu->setSelected($this->getName(), 'post-text');

        $sContent = $this->_oTemplate->getPostBlock($this->_iOwnerId);
        return array('content' => $sContent, 'menu' => $oMenu);
	}

	public function serviceGetBlockView($iProfileId = 0)
	{
		$aBlock = $this->serviceGetBlockViewProfile($iProfileId);
		if(!empty($aBlock))
			return $aBlock;

    	return array('content' => MsgBox(_t('_bx_timeline_txt_msg_no_results'))); 
    }

	public function serviceGetBlockViewProfile($iProfileId = 0, $iStart = -1, $iPerPage = -1, $sFilter = '', $aModules = array(), $iTimeline = -1)
	{
		if(!$iProfileId && bx_get('id') !== false) {
			$oProfile = BxDolProfile::getInstanceByContentAndType(bx_process_input(bx_get('id'), BX_DATA_INT), 'bx_persons');
			if(!empty($oProfile))
            	$iProfileId = $oProfile->id();
		}

        if (!$iProfileId)
            return array();

		$sJsObject = $this->_oConfig->getJsObject('view');
		$aParams = $this->_prepareParams('owner', $iProfileId, $iStart, $iPerPage, $sFilter, $aModules, $iTimeline);

		$this->_iOwnerId = $aParams['owner_id'];
		list($sUserName, $sUserUrl) = $this->getUserInfo($aParams['owner_id']);

        $aMenu = array(
			array('id' => 'timeline-view-all', 'name' => 'timeline-view-all', 'class' => '', 'link' => 'javascript:void(0)', 'onclick' => 'javascript:' . $sJsObject . '.changeFilter(this)', 'target' => '_self', 'title' => _t('_bx_timeline_menu_item_view_all'), 'active' => 1),
            array('id' => 'timeline-view-owner', 'name' => 'timeline-view-owner', 'class' => '', 'link' => 'javascript:void(0)', 'onclick' => 'javascript:' . $sJsObject . '.changeFilter(this)', 'target' => '_self', 'title' => _t('_bx_timeline_menu_item_view_owner', $sUserName)),
            array('id' => 'timeline-view-other', 'name' => 'timeline-view-other', 'class' => '', 'link' => 'javascript:void(0)', 'onclick' => 'javascript:' . $sJsObject . '.changeFilter(this)', 'target' => '_self', 'title' => _t('_bx_timeline_menu_item_view_other')),
            array('id' => 'timeline-get-rss', 'name' => 'timeline-get-rss', 'class' => '', 'link' => BX_DOL_URL_ROOT . $this->_oConfig->getBaseUri() . 'rss/' . $iProfileId . '/', 'target' => '_blank', 'title' => _t('_bx_timeline_menu_item_get_rss')),
        );

        bx_import('BxTemplMenuInteractive');
		$oMenu = new BxTemplMenuInteractive(array('template' => 'menu_interactive_vertical.html', 'menu_id'=> 'timeline-view-all', 'menu_items' => $aMenu));
		$oMenu->setSelected('', 'timeline-view-all');

		$sContent = $this->_oTemplate->getViewBlock($aParams);
        return array('content' => $sContent, 'menu' => $oMenu);
    }

    public function serviceGetBlockViewAccount($iProfileId = 0, $iStart = -1, $iPerPage = -1, $iTimeline = -1, $sFilter = '', $aModules = array())
    {
    	$aParams = $this->_prepareParams('connections', $iProfileId, $iStart, $iPerPage, $sFilter, $aModules, $iTimeline);

    	$this->_iOwnerId = $aParams['owner_id'];

    	$sContent = $this->_oTemplate->getViewBlock($aParams);
    	return array('content' => $sContent);
    }

    public function serviceGetBlockItem()
    {
    	$iItemId = bx_process_input(bx_get('id'), BX_DATA_INT);
    	if(!$iItemId)
    		return array();

    	return array('content' => $this->_oTemplate->getViewItemBlock($iItemId));
    }

	public function serviceGetShareOnclick($iOwnerId, $sType, $sAction, $iObjectId)
    {
    	$sJsObject = $this->_oConfig->getJsObject('view');
    	$sFormat = "%s.shareItem(this, %d, '%s', '%s', %d);";

    	$iOwnerId = !empty($iOwnerId) ? (int)$iOwnerId : $this->getUserId(); //--- in whose timeline the content will be shared
    	return sprintf($sFormat, $sJsObject, $iOwnerId, $sType, $sAction, (int)$iObjectId);
    }

    /*
     * COMMON METHODS 
     */
	public function getFormText()
    {
    	bx_import('BxDolForm');
        $oForm = BxDolForm::getObjectInstance('mod_tml_text', 'mod_tml_text_add');
        $oForm->aFormAttrs['action'] = BX_DOL_URL_ROOT . $this->_oConfig->getBaseUri() . 'post/';
        $oForm->aInputs['owner_id']['value'] = $this->_iOwnerId;

        $oForm->initChecker();
        if($oForm->isSubmittedAndValid()) {
        	$iUserId = $this->getUserId();
        	list($sUserName) = $this->getUserInfo($iUserId);

        	$sType = $oForm->getCleanValue('type');
        	$sType = $this->_oConfig->getPrefix('common_post') . $sType;
        	BxDolForm::setSubmittedValue('type', $sType, $oForm->aFormAttrs['method']);

        	$sContent = $sDescription = $oForm->getCleanValue('content');
        	$sContent = serialize(array('text' => $sContent));
        	BxDolForm::setSubmittedValue('content', $sContent, $oForm->aFormAttrs['method']);

        	$iId = $oForm->insert(array(
        		'object_id' => $iUserId,
        		'object_privacy_view' => $this->_oConfig->getPrivacyViewDefault(),
				'title' => bx_process_input($sUserName . ' ' . _t('_bx_timeline_txt_wrote')),
				'description' => $sDescription,
        		'date' => time()
			));

			if(!empty($iId)) {
				$this->onPost($iId);

                return array('id' => $iId);
			}

			return array('msg' => _t('_bx_timeline_txt_err_cannot_perform_action'));
        }

        return array('form' => $oForm->getCode(), 'form_id' => $oForm->id);
    }

	public function getFormLink()
    {
        bx_import('BxDolForm');
        $oForm = BxDolForm::getObjectInstance('mod_tml_link', 'mod_tml_link_add');
        $oForm->aFormAttrs['action'] = BX_DOL_URL_ROOT . $this->_oConfig->getBaseUri() . 'post/';
        $oForm->aInputs['owner_id']['value'] = $this->_iOwnerId;

        $oForm->initChecker();
        if($oForm->isSubmittedAndValid()) {
        	$iUserId = $this->getUserId();
        	list($sUserName) = $this->getUserInfo($iUserId);

        	$sType = $oForm->getCleanValue('type');
        	$sType = $this->_oConfig->getPrefix('common_post') . $sType;
        	BxDolForm::setSubmittedValue('type', $sType, $oForm->aFormAttrs['method']);

        	$sUrl = $oForm->getCleanValue('content');
        	$sContent = bx_file_get_contents($sUrl);

	        preg_match("/<title>(.*)<\/title>/", $sContent, $aMatch);
	        $sTitle = $aMatch ? $aMatch[1] : '';
	
	        preg_match("/<meta.*name[='\" ]+description['\"].*content[='\" ]+(.*)['\"].*><\/meta>/", $sContent, $aMatch);
	        $sDescription = $aMatch ? $aMatch[1] : '';

	        $sContent = serialize(array(
				'url' => strpos($sUrl, 'http://') === false && strpos($sUrl, 'https://') === false ? 'http://' . $sUrl : $sUrl,
	        	'title' => $sTitle,
				'text' => $sDescription
			));
	        BxDolForm::setSubmittedValue('content', $sContent, $oForm->aFormAttrs['method']);

        	$iId = $oForm->insert(array(
        		'object_id' => $iUserId,
        		'object_privacy_view' => $this->_oConfig->getPrivacyViewDefault(),
				'title' => bx_process_input($sUserName . ' ' . _t('_bx_timeline_txt_shared_link')),
				'description' => bx_process_input($sUrl . ' - ' . $sTitle),
        		'date' => time()
			));

			if(!empty($iId)) {
				$this->onPost($iId);

                return array('id' => $iId);
			}

			return array('msg' => _t('_bx_timeline_txt_err_cannot_perform_action'));
        }

        return array('form' => $oForm->getCode(), 'form_id' => $oForm->id);
    }

    public function getFormPhoto()
    {
    	$aFormNested = array(
			'inputs' => array(
		    	'file_title' => array(
		        	'type' => 'text',
		            'name' => 'file_title[]',
		            'value' => '{file_title}',
		            'caption' => _t('_bx_timeline_form_photo_input_title'),
		            'required' => true,
		            'checker' => array(
		            	'func' => 'length',
		                'params' => array(1, 150),
		                'error' => _t('_bx_timeline_form_photo_input_err_title')
					),
					'db' => array (
 						'pass' => 'Xss',
 					),
				),

				'file_text' => array(
		        	'type' => 'textarea',
		            'name' => 'file_text[]',
		            'caption' => _t('_bx_timeline_form_photo_input_description'),
		            'required' => true,
		            'checker' => array(
		            	'func' => 'length',
		                'params' => array(10, 5000),
		                'error' => _t('_bx_timeline_form_photo_input_err_description')
					),
					'db' => array (
 						'pass' => 'Xss',
 					),
				),
			),
		);

    	bx_import('BxDolForm');
        $oForm = BxDolForm::getObjectInstance('mod_tml_photo', 'mod_tml_photo_add');
        $oForm->aFormAttrs['action'] = BX_DOL_URL_ROOT . $this->_oConfig->getBaseUri() . 'post/';
        $oForm->aInputs['owner_id']['value'] = $this->_iOwnerId;

	    bx_import('BxDolFormNested');
	    $oFormNested = new BxDolFormNested('content', $aFormNested, 'do_submit');

        $oForm->aInputs['content']['storage_object'] = $this->_oConfig->getObject('storage');
        $oForm->aInputs['content']['images_transcoder'] = $this->_oConfig->getObject('transcoder_preview');
        $oForm->aInputs['content']['uploaders'] = $this->_oConfig->getUploaders('image');
        $oForm->aInputs['content']['multiple'] = false;
        $oForm->aInputs['content']['ghost_template'] = $oFormNested;

        $oForm->initChecker();
        if($oForm->isSubmittedAndValid()) {
        	$iUserId = $this->getUserId();
        	list($sUserName) = $this->getUserInfo($iUserId);

        	$sType = $oForm->getCleanValue('type');
        	$sType = $this->_oConfig->getPrefix('common_post') . $sType;
        	BxDolForm::setSubmittedValue('type', $sType, $oForm->aFormAttrs['method']);

        	$aPhIds = $oForm->getCleanValue('content');
        	BxDolForm::setSubmittedValue('content', serialize(array()), $oForm->aFormAttrs['method']);
        	$iPhIds = count($aPhIds);

        	$iId = $oForm->insert(array(
        		'object_id' => $iUserId,
        		'object_privacy_view' => $this->_oConfig->getPrivacyViewDefault(),
				'title' => bx_process_input($sUserName . ' ' . _t('_bx_timeline_txt_added_photo' . ($iPhIds > 1 ? 's' : ''))),
				'description' => '',
        		'date' => time()
			));

			if(!empty($iId)) {
				if($iPhIds > 0) {
					$aPhTitles = $oForm->getCleanValue('file_title');
					$aPhTexts = $oForm->getCleanValue('file_text');

					bx_import('BxDolStorage');
					$oStorage = BxDolStorage::getObjectInstance($this->_oConfig->getObject('storage'));

					for($i = 0; $i < $iPhIds; $i++) 
						if($this->_oDb->savePhoto($iId, $aPhIds[$i], $aPhTitles[$i], $aPhTexts[$i]))
							$oStorage->afterUploadCleanup($aPhIds[$i], $iUserId);
				}

				$this->onPost($iId);

                return array('id' => $iId);
			}

			return array('msg' => _t('_bx_timeline_txt_err_cannot_perform_action'));
        }

        return array('form' => $oForm->getCode(), 'form_id' => $oForm->id);
    }

    public function getCmtsObject($sSystem, $iId)
    {
    	if(empty($sSystem) || (int)$iId == 0)
    		return false;

    	bx_import('BxDolCmts');
        $oCmts = BxDolCmts::getObjectInstance($sSystem, $iId);
		if(!$oCmts->isEnabled())
			return false;

		return $oCmts;
    }

	public function getVoteObject($sSystem, $iId)
    {
    	if(empty($sSystem) || (int)$iId == 0)
    		return false;

    	bx_import('BxDolVote');
        $oVote = BxDolVote::getObjectInstance($sSystem, $iId);
		if(!$oVote->isEnabled())
			return false;

		return $oVote;
    }
    
	public function getUserId()
    {
        return isLogged() ? bx_get_logged_profile_id() : 0;
    }

	public function getUserInfo($iUserId = 0)
    {
    	bx_import('BxDolProfile');
		$oProfile = BxDolProfile::getInstance($iUserId);
		if (!$oProfile) {
			bx_import('BxDolProfileUndefined');
			$oProfile = BxDolProfileUndefined::getInstance();
		}

		return array(
			$oProfile->getDisplayName(), 
			$oProfile->getUrl(), 
			$oProfile->getThumb(),
			$oProfile->getUnit()
		);
    }

    public function isAllowedPost($bPerform = false)
    {
		if(isAdmin())
			return true;

        $iUserId = $this->getUserId();
		if($iUserId == 0 && $this->_oConfig->isAllowGuestComments())
			return true;

        $aCheckResult = checkActionModule($iUserId, 'post', $this->getName(), $bPerform);
        return $aCheckResult[CHECK_ACTION_RESULT] == CHECK_ACTION_RESULT_ALLOWED;
    }

    public function isAllowedDelete($aEvent, $bPerform = false)
    {
        if(isAdmin())
            return true;

        $iUserId = (int)$this->getUserId();
        if((int)$aEvent['owner_id'] == $iUserId)
           return true;

        $aCheckResult = checkActionModule($iUserId, 'delete', $this->getName(), $bPerform);
        return $aCheckResult[CHECK_ACTION_RESULT] == CHECK_ACTION_RESULT_ALLOWED;
    }

	public function isAllowedComment($aEvent, $bPerform = false)
    {
    	$mixedComments = $this->getCommentsData($aEvent['comments']);
    	if($mixedComments === false)
    		return false;

		list($sSystem, $iObjectId) = $mixedComments;
		$oCmts = $this->getCmtsObject($sSystem, $iObjectId);
    	$oCmts->addCssJs();

    	$iUserId = (int)$this->getUserId();
    	if($iUserId == 0)
    		return false;

		if(isAdmin())
			return true;

        return $oCmts->isPostReplyAllowed($bPerform);
    }

	public function isAllowedVote($aEvent, $bPerform = false)
    {
    	$mixedVotes = $this->getVotesData($aEvent['votes']);
    	if($mixedVotes === false)
    		return false;

		list($sSystem, $iObjectId) = $mixedVotes;
		$oVote = $this->getVoteObject($sSystem, $iObjectId);
    	$oVote->addCssJs();

    	$iUserId = (int)$this->getUserId();
    	if($iUserId == 0)
    		return false;

    	if(isAdmin())
			return true;

        return $oVote->isAllowedVote($bPerform);
    }

    public function isAllowedShare($aEvent, $bPerform = false)
    {
    	$iUserId = (int)$this->getUserId();
    	if($iUserId == 0)
    		return false;

    	if(isAdmin())
			return true;

		$aCheckResult = checkActionModule($iUserId, 'share', $this->getName(), $bPerform);
        return $aCheckResult[CHECK_ACTION_RESULT] == CHECK_ACTION_RESULT_ALLOWED;
    }

	public function onPost($iId)
    {
    	$aEvent = $this->_oDb->getEvents(array('browse' => 'id', 'value' => $iId));
    	
    	if($this->_oConfig->isSystem($aEvent['type'], $aEvent['action'])) {
    		$sPostType = 'system';
    		$iSenderId = $aEvent['owner_id'];
    	}
    	else {
    		$sPostType = 'common';
    		$iSenderId = $aEvent['object_id'];
    	}

    	//--- Event -> Post for Alerts Engine ---//
		bx_import('BxDolAlerts');
        $oAlert = new BxDolAlerts($this->_oConfig->getSystemName('alert'), 'post_' . $sPostType, $iId, $iSenderId);
        $oAlert->alert();
        //--- Event -> Post for Alerts Engine ---//
    }

	public function onShare($iId)
    {
    	$aEvent = $this->_oDb->getEvents(array('browse' => 'id', 'value' => $iId));

		$aContent = unserialize($aEvent['content']);
    	$iSharedId = $this->_oDb->updateSharesCounter($aContent['type'], $aContent['action'], $aContent['object_id']);

		//--- Timeline -> Update for Alerts Engine ---//
		bx_import('BxDolAlerts');
		$oAlert = new BxDolAlerts($this->_oConfig->getSystemName('alert'), 'share', $iSharedId, $aEvent['object_id']);
		$oAlert->alert();
		//--- Timeline -> Update for Alerts Engine ---//
    }

    public function onDelete($aEvent)
    {
		$aPhotos = $this->_oDb->getPhotos($aEvent['id']);
		if(is_array($aPhotos) && !empty($aPhotos)) {
			bx_import('BxDolStorage');
			$oStorage = BxDolStorage::getObjectInstance($this->_oConfig->getObject('storage'));

			foreach($aPhotos as $aPhoto)
				$oStorage->deleteFile($aPhoto['id']);

			$this->_oDb->deletePhotos($aEvent['id']);
		}

		if($aEvent['type'] == $this->_oConfig->getPrefix('common_post') . BX_TIMELINE_PARSE_TYPE_SHARE) {
			$aContent = unserialize($aEvent['content']);
			$this->_oDb->updateSharesCounter($aContent['type'], $aContent['action'], $aContent['object_id'], -1);
		}
		
		
		//--- Event -> Delete for Alerts Engine ---//
        bx_import('BxDolAlerts');
        $oAlert = new BxDolAlerts($this->_oConfig->getSystemName('alert'), 'delete', $aEvent['id'], $this->getUserId());
        $oAlert->alert();
        //--- Event -> Delete for Alerts Engine ---//
    }

	public function getVotesData(&$aVotes)
    {
    	if(empty($aVotes) || !is_array($aVotes))
    		return false; 

		$sSystem = isset($aVotes['system']) ? $aVotes['system'] : '';
	    $iObjectId = isset($aVotes['object_id']) ? (int)$aVotes['object_id'] : 0;
	    $iCount = isset($aVotes['count']) ? (int)$aVotes['count'] : 0;
	    if($sSystem == '' || $iObjectId == 0)
	    	return false;

		return array($sSystem, $iObjectId, $iCount);
    }

    public function getCommentsData(&$aComments)
    {
    	if(empty($aComments) || !is_array($aComments))
    		return false; 

		$sSystem = isset($aComments['system']) ? $aComments['system'] : '';
	    $iObjectId = isset($aComments['object_id']) ? (int)$aComments['object_id'] : 0;
	    $iCount = isset($aComments['count']) ? (int)$aComments['count'] : 0;
	    if($sSystem == '' || $iObjectId == 0 || ($iCount == 0 && !isLogged()))
	    	return false;

		return array($sSystem, $iObjectId, $iCount);
    }
    
    protected function _prepareParams($sType, $iOwnerId, $iStart, $iPerPage, $sFilter, $aModules, $iTimeline)
    {
    	$aParams = array();
    	$aParams['browse'] = 'list';
    	$aParams['type'] = !empty($sType) ? $sType : BX_TIMELINE_TYPE_OWNER;
		$aParams['owner_id'] = (int)$iOwnerId != 0 ? $iOwnerId : $this->getUserId();
    	$aParams['start'] = (int)$iStart > 0 ? $iStart : 0;
    	$aParams['per_page'] = (int)$iPerPage > 0 ? $iPerPage : $this->_oConfig->getPerPage();
    	$aParams['filter'] = !empty($sFilter) ? $sFilter : BX_TIMELINE_FILTER_ALL;
    	$aParams['modules'] = is_array($aModules) && !empty($aModules) ? $aModules : array();
    	$aParams['timeline'] = (int)$iTimeline > 0 ? $iTimeline : 0;

    	return $aParams;
    }

    protected function _prepareParamsGet()
    {
    	$aParams = array();
    	$aParams['browse'] = 'list';

    	$sType = bx_get('type');
		$aParams['type'] = $sType !== false ? bx_process_input($sType, BX_DATA_TEXT) : BX_TIMELINE_TYPE_OWNER;

		$iOwnerId = bx_get('owner_id');
		$aParams['owner_id'] = $sType !== false ? bx_process_input(bx_get('owner_id'), BX_DATA_INT) : $this->getUserId();

        $iStart = bx_get('start');
        $aParams['start'] = $iStart !== false ? bx_process_input($iStart, BX_DATA_INT) : 0;

        $iPerPage = bx_get('per_page');
		$aParams['per_page'] = $iPerPage !== false ? bx_process_input($iPerPage, BX_DATA_INT) : $this->_oConfig->getPerPage();

        $sFilter = bx_get('filter');
		$aParams['filter'] = $sFilter !== false ? bx_process_input($sFilter, BX_DATA_TEXT) : BX_TIMELINE_FILTER_ALL;

		$aModules = bx_get('modules');
		$aParams['modules'] = $aModules !== false ? bx_process_input($aModules, BX_DATA_TEXT) : array();

		$iTimeline = bx_get('timeline');
		$aParams['timeline'] = $iTimeline !== false ? bx_process_input($iTimeline, BX_DATA_INT) : 0;

		return $aParams;
    }

	protected function _updateHandlers($sModuleUri = 'all', $bInstall = true)
    {
        $aModules = $sModuleUri == 'all' ? $this->_oDb->getModules() : array($this->_oDb->getModuleByUri($sModuleUri));

        foreach($aModules as $aModule) {
			if(!BxDolRequest::serviceExists($aModule, 'get_timeline_data'))
				continue;

			$aData = BxDolService::call($aModule['name'], 'get_timeline_data');
			if(empty($aData) || !is_array($aData))
				continue;

			if($bInstall)
				$this->_oDb->insertData($aData);
			else
				$this->_oDb->deleteData($aData);
        }

        BxDolAlerts::cache();
    }

	protected function _echoResultJson($a, $isAutoWrapForFormFileSubmit = false) {

        header('Content-type: text/html; charset=utf-8');    

        require_once(BX_DIRECTORY_PATH_PLUGINS . 'Services_JSON.php');

        $oParser = new Services_JSON();
        $s = $oParser->encode($a);
        if ($isAutoWrapForFormFileSubmit && !empty($_FILES)) 
            $s = '<textarea>' . $s . '</textarea>'; // http://jquery.malsup.com/form/#file-upload
        echo $s;
    }
}

/** @} */ 
