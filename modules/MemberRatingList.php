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

class MemberRatingList extends MemberRating
{

       /**
        * Template
        *
        * @var string
        */
       protected $strTemplate = 'mod_member_rating_list';


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

              // overwrite default template
              if ($this->memberRatingListTemplate != '')
              {
                     $this->strTemplate = $this->memberRatingListTemplate;
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

              // get href for the detail-page
              $objDetailPage = \PageModel::findWithDetails($this->detailPage);
              if ($objDetailPage === NULL)
              {
                     $href = NULL;
              }
              else
              {
                     $href = $this->generateFrontendUrl($objDetailPage->row(), ((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/%s' : '/member/%s'), $objDetailPage->language);
              }

              $objMember = $this->Database->prepare('SELECT * FROM tl_member WHERE disable = ?')->execute(0);
              $arrRows = array();
              while ($row = $objMember->fetchAssoc())
              {
                     foreach($row as $k => $v)
                     {
                            if($k == 'id' || $k == 'tstamp' || $k == 'password' || $k == 'avatar'){
                                   continue;
                            }
                            $row[$k] = $v;
                     }
                     // score and grade
                     $row['score'] = $this->getScore($row['id']);
				     $row['averageRating'] = $this->getAverageRating($row['id']);
				  	 $row['ratingEnities'] = $this->getRatingEnities($row['id']);

				  	 $row['gradeLabel'] = $this->getGrade($row['id'], 'label');
                     $row['gradeIcon'] = $this->getGrade($row['id'], 'label');

                     // link to detail page
                     $row['hrefDetailPage'] = $href ? sprintf($href, $row['id']) : false;

                     // get avatar of member
                     $arrSize = deserialize($this->avatarSizeListing);
                     $title = $row['firstname'] . ' ' . $row['lastname'];
                     $row['avatar'] = $this->getAvatar($objMember->id, $arrSize, 'avatar', $title, 'avatar_thumb', $this);

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

              $arrRows = sortArrayByFields($arrRows, $arrSorting);
              $this->Template->rows = count($arrRows) ? $arrRows : false;

       }
}