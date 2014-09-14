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

class RatedMemberList extends \Module
{

       /**
        * Template
        * @var string
        */
       protected $strTemplate = 'mod_rated_member_list';

       /**
        * @var string
        */
       public $imageDir = 'system/modules/member_rating/assets/images';



       /**
        * @return string
        */
       public function generate()
       {

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
              // FE
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

              global $objPage;
              // get href for the detail-page
              $objDetailPage = \PageModel::findWithDetails($this->detailPage);
              if ($objDetailPage === null)
              {
                     $href = null;
              }
              else
              {
                     $href = $this->generateFrontendUrl($objDetailPage->row(), ((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/%s' : '/member/%s'), $objDetailPage->language);
              }

              $objMember = $this->Database->prepare('SELECT * FROM tl_member WHERE disable = ? ORDER BY firstname ASC, lastname ASC')->execute(0);
              $arrRows = array();
              while ($row = $objMember->fetchAssoc())
              {
                     $row['score'] = MemberRatingHelper::getScore($row['id']);
                     $row['gradeLabel'] = MemberRatingHelper::getGrade($row['id'], 'label');
                     $row['gradeIcon'] = MemberRatingHelper::getGrade($row['id'], 'label');


                     $row['hrefDetailPage'] = $href ? sprintf($href, $row['id']) : false;
                     $objFile = \FilesModel::findByUuid($objMember->avatar);
                     if ($objFile !== null)
                     {
                            if (is_file(TL_ROOT . '/' . $objFile->path))
                            {
                                   $row['avatar'] = TL_FILES_URL . \Image::get($objFile->path, 50, 50, 'center_center');
                            }
                     }
                     else
                     {
                            $path = $objMember->gender == 'female' ? $this->imageDir . '/female.png' : $this->imageDir . '/male.png';
                            if (is_file(TL_ROOT . '/' . $path))
                            {
                                   $row['avatar'] = TL_FILES_URL . \Image::get($path, 50, 50, 'center_center');
                            }
                     }
                     $arrRows[] = $row;
              }

              $this->Template->rows = count($arrRows) ? $arrRows : false;
       }

}