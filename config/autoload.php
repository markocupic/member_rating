<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Member_rating
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'MCupic',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Modules
	'MCupic\MemberRating\MemberRatingList'   => 'system/modules/member_rating/modules/MemberRatingList.php',
	'MCupic\MemberRating\MemberRatingDetail' => 'system/modules/member_rating/modules/MemberRatingDetail.php',
	'MCupic\MemberRating\MemberRating'       => 'system/modules/member_rating/modules/MemberRating.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_member_rating_list'           => 'system/modules/member_rating/templates',
	'member_rating_email_notification' => 'system/modules/member_rating/templates',
	'mod_member_rating_detail'         => 'system/modules/member_rating/templates',
));
