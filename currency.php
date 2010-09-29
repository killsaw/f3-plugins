<?php

/**
	Currency plugin for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2010-2011 Killsaw
	Steven Bredenberg <steven@killsaw.com>

		@package Currency
		@version 1.0.0
**/

//! Plugin for currency conversion methods
class Currency extends Core {

	//! Minimum framework version required to run
	const F3_Minimum='1.4.0';

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_NoConversion='Currency could not be converted.',
		TEXT_AmountNotNumber='Money amount is not a number.';
	//@}
	
	/**
		Retrieve currency conversion from Google Finance.
			@return bool|float
			@param $amount float
			@param $from string Currency to convert from. e.g. USD
			@param $to string Currency to convert to. e.g. EUR
			@public
	**/
	public static function convertAmount($amount, $from, $to, $quiet=false) {
	
		$from = strtoupper($from);
		$to = strtoupper($to);
		
		if (!is_numeric($amount)) {
			trigger_error(self::TEXT_AmountNotNumber);
			return false;
		}
		
		// Workaround for empty ENV. Causes an issue in f3::http()
		if (!isset($_ENV['OS'])) {
			$_ENV['OS'] = 'Windows';
		}
		
		$data = f3::http(
					  "GET http://www.google.com/finance/converter",
					   http_build_query(
					   		array(
						 		'a'=>$amount,
						 		'from'=>$from,
						 		'to'=>$to
						    )
					   )
				);
		
		if (preg_match('/<span class=bld>(.+) (.+)<\/span>/', $data, $match)) {
			if ($match[2] == $to) {
				return (float)$match[1];
			}
		}
		
		if (!$quiet) {
			trigger_error(self::TEXT_NoConversion);
		}
		return false;		
	}
}
