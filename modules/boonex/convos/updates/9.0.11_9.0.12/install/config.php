<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 */

$aConfig = array(
    /**
     * Main Section.
     */
    'title' => 'Conversations',
    'version_from' => '9.0.11',
	'version_to' => '9.0.12',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '9.0.0-RC11'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/convos/updates/update_9.0.11_9.0.12/',
	'home_uri' => 'convos_update_9011_9012',

	'module_dir' => 'boonex/convos/',
	'module_uri' => 'convos',

    'db_prefix' => 'bx_convos_',
    'class_prefix' => 'BxCnv',

    /**
     * Installation/Uninstallation Section.
     */
    'install' => array(
        'execute_sql' => 0,
        'update_files' => 1,
        'update_languages' => 1,
        'clear_db_cache' => 0,
    ),

	/**
     * Category for language keys.
     */
    'language_category' => 'Conversations',

	/**
     * Files Section
     */
    'delete_files' => array(),
);
