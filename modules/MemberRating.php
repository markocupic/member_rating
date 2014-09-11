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

class MemberRating extends \Module
{

       /**
        * Template
        * @var string
        */
       protected $strTemplate = 'mod_member_rating';

       protected $arrRanking = array(
              'cool' => 500,
              'verycool' => 1000
       );

       /**
        * @var string
        */
       public $imageDir = 'system/modules/member_rating/assets/images';


       /**
        *
        */
       public function __construct($objModule, $strColumn = 'main')
       {

              define('MOD_MEMBER_RATING', 'true');

              return parent::__construct($objModule, $strColumn);
       }


       /**
        * @return string
        */
       public function generate()
       {

              // activate comment by token via url
              if (strlen(\Input::get('activation_token')))
              {
                     $objComments = \CommentsModel::findByActivation_token(\Input::get('activation_token'));
                     if ($objComments === null)
                     {
                            die(utf8_decode('Ungültiges oder abgelaufenes Aktivierungstoken!'));
                     }
                     if (\Input::get('del') == 'true')
                     {
                            // delete comment
                            $objComments->delete();
                     }
                     elseif (\Input::get('publish') == 'true')
                     {
                            // publish comment
                            $objComments->published = 1;
                            $objComments->activation_token = '';
                            $objComments->save();
                     }
                     else
                     {
                            //
                     }

                     if ($this->emailNotifyPage)
                     {
                            if (($objNextPage = \PageModel::findPublishedById($this->emailNotifyPage)) !== null)
                            {
                                   $this->redirect($this->generateFrontendUrl($objNextPage->row()));
                            }
                     }
                     exit();
              }

              if (TL_MODE == 'BE')
              {
                     $objTemplate = new \BackendTemplate('be_wildcard');

                     $objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['member_rating'][0]) . ' ###';
                     $objTemplate->title = $this->headline;
                     $objTemplate->id = $this->id;
                     $objTemplate->link = $this->name;
                     $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

                     return $objTemplate->parse();
              }

              // set the item from the auto_item parameter
              if ($GLOBALS['TL_CONFIG']['useAutoItem'] && isset($_GET['auto_item']))
              {
                     \Input::setGet('member', \Input::get('auto_item'));
              }

              $idRatedMember = \Input::get('member');
              $this->ratedUser = \MemberModel::findByPk($idRatedMember);
              if ($this->ratedUser === null)
              {
                     return;
              }


              if (FE_USER_LOGGED_IN)
              {
                     $this->User = \FrontendUser::getInstance();
              }

              // overwrite default imageDir if a custom directory was selected
              if (($imageDir = MemberRatingHelper::getImageDir($this)) !== null)
              {
                     $this->imageDir = $imageDir;
              }


              return parent::generate();

       }


       /**
        * Generate the module
        */
       protected function compile()
       {

              // set the item from the auto_item parameter
              if ($GLOBALS['TL_CONFIG']['useAutoItem'] && isset($_GET['auto_item']))
              {
                     \Input::setGet('member', \Input::get('auto_item'));
              }


              if (FE_USER_LOGGED_IN)
              {
                     $this->loggedInUser = $this->User;
              }

              $this->loadDataContainer('tl_comments');
              $this->loadDataContainer('tl_member');
              $this->loadLanguageFile('tl_comments');
              $this->loadLanguageFile('tl_member');


              // handle Ajax requests
              if (\Input::get('isAjaxRequest'))
              {
                     $this->handleAjax();
                     exit();
              }

              if (FE_USER_LOGGED_IN)
              {
                     // get avatar of logged in user
                     $objFile = \FilesModel::findByUuid($this->User->avatar);
                     if ($objFile !== null)
                     {
                            if (is_file(TL_ROOT . '/' . $objFile->path))
                            {
                                   $this->loggedInUser->avatar = \Image::get($objFile->path, 150, 150, 'center_center');
                            }
                     }
                     else
                     {
                            $path = $this->imageDir . '/avatar_default.jpg';
                            if (is_file(TL_ROOT . '/' . $path))
                            {
                                   $this->loggedInUser->avatar = \Image::get($path, 150, 150, 'center_center');
                            }
                     }

                     // get score  of logged in user
                     $objPoints = $this->Database->prepare("SELECT SUM(score) as score FROM tl_comments WHERE source = ? AND parent = ? AND published = ?")->execute('tl_member', $this->loggedInUser->id, 1);
                     $this->loggedInUser->score = $objPoints->score <= 0 ? '0' : $objPoints->score;

                     // get grade of logged in user
                     $arrGrade = MemberRatingHelper::getGrade($this->loggedInUser->id, 'label');
                     $this->loggedInUser->gradeLabel = MemberRatingHelper::getGrade($this->loggedInUser->id, 'label');
                     $this->loggedInUser->gradeIcon = MemberRatingHelper::getGrade($this->loggedInUser->id, 'icon');
              }

              // get score of rated user
              $this->ratedUser->score = MemberRatingHelper::getScore($this->ratedUser->id);

              // get grade
              $this->ratedUser->gradeLabel = MemberRatingHelper::getGrade($this->loggedInUser->id, 'label');
              $this->ratedUser->gradeIcon = MemberRatingHelper::getGrade($this->loggedInUser->id, 'icon');

              // get all ratings
              if ($this->ratedUser->id == $this->loggedInUser->id)
              {
                     $strSql = "SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? ORDER BY dateOfCreation DESC, score DESC";
              }
              else
              {
                     $strSql = "SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? AND published = '1' ORDER BY dateOfCreation DESC, score DESC";
              }
              $objRatings = $this->Database->prepare($strSql)->execute('tl_member', $this->ratedUser->id);
              $arrAllRatings = array();
              while ($row = $objRatings->fetchAssoc())
              {
                     $objMember = \MemberModel::findByPk($row['owner']);
                     if ($objMember !== null)
                     {
                            $row['firstname'] = $objMember->firstname;
                            $row['lastname'] = $objMember->lastname;
                            $objFile = \FilesModel::findByUuid($objMember->avatar);
                            if ($objFile !== null)
                            {
                                   if (is_file(TL_ROOT . '/' . $objFile->path))
                                   {
                                          $row['avatar'] = \Image::get($objFile->path, 50, 50, 'center_center');
                                   }
                            }
                            else
                            {
                                   $path = $this->imageDir . '/avatar_default.jpg';
                                   if (is_file(TL_ROOT . '/' . $path))
                                   {
                                          $row['avatar'] = \Image::get($path, 50, 50, 'center_center');
                                   }
                            }
                            $visibility = $row['published'] ? 'visible.png' : 'invisible.png';
                            $row['visibility_icon_src'] = sprintf($this->imageDir . '/%s', $visibility);
                     }
                     $arrAllRatings[] = $row;
              }
              $this->ratedUser->allRatings = count($arrAllRatings) ? $arrAllRatings : false;


              // get top 3
              $objRatings = $this->Database->prepare("SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? AND published = ? ORDER BY score DESC, dateOfCreation DESC")->limit(3)->execute('tl_member', $this->ratedUser->id, 1);
              $arrTop3 = array();
              while ($row = $objRatings->fetchAssoc())
              {
                     $objMember = \MemberModel::findByPk($row['owner']);
                     if ($objMember !== null)
                     {
                            $row['firstname'] = $objMember->firstname;
                            $row['lastname'] = $objMember->lastname;
                            $objFile = \FilesModel::findByUuid($objMember->avatar);
                            if ($objFile !== null)
                            {
                                   if (is_file(TL_ROOT . '/' . $objFile->path))
                                   {
                                          $row['avatar'] = \Image::get($objFile->path, 50, 50, 'center_center');
                                   }
                            }
                            else
                            {
                                   $path = $this->imageDir . '/avatar_default.jpg';
                                   if (is_file(TL_ROOT . '/' . $path))
                                   {
                                          $row['avatar'] = \Image::get($path, 50, 50, 'center_center');
                                   }
                            }
                     }
                     $arrTop3[] = $row;
              }
              $this->ratedUser->top3 = count($arrTop3) > 2 ? $arrTop3 : false;

              // my ratings
              if (FE_USER_LOGGED_IN)
              {
                     $strSql = "SELECT * FROM tl_comments WHERE source = ? AND owner = ? ORDER BY dateOfCreation DESC, score DESC";
                     $objRatings = $this->Database->prepare($strSql)->execute('tl_member', $this->loggedInUser->id);
                     $arrMyRatings = array();
                     while ($row = $objRatings->fetchAssoc())
                     {
                            $objMember = \MemberModel::findByPk($row['owner']);
                            if ($objMember !== null)
                            {
                                   $row['firstname'] = $objMember->firstname;
                                   $row['lastname'] = $objMember->lastname;
                                   $objFile = \FilesModel::findByUuid($objMember->avatar);
                                   if ($objFile !== null)
                                   {
                                          if (is_file(TL_ROOT . '/' . $objFile->path))
                                          {
                                                 $row['avatar'] = \Image::get($objFile->path, 50, 50, 'center_center');
                                          }
                                   }
                                   else
                                   {
                                          $path = $this->imageDir . '/avatar_default.jpg';
                                          if (is_file(TL_ROOT . '/' . $path))
                                          {
                                                 $row['avatar'] = \Image::get($path, 50, 50, 'center_center');
                                          }
                                   }
                                   $visibility = $row['published'] ? 'visible.png' : 'invisible.png';
                                   $row['visibility_icon_src'] = sprintf($this->imageDir . '/%s', $visibility);
                            }
                            $arrMyRatings[] = $row;
                     }
                     $this->loggedInUser->myRatings = count($arrMyRatings) ? $arrMyRatings : false;
              }


              $this->Template->loggedInUser = $this->loggedInUser;
              $this->Template->ratedUser = $this->ratedUser;

              if (FE_USER_LOGGED_IN)
              {
                     $this->generateSocialMediaLinksForm();
                     $this->generateVotingForm();
              }

              $this->Template->module = $this;

              // add javascript language file object to template
              $strLang = "objLang = {";
              foreach ($GLOBALS['TL_LANG']['MOD']['member_rating'] as $k => $v)
              {
                     $strLang .= $k . ": '" . $v . "',";
              }
              $strLang .= "};";
              $this->Template->languageObject = $strLang;

       }


       /**
        * generate voting-form
        */
       protected function generateVotingForm()
       {

              if (!$this->loggedInUser || $this->loggedInUser->id == $this->ratedUser->id)
              {
                     return;
              }

              $strFields = '';
              $scoreError = false;
              $this->Template->formId = 'tl_comments_' . $this->id;
              $this->Template->action = \Environment::get('indexFreeRequest');
              $this->Template->enctype = 'application/x-www-form-urlencoded';

              $arrFields = array();
              $objComment = new \CommentsModel();

              // Build the form
              $arrFF = array(
                     'comment',
                     'score',
                     'captcha'
              );
              foreach ($arrFF as $field)
              {
                     $arrData = & $GLOBALS['TL_DCA']['tl_comments']['fields'][$field];
                     $strClass = $GLOBALS['TL_FFL'][$arrData['inputType']];
                     $arrData['eval']['tableless'] = 'true';
                     $arrData['label'] = $GLOBALS['TL_LANG']['tl_comments'][$field][0];
                     $varValue = '';

                     $objWidget = new $strClass($strClass::getAttributesFromDca($arrData, $field, $varValue, '', '', $this));
                     $objWidget->storeValues = true;


                     // Validate the form data
                     if (\Input::post('FORM_SUBMIT') == 'tl_comments_' . $this->id)
                     {
                            $objWidget->validate();
                            $varValue = $objWidget->value;

                            // check vor valid score interval
                            if ($field == 'score')
                            {
                                   if (!mberegi('^(1|2|3|4|5)\d{0}$', $varValue))
                                   {
                                          $doNotSubmit = true;
                                          $scoreError = true;
                                   }
                            }

                            // Do not submit the field if there are errors
                            if ($objWidget->hasErrors())
                            {
                                   $doNotSubmit = true;
                            }
                            elseif ($objWidget->submitInput())
                            {
                                   $blnModified = true;
                                   // Store the form data
                                   $_SESSION['FORM_DATA'][$field] = $varValue;

                                   // Set the correct empty value (see #6284, #6373)
                                   if ($varValue === '')
                                   {
                                          $varValue = $objWidget->getEmptyValue();
                                   }

                                   // Set the new value
                                   if ($field !== 'captcha')
                                   {
                                          $objComment->$field = $varValue;
                                   }
                            }
                     }

                     $temp = $objWidget->parse();
                     if ($field == 'score')
                     {
                            $temp = '<input type="hidden" name="score" id="ctrl_score" value="">';
                     }

                     $strFields .= $temp;
                     $arrFields[$field] = $temp;
              }

              // Save the model
              if ($doNotSubmit !== true && $blnModified && \Input::post('FORM_SUBMIT') == 'tl_comments_' . $this->id)
              {
                     $objComment->owner = $this->User->id;
                     $objComment->dateOfCreation = time();
                     $objComment->source = 'tl_member';
                     $objComment->ip = \Environment::get('ip');
                     $objComment->activation_token = md5(session_id() . time() . $this->User->id);
                     $objComment->parent = $this->ratedUser->id;
                     $objComment->published = 0;

                     $objComment->save();

                     // notify rated member
                     if ($this->notifyRatedUser && $objComment->id > 0 && $objComment->comment != '')
                     {
                            $this->notifyUser($objComment);
                     }
                     $this->jumpToOrReload($this->jumpTo);
              }
              if ($scoreError)
              {
                     $strFields = '<p class="error">Bitte eine gültige Punktzahl vergeben.</p>' . $strFields;
              }
              $this->Template->slabel = specialchars($GLOBALS['TL_LANG']['MSC']['saveData']);
              $this->Template->fields = $strFields;
              $this->Template->arrFields = $arrFields;
       }


       protected function notifyUser($objComment)
       {

              global $objPage;
              if ($this->ratedUser->email == '')
              {
                     return;
              }
              $objTemplate = new \FrontendTemplate('member_rating_email_notification');
              $objTemplate->name = $this->ratedUser->firstname;
              $objTemplate->author = $this->loggedInUser->firstname . ' ' . $this->loggedInUser->lastname;
              $objTemplate->comment = nl2br($objComment->comment);
              $objTemplate->score = $objComment->score;
              $objTemplate->link = 'http://' . $_SERVER['SERVER_NAME'] . '/' . $this->generateFrontendUrl($objPage->row(), '', $objPage->language) . '?publish=true&activation_token=' . $objComment->activation_token;
              $objTemplate->link_del = 'http://' . $_SERVER['SERVER_NAME'] . '/' . $this->generateFrontendUrl($objPage->row(), '', $objPage->language) . '?del=true&activation_token=' . $objComment->activation_token;
              $strContent = $objTemplate->parse();

              // Mail
              $objEmail = new \Email();
              $objEmail->subject = 'Neuer Kommentar von ' . $objTemplate->author . ' auf ' . $_SERVER['SERVER_NAME'];

              $strContent = $this->replaceInsertTags($strContent);
              $objEmail->html = $strContent;

              // text version
              $strContent = \String::decodeEntities($strContent);
              $strContent = strip_tags($strContent);
              $strContent = str_replace(array(

                                               '[&]',
                                               '[lt]',
                                               '[gt]'
                                        ), array(
                                               '&',
                                               '<',
                                               '>'
                                        ), $strContent);
              $objEmail->text = $strContent;

              $objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
              $objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
              $objEmail->sendTo($this->ratedUser->email);
       }


       /**
        * handle ajax requests
        */
       protected function handleAjax()
       {

              // delete socialmedia links
              if (\Input::get('act') == 'delSocialMediaLink' && \Input::get('type'))
              {
                     if (FE_USER_LOGGED_IN)
                     {
                            $arrSocialmediaLinks = deserialize($this->loggedInUser->socialmediaLinks);
                            unset($arrSocialmediaLinks[\Input::get('type')]);
                            $this->loggedInUser->socialmediaLinks = serialize($arrSocialmediaLinks);
                            $this->loggedInUser->save();
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
                                          $strReturn = $isPublished == 0 ? 'invisible' : 'visible';
                                          echo $strReturn;
                                   }
                            }
                     }
              }
              exit;
       }


       /**
        * generate socialmedia-links textfield
        */
       protected function generateSocialMediaLinksForm()
       {

              $this->Template->socialMediaFormId = 'tl_member_' . $this->id;

              $arrData = & $GLOBALS['TL_DCA']['tl_member']['fields']['socialmediaLinks'];
              $field = 'socialmediaLinks';
              $strClass = $GLOBALS['TL_FFL'][$arrData['inputType']];
              $arrData['eval']['tableless'] = 'true';
              $arrData['label'] = 'Socialmedia Links hinzufügen';
              $varValue = 'http://';

              $objWidget = new $strClass($strClass::getAttributesFromDca($arrData, $field, $varValue, '', '', $this));
              $objWidget->storeValues = true;

              if (FE_USER_LOGGED_IN && \Input::post('FORM_SUBMIT') == 'tl_member_' . $this->id)
              {
                     $objMember = \MemberModel::findByPk($this->loggedInUser->id);
                     if ($objMember !== null)
                     {
                            $arrSocialMediaLinks = deserialize($objMember->socialmediaLinks);
                            $this->Template->loggedInUser->socialmediaLinks = $arrSocialMediaLinks;
                            $objWidget->validate();
                            if (!$objWidget->hasErrors() && \Input::post('socialmediaLinks') != '')
                            {
                                   $value = \Input::post('socialmediaLinks');
                                   if (stripos($value, 'facebook') !== false)
                                   {
                                          $case = 'facebook';
                                   }
                                   elseif (stripos($value, 'linkedin') !== false)
                                   {
                                          $case = 'linkedin';
                                   }
                                   elseif (stripos($value, 'twitter') !== false)
                                   {
                                          $case = 'twitter';
                                   }
                                   elseif (stripos($value, 'plus.google') !== false)
                                   {
                                          $case = 'googleplus';
                                   }
                                   elseif (stripos($value, 'instagram') !== false)
                                   {
                                          $case = 'instagram';
                                   }
                                   else
                                   {
                                          $case = null;
                                          $objWidget->addError('Bitte geben Sie einen gültigen Pfad zu einer social media Plattform an.');
                                          $objWidget->value = 'http://';
                                   }
                                   if ($case !== null)
                                   {
                                          $arrSocialMediaLinks[$case] = $value;
                                          $objMember->socialmediaLinks = serialize($arrSocialMediaLinks);
                                          $objMember->save();
                                          $this->reload();
                                   }
                            }
                     }
              }

              $this->Template->socialMediaTextField = $objWidget->parse();

              // shit storm protection
              if ($this->blockingTime > 0)
              {
                     $objRatings = $this->Database->prepare("SELECT * FROM tl_comments WHERE source = ? AND parent = ? AND owner = ? AND dateOfCreation > ? ORDER BY dateOfCreation DESC")->limit(1)->execute('tl_member', $this->ratedUser->id, $this->loggedInUser->id, time() - $this->blockingTime);
                     if ($objRatings->numRows > 0)
                     {
                            $this->Template->commentFormLocked = true;
                            $time = ($this->blockingTime) - (time() - $objRatings->dateOfCreation);
                            $h = floor($time / 3600);
                            $min = floor(($time / 3600 - $h) * 60);
                            if ($time <= 60)
                            {
                                   $this->Template->commentFormLockedTime = $time . ' s';
                            }
                            else
                            {
                                   $this->Template->commentFormLockedTime = ($h > 0 ? $h . ' h  ' : '') . $min . ' min';
                            }
                     }
              }
       }
}