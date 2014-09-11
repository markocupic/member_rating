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

$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{member_rating_legend:hide},gradeLabeling,customImageDir';

/**
 * Add fields
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['gradeLabeling'] = array(
       'label' => &$GLOBALS['TL_LANG']['tl_settings']['gradeLabeling'],
       'inputType' => 'text',
       'default' => '0|Anwärter|thumbsup.png###500|Cooler Typ|star.png###1000|Gottheit|trophy.png',
       'eval' => array('tl_class' => 'long', )
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['customImageDir'] = array(
       'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['customImageDir'],
       'exclude'                 => true,
       'inputType'               => 'fileTree',
       'eval'                    => array('files'=>false, 'fieldType'=>'radio', 'mandatory'=>false, 'tl_class'=>'clr'),
);
