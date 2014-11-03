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

class MemberRatingDetail extends MemberRating
{

       /**
        * Template
        *
        * @var string
        */
       protected $strTemplate = 'mod_member_rating_detail';

       /**
        * @return string
        */
       public function generate()
       {

              // Backend
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

              // Set the item from the auto_item parameter
              if ($GLOBALS['TL_CONFIG']['useAutoItem'] && isset($_GET['auto_item']))
              {
                     \Input::setGet('member', \Input::get('auto_item'));
              }

              // activate comment by token via url
              if (strlen(\Input::get('activation_token')))
              {
                     $this->activateOrDelete();
                     exit;
              }

              // set the ratedUser var
              $this->ratedUser = \MemberModel::findByPk(\Input::get('member'));
              if ($this->ratedUser === null)
              {
                     return '';
              }

              // overwrite default template
              if ($this->memberRatingDetailTemplate != '')
              {
                     $this->strTemplate = $this->memberRatingDetailTemplate;
              }


              return parent::generate();
       }

       /**
        * Generate the module
        */
       protected function compile()
       {

              // handle Ajax requests
              if (\Input::get('isAjaxRequest') && \Input::get('act') == 'toggleVisibility')
              {
                     $this->handleAjax();
                     exit();
              }

              // add miscellaneous vars to the template
              $this->addTemplateVars();

              // ***** RATED USER PROFILE *****
              foreach($this->ratedUser->row() as $k => $v)
              {
                     if($k == 'password'){
                            continue;
                     }
                     $this->Template->ratedUser->$k = $v;
              }

              // get avatar of rated user
              $arrSize = deserialize($this->avatarSizeProfile);
              $title = $this->ratedUser->firstname . ' ' . $this->ratedUser->lastname;
              $this->ratedUser->avatar = $this->getAvatar($this->ratedUser->id, $arrSize, 'avatar', $title, 'avatar_large', $this); // get socialmedia links

              // socialmedia links
              $this->ratedUser->socialmediaLinks = $this->getSocialmediaLinks($this->ratedUser->id);
              $this->ratedUser->deleteSocialmediaLinkIcon = TL_FILES_URL . $this->getImageDir() . '/cancel_circle.png';

              // get score and grade of rated user
              $this->ratedUser->score = $this->getScore($this->ratedUser->id);
		      $this->ratedUser->averageRating = $this->getAverageRating($this->ratedUser->id);
		      $this->ratedUser->ratingEnities = $this->getRatingEnities($this->ratedUser->id);

		      $this->ratedUser->gradeLabel = $this->getGrade($this->ratedUser->id, 'label');
              $this->ratedUser->gradeIcon = $this->getGrade($this->ratedUser->id, 'icon');

              // add data to template
              $keys = array(
                     'firstname', 'lastname', 'avatar', 'socialmediaLinks', 'deleteSocialmediaLinkIcon', 'score',
                     'gradeLabel', 'gradeIcon',
              );
              foreach ($keys as $key)
              {
                     $this->Template->$key = $this->ratedUser->$key;
              }

              if ($this->showTop3)
              {
                     $this->Template->showTop3 = true;
                     // ***** TOP 3 SECTION *****
                     $objRatings = $this->Database->prepare("SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? AND published = ? AND owner > 0 ORDER BY score DESC, dateOfCreation DESC")->limit(3)->execute('tl_member', $this->ratedUser->id, 1);
                     $arrTop3 = array();
                     while ($row = $objRatings->fetchAssoc())
                     {
                            $objMember = \MemberModel::findByPk($row['owner']);
                            $row['time'] = \Date::parse(\Config::get('datimFormat'), $row['dateOfCreation']);
                            if ($objMember !== null)
                            {
                                   foreach(\MemberModel::findByPk($objMember->id)->row() as $k => $v)
                                   {
                                          if($k == 'id' || $k == 'tstamp' || $k == 'password'){
                                                 continue;
                                          }
                                          $row[$k] = $v;
                                   }

                                   // avatar
                                   $arrSize = deserialize($this->avatarSizeListing);
                                   $title = $objMember->firstname . ' ' . $objMember->lastname;
                                   $row['avatar'] = $this->getAvatar($objMember->id, $arrSize, 'avatar', $title, 'avatar_thumb', $this);
                            }
                            $arrTop3[] = $row;
                     }
                     $this->Template->top3 = count($arrTop3) > 2 ? $arrTop3 : false;
              }

              // ***** ALL RATINGS SECTION *****
              if ($this->ratedUser->id == $this->loggedInUser->id)
              {
                     $strSql = "SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? AND owner > 0 ORDER BY dateOfCreation DESC, score DESC";
              }
              else
              {
                     $strSql = "SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? AND published = '1' AND owner > 0 ORDER BY dateOfCreation DESC, score DESC";
              }
              $objRatings = $this->Database->prepare($strSql)->execute('tl_member', $this->ratedUser->id);
              $arrAllRatings = array();
              while ($row = $objRatings->fetchAssoc())
              {
                     $objMember = \MemberModel::findByPk($row['owner']);
                     $row['time'] = \Date::parse(\Config::get('datimFormat'), $row['dateOfCreation']);
                     if ($objMember !== null)
                     {
                            foreach(\MemberModel::findByPk($objMember->id)->row() as $k => $v)
                            {
                                   if($k == 'id' || $k == 'tstamp' || $k == 'password'){
                                          continue;
                                   }
                                   $row[$k] = $v;
                            }

                            // avatar
                            $arrSize = deserialize($this->avatarSizeListing);
                            $title = $objMember->firstname . ' ' . $objMember->lastname;
                            $row['avatar'] = $this->getAvatar($objMember->id, $arrSize, 'avatar', $title, 'avatar_thumb', $this);
                            // toggle visibility icon
                            $visibility = $row['published'] ? 'visible.png' : 'invisible.png';
                            $row['visibility_icon_src'] = TL_FILES_URL . sprintf($this->getImageDir() . '/%s', $visibility);
                     }
                     $arrAllRatings[] = $row;
              }

              // Pagination
              $total = count($arrAllRatings);
              $limit = $total;
              $offset = 0;
              if ($this->perPage > 0)
              {
                     $id = 'page_e' . $this->id;
                     $page = \Input::get($id) ?: 1;

                     // Do not index or cache the page if the page number is outside the range
                     if ($page < 1 || $page > max(ceil($total / $this->perPage), 1))
                     {
                            global $objPage;
                            $objPage->noSearch = 1;
                            $objPage->cache = 0;

                            // Send a 404 header
                            header('HTTP/1.1 404 Not Found');
                            return;
                     }

                     $offset = ($page - 1) * $this->perPage;
                     $limit = min($this->perPage + $offset, $total);

                     $objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
                     $this->Template->pagination = $objPagination->generate("\n  ");
              }

              $arrRatings = array();
              for ($i = $offset; $i < $limit; $i++)
              {
                     $arrRatings[] = $arrAllRatings[$i];
              }

              $this->Template->allRatings = count($arrRatings) ? $arrRatings : false;

              // generate forms
              if (FE_USER_LOGGED_IN)
              {
                     if ($this->loggedInUser->id != $this->ratedUser->id)
                     {
                            $this->generateVotingForm();
                     }
              }

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
                     'comment', 'score', 'captcha'
              );
              foreach ($arrFF as $field)
              {
                     $arrData = &$GLOBALS['TL_DCA']['tl_comments']['fields'][$field];
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
                     // add a hidden field for the starrating
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
                     $objComment->owner = $this->loggedInUser->id;
                     $objComment->dateOfCreation = time();
                     $objComment->source = 'tl_member';
                     $objComment->ip = \Environment::get('ip');
                     $objComment->activation_token = md5(session_id() . time() . $this->loggedInUser->id);
                     $objComment->parent = $this->ratedUser->id;
                     $objComment->published = 0;
                     $objComment->save();
                     $this->log('A new entry "tl_comments.id=' . $objComment->id . '" has been created', __METHOD__, TL_GENERAL);

                     // notify rated member
                     if ($this->notifyRatedUser && $objComment->id > 0 && $objComment->comment != '')
                     {
                            $this->notifyUser($objComment);
                     }
                     $this->jumpToOrReload($this->jumpTo);
              }
              if ($scoreError)
              {
                     $strFields = '<p class="error">Bitte eine g&uuml;ltige Punktzahl vergeben.</p>' . $strFields;
              }
              $this->Template->slabel = specialchars($GLOBALS['TL_LANG']['MSC']['saveData']);
              $this->Template->fields = $strFields;
              $this->Template->arrFields = $arrFields;

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

       /**
        * @param $objComment
        */
       public function notifyUser($objComment)
       {

              global $objPage;
              $objRatedMember = \MemberModel::findByPk($objComment->parent);
              if ($objRatedMember === null)
              {
                     return;
              }

              if ($objRatedMember->email == '')
              {
                     return;
              }

              $objAuthor = \MemberModel::findByPk($objComment->owner);
              if ($objAuthor === null)
              {
                     return;
              }

              // Generate the data array for simple token use
              $arrData = array();
              foreach ($objAuthor->row() as $k => $v)
              {
                     $arrData['author_' . $k] = $v;
              }
              foreach ($objRatedMember->row() as $k => $v)
              {
                     $arrData['recipient_' . $k] = $v;
              }
              foreach ($objComment->row() as $k => $v)
              {
                     $arrData['comments_' . $k] = $v;
              }

              $objTemplate = new \FrontendTemplate('member_rating_email_notification');
              $objTemplate->comment = nl2br($objComment->comment);
              $objTemplate->score = $objComment->score;
              $objTemplate->link = \Environment::get('url') . '/' . \Controller::generateFrontendUrl($objPage->row(), '', $objPage->language) . '?publish=true&activation_token=' . $objComment->activation_token;
              $objTemplate->link_del = \Environment::get('url') . '/' . \Controller::generateFrontendUrl($objPage->row(), '', $objPage->language) . '?del=true&activation_token=' . $objComment->activation_token;
              $strContent = $objTemplate->parse();

              // Mail
              $objEmail = new \Email();
              $strSubject = sprintf($GLOBALS['TL_LANG']['MOD']['member_rating']['emailNotify']['subject'], $_SERVER['SERVER_NAME']);
              $objEmail->subject = \String::parseSimpleTokens($strSubject, $arrData);
              $strContent = $this->replaceInsertTags($strContent);
              $strContent = \String::parseSimpleTokens($strContent, $arrData);
              $objEmail->html = $strContent;

              // Text version
              $strContent = \String::decodeEntities($strContent);
              $strContent = strip_tags($strContent);
              $strContent = str_replace(array(
                                               '[&]', '[lt]', '[gt]'
                                        ), array(
                                               '&', '<', '>'
                                        ), $strContent);
              $objEmail->text = $strContent;

              $objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
              $objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
              $objEmail->sendTo($objRatedMember->email);
       }

}