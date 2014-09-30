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

abstract class MemberRating extends \Module
{

       /**
        * image directory
        *
        * @var string
        */
       public $imageDir = 'system/modules/member_rating/assets/images';

       /**
        * rated user object
        *
        * @var object
        */
       protected $ratedUser;

       /**
        * logged in user object
        *
        * @var object
        */
       protected $loggedInUser;


       /**
        * @return string|void
        */
       public function generate()
       {

              define('MOD_MEMBER_RATING', 'true');

              require_once TL_ROOT . '/system/modules/member_rating/helper/functions.php';


              // Set the loggedInUser var
              if (FE_USER_LOGGED_IN)
              {
                     $this->User = \FrontendUser::getInstance();
                     $this->loggedInUser = $this->User;
              }

              // Overwrite imageDir if a custom directory was selected
              $this->setImageDir();

              // Load DCA
              $this->loadDataContainer('tl_comments');
              $this->loadDataContainer('tl_member');
              $this->loadLanguageFile('tl_comments');
              $this->loadLanguageFile('tl_member');

              return parent::generate();
       }


       /**
        *
        */
       public function addTemplateVars()
       {

              // MSC
              $this->Template->loggedInUser = $this->loggedInUser ? $this->loggedInUser : null;
              $this->Template->ratedUser = $this->ratedUser ? $this->ratedUser : null;

              // closures
              $this->Template->getImageDir = function ()
              {

                     return TL_FILES_URL . MemberRating::getImageDir();
              };
              $this->Template->getSocialmediaIcon = function ($strHref)
              {

                     return MemberRating::getSocialmediaIcon($strHref);
              };

              // add javascript language-file-object to template
              $strLang = "objLang = {";
              foreach ($GLOBALS['TL_LANG']['MOD']['member_rating'] as $k => $v)
              {
                     if (is_array($v))
                     {
                            $strLang .= $k . ": {";
                            foreach ($v as $kk => $vv)
                            {
                                   $strLang .= $kk . ": '" . $vv . "',";
                            }
                            $strLang .= "},";
                     }
                     else
                     {
                            $strLang .= $k . ": '" . $v . "',";
                     }
              }
              $strLang .= "};";
              $this->Template->JsLanguageObject = str_replace(',}', '}', $strLang) . "\r\n";

              $jsModuleVars = "ModuleVars = {";
              $jsModuleVars .= "REQUEST_TOKEN: '" . REQUEST_TOKEN . "',";
              $jsModuleVars .= "imgDir: '" . $this->getImageDir() . "',";
              $jsModuleVars .= "};";
              $this->Template->JsModuleObject = str_replace(',}', '}', $jsModuleVars);;
       }


       /**
        * activateOrDelete
        */
       protected function activateOrDelete()
       {

              $dbChange = false;
              $objComments = \CommentsModel::findByActivation_token(\Input::get('activation_token'));
              if ($objComments === null)
              {
                     die(utf8_decode($GLOBALS['TL_LANG']['MOD']['member_rating']['invalidToken']));
              }

              // delete or publish comment via url, received from a notification email
              if (\Input::get('del') == 'true')
              {
                     $dbChange = true;
                     $this->log('DELETE FROM tl_comments WHERE id=' . $objComments->id, __METHOD__, TL_GENERAL);
                     $objComments->delete();
                     $msg = $GLOBALS['TL_LANG']['MOD']['member_rating']['itemHasBeenActivated'];
                     if (($objNextPage = \PageModel::findPublishedById($this->emailNotifyPage_DeleteComment)) !== null)
                     {
                            $this->redirect($this->generateFrontendUrl($objNextPage->row()));
                     }
              }
              elseif (\Input::get('publish') == 'true')
              {
                     $dbChange = true;
                     $objComments->published = 1;
                     $objComments->activation_token = '';
                     $objComments->save();
                     $this->log('A new version of tl_comments ID ' . $objComments->id . ' has been created', __METHOD__, TL_GENERAL);
                     $msg = $GLOBALS['TL_LANG']['MOD']['member_rating']['itemHasBeenActivated'];
                     if (($objNextPage = \PageModel::findPublishedById($this->emailNotifyPage_ActivateComment)) !== null)
                     {
                            $this->redirect($this->generateFrontendUrl($objNextPage->row()));
                     }
              }
              else
              {
                     //
              }

              if ($dbChange === true)
              {
                     die($msg);
              }
              exit();
       }


       /**
        * handle ajax requests
        */
       protected function handleAjax()
       {

              // delete socialmedia links
              if (\Input::get('act') == 'delSocialmediaLink' && \Input::post('type'))
              {
                     if (FE_USER_LOGGED_IN)
                     {
                            $arrSocialmediaLinks = deserialize($this->loggedInUser->socialmediaLinks);
                            if (array_search(\Input::post('type'), $arrSocialmediaLinks) !== false)
                            {
                                   $key = array_search(\Input::post('type'), $arrSocialmediaLinks);
                                   unset($arrSocialmediaLinks[$key]);
                            }
                            $this->loggedInUser->socialmediaLinks = serialize(array_values($arrSocialmediaLinks));
                            $this->loggedInUser->save();
                            $this->log('A new version of tl_member ID ' . $this->loggedInUser->id . ' has been created', __METHOD__, TL_GENERAL);

                     }
              }

              // toggle visibility (publish or unpublish)
              if (\Input::get('act') == 'toggleVisibility' && \Input::get('id'))
              {
                     if (FE_USER_LOGGED_IN)
                     {
                            $objComment = \CommentsModel::findByPk(\Input::get('id'));
                            if ($objComment !== null)
                            {
                                   if ($this->loggedInUser->id == $objComment->parent)
                                   {
                                          $isPublished = $objComment->published ? 0 : 1;
                                          $objComment->published = $isPublished;
                                          $objComment->save();
                                          $this->log('A new version of tl_comments ID ' . $objComment->id . ' has been created', __METHOD__, TL_GENERAL);
                                          $strReturn = $isPublished == 0 ? 'invisible' : 'visible';
                                          echo $strReturn;
                                   }
                            }
                     }
              }
              exit;
       }


       /**
        * get score of a member
        *
        * @param $id
        * @return string
        */
       public static function getScore($id)
       {

              $objPoints = \Database::getInstance()->prepare("SELECT SUM(score) as sumscore FROM tl_comments WHERE source = ? AND parent = ? AND owner > ?")->execute('tl_member', $id, 0);
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
       public static function getGrade($id, $key = 'label')
       {

              $score = self::getScore($id);
              if ($score == '0')
              {
                     $score = 0;
              }
              $arrReturn = array();
              $arrayGrades = MemberRating::getGradeLabelingArray();

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
        * @return array
        */
       public static function getGradeLabelingArray()
       {

              $arrayGrades = array();
              if (trim($GLOBALS['TL_CONFIG']['gradeLabeling']) != '')
              {
                     $arritems = explode('***', trim($GLOBALS['TL_CONFIG']['gradeLabeling']));
                     foreach ($arritems as $item)
                     {
                            preg_match_all('/^(.*?)\|(.*?)\|(.*?)\|Groups{(.*?)}/', $item, $matches);
                            $arrayGrades[$matches[1][0]] = array(
                                   'score' => $matches[1][0],
                                   'label' => $matches[2][0],
                                   'icon' => $matches[3][0],
                                   'groups' => array_values(explode(',', $matches[4][0]))
                            );
                     }
              }
              return $arrayGrades;
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
       public function setImageDir()
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
                                   define('MEMBER_RATING_IMAGE_DIR', $this->imageDir);
                            }
                     }
                     else
                     {
                            define('MEMBER_RATING_IMAGE_DIR', $this->imageDir);
                     }
                     if (defined('MEMBER_RATING_IMAGE_DIR'))
                     {
                            $this->imageDir = MEMBER_RATING_IMAGE_DIR;
                     }
              }
       }


       /**
        * @return array
        */
       public static function findAllGroups()
       {

              $arrGroups = array();
              if (trim($GLOBALS['TL_CONFIG']['gradeLabeling']) != '')
              {
                     preg_match_all('/Groups{(.*?)}/', $GLOBALS['TL_CONFIG']['gradeLabeling'], $matches);
                     foreach ($matches[1] as $strIDS)
                     {
                            foreach (explode(',', $strIDS) as $groupId)
                            {
                                   $arrGroups[] = $groupId;
                            }
                     }
              }

              return $arrGroups;
       }


       /**
        * @param $memberId
        * @param $arrGroups
        */
       public static function addToGroup($memberId, $arrGroups)
       {

              $objMember = \MemberModel::findByPk($memberId);
              if ($objMember !== null)
              {
                     $groups = array_values(array_unique(array_merge(is_array(deserialize($objMember->groups)) ? deserialize($objMember->groups) : array(), $arrGroups)));
                     $objMember->groups = serialize($groups);
                     $objMember->save();
              }
       }
}
