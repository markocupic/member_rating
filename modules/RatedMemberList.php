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

              // set imageDir if a custom directory was selected
              MemberRatingHelper::setImageDir($this->imageDir);
              $this->imageDir = MemberRatingHelper::getImageDir();

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
                     // get avatar of member
                     $arrSize = array(50, 50, 'center_center');
                     $title = $row['firstname'] . ' ' . $row['lastname'];
                     $row['avatar'] = MemberRatingHelper::getAvatar($objMember->id, $arrSize, 'avatar', $title, 'avatar_thumb', $this);
                     $arrRows[] = $row;
              }

              $this->Template->rows = count($arrRows) ? $arrRows : false;
       }

}