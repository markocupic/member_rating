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
	 * @param object $objModule
	 * @param string $strColumn
	 */
	public function __construct($objModule, $strColumn = 'main')
	{
		define('MOD_MEMBER_RATING', 'true');
		require_once TL_ROOT . '/system/modules/member_rating/helper/functions.php';

		// set the item from the auto_item parameter
		if($GLOBALS['TL_CONFIG']['useAutoItem'] && isset($_GET['auto_item']))
		{
			\Input::setGet('member', \Input::get('auto_item'));
		}

		// set the loggedInUser var
		if(FE_USER_LOGGED_IN)
		{
			$this->User = \FrontendUser::getInstance();
			$this->loggedInUser = $this->User;
		}

		// overwrite imageDir if a custom directory was selected
		$this->setImageDir();

		return parent::__construct($objModule, $strColumn);
	}


	/**
	 * @return string|void
	 */
	public function generate()
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

		return parent::generate();
	}


	/**
	 *
	 */
	public function addTemplateVars()
	{
		// MSC
		$this->Template->loggedInUser = $this->loggedInUser;

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
		$strLang .= "imgDir: '" . $this->getImageDir() . "',";
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


	}


	/**
	 * activateOrDelete
	 */
	protected function activateOrDelete()
	{
		$dbChange = false;
		$objComments = \CommentsModel::findByActivation_token(\Input::get('activation_token'));
		if($objComments === NULL)
		{
			die(utf8_decode($GLOBALS['TL_LANG']['MOD']['member_rating']['invalidToken']));
		}

		// delete or publish comment via url, received from a notification email
		if(\Input::get('del') == 'true')
		{
			$dbChange = true;
			$objComments->delete();
			$msg = $GLOBALS['TL_LANG']['MOD']['member_rating']['itemHasBeenActivated'];
			if(($objNextPage = \PageModel::findPublishedById($this->emailNotifyPage_DeleteComment)) !== NULL)
			{
				$this->redirect($this->generateFrontendUrl($objNextPage->row()));
			}
		}
		elseif(\Input::get('publish') == 'true')
		{
			$dbChange = true;
			$objComments->published = 1;
			$objComments->activation_token = '';
			$objComments->save();
			$msg = $GLOBALS['TL_LANG']['MOD']['member_rating']['itemHasBeenActivated'];
			if(($objNextPage = \PageModel::findPublishedById($this->emailNotifyPage_ActivateComment)) !== NULL)
			{
				$this->redirect($this->generateFrontendUrl($objNextPage->row()));
			}
		}
		else
		{
			//
		}

		if($dbChange === true)
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
	 * get score of a member
	 *
	 * @param $id
	 * @return string
	 */
	public static function getScore($id)
	{
		$objPoints = \Database::getInstance()
							  ->prepare("SELECT SUM(score) as sumscore FROM tl_comments WHERE source = ? AND parent = ? AND owner > ?")
							  ->execute('tl_member', $id, 0);
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
		if(trim($GLOBALS['TL_CONFIG']['socialmediaLinks']) != '')
		{
			foreach(explode('***', trim($GLOBALS['TL_CONFIG']['socialmediaLinks'])) as $item)
			{
				$arrSMBrand = explode('|', $item);
				if(is_array($arrSMBrand))
				{
					if(count($arrSMBrand) == 2)
					{
						$arrNeedle[] = $arrSMBrand[0];
						$arrIcons[] = $arrSMBrand[1];
					}
				}
			}
		}

		foreach($arrNeedle as $key => $needle)
		{
			if(strpos($strLink, $needle) !== false)
			{
				$icon = self::getImageDir() . '/socialmedia/' . $arrIcons[$key];
				if(is_file(TL_ROOT . '/' . $icon))
				{
					return $icon;
				}
			}
		}
		$icon = self::getImageDir() . '/socialmedia/default.png';
		if(is_file(TL_ROOT . '/' . $icon))
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
		if($src != '')
		{
			$objFile = new \File($src, true);
			if($objFile !== NULL)
			{
				if($objFile->isGdImage)
				{

					$src = TL_FILES_URL . $src;
					$size = sprintf('width="%s" height="%s"', $objFile->width, $objFile->height);
					$alt = 'socialmediaIcon';
					$title = specialchars($strHref);
					return sprintf('<img src="%s" %s alt="%s" title="%s" class="socialmediaIcon">', $src, $size, $alt,
						$title);
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
		if($score == '0')
		{
			$score = 0;
		}
		$arrReturn = array();
		$arrayGrades = array();
		if(trim($GLOBALS['TL_CONFIG']['gradeLabeling']) != '')
		{
			foreach(explode('***', trim($GLOBALS['TL_CONFIG']['gradeLabeling'])) as $strLine)
			{
				$arrLine = explode('|', $strLine);
				if(count($arrLine) != 3)
				{
					continue;
				}
				if(!intval($arrLine[0]) && $arrLine[0] != '0')
				{
					continue;
				}
				$arrayGrades[$arrLine[0]] = array(
					'score' => $arrLine[0],
					'label' => $arrLine[1],
					'icon'  => $arrLine[2]
				);
			}
		}
		krsort($arrayGrades);
		$arrayGrades = count($arrayGrades) ? $arrayGrades : false;
		if($arrayGrades)
		{
			foreach($arrayGrades as $arrGrade)
			{
				if($score >= $arrGrade['score'])
				{
					$arrReturn['label'] = $arrGrade['label'];
					$src = self::getImageDir() . '/levelicons/' . $arrGrade['icon'];
					if(is_file(TL_ROOT . '/' . $src))
					{
						$objFile = new \File($src, true);
						if($objFile !== NULL)
						{
							if($objFile->isGdImage)
							{
								$size = sprintf('width="%s" height="%s"', $objFile->width, $objFile->height);
								$arrReturn['icon'] = sprintf('<img src="%s" %s alt="%s" title="%s" class="%s">',
									TL_FILES_URL . $src, $size, 'grade icon', specialchars($arrGrade['label']),
									'gradeIcon');
							}
						}
					}
					break;
				}
			}
		}
		return $arrReturn[$key] ? $arrReturn[$key] : NULL;
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
		if($objMember !== NULL)
		{
			$size = sprintf('width="%s" height="%s"', $arrSize[0], $arrSize[1]);
			$avatar = array(
				'alt'   => specialchars($alt),
				'title' => specialchars($title),
				'size'  => $size,
				'class' => strlen($class) ? ' class="' . $class . '"' : '',
			);

			$src = NULL;
			$objFile = \FilesModel::findByUuid($objMember->avatar);
			if($objFile !== NULL)
			{
				if(is_file(TL_ROOT . '/' . $objFile->path))
				{
					$src = $objFile->path;
				}
			}
			else
			{
				$path = $objMember->gender == 'female' ? $objModule->imageDir . '/avatars/female.png' : $objModule->imageDir . '/avatars/male.png';
				if(is_file(TL_ROOT . '/' . $path))
				{
					$src = $path;
				}
			}

			// return default avatar
			if(!$src)
			{
				$src = $objMember->gender == 'female' ? 'system/modules/member_rating/assets/images/avatars/female.png' : 'system/modules/member_rating/assets/images/avatars/male.png';
			}

			// return image markup
			$avatar['src'] = TL_FILES_URL . \Image::get($src, $arrSize[0], $arrSize[1], $arrSize[2]);
			if(strlen($avatar['src']))
			{
				return sprintf('<img src="%s" %s alt="%s" title="%s"%s>', $avatar['src'], $avatar['size'],
					$avatar['alt'], $avatar['title'], $avatar['class']);
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
		if($objMember !== NULL)
		{
			if(!empty($objMember->socialmediaLinks))
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
	public static function findMemberByPk($id = NULL, $type = 'fullname')
	{
		$username = '';
		$objMember = \MemberModel::findByPk($id);
		if($objMember !== NULL)
		{
			if($type == 'firstname')
			{
				$username = $objMember->firstname;
			}
			elseif($type == 'lastname')
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
		if(!defined('MEMBER_RATING_IMAGE_DIR'))
		{
			if(!empty($GLOBALS['TL_CONFIG']['customImageDir']))
			{
				$objFile = \FilesModel::findByUuid(trim($GLOBALS['TL_CONFIG']['customImageDir']));
				if($objFile !== NULL)
				{
					if(is_dir(TL_ROOT . '/' . $objFile->path))
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
			if(defined('MEMBER_RATING_IMAGE_DIR'))
			{
				$this->imageDir = MEMBER_RATING_IMAGE_DIR;
			}
		}
	}

}
