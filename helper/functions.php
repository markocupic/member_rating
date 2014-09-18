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

/**
 * @param $arr
 * @param $fields
 * @return mixed
 */
function sortArrayByFields($arr,$fields)
{
	$sortFields = array();
	$args = array();

	foreach($arr as $key => $row)
	{
		foreach($fields as $field => $order)
		{
			$sortFields[$field][$key] = $row[$field];
		}
	}

	foreach($fields as $field => $order)
	{
		$args[] = $sortFields[$field];

		if(is_array($order))
		{
			foreach($order as $pt)
			{
				$args[$pt];
			}
		}
		else
		{
			$args[] = $order;
		}
	}

	$args[] = &$arr;

	call_user_func_array('array_multisort',$args);

	return $arr;
}