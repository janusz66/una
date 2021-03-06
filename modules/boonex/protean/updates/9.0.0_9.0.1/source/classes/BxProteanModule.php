<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) BoonEx Pty Limited - http://www.boonex.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Protean Protean template
 * @ingroup     TridentModules
 *
 * @{
 */

bx_import ('BxBaseModTemplateModule');

class BxProteanModule extends BxBaseModTemplateModule
{
    function __construct(&$aModule)
    {
        parent::__construct($aModule);
    }

	function serviceIncludeCssJs()
    {
    	$sCss = trim(getParam($this->_oConfig->getName() . '_styles_custom'));
        return !empty($sCss) ? $this->_oTemplate->_wrapInTagCssCode($sCss) : '';
    }
}

/** @} */
