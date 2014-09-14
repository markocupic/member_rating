<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 * @package member_rating
 * @author Marko Cupic 2014
 * @link    http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 * @link https://github.com/markocupic/member_rating
 */

if (strpos($GLOBALS['TL_DCA']['tl_member']['palettes']['default'], '{personal_legend},') === false) {
	$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= 'rang';
} else {
	$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace('{personal_legend},','{personal_legend},rang,', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);
}
if (strpos($GLOBALS['TL_DCA']['tl_member']['palettes']['default'], '{personal_legend},') === false) {
	$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= ',socialmedia_links';
} else {
	$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace('{personal_legend},','{personal_legend},socialmedia_links,', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);
}
if (strpos($GLOBALS['TL_DCA']['tl_member']['palettes']['default'], '{personal_legend},') === false) {
	$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= ',punktestand';
} else {
	$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace('{personal_legend},','{personal_legend},punktestand,', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);
}
if (strpos($GLOBALS['TL_DCA']['tl_member']['palettes']['default'], '{personal_legend},') === false) {
       $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= ',avatar';
} else {
       $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace('{personal_legend},','{personal_legend},avatar,', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);
}


// fields
$GLOBALS['TL_DCA']['tl_member']['fields']['socialmediaLinks'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_member']['socialmediaLinks'],
       'exclude'                 => true,
       'inputType'               => 'text',
       'eval'                    => array('rgxp' => 'url','feGroup' => 'personal'),
       'sql'                     => "blob NULL"
);


$GLOBALS['TL_DCA']['tl_member']['fields']['avatar'] = array
(
       'label'     => &$GLOBALS['TL_LANG']['tl_member']['avatar'],
       'exclude'   => true,
       'inputType' => 'avatar',
       'eval'      => array
       (
              'fieldType'      => 'radio',
              'filesOnly'      => true,
              'tl_class'       => 'clr',
              'filename'       => 'member_%s',
              'feViewable'     => true,
              'feEditable'     => true,
              'feGroup'        => 'personal',
              'doNotOverwrite' => !$GLOBALS['TL_CONFIG']['avatar_rename']
       ),
       'sql'       => "binary(16) NULL"
);
/**
 * Class tl_member_member_rating
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Marko Cupic 2014
 * @author     Marko Cupic
 * @package    Controller
 */
class tl_member_member_rating extends tl_member
{
}

?>