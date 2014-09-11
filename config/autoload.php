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
	// Classes
	'MCupic\MemberRating\MemberRatingHelper' => 'system/modules/member_rating/classes/MemberRatingHelper.php',

	// Modules
	'MCupic\MemberRating\RatedMemberList'    => 'system/modules/member_rating/modules/RatedMemberList.php',
	'MCupic\MemberRating\MemberRating'       => 'system/modules/member_rating/modules/MemberRating.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_member_rating'                        => 'system/modules/member_rating/templates',
	'mod_rated_member_list'                    => 'system/modules/member_rating/templates',
	'member_rating_email_notification' => 'system/modules/member_rating/templates',
));
