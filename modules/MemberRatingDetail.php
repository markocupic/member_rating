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
	 * @param object $objModule
	 * @param string $strColumn
	 */
	public function __construct($objModule, $strColumn = 'main')
	{
		return parent::__construct($objModule, $strColumn);
	}


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
		// add miscellaneous vars to the template
		$this->addTemplateVars();

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
		$objRatings = $this->Database->prepare("SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? AND published = ? AND owner > 0 ORDER BY score DESC, dateOfCreation DESC")
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
			$strSql = "SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? AND owner > 0 ORDER BY dateOfCreation DESC, score DESC";
		}
		else
		{
			$strSql = "SELECT * FROM tl_comments WHERE comment != '' AND source = ? AND parent = ? AND published = '1' AND owner > 0 ORDER BY dateOfCreation DESC, score DESC";
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
		$this->Template->ratedUser = $this->ratedUser;


		// generate forms
		if(FE_USER_LOGGED_IN)
		{
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