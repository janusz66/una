<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 */

$aConfig = array(
    /**
     * Main Section.
     */
    'title' => 'LinkedIn connect',
    'version_from' => '9.0.4',
	'version_to' => '9.0.5',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '9.0.0-RC9'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/linkedin_connect/updates/update_9.0.4_9.0.5/',
	'home_uri' => 'linkedin_update_904_905',

	'module_dir' => 'boonex/linkedin_connect/',
	'module_uri' => 'linkedin',

    'db_prefix' => 'bx_linkedin_',
    'class_prefix' => 'BxLinkedin',

    /**
     * Installation/Uninstallation Section.
     */
    'install' => array(
		'execute_sql' => 0,
        'update_files' => 1,
        'update_languages' => 0,
		'clear_db_cache' => 1,
    ),

	/**
     * Category for language keys.
     */
    'language_category' => 'LinkedIn Connect',

	/**
     * Files Section
     */
    'delete_files' => array(),
);
