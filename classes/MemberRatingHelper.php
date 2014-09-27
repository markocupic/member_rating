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

namespace MCupic\MemberRating;

class MemberRatingHelper extends \System
{

       /**
        * add member to a certain group that is assigned to a certain score
        */
       public function addGroupMembership()
       {

              $arrayGrades = MemberRating::getGradeLabelingArray();

              if (!count($arrayGrades) > 0)
              {
                     return;
              }

              krsort($arrayGrades);

              $objMember = \MemberModel::findAll();
              while ($objMember->next())
              {
                     foreach ($arrayGrades as $grade)
                     {
                            $score = MemberRating::getScore($objMember->id);
                            if ($score >= $grade['score'])
                            {
                                   if (count($grade['groups']) > 0)
                                   {
                                          MemberRating::addToGroup($objMember->id, $grade['groups']);
                                   }
                                   break;
                            }
                     }
              }
       }
} 