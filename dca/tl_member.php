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

/**
 * Table tl_member
 */
// Delete related comments
$GLOBALS['TL_DCA']['tl_member']['config']['ondelete_callback'][] = array(
       'tl_member_member_rating',
       'deleteRelatedComments'
);

if (strpos($GLOBALS['TL_DCA']['tl_member']['palettes']['default'], '{personal_legend},') === false)
{
       $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= 'rang';
}
else
{
       $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace('{personal_legend},', '{personal_legend},rang,', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);
}
if (strpos($GLOBALS['TL_DCA']['tl_member']['palettes']['default'], '{personal_legend},') === false)
{
       $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= ',socialmedia_links';
}
else
{
       $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace('{personal_legend},', '{personal_legend},socialmedia_links,', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);
}
if (strpos($GLOBALS['TL_DCA']['tl_member']['palettes']['default'], '{personal_legend},') === false)
{
       $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= ',punktestand';
}
else
{
       $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace('{personal_legend},', '{personal_legend},punktestand,', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);
}

if (!file_exists(TL_ROOT . '/system/modules/avatar'))
{
       if (strpos($GLOBALS['TL_DCA']['tl_member']['palettes']['default'], '{personal_legend},') === false)
       {
              $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= ',avatar';
       }
       else
       {
              $GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace('{personal_legend},', '{personal_legend},avatar,', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);
       }
}


// fields
$GLOBALS['TL_DCA']['tl_member']['fields']['socialmediaLinks'] = array(
       'label' => &$GLOBALS['TL_LANG']['tl_member']['socialmediaLinks'],
       'exclude' => true,
       'inputType' => 'text',
       'eval' => array('rgxp' => 'url', 'feGroup' => 'personal'),
       'sql' => "blob NULL"
);

if (!file_exists(TL_ROOT . '/system/modules/avatar'))
{
       $GLOBALS['TL_DCA']['tl_member']['fields']['avatar'] = array(
              'label' => &$GLOBALS['TL_LANG']['tl_member']['avatar'],
              'exclude' => true,
              'inputType'               => 'fileTree',
              //'inputType' => 'text',
              'eval' => array(
                     'fieldType' => 'radio',
                     'filesOnly' => true,
                     'tl_class' => 'clr',
                     'feViewable' => true,
                     'feEditable' => true,
                     'feGroup' => 'personal',
              ),
              'sql' => "binary(16) NULL"
       );
}

/**
 * Class tl_member_member_rating
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    Core
 */
class tl_member_member_rating extends Backend
{

       /**
        * ondelete_callback
        * Delete related items in tl_comments
        * @param DataContainer $dc
        */
       public function deleteRelatedComments(DataContainer $dc)
       {

              // Return if there is no ID
              if (!$dc->activeRecord->id || Input::get('act') == 'copy')
              {
                     return;
              }

              $objComments = $this->Database->prepare('SELECT * FROM tl_comments WHERE source = ? AND (owner = ? OR parent = ?)')->execute('tl_member', $dc->activeRecord->id, $dc->activeRecord->id);
              while ($objComments->next())
              {

                     $objDb = CommentsModel::findByPk($objComments->id);
                     $objDb->delete();
                     $this->log('DELETE FROM tl_comments WHERE id=' . $objComments->id, __METHOD__, TL_GENERAL);
              }
       }
}