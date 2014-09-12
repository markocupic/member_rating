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

/**
 * Add to palette
 */

$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{member_rating_legend:hide},gradeLabeling,customImageDir,socialmediaLinks';

/**
 * Add fields
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['gradeLabeling'] = array(
       'label' => &$GLOBALS['TL_LANG']['tl_settings']['gradeLabeling'],
       'inputType' => 'text',
       'eval' => array('tl_class' => 'long',)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['customImageDir'] = array(
       'label' => &$GLOBALS['TL_LANG']['tl_settings']['customImageDir'],
       'inputType' => 'fileTree',
       'eval' => array(
              'files' => false,
              'fieldType' => 'radio',
              'mandatory' => false,
              'tl_class' => 'clr'
       ),
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['socialmediaLinks'] = array(
       'label' => &$GLOBALS['TL_LANG']['tl_settings']['socialmediaLinks'],
       'inputType' => 'text',
       'eval' => array('tl_class' => 'long',)
);
