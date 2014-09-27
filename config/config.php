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
       'module_type_member_rating' => array(
              'member_rating_detail' => 'MemberRating\\MemberRatingDetail',
              'member_rating_list' => 'MemberRating\\MemberRatingList',
              'member_rating_logged_in_users_profile' => 'MemberRating\\MemberRatingLoggedInUsersProfile',
       )
));

if (TL_MODE == 'FE')
{
       /**
        * Register the auto_item keywords
        */
       $GLOBALS['TL_AUTO_ITEM'][] = 'member';

       // auto add groupmembership
       $GLOBALS['TL_HOOKS']['postLogin'][] = array('MemberRating\\MemberRatingHelper', 'addGroupMembership');
}


