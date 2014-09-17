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
        * image directory
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

              // Frontend
              // overwrite imageDir if a custom directory was selected
              MemberRatingHelper::setImageDir($this->imageDir);
              $this->imageDir = MemberRatingHelper::getImageDir();

              return parent::generate();
       }


       /**
        * Generate the module
        */
       protected function compile()
       {
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

              $objMember = $this->Database->prepare('SELECT * FROM tl_member WHERE disable = ?')->execute(0);
              $arrRows = array();
              while ($row = $objMember->fetchAssoc())
              {
                     // score and grade
                     $row['score'] = MemberRatingHelper::getScore($row['id']);
                     $row['gradeLabel'] = MemberRatingHelper::getGrade($row['id'], 'label');
                     $row['gradeIcon'] = MemberRatingHelper::getGrade($row['id'], 'label');

                     // link to detail page
                     $row['hrefDetailPage'] = $href ? sprintf($href, $row['id']) : false;

                     // get avatar of member
                     $arrSize = deserialize($this->avatarSizeListing);
                     $title = $row['firstname'] . ' ' . $row['lastname'];
                     $row['avatar'] = MemberRatingHelper::getAvatar($objMember->id, $arrSize, 'avatar', $title, 'avatar_thumb', $this);

                     $arrRows[] = $row;
              }

              // Sorting
              $arrSorting = array();
              if (!empty($this->sortingField1) && !empty($this->sortingDirection1))
              {
                     $arrSorting[$this->sortingField1] = constant($this->sortingDirection1);
              }
              if (!empty($this->sortingField2) && !empty($this->sortingDirection2))
              {
                     $arrSorting[$this->sortingField2] = constant($this->sortingDirection2);
              }
              if (!empty($this->sortingField3) && !empty($this->sortingDirection3))
              {
                     $arrSorting[$this->sortingField3] = constant($this->sortingDirection3);
              }

              $arrRows = MemberRatingHelper::sortArrayByFields($arrRows, $arrSorting);
              $this->Template->rows = count($arrRows) ? $arrRows : false;
       }
}