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
        * @var string
        */
       public $imageDir = 'system/modules/member_rating/assets/images';


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
       private static function _getSocialmediaIconSRC($strLink)
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
       public static function getSocialmediaIcon($strHref)
       {
              $src = self::_getSocialmediaIconSRC($strHref);
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
                            $arrayGrades[$arrLine[0]] = array(
                                   'score' => $arrLine[0],
                                   'label' => $arrLine[1],
                                   'icon' => $arrLine[2]
                            );
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
                                   $src = self::getImageDir() . '/levelicons/' . $arrGrade['icon'];
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
              return $arrReturn[$key] ? $arrReturn[$key] : null;
       }


       /**
        * @param $memberId
        * @param array $arrSize
        * @param string $alt
        * @param string $title
        * @param string $class
        * @param $objModule
        * @return bool|string
        */
       public static function getAvatar($memberId, array $arrSize, $alt = '', $title = '', $class = '', $objModule)
       {

              $objMember = \MemberModel::findByPk($memberId);
              if ($objMember !== null)
              {
                     $size = sprintf('width="%s" height="%s"', $arrSize[0], $arrSize[1]);
                     $avatar = array(
                            'alt' => specialchars($alt),
                            'title' => specialchars($title),
                            'size' => $size,
                            'class' => strlen($class) ? ' class="' . $class . '"' : '',
                     );

                     $src = null;
                     $objFile = \FilesModel::findByUuid($objMember->avatar);
                     if ($objFile !== null)
                     {
                            if (is_file(TL_ROOT . '/' . $objFile->path))
                            {
                                   $src = $objFile->path;
                            }
                     }
                     else
                     {
                            $path = $objMember->gender == 'female' ? $objModule->imageDir . '/avatars/female.png' : $objModule->imageDir . '/avatars/male.png';
                            if (is_file(TL_ROOT . '/' . $path))
                            {
                                   $src = $path;
                            }
                     }

                     // return default avatar
                     if (!$src)
                     {
                            $src = $objMember->gender == 'female' ? 'system/modules/member_rating/assets/images/avatars/female.png' : 'system/modules/member_rating/assets/images/avatars/male.png';
                     }

                     // return image markup
                     $avatar['src'] = TL_FILES_URL . \Image::get($src, $arrSize[0], $arrSize[1], $arrSize[2]);
                     if (strlen($avatar['src']))
                     {
                            return sprintf('<img src="%s" %s alt="%s" title="%s"%s>', $avatar['src'], $avatar['size'], $avatar['alt'], $avatar['title'], $avatar['class']);
                     }
              }
       }


       /**
        * @param $memberId
        * @return bool|mixed
        */
       public static function getSocialmediaLinks($memberId)
       {
              $objMember = \MemberModel::findByPk($memberId);
              if ($objMember !== null)
              {
                     if (!empty($objMember->socialmediaLinks))
                     {
                            return deserialize($objMember->socialmediaLinks);
                     }
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
        * @return string
        */
       public static function getImageDir()
       {
              return MEMBER_RATING_IMAGE_DIR;
       }


       /**
        * @param $strPath
        */
       public static function setImageDir($strPath)
       {
              if (!defined('MEMBER_RATING_IMAGE_DIR'))
              {
                     if (!empty($GLOBALS['TL_CONFIG']['customImageDir']))
                     {
                            $objFile = \FilesModel::findByUuid(trim($GLOBALS['TL_CONFIG']['customImageDir']));
                            if ($objFile !== null)
                            {
                                   if (is_dir(TL_ROOT . '/' . $objFile->path))
                                   {
                                          define('MEMBER_RATING_IMAGE_DIR', $objFile->path);
                                   }
                            }
                            else
                            {
                                   define('MEMBER_RATING_IMAGE_DIR', $strPath);
                            }
                     }
                     else
                     {
                            define('MEMBER_RATING_IMAGE_DIR', $strPath);
                     }
              }
       }


       /**
        * @param $arr
        * @param $fields
        * @return mixed
        */
       public static function sortArrayByFields($arr, $fields)
       {
              $sortFields = array();
              $args = array();

              foreach ($arr as $key => $row)
              {
                     foreach ($fields as $field => $order)
                     {
                            $sortFields[$field][$key] = $row[$field];
                     }
              }

              foreach ($fields as $field => $order)
              {
                     $args[] = $sortFields[$field];

                     if (is_array($order))
                     {
                            foreach ($order as $pt)
                            {
                                   $args[$pt];
                            }
                     }
                     else
                     {
                            $args[] = $order;
                     }
              }

              $args[] = &$arr;

              call_user_func_array('array_multisort', $args);

              return $arr;
       }
}