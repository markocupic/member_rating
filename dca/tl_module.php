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


// load language file
Controller::loadLanguageFile('tl_member');

/**
 * Table tl_module
 */

// palettes
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'notifyRatedUser';
$GLOBALS['TL_DCA']['tl_module']['palettes']['member_rating'] = '{title_legend},name,headline,type,blockingTime,jumpTo,notifyRatedUser;{avatar:hide},avatarSizeProfile,avatarSizeListing;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['rated_member_list'] = '{title_legend},name,headline,type;{member_rating_list_settings},sortingField1,sortingDirection1,sortingField2,sortingDirection2,sortingField3,sortingDirection3,detailPage,avatarSizeListing;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['notifyRatedUser'] = 'emailNotifyPage_ActivateComment,emailNotifyPage_DeleteComment';

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
       'eval'                    => array('submitOnChange'=>true,'tl_class'=>'clr m12'),
       'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['emailNotifyPage_ActivateComment'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['emailNotifyPage_ActivateComment'],
       'exclude'                 => true,
       'inputType'               => 'pageTree',
       'foreignKey'              => 'tl_page.title',
       'eval'                    => array('fieldType'=>'radio','tl_class'=>'w50'),
       'sql'                     => "int(10) unsigned NOT NULL default '0'",
       'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['emailNotifyPage_DeleteComment'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['emailNotifyPage_DeleteComment'],
       'exclude'                 => true,
       'inputType'               => 'pageTree',
       'foreignKey'              => 'tl_page.title',
       'eval'                    => array('fieldType'=>'radio','tl_class'=>'w50'),
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
$GLOBALS['TL_DCA']['tl_module']['fields']['sortingField1'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['sortingField1'],
       'exclude'                 => true,
       'inputType'               => 'select',
       'reference'               => &$GLOBALS['TL_LANG']['tl_member'],
       'default'                 => 'score',
       'options'                 => array('score', 'firstname', 'lastname'),
       'eval'                    => array('includeBlankOption' => true, 'tl_class'=>'w50'),
       'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['sortingField2'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['sortingField2'],
       'exclude'                 => true,
       'inputType'               => 'select',
       'reference'               => &$GLOBALS['TL_LANG']['tl_member'],
       'default'                 => 'score',
       'options'                 => array('score', 'firstname', 'lastname'),
       'eval'                    => array('includeBlankOption' => true, 'tl_class'=>'w50'),
       'sql'                     => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_module']['fields']['sortingField3'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['sortingField3'],
       'exclude'                 => true,
       'inputType'               => 'select',
       'reference'               => &$GLOBALS['TL_LANG']['tl_member'],
       'default'                 => 'score',
       'options'                 => array('score', 'firstname', 'lastname'),
       'eval'                    => array('includeBlankOption' => true, 'tl_class'=>'w50'),
       'sql'                     => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_module']['fields']['sortingDirection1'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['sortingDirection1'],
       'exclude'                 => true,
       'inputType'               => 'select',
       'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
       'default'                 => 'SORT_ASC',
       'options'                 => array('SORT_ASC','SORT_DESC'),
       'eval'                    => array('tl_class'=>'w50'),
       'sql'                     => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_module']['fields']['sortingDirection2'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['sortingDirection2'],
       'exclude'                 => true,
       'inputType'               => 'select',
       'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
       'default'                 => 'SORT_ASC',
       'options'                 => array('SORT_ASC','SORT_DESC'),
       'eval'                    => array('tl_class'=>'w50'),
       'sql'                     => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_module']['fields']['sortingDirection3'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['sortingDirection3'],
       'exclude'                 => true,
       'inputType'               => 'select',
       'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
       'default'                 => 'SORT_ASC',
       'options'                 => array('SORT_ASC','SORT_DESC'),
       'eval'                    => array('tl_class'=>'w50'),
       'sql'                     => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_module']['fields']['avatarSizeProfile'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['avatarSizeProfile'],
       'exclude'                 => true,
       'inputType'               => 'imageSize',
       'options'                 => $GLOBALS['TL_CROP'],
       'reference'               => &$GLOBALS['TL_LANG']['MSC'],
       'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
       'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['avatarSizeListing'] = array
(
       'label'                   => &$GLOBALS['TL_LANG']['tl_module']['avatarSizeListing'],
       'exclude'                 => true,
       'inputType'               => 'imageSize',
       'options'                 => $GLOBALS['TL_CROP'],
       'reference'               => &$GLOBALS['TL_LANG']['MSC'],
       'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
       'sql'                     => "varchar(64) NOT NULL default ''"
);