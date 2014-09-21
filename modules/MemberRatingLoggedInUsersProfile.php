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

class MemberRatingLoggedInUsersProfile extends MemberRating
{

       /**
        * Template
        *
        * @var string
        */
       protected $strTemplate = 'mod_member_rating_logged_in_users_profile';

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

              if (!FE_USER_LOGGED_IN)
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
              if (\Input::get('isAjaxRequest') && \Input::get('act') == 'delSocialmediaLink')
              {
                     $this->handleAjax();
                     exit();
              }

              // add miscellaneous vars to the template
              $this->addTemplateVars();

              if (FE_USER_LOGGED_IN)
              {
                     // ***** LOGGED USER PROFILE *****
                     // get avatar of logged in user
                     $arrSize = deserialize($this->avatarSizeProfile);
                     $title = $this->loggedInUser->firstname . ' ' . $this->loggedInUser->lastname;
                     $this->loggedInUser->avatar = $this->getAvatar($this->loggedInUser->id, $arrSize, 'avatar', $title, 'avatar_large', $this);

                     // socialmedia links
                     $this->loggedInUser->socialmediaLinks = $this->getSocialmediaLinks($this->loggedInUser->id);
                     $this->loggedInUser->deleteSocialmediaLinkIcon = TL_FILES_URL . $this->getImageDir() . '/cancel_circle.png';

                     // get score and grade of logged user
                     $this->loggedInUser->score = $this->getScore($this->loggedInUser->id);
                     $this->loggedInUser->gradeLabel = $this->getGrade($this->loggedInUser->id, 'label');
                     $this->loggedInUser->gradeIcon = $this->getGrade($this->loggedInUser->id, 'icon');

                     // add data to template
                     $keys = array(
                            'firstname', 'lastname', 'avatar', 'socialmediaLinks', 'deleteSocialmediaLinkIcon', 'score',
                            'gradeLabel', 'gradeIcon',
                     );
                     foreach ($keys as $key)
                     {
                            $this->Template->$key = $this->loggedInUser->$key;
                     }
              }

              // generate forms
              if (FE_USER_LOGGED_IN)
              {
                     $this->generateSocialMediaLinksForm();
              }

       }

       /**
        * generate socialmedia-links textfield
        */
       protected function generateSocialMediaLinksForm()
       {

              $this->Template->socialMediaFormId = 'tl_member_' . $this->id;

              $arrData = &$GLOBALS['TL_DCA']['tl_member']['fields']['socialmediaLinks'];
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
                            if (!$objWidget->hasErrors() && trim(\Input::post('socialmediaLinks')) != '')
                            {
                                   $value = \Input::post('socialmediaLinks');
                                   // validate Link (check for a valid socialmedia Server)
                                   $doNotSubmit = true;
                                   if (trim($GLOBALS['TL_CONFIG']['socialmediaLinks']) != '')
                                   {
                                          foreach (explode('***', trim($GLOBALS['TL_CONFIG']['socialmediaLinks'])) as $item)
                                          {
                                                 $arrSMBrand = explode('|', $item);
                                                 if (is_array($arrSMBrand))
                                                 {
                                                        if (count($arrSMBrand) == 2)
                                                        {
                                                               $strServer = $arrSMBrand[0];
                                                               if (strpos($value, $strServer) !== false)
                                                               {
                                                                      $doNotSubmit = false;
                                                               }
                                                        }
                                                 }
                                          }
                                   }
                                   if ($doNotSubmit)
                                   {
                                          $objWidget->hasErrors();
                                          $objWidget->addError($GLOBALS['TL_LANG']['MOD']['member_rating']['invalidSocialmediaLink']);
                                   }
                                   else
                                   {
                                          $arrSocialMediaLinks[] = $value;
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