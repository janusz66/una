<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) BoonEx Pty Limited - http://www.boonex.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    DolphinCore Dolphin Core
 * @{
 */

bx_import('BxDolEditor');

/**
 * TinyMCE editor representation.
 * @see BxDolEditor
 */
class BxBaseEditorTinyMCE extends BxDolEditor 
{
    /**
     * Common initialization params
     */
    protected static $CONF_COMMON = "                    
                    jQuery('{bx_var_selector}').tinymce({
                        {bx_var_custom_init}
                        document_base_url: '{bx_url_root}',
                        skin: '{bx_var_skin}',
                        language: '{bx_var_lang}',
                        content_css: '{bx_var_css_path}',
                        entity_encoding: 'raw'
                    });
    ";

    /**
     * Standard view initialization params
     */
    protected static $CONF_STANDARD = "
                        plugins: 'autolink,autosave,lists,media,paste,fullscreen,hr,link,image,code',
                        width: '100%',
                        height: '270',
                        theme: 'modern',
                        toolbar: 'bold italic underline removeformat | bullist numlist | alignleft aligncenter alignright | undo redo  pastetext | blockquote hr link unlink image media | fullscreen code',
                        statusbar: true,
                        resize: true,
    ";

    /**
     * Minimal view initialization params
     */
    protected static $CONF_MINI = "
                        menubar: false,
                        plugins: 'autolink,lists,paste,link,image',
                        width: '100%',
                        height: '150',
                        theme: 'modern',
                        toolbar: 'bold italic underline removeformat | bullist numlist | alignleft aligncenter alignright | blockquote | link unlink image',
                        statusbar: false,
    ";

    /**
     * Full view initialization params
     */
    protected static $CONF_FULL = "
                        plugins: 'autolink,autosave,lists,table,media,searchreplace,print,paste,fullscreen,hr,link,image,anchor,code,autoresize',
                        width: '100%',
                        height: '320',
                        theme: 'modern',
                        toolbar: [
                            'bold italic underline removeformat | subscript superscript | bullist numlist | alignleft aligncenter alignright alignjustify | undo redo pastetext | blockquote hr link unlink image media',
                            'outdent indent | anchor | searchreplace | print | fullscreen code'
                        ],
                        statusbar: true,
                        resize: true,
    ";

    /**
     * Available editor languages
     */
    protected static $CONF_LANGS = array('ar' => 1, 'ar_SA' => 1, 'az' => 1, 'be' => 1, 'bg_BG' => 1, 'bn_BD' => 1, 'bs' => 1, 'ca' => 1, 'cs' => 1, 'cy' => 1, 'da' => 1, 'de' => 1, 'de_AT' => 1, 'dv' => 1, 'el' => 1, 'en_CA' => 1, 'en_GB' => 1, 'es' => 1, 'et' => 1, 'eu' => 1, 'fa' => 1, 'fi' => 1, 'fo' => 1, 'fr_FR' => 1, 'gd' => 1, 'gl' => 1, 'he_IL' => 1, 'hr' => 1, 'hu_HU' => 1, 'hy' => 1, 'id' => 1, 'is_IS' => 1, 'it' => 1, 'ja' => 1, 'ka_GE' => 1, 'kk' => 1, 'km_KH' => 1, 'ko_KR' => 1, 'lb' => 1, 'lt' => 1, 'lv' => 1, 'ml' => 1, 'ml_IN' => 1, 'mn_MN' => 1, 'nb_NO' => 1, 'nl' => 1, 'pl' => 1, 'pt_BR' => 1, 'pt_PT' => 1, 'ro' => 1, 'ru' => 1, 'si_LK' => 1, 'sk' => 1, 'sl_SI' => 1, 'sr' => 1, 'sv_SE' => 1, 'ta' => 1, 'ta_IN' => 1, 'tg' => 1, 'th_TH' => 1, 'tr_TR' => 1, 'tt' => 1, 'ug' => 1, 'uk' => 1, 'uk_UA' => 1, 'vi' => 1, 'vi_VN' => 1, 'zh_CN' => 1, 'zh_TW' => 1);

    protected $_oTemplate;
    protected $_bJsCssAdded = false;

    public function __construct ($aObject, $oTemplate) 
    {
        parent::__construct ($aObject);

        if ($oTemplate)
            $this->_oTemplate = $oTemplate;
        else
            $this->_oTemplate = BxDolTemplate::getInstance();
    }

    /**
     * Attach editor to HTML element, in most cases - textarea.
     * @param $sSelector - jQuery selector to attach editor to.
     * @param $iViewMode - editor view mode: BX_EDITOR_STANDARD, BX_EDITOR_MINI, BX_EDITOR_FULL
     * @param $bDynamicMode - is AJAX mode or not, the HTML with editor area is loaded dynamically.
     */
    public function attachEditor ($sSelector, $iViewMode = BX_EDITOR_STANDARD, $bDynamicMode = false) 
    {
        // set visual mode
        switch ($iViewMode) {
            case BX_EDITOR_MINI:
                 $sToolsItems = self::$CONF_MINI;
                break;
            case BX_EDITOR_FULL:
                $sToolsItems = self::$CONF_FULL;
            break;
            case BX_EDITOR_STANDARD:
            default:
                 $sToolsItems = self::$CONF_STANDARD;
        }

        // detect language
        bx_import('BxDolLanguages');
        $sLang = BxDolLanguages::getInstance()->detectLanguageFromArray (self::$CONF_LANGS);

                
        // initialize editor
        $sInitEditor = $this->_replaceMarkers(self::$CONF_COMMON, array(            
            'bx_var_custom_init' => $sToolsItems,
            'bx_var_plugins_path' => bx_js_string(BX_DOL_URL_PLUGINS, BX_ESCAPE_STR_APOS),
            'bx_var_css_path' => bx_js_string($this->_oTemplate->getCssUrl('editor.css'), BX_ESCAPE_STR_APOS),
            'bx_var_skin' => bx_js_string($this->_aObject['skin'], BX_ESCAPE_STR_APOS),
            'bx_var_lang' => bx_js_string($sLang, BX_ESCAPE_STR_APOS),
            'bx_var_selector' => bx_js_string($sSelector, BX_ESCAPE_STR_APOS),
            'bx_url_root' => bx_js_string(BX_DOL_URL_ROOT, BX_ESCAPE_STR_APOS),
        ));

        if ($bDynamicMode) {

            $sScript = "<script>
                if ('undefined' == typeof(jQuery(document).tinymce)) {
                    $.getScript('" . bx_js_string(BX_DOL_URL_BASE . 'inc/js/editor.tinymce.js', BX_ESCAPE_STR_APOS) . "');
                    $.getScript('" . bx_js_string(BX_DOL_URL_BASE . 'inc/js/tinymce.js', BX_ESCAPE_STR_APOS) . "', function(data, textStatus, jqxhr) {
                        $.getScript('" . bx_js_string(BX_DOL_URL_PLUGINS . 'tinymce/jquery.tinymce.min.js', BX_ESCAPE_STR_APOS) . "', function(data, textStatus, jqxhr) {
                            $sInitEditor
                        });
                    });
                } else {
                    $sInitEditor
                }
            </script>";

        } else {

            $sScript = "
            <script>
                $(document).ready(function () {
                    $sInitEditor
                });
            </script>";

        }

        return $this->_addJsCss($bDynamicMode) . $sScript;
    }

    /**
     * Add css/js files which are needed for editor display and functionality.
     */
    protected function _addJsCss($bDynamicMode = false, $sInitEditor = '') 
    {
        if ($bDynamicMode)
            return '';
        if ($this->_bJsCssAdded)
            return '';
        $this->_oTemplate->addJs(array(BX_DOL_URL_PLUGINS . 'tinymce/tinymce.min.js', BX_DOL_URL_PLUGINS . 'tinymce/jquery.tinymce.min.js', 'editor.tinymce.js'));
        $this->_bJsCssAdded = true;
        return '';
    }

}

/** @} */
