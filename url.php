<?php

/**
	URL plugin for the PHP Fat-Free Framework
	Allows for easy building and manipulation of complicated URLs.
	
	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2010-2011 Killsaw
	Steven Bredenberg <steven@killsaw.com>

		@package URL
		@version 1.0.0
**/

//! URL plugin
class URL extends Core
{
	
	//! Minimum framework version required to run
	const F3_Minimum='1.4.0';

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_GenericError='An error occurred. {@CONTEXT}',
		TEXT_NoProperty="Property '{@CONTEXT}' does not exist.",
		TEXT_NoMethod="Method '{@CONTEXT}' does not exist.";
	//@}
	
	//@{
	//! URL properties
    protected $scheme = NULL;
    protected $host = NULL;
    protected $port = NULL;
    protected $user = NULL;
    protected $pass = NULL;
    protected $path = NULL;
    protected $query = array();
    protected $fragment = NULL;
	//@}

    /**
    	F3 plugin entry-point.
    		@param $url string	Optional URL to build object from
    		@return URL
    		@public
    **/
    public static function url($url=null) {
        return new URL($url);
    }
    
    /**
    	Set username and password for URL
    		@param $user string	Username
    		@param $pass string Password
    		@return URL
    		@public
    **/
    public function setLogin($user, $pass) {
        $this->user = $user;
        $this->pass = $pass;
        return $this;
    }

    /**
    	Set query parameters for URL
    		@param $query array|string	Query pairs for URL.
    		@return URL
    		@public
    **/
    public function setQuery($query) {
        if (!is_array($query)) {
            $parts = explode('&', urldecode($query));
            $pairs = array();
            foreach($parts as $p) {
                list($k, $v) = explode('=', $p, 2);
                $pairs[$k] = $v;
            }
            $query = $pairs;
        }
        $this->query = $query;
        return $this;
    }

    /**
    	Set a single query parameter for URL.
    		@param $name string	Query argument name
    		@param $value mixed	Query argument value
    		@return URL
    		@public
    **/
    public function setParam($name, $value) {
        $this->query[$name] = $value;
        return $this;
    }

    /**
    	Get a query parameter value.
    		@param $name string	Name of query parameter.
    		@return string|NULL
    		@public
    **/
    public function getParam($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
        	return NULL;
        }
    }

    /**
    	Retrieves data from URL.
    		@param $cookies array	Cookies for request.
    		@param $referer string	Referring URL.
    		@param $agent string	User-agent for request.
    		@return string
    		@public
    **/
    public function get($cookies=array(), $referer=null, $agent=null) {
        $headers = '';

        if (is_null($agent)) {
            $agent = 'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)';
        }
        if (!is_null($referer)) {
            $headers .= "Referer: $referer\r\n";
        }
        if (count($cookies)) {
            $cookie_pairs = array();
            foreach($cookies as $k=>$v) {
                $cookie_pairs[] = sprintf("%s=%s", $k, $v);
            }
            $headers .= "Cookies: ".join("; ", $cookie_pairs)."\r\n";
        }

        $opts = array(
                    'http'=>array(
                               'method'=>'GET',
                               'user_agent'=>$agent,
                               'headers'=>$headers
                           )
                );

        return file_get_contents(
                   $this->toString(), NULL,
                   stream_context_create($opts)
               );
    }

    /**
    	Returns HTML link from current URL.
    		@param $with_name string Optional label for link.
    		@return string
    		@public
    **/
    public function toLink($with_name=null) {
        if (is_null($with_name)) {
            $with_name = $this->toString();
        }
        return sprintf('<a href="%s">%s</a>', $this->toString(), $with_name);
    }

    /**
    	Returns URL without path, query, or fragment.
    		@return string
    		@public
    **/
    public function getBaseURL() {
        return $this->toString($just_base=true);
    }

    /**
    	Returns full string representation of URL.
    		@param $just_base boolean	Return part or entire URL.
    		@return string
    		@public
    **/
    public function toString($just_base=false) {
        if (empty($this->scheme)) {
            $this->scheme = 'http';
        }
        if ($this->path[0] !== '/') {
            $this->path = '/'.$this->path;
        }

        $login = '';
        if (!empty($this->user) && !empty($this->pass)) {
            $login = sprintf("%s:%s@", $this->user, $this->pass);
        }

        $url = sprintf("%s://%s%s", $this->scheme, $login, $this->host);

        if (!empty($this->port) && $this->port != 80) {
            $url .= ':'.$this->port;
        }

        // Skip the path, query and fragment.
        if ($just_base) {
            return $url;
        }

        $url .= $this->path;

        if (count($this->query)) {
            $url .= '?'.http_build_query($this->query);
        }

        if (!empty($this->fragment)) {
            $url .= '#'.$this->fragment;
        }

        return $url;
    }

    /**
    	Returns string representation of object.
    		@return string
    		@magic
    		@public
    **/
    public function __toString() {
        return $this->toString();
    }


    /**
    	Assign value to class property.
    		@param $name string 	Name of property
    		@param $value mixed 	Value of property
    		@return mixed
    		@magic
    		@public
    **/
    public function __set($name, $value) {
        if (property_exists($this, $name)) {
            $this->$name = $value;
            return $value;
        } else {
        	self::$global['CONTEXT'] = $name;
            trigger_error(self::TEXT_NoProperty);
        }
    }

    /**
    	Return value of class property.
    		@param $name string 	Name of property
    		@return mixed 	Value of property
    		@magic
    		@public
    **/
    public function __get($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
        	self::$global['CONTEXT'] = $name;
            trigger_error(self::TEXT_NoProperty);
        }
    }

    /**
    	Intercepts method calls to this class.
    		@param $name string	Method name
    		@param $args mixed
    		@return URL
    		@magic
    		@public
    **/
    public function __call($name, $args) {
        if (preg_match('/^set(.+)/', $name, $matches)) {
            $property = strtolower($matches[1]);
            if (property_exists($this, $property)) {
                $this->$property = $args[0];
                return $this;
            }
        }
        self::$global['CONTEXT'] = $name;
        trigger_error(self::TEXT_NoMethod);
    }

    /**
    	Class constructor.
    		@param $url string	Optional URL to build object from.
    		@public
    **/
    public function __construct($url=null) {
        if (!is_null($url)) {
            foreach(parse_url($url) as $k=>$v) {
                call_user_func(array($this, "set$k"), $v);
            }
        }
    }

}
