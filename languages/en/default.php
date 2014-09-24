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

$GLOBALS['TL_LANG']['MOD']['member_rating']['grade'] = 'Rang';
$GLOBALS['TL_LANG']['MOD']['member_rating']['remove_link'] = 'Link entfernen';
$GLOBALS['TL_LANG']['MOD']['member_rating']['all_ratings'] = 'Alle Bewertungen';
$GLOBALS['TL_LANG']['MOD']['member_rating']['no_rating_available'] = 'Zu diesem Mitglied liegen noch keine (ver&ouml;ffentlichten) Kommentare vor.';
$GLOBALS['TL_LANG']['MOD']['member_rating']['score'] = 'Punkte';
$GLOBALS['TL_LANG']['MOD']['member_rating']['current_score_of'] = 'Aktueller Punktestand von';
$GLOBALS['TL_LANG']['MOD']['member_rating']['reason_why'] = 'Grund';
$GLOBALS['TL_LANG']['MOD']['member_rating']['invalidToken'] = 'Ung&uuml;ltiges oder abgelaufenes Token.';
$GLOBALS['TL_LANG']['MOD']['member_rating']['itemHasBeenDeleted'] = 'Der Datensatz wurde erfolgreich gel&ouml;scht.';
$GLOBALS['TL_LANG']['MOD']['member_rating']['itemHasBeenActivated'] = 'Der Datensatz wurde erfolgreich aktiviert.';
$GLOBALS['TL_LANG']['MOD']['member_rating']['comment_by'] = 'Kommentar von';
$GLOBALS['TL_LANG']['MOD']['member_rating']['your_rating'] = 'Deine Wertung';
$GLOBALS['TL_LANG']['MOD']['member_rating']['comment_form_locked'] = array('Formular gesperrt', 'Sie haben erst vor kurzem bei diesem Mitglied eine Bewertung abgegeben.');
$GLOBALS['TL_LANG']['MOD']['member_rating']['comment_form_locked_time'] = 'Eine weitere Bewertung ist erst wieder in %s m&ouml;glich.';
$GLOBALS['TL_LANG']['MOD']['member_rating']['publish_or_unpublish'] = 'aktivieren/deaktivieren';


// buttons
$GLOBALS['TL_LANG']['MOD']['member_rating']['add'] = 'Hinzuf&uuml;gen';


// errors
$GLOBALS['TL_LANG']['MOD']['member_rating']['err_add_score_between'] = 'Bitte vergeben Sie &uuml;ber das starvoting eine Punktezahl zwischen';
$GLOBALS['TL_LANG']['MOD']['member_rating']['invalidSocialmediaLink'] = 'Bitte geben Sie einen g&uuml;ltigen Socialmedia Link ein.';


// email
$GLOBALS['TL_LANG']['MOD']['member_rating']['emailNotify']['subject'] = 'Neuer Kommentar von ##author_firstname## ##author_lastname## auf %s';
$GLOBALS['TL_LANG']['MOD']['member_rating']['emailNotify']['line_1'] = 'Hallo ##recipient_firstname##';
$GLOBALS['TL_LANG']['MOD']['member_rating']['emailNotify']['line_2'] = '##author_firstname## ##author_lastname## gibt dir ##comments_score## %s';
$GLOBALS['TL_LANG']['MOD']['member_rating']['emailNotify']['line_3'] = 'Klicke auf diesen Link, um den Kommentar zu ver&ouml;ffentlichen:{{br}}<a href="%s">%s</a>';
$GLOBALS['TL_LANG']['MOD']['member_rating']['emailNotify']['line_4'] = 'Klicke auf diesen Link, um den Kommentar unwiderruflich zu l&ouml;schen:{{br}}<a href="%s">%s</a>';
$GLOBALS['TL_LANG']['MOD']['member_rating']['emailNotify']['line_5'] = 'Dies ist eine automatisch generierte Nachricht und kann nicht beantwortet werden. Bei Fragen wenden Sie sich an den Administrator oder Betreiber der Seite.';
