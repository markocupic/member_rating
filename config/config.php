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
 * Back end modules
 */

/**
 * Front end modules
 */

array_insert($GLOBALS['FE_MOD'], 2, array(
       'member_rating' => array(
              'member_rating' => 'MemberRating\\MemberRating',
              'rated_member_list' => 'MemberRating\\RatedMemberList',
       )
));

if (TL_MODE == 'FE')
{
       $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/member_rating/assets/js/member_rating.js';
       $GLOBALS['TL_CSS'][] = 'system/modules/member_rating/assets/css/member_rating.css';

       /**
        * Register the auto_item keywords
        */
       $GLOBALS['TL_AUTO_ITEM'][] = 'member';
}


