<?php

/**
	AxonREST plugin for the PHP Fat-Free Framework
	Provides REST access to an Axon model, automagically.
	
	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2010-2011 Killsaw
	Steven Bredenberg <steven@killsaw.com>

		@package AxonREST
		@version 1.0.0
**/

//! Plugin for REST access to an Axon model.
class AxonREST extends Core {

	//! Minimum framework version required to run
	const F3_Minimum='1.4.0';

	//! Service status messages.
	const Status_Failure = 'FAILURE',
		  Status_Success = 'SUCCESS';
	
	//@{
	//! Locale-specific error/exception messages
	const 
		TEXT_NeedResourceID='Please provide a resource identifier.',
		TEXT_NoAccess='You do not have the priveleges to access this resource.',
		TEXT_NoModelNameSet='No model name has been specified.';
	//@}
	
	//! Allows for the use of ACL or other access restrictions.
	static protected $accessDelegate=false;
	
	//! Allows for hard set object names not pulled from route vars.
	static protected $objectName=null;

	/**
		Centralized JSON-encoded output for REST client.
			@param $status string
			@param $code string
			@param $message string
			@public
	**/	
	static protected function reportStatus($status, $message=null, $code=null) {
		echo json_encode(array(
							'status'=>$status,
							'code'=>$code,
							'message'=>$message
						));
	}
	
	/**
		Get Axon model for current request.
			@return AxonHelper
			@param $name string
			@param $where string
			@public
	**/	
	static protected function getObject($name, $where=null) {
	
		// Scary hack. Make properties on Axon available to AxonREST
		if (!in_array('AxonHelper', get_declared_classes())) {
			$class = str_replace(
						'class Axon',
						'class AxonHelper',
						str_replace(
							'private ',
							'public ',
							file_get_contents(__DIR__."/axon.php")
					   )
					);
			eval('?>'.$class);
		}
		
		// Now load this mess.
		$x = new AxonHelper($name);
		if ($where !== null) {
			if (is_numeric($where)) {
				$where = sprintf("id=%d", $where);
			}
			$x->load($where);
		}
		return $x;
	}

	/**
		Get effective object name for Axon model.
			@return string
			@public
	**/	
	static public function getObjectName() {
		if (!empty(self::$objectName)) {
			return self::$objectName;
		} elseif (F3::get('PARAMS["name"]')) {
			return F3::get('PARAMS["name"]');
		} else {
			trigger_error(self::TEXT_NoModelNameSet);
		}
	}
	
	/**
		Hard-set object name for Axon model.
			@param $objectName string
			@public
	**/	
	static public function setObjectName($objectName) {
		self::$objectName = $objectName;
	}
	
	/**
		Sets callback for access checks.
			@param $delegate callable
			@public
	**/	
	static public function setAccessDelegate($delegate) {
		if (is_callable($delegate)) {
			self::$accessDelegate = $delegate;
		}
	}
	
	/**
		Check access to current object.
			@return bool
			@param $object string
			@param $constraints string
			@public
	**/	
	static protected function hasAccess($object, $constraints=null) {
		if (self::$accessDelegate === false) {
			return true;
		} else {
			$return = call_user_func(self::$accessDelegate, 
								  $object, $constraints
								  );
								  
			if (is_bool($return)) {
				return $return;
			} else {
				// By default, deny, if an access
				// delegate has been specified.
				return false;
			}
		}
	}
	
	/**
		View one or more records.
			@public
	**/	
	static public function get() {
		$object_name = self::getObjectName();
		$object_id   = F3::get('PARAMS["id"]');

		if (!self::hasAccess($object_name, 'view', $object_id)) {
			self::reportStatus(self::Status_Failure, self::TEXT_NoAccess);
			return;
		}
		
		if (!empty($object_id)) {		
			$object = self::getObject($object_name, intval($object_id));
			if ($object->dry()) {
				f3::http404();
			} else {
				echo json_encode($object->fields);
			}
			
		} else {
			// It's a listing.
			$object = self::getObject($object_name);
			$fields = '*';
			$where = null;
			$group_by = (isset($_GET['group_by']))?$_GET['group_by']:null;
			$order_by = (isset($_GET['order_by']))?$_GET['order_by']:null;
			$limit = (isset($_GET['limit']))?$_GET['limit']:null;
			$offset = (isset($_GET['offset']))?$_GET['offset']:null;
			
			$find = array();
			foreach(array_keys($object->fields) as $key) {
				if (isset($_GET[$key]) && !empty($_GET[$key])) {
					$value = $_GET[$key];
					
					if (preg_match('/^([<>!]+)/', $value, $match)) {
						$find[] = $key.$value;
					} elseif ($value[0] == '%' || substr($value, -1) == '%') {
						$find[] = $key." LIKE '".$value."'";
					} else {
						$find[] = sprintf("%s='%s'", $key, $value);
					}
				}
			}
			if (count($find) > 0) {
				$where = join(' AND ', $find);
			}
			
			echo json_encode(
					$object->select($fields, $where, $group_by, 
									$order_by, $limit, $offset)
				 );
		}
	}

	/**
		Create a new record.
			@public
	**/	
	static public function put() {
		$object_name = self::getObjectName();
		$object = self::getObject($object_name);

		if (!self::hasAccess($object_name, 'create')) {
			self::reportStatus(self::Status_Failure, self::TEXT_NoAccess);
			return;
		}
		
		foreach($_REQUEST as $k=>$v) {
			if (array_key_exists($k, $object->fields)) {
				$object->$k = $v;
			}
		}
		
		if ($object->save() !== false) {
			self::reportStatus(self::Status_Success, $object->id);
		} else {
			self::reportStatus(self::Status_Failure);
		}
	}

	/**
		Edit an existing record.
			@public
	**/	
	static public function post() {
		$object_name = self::getObjectName();
		$object_id   = F3::get('PARAMS["id"]');
		
		// Access check.
		if (!self::hasAccess($object_name, 'edit', $object_id)) {
			self::reportStatus(self::Status_Failure, self::TEXT_NoAccess);
			return;
		}
		
		// Require an ID
		if (empty($object_id)) {
			self::reportStatus(self::Status_Failure, self::TEXT_NeedResourceID);
			return;
		}

		$object = self::getObject($object_name, $object_id);
		
		foreach($_POST as $k=>$v) {
			if (array_key_exists($k, $object->fields)) {
				$object->$k = $v;
			}
		}
		
		if ($object->save() !== false) {
			self::reportStatus(self::Status_Success);
		} else {
			self::reportStatus(self::Status_Failure);
		}
	}

	/**
		Delete object by ID.
			@param $userid string
			@public
	**/	
	static public function delete() {
		$object_name = self::getObjectName();
		$object_id   = F3::get('PARAMS["id"]');

		// Access check.
		if (!self::hasAccess($object_name, 'delete', $object_id)) {
			self::reportStatus(self::Status_Failure, self::TEXT_NoAccess);
			return;
		}
		
		// Require an ID.
		if (empty($object_id)) {
			self::reportStatus(self::Status_Failure, self::TEXT_NeedResourceID);
			return;
		}
		
		$object = self::getObject($object_name, $object_id);
		
		if ($object->erase()) {
			self::reportStatus(self::Status_Success);
		} else {
			self::reportStatus(self::Status_Failure);
		}
	}
}