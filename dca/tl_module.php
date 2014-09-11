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
 * Table tl_module
 */

// palettes
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'notifyRatedUser';
$GLOBALS['TL_DCA']['tl_module']['palettes']['member_rating'] = '{title_legend},name,headline,type,blockingTime,jumpTo,notifyRatedUser;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['rated_member_list'] = '{title_legend},name,headline,type,detailPage;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['notifyRatedUser'] = 'emailNotifyPage';

// fields
$GLOBALS['TL_DCA']['tl_module']['fields']['detailPage'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['detailPage'],
       'exclude'                 => true,
       'inputType'               => 'pageTree',
       'foreignKey'              => 'tl_page.title',
       'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
       'sql'                     => "int(10) unsigned NOT NULL default '0'",
       'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['notifyRatedUser'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['notifyRatedUser'],
       'exclude'                 => true,
       'inputType'               => 'checkbox',
       'eval'                    => array('submitOnChange'=>true,'tl_class'=>'w50 m12'),
       'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['emailNotifyPage'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['emailNotifyPage'],
       'exclude'                 => true,
       'inputType'               => 'pageTree',
       'foreignKey'              => 'tl_page.title',
       'eval'                    => array('fieldType'=>'radio','tl_class'=>'clr'),
       'sql'                     => "int(10) unsigned NOT NULL default '0'",
       'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['blockingTime'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['blockingTime'],
       'exclude'                 => true,
       'inputType'               => 'text',
       'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
       'sql'                     => "int(10) unsigned NOT NULL default '0'",
);