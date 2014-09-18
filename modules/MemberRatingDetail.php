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
		if(TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['member_rating'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// activate comment by token via url
		if(strlen(\Input::get('activation_token')))
		{
			$this->activateOrDelete();
			exit;
		}

		// set the ratedUser var
		$this->ratedUser = \MemberModel::findByPk(\Input::get('member'));
		if($this->ratedUser === NULL)
		{
			return '';
		}

		// overwrite default template
		if($this->memberRatingDetailTemplate != '')
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

		$this->loadDataContainer('tl_comments');
		$this->loadDataContainer('tl_member');
		$this->loadLanguageFile('tl_comments');
		$this->loadLanguageFile('tl_member');

		// handle Ajax requests
		if(\Input::get('isAjaxRequest'))
		{
			$this->handleAjax();
			exit();
		}

		if(FE_USER_LOGGED_IN)
		{

			// ***** LOGGED USER PROFILE *****
			// get avatar of logged in user
			$arrSize = deserialize($this->avatarSizeProfile);
			$title = $this->loggedInUser->firstname . ' ' . $this->loggedInUser->lastname;
			$this->loggedInUser->avatar = $this->getAvatar($this->loggedInUser->id, $arrSize, 'avatar', $title, 'avatar_large', $this);

			// socialmedia links
			$this->getSocialmediaLinks($this->loggedInUser->id);

			// get score and grade of logged user
			$this->loggedInUser->score = $this->getScore($this->loggedInUser->id);
			$this->loggedInUser->gradeLabel = $this->getGrade($this->loggedInUser->id, 'label');
			$this->loggedInUser->gradeIcon = $this->getGrade($this->loggedInUser->id, 'icon');
		}

		// ***** RATED USER PROFILE *****
		// get avatar of logged in user
		$arrSize = deserialize($this->avatarSizeProfile);
		$title = $this->ratedUser->firstname . ' ' . $this->ratedUser->lastname;
		$this->ratedUser->avatar = $this->getAvatar($this->ratedUser->id, $arrSize, 'avatar', $title, 'avatar_large', $this); // get socialmedia links

		// socialmedia links
		$this->ratedUser->socialmediaLinks = $this->getSocialmediaLinks($this->ratedUser->id);
		$this->Template->deleteSocialmediaLinkIcon = TL_FILES_URL . $this->getImageDir() . '/cancel_circle.png';

		// get score and grade of logged user
		$this->ratedUser->score = $this->getScore($this->ratedUser->id);
		$this->ratedUser->gradeLabel = $this->getGrade($this->ratedUser->id, 'label');
		$this->ratedUser->gradeIcon = $this->getGrade($this->ratedUser->id, 'icon');


		// ***** TOP 3 SECTION *****
		$objRatings = $this->Database->prepare("SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? AND published = ? ORDER BY score DESC, dateOfCreation DESC")
									 ->limit(3)->execute('tl_member', $this->ratedUser->id, 1);
		$arrTop3 = array();
		while($row = $objRatings->fetchAssoc())
		{
			$objMember = \MemberModel::findByPk($row['owner']);
			$row['time'] = \Date::parse(\Config::get('datimFormat'), $row['dateOfCreation']);
			if($objMember !== NULL)
			{
				$row['firstname'] = $objMember->firstname;
				$row['lastname'] = $objMember->lastname;
				// avatar
				$arrSize = deserialize($this->avatarSizeListing);
				$title = $objMember->firstname . ' ' . $objMember->lastname;
				$row['avatar'] = $this->getAvatar($objMember->id, $arrSize, 'avatar', $title, 'avatar_thumb', $this);
			}
			$arrTop3[] = $row;
		}
		$this->ratedUser->top3 = count($arrTop3) > 2 ? $arrTop3 : false;


		// ***** ALL RATINGS SECTION *****
		if($this->ratedUser->id == $this->loggedInUser->id)
		{
			$strSql = "SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? ORDER BY dateOfCreation DESC, score DESC";
		}
		else
		{
			$strSql = "SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? AND published = '1' ORDER BY dateOfCreation DESC, score DESC";
		}
		$objRatings = $this->Database->prepare($strSql)->execute('tl_member', $this->ratedUser->id);
		$arrAllRatings = array();
		while($row = $objRatings->fetchAssoc())
		{
			$objMember = \MemberModel::findByPk($row['owner']);
			$row['time'] = \Date::parse(\Config::get('datimFormat'), $row['dateOfCreation']);
			if($objMember !== NULL)
			{
				$row['firstname'] = $objMember->firstname;
				$row['lastname'] = $objMember->lastname;
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
		$this->ratedUser->allRatings = count($arrAllRatings) ? $arrAllRatings : false;


		// MSC
		$this->Template->loggedInUser = $this->loggedInUser;
		$this->Template->ratedUser = $this->ratedUser;


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
		foreach($GLOBALS['TL_LANG']['MOD']['member_rating'] as $k => $v)
		{
			if(is_array($v))
			{
				$strLang .= $k . ": {";
				foreach($v as $kk => $vv)
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
		$this->Template->JsVarsObject = "ModuleVars = {REQUEST_TOKEN: '" . REQUEST_TOKEN . "'};" . "\r\n";

		// generate forms
		if(FE_USER_LOGGED_IN)
		{
			$this->generateSocialMediaLinksForm();
			if($this->loggedInUser->id != $this->ratedUser->id)
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
		if(!$this->loggedInUser || $this->loggedInUser->id == $this->ratedUser->id)
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
		foreach($arrFF as $field)
		{
			$arrData = &$GLOBALS['TL_DCA']['tl_comments']['fields'][$field];
			$strClass = $GLOBALS['TL_FFL'][$arrData['inputType']];
			$arrData['eval']['tableless'] = 'true';
			$arrData['label'] = $GLOBALS['TL_LANG']['tl_comments'][$field][0];
			$varValue = '';

			$objWidget = new $strClass($strClass::getAttributesFromDca($arrData, $field, $varValue, '', '', $this));
			$objWidget->storeValues = true;


			// Validate the form data
			if(\Input::post('FORM_SUBMIT') == 'tl_comments_' . $this->id)
			{
				$objWidget->validate();
				$varValue = $objWidget->value;

				// check vor valid score interval
				if($field == 'score')
				{
					if(!mberegi('^(1|2|3|4|5)\d{0}$', $varValue))
					{
						$doNotSubmit = true;
						$scoreError = true;
					}
				}

				// Do not submit the field if there are errors
				if($objWidget->hasErrors())
				{
					$doNotSubmit = true;
				}
				elseif($objWidget->submitInput())
				{
					$blnModified = true;
					// Store the form data
					$_SESSION['FORM_DATA'][$field] = $varValue;

					// Set the correct empty value (see #6284, #6373)
					if($varValue === '')
					{
						$varValue = $objWidget->getEmptyValue();
					}

					// Set the new value
					if($field !== 'captcha')
					{
						$objComment->$field = $varValue;
					}
				}
			}

			$temp = $objWidget->parse();
			// add a hidden field for the starrating
			if($field == 'score')
			{
				$temp = '<input type="hidden" name="score" id="ctrl_score" value="">';
			}

			$strFields .= $temp;
			$arrFields[$field] = $temp;
		}

		// Save the model
		if($doNotSubmit !== true && $blnModified && \Input::post('FORM_SUBMIT') == 'tl_comments_' . $this->id)
		{
			$objComment->owner = $this->loggedInUser->id;
			$objComment->dateOfCreation = time();
			$objComment->source = 'tl_member';
			$objComment->ip = \Environment::get('ip');
			$objComment->activation_token = md5(session_id() . time() . $this->loggedInUser->id);
			$objComment->parent = $this->ratedUser->id;
			$objComment->published = 0;

			$objComment->save();

			// notify rated member
			if($this->notifyRatedUser && $objComment->id > 0 && $objComment->comment != '')
			{
				$this->notifyUser($objComment);
			}
			$this->jumpToOrReload($this->jumpTo);
		}
		if($scoreError)
		{
			$strFields = '<p class="error">Bitte eine gültige Punktzahl vergeben.</p>' . $strFields;
		}
		$this->Template->slabel = specialchars($GLOBALS['TL_LANG']['MSC']['saveData']);
		$this->Template->fields = $strFields;
		$this->Template->arrFields = $arrFields;
	}


	/**
	 * handle ajax requests
	 */
	protected function handleAjax()
	{
		// delete socialmedia links
		if(\Input::get('act') == 'delSocialMediaLink' && \Input::post('type'))
		{
			if(FE_USER_LOGGED_IN)
			{
				$arrSocialmediaLinks = deserialize($this->loggedInUser->socialmediaLinks);
				if(array_search(\Input::post('type'), $arrSocialmediaLinks) !== false)
				{
					$key = array_search(\Input::post('type'), $arrSocialmediaLinks);
					unset($arrSocialmediaLinks[$key]);
				}
				$this->loggedInUser->socialmediaLinks = serialize(array_values($arrSocialmediaLinks));
				$this->loggedInUser->save();
			}
		}

		// toggle visibility (publish or unpublish)
		if(\Input::get('act') == 'toggleVisibility' && \Input::get('id'))
		{
			if(FE_USER_LOGGED_IN)
			{
				$objComment = \CommentsModel::findByPk(\Input::get('id'));
				if($objComment !== NULL)
				{
					if($this->loggedInUser->id == $objComment->parent)
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

		$arrData = &$GLOBALS['TL_DCA']['tl_member']['fields']['socialmediaLinks'];
		$field = 'socialmediaLinks';
		$strClass = $GLOBALS['TL_FFL'][$arrData['inputType']];
		$arrData['eval']['tableless'] = 'true';
		$arrData['label'] = 'Socialmedia Links hinzufügen';
		$varValue = 'http://';
		$objWidget = new $strClass($strClass::getAttributesFromDca($arrData, $field, $varValue, '', '', $this));
		$objWidget->storeValues = true;
		if(FE_USER_LOGGED_IN && \Input::post('FORM_SUBMIT') == 'tl_member_' . $this->id)
		{
			$objMember = \MemberModel::findByPk($this->loggedInUser->id);
			if($objMember !== NULL)
			{
				$arrSocialMediaLinks = deserialize($objMember->socialmediaLinks);
				$this->Template->loggedInUser->socialmediaLinks = $arrSocialMediaLinks;
				$objWidget->validate();
				if(!$objWidget->hasErrors() && trim(\Input::post('socialmediaLinks')) != '')
				{
					$value = \Input::post('socialmediaLinks');
					$arrSocialMediaLinks[] = $value;
					$objMember->socialmediaLinks = serialize($arrSocialMediaLinks);
					$objMember->save();
					$this->reload();
				}
			}
		}
		$this->Template->socialMediaTextField = $objWidget->parse();
		// shit storm protection
		if($this->blockingTime > 0)
		{
			$objRatings = $this->Database->prepare("SELECT * FROM tl_comments WHERE source = ? AND parent = ? AND owner = ? AND dateOfCreation > ? ORDER BY dateOfCreation DESC")
										 ->limit(1)
										 ->execute('tl_member', $this->ratedUser->id, $this->loggedInUser->id, time() - $this->blockingTime);
			if($objRatings->numRows > 0)
			{
				$this->Template->commentFormLocked = true;
				$time = ($this->blockingTime) - (time() - $objRatings->dateOfCreation);
				$h = floor($time / 3600);
				$min = floor(($time / 3600 - $h) * 60);
				if($time <= 60)
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
		if($objRatedMember === NULL)
		{
			return;
		}

		if($objRatedMember->email == '')
		{
			return;
		}

		$objAuthor = \MemberModel::findByPk($objComment->owner);
		if($objAuthor === NULL)
		{
			return;
		}

		$objTemplate = new \FrontendTemplate('member_rating_email_notification');
		$objTemplate->name = $objRatedMember->firstname;
		$objTemplate->author = $objAuthor->firstname . ' ' . $objAuthor->lastname;
		$objTemplate->comment = nl2br($objComment->comment);
		$objTemplate->score = $objComment->score;
		$objTemplate->link = 'http://' . $_SERVER['SERVER_NAME'] . '/' . \Controller::generateFrontendUrl($objPage->row(), '', $objPage->language) . '?publish=true&activation_token=' . $objComment->activation_token;
		$objTemplate->link_del = 'http://' . $_SERVER['SERVER_NAME'] . '/' . \Controller::generateFrontendUrl($objPage->row(), '', $objPage->language) . '?del=true&activation_token=' . $objComment->activation_token;
		$strContent = $objTemplate->parse();

		// Mail
		$objEmail = new \Email();
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MOD']['member_rating']['emailNotify']['subject'], $objTemplate->author, $_SERVER['SERVER_NAME']);
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
		$objEmail->sendTo($objRatedMember->email);
	}


}