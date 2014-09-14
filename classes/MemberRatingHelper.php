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

namespace MCupic\MemberRating;

class MemberRatingHelper extends \System
{

       /**
        * get score of a member
        * @param $id
        * @return string
        */
       public static function getScore($id)
       {

              $objPoints = \Database::getInstance()->prepare("SELECT SUM(score) as sumscore FROM tl_comments WHERE source = ? AND parent = ?")->execute('tl_member', $id);
              $score = $objPoints->sumscore <= 0 ? '0' : $objPoints->sumscore;
              return $score;
       }

       /**
        * @param $strLink
        * @return string
        */
       public static function getSocialmediaIconSRC($strLink)
       {

              $arrNeedle = array();
              $arrIcons = array();
              if (trim($GLOBALS['TL_CONFIG']['socialmediaLinks']) != '')
              {
                     foreach (explode('***', trim($GLOBALS['TL_CONFIG']['socialmediaLinks'])) as $item)
                     {
                            $arrSMBrand = explode('|', $item);
                            if (is_array($arrSMBrand))
                            {
                                   if (count($arrSMBrand) == 2)
                                   {
                                          $arrNeedle[] = $arrSMBrand[0];
                                          $arrIcons[] = $arrSMBrand[1];
                                   }
                            }
                     }
              }

              foreach ($arrNeedle as $key => $needle)
              {
                     if (strpos($strLink, $needle) !== false)
                     {
                            $icon = self::getImageDir() . '/socialmedia/' . $arrIcons[$key];
                            if (is_file(TL_ROOT . '/' . $icon))
                            {
                                   return $icon;
                            }
                     }
              }
              $icon = self::getImageDir() . '/socialmedia/default.png';
              if (is_file(TL_ROOT . '/' . $icon))
              {
                     return $icon;
              }

       }

       /**
        * @param $strHref
        * @return string
        */
       public static function generateSocialmediaIcon($strHref)
       {

              $src = self::getSocialmediaIconSRC($strHref);
              if ($src != '')
              {
                     $objFile = new \File($src, true);
                     if ($objFile !== null)
                     {
                            if ($objFile->isGdImage)
                            {

                                   $src = TL_FILES_URL . $src;
                                   $size = sprintf('width="%s" height="%s"', $objFile->width, $objFile->height);
                                   $alt = 'socialmediaIcon';
                                   $title = specialchars($strHref);
                                   return sprintf('<img src="%s" %s alt="%s" title="%s" class="socialmediaIcon">', $src, $size, $alt, $title);

                            }
                     }
              }
       }

       /**
        * @param $score
        * @return array
        */
       public static function getGrade($id, $key)
       {

              $score = self::getScore($id);
              if ($score == '0')
              {
                     $score = 0;
              }
              $arrReturn = array();
              $arrayGrades = array();
              if (trim($GLOBALS['TL_CONFIG']['gradeLabeling']) != '')
              {
                     foreach (explode('***', trim($GLOBALS['TL_CONFIG']['gradeLabeling'])) as $strLine)
                     {
                            $arrLine = explode('|', $strLine);
                            if (count($arrLine) != 3)
                            {
                                   continue;
                            }
                            if (!intval($arrLine[0]) && $arrLine[0] != '0')
                            {
                                   continue;
                            }
                            $arrayGrades[$arrLine[0]] = array('score' => $arrLine[0], 'label' => $arrLine[1],
                                   'icon' => $arrLine[2]);
                     }
              }
              krsort($arrayGrades);
              $arrayGrades = count($arrayGrades) ? $arrayGrades : false;
              if ($arrayGrades)
              {
                     foreach ($arrayGrades as $arrGrade)
                     {
                            if ($score >= $arrGrade['score'])
                            {
                                   $arrReturn['label'] = $arrGrade['label'];
                                   $src = self::getImageDir() . '/' . $arrGrade['icon'];
                                   if (is_file(TL_ROOT . '/' . $src))
                                   {
                                          $objFile = new \File($src, true);
                                          if ($objFile !== null)
                                          {
                                                 if ($objFile->isGdImage)
                                                 {
                                                        $size = sprintf('width="%s" height="%s"', $objFile->width, $objFile->height);
                                                        $arrReturn['icon'] = sprintf('<img src="%s" %s alt="%s" title="%s" class="%s">', TL_FILES_URL . $src, $size, 'grade icon', specialchars($arrGrade['label']), 'gradeIcon');
                                                 }
                                          }
                                   }
                                   break;
                            }
                     }
              }
              return $arrReturn[$key] ? $arrReturn[$key] : false;

       }

       /**
        * @param $memberId
        * @param array $arrSize
        * @param $objModule
        * @return bool|string
        */
       public static function getAvatar($memberId, array $arrSize, $alt = '', $title = '', $objModule)
       {

              $objMember = \MemberModel::findByPk($memberId);
              if ($objMember === null)
              {
                     return false;
              }

              $avatar = array('alt' => specialchars($alt), 'title' => specialchars($title),
                     'size' => sprintf('width="%s" height="%s"', $arrSize[0], $arrSize[1]));

              $objFile = \FilesModel::findByUuid($objMember->avatar);
              if ($objFile !== null)
              {
                     if (is_file(TL_ROOT . '/' . $objFile->path))
                     {
                            $avatar['src'] = TL_FILES_URL . \Image::get($objFile->path, $arrSize[0], $arrSize[1], $arrSize[2]);
                     }
              }
              else
              {
                     $path = $objMember->gender == 'female' ? $objModule->imageDir . '/female.png' : $objModule->imageDir . '/male.png';
                     if (is_file(TL_ROOT . '/' . $path))
                     {
                            $avatar['src'] = TL_FILES_URL . \Image::get($path, 150, 150, 'center_center');
                     }
              }

              return $avatar;
       }

       /**
        * @param $memberId
        * @return bool|mixed
        */
       public static function getSocialmediaLinks($memberId)
       {

              $objMember = \MemberModel::findByPk($memberId);
              if ($objMember === null)
              {
                     return false;
              }

              if (empty($objMember->socialmediaLinks))
              {
                     return false;
              }
              else
              {
                     return deserialize($objMember->socialmediaLinks);
              }
       }

       /**
        * @param null $id
        * @param string $type
        * @return mixed|null|string
        */
       public static function findMemberByPk($id = null, $type = 'fullname')
       {

              $username = '';
              $objMember = \MemberModel::findByPk($id);
              if ($objMember !== null)
              {
                     if ($type == 'firstname')
                     {
                            $username = $objMember->firstname;
                     }
                     elseif ($type == 'lastname')
                     {
                            $username = $objMember->lastname;
                     }
                     else
                     {
                            $username = $objMember->firstname . ' ' . $objMember->lastname;
                     }
              }
              return $username;
       }

       /**
        * @return mixed|null
        */
       public static function getImageDir()
       {

              if (!empty($GLOBALS['TL_CONFIG']['customImageDir']))
              {
                     $objFile = \FilesModel::findByUuid(trim($GLOBALS['TL_CONFIG']['customImageDir']));
              }
              if ($objFile !== null)
              {
                     if (is_dir(TL_ROOT . '/' . $objFile->path))
                     {
                            return $objFile->path;
                     }
              }
              return null;
       }

}