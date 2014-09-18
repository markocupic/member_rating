<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 * @package member_rating
 * @author Marko Cupic 2014
 * @link    http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 * @link https://github.com/markocupic/member_rating
 */

// overwrite default settings
if(defined('MOD_MEMBER_RATING'))
{
	$GLOBALS['TL_DCA']['tl_comments']['fields']['comment']['eval']['mandatory'] = false;
	$GLOBALS['TL_DCA']['tl_comments']['fields']['parent']['inputType'] = 'text';
	$GLOBALS['TL_DCA']['tl_comments']['fields']['ip']['inputType'] = 'text';
	$GLOBALS['TL_DCA']['tl_comments']['fields']['source']['inputType'] = 'text';
}


/**
 * Table tl_comments
 */
$GLOBALS['TL_DCA']['tl_comments']['fields']['score'] = array(
	'label'     => &$GLOBALS['TL_LANG']['tl_comments']['score'],
	'filter'    => true,
	'inputType' => 'hidden',
	'sorting'   => true,
	'eval'      => array(
		'mandatory' => true,
		'rgxp'      => 'digit'
	),
	'sql'       => "int(1) NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_comments']['fields']['activation_token'] = array(
	'label'     => &$GLOBALS['TL_LANG']['tl_comments']['activation_token'],
	'filter'    => true,
	'inputType' => 'hidden',
	'sorting'   => true,
	'sql'       => "varchar(255) NOT NULL default ''",
	'eval'      => array('unique' => true)
);

$GLOBALS['TL_DCA']['tl_comments']['fields']['dateOfCreation'] = array(
	'label'     => &$GLOBALS['TL_LANG']['tl_comments']['dateOfCreation'],
	'filter'    => true,
	'inputType' => 'hidden',
	'sorting'   => true,
	'sql'       => "int(10) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_comments']['fields']['owner'] = array(
	'label'     => &$GLOBALS['TL_LANG']['tl_comments']['owner'],
	'filter'    => true,
	'inputType' => 'hidden',
	'sorting'   => true,
	'sql'       => "int(10) unsigned NOT NULL default '0'",
);
$GLOBALS['TL_DCA']['tl_comments']['fields']['captcha'] = array(
	'inputType' => 'captcha',
);







