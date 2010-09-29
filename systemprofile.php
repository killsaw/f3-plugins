<?php

/**
	System Profile plugin for the PHP Fat-Free Framework
	Can be used with Nagios or F3's throttling feature, amongst other things.

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2010-2011 Killsaw
	Steven Bredenberg <steven@killsaw.com>

		@package SystemProfile
		@version 1.0.0
**/

//! Plugin for retrieving information about the current system.
class SystemProfile {

	//! Minimum framework version required to run
	const F3_Minimum='1.4.0';
	
	//! Treshold for determining if 5-minute system load average is too high.
	const OVERLOADED_THRESHOLD = 2.0;

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_NoWindows='Sorry, this class does not work with Windows.';
	//@}

	/**
		Check that this system is not Windows. Only works for *nix.
			@protected
	**/	
	protected static function checkOS() {	
		if (preg_match('/^(Cyg|Win)/', PHP_OS)) {
			trigger_error(self::TEXT_NoWindows);
		}
	}

	/**
		Get system load levels (1, 5, and 15 minute load avgs)
			@return array
			@param $userid string
			@public
	**/	
	public static function getServerInfo() {	
		self::checkOS();
		
		return array(
			'os' => php_uname('s'),
			'hostname' => php_uname('n'),
			'release' => php_uname('r'),
			'version' => php_uname('v'),
			'machine' => php_uname('m'),
		);
	}
	
	/**
		Returns current system's hostname.
			@return string
			@public
	**/	
	public static function getHostname()
	{
		return php_uname('n');
	}
	
	/**
		Parse and return `uptime` info. All or some.
			@return array|string
			@param $all bool  Return all uptime info or just actual uptime value?
			@public
	**/	
	public static function getUptime($all=false) {
	
		self::checkOS();
		
		$orig_uptime = `uptime`;
		
		list($sys_time, $info) = explode('  ', $orig_uptime, 2);
		
		$info_parts = explode(', ', $info);
		
		// Some rather nasty string manipulations.
		$load = substr($info_parts[3], 15);
		$users = intval($info_parts[2]);
		$uptime = substr($info_parts[0], 3).$info_parts[1].' hours';
		
		$data = array(
					'system_time'=>$sys_time,
					'uptime'=>$uptime,
					'users'=>$users,
					'load'=>$load
				);
		
		if (!$all) {
			return $data['uptime'];
		} else {
			return $data;
		}
	}
	
	/**
		Get system load levels (1, 5, and 15 minute load avgs)
			@return array
			@public
	**/
	public static function getLoadLevels() {
	
		self::checkOS();
	
		$info = self::getUptime($all=true);
		$parts = explode(' ', $info['load']);
		
		return array(
				'1m_avg'=>(float)$parts[0],
				'5m_avg'=>(float)$parts[1],
				'15m_avg'=>(float)$parts[2]
			   );
	}
	
	/**
		Get list of logged in users.
			@return array
			@public
	**/
	public static function getOnlineUsers() {
	
		self::checkOS();

		$lines = explode("\n", trim(`who`));
		$users = array();
		
		foreach($lines as $line) {
			$parts = preg_split("/\s+/", $line);
			
			$users[] = array(
						'user'=>$parts[0],
						'term'=>$parts[1],
						'date'=>sprintf('%s %s', $parts[2], $parts[3]), 
						'time'=>$parts[4],
						'host'=>isset($parts[5])?substr($parts[5], 1, -1):null
						);
		}
		return $users;
	}
	
	/**
		Check if system load is too high.
			@param $check string
			@return bool
			@public
	**/
	public static function systemIsOkay($check='5m_avg') {
		$load = self::getLoadLevels();
		
		if ($load[$check] >= self::OVERLOADED_THRESHOLD) {
			return false;
		} else {
			return true;
		}
	}
}
