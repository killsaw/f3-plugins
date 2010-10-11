<?php
/**
 * URL plugin for the PHP Fat-Free Framework
 * Allows for easy building and manipulation of complicated URLs.
 *
 * PHP version 5
 *
 * @category  Plugin
 * @package   URL
 * @author    Steven Bredenberg <steven@killsaw.com>
 * @copyright 2010 Steven Bredenberg
 * @license   GPL 2.0
 * @link      http://github.com/killsaw/f3-plugins
 */

/**
 * URL plugin.
 *
 * @category Plugin
 * @package  URL
 * @author   Steven Bredenberg <steven@killsaw.com>
 * @license  GPL 2.0
 * @link     http://github.com/killsaw/f3-plugins
 */
class URL extends Core
{

    //! Minimum framework version required to run
    const F3_Minimum='1.4.0';

    //! Locale-specific error/exception messages
    const
    TEXT_GENERIC_ERROR='An error occurred. {@CONTEXT}',
    TEXT_NO_PROPERTY="Property '{@CONTEXT}' does not exist.",
    TEXT_NO_METHOD="Method '{@CONTEXT}' does not exist.";

    /**
    * URL Scheme (e.g. 'http')
    * @type   string
    * @access protected
    */
    protected $scheme = null;

    /**
    * URL Host (e.g. 'www.cnn.com')
    * @type   string
    * @access protected
    */
    protected $host = null;

    /**
    * URL Port (e.g. 80)
    * @type   int
    * @access protected
    */
    protected $port = null;

    /**
    * URL HTTP auth password.
    * @type   string
    * @access protected
    */
    protected $user = null;

    /**
    * URL HTTP auth username.
    * @type   string
    * @access protected
    */
    protected $pass = null;

    /**
    * URL Path (e.g. '/index.php')
    * @type   string
    * @access protected
    */
    protected $path = null;

    /**
    * URL query params (e.g. ('id'=>1, 's'=>'search'))
    * @type   array
    * @access protected
    */
    protected $query = array();

    /**
    * URL Fragment (e.g. '#page-top')
    * @type   string
    * @access protected
    */
    protected $fragment = null;

    /**
     * Static constructor alias.
     *
     * @param string $url Optional string URL to build object from.
     *
     * @return URL
     * @access public
     */
    public static function url($url=null)
    {
        return new URL($url);
    }

    /**
     * Set username and password for URL
     *
     * @param string $user HTTP auth username
     * @param string $pass HTTP auth password
     *
     * @return URL
     * @access public
     */
    public function setLogin($user, $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
        return $this;
    }

    /**
     * Set query parameters for URL
     *
     * @param string|array $query Query string or key=>val array.
     *
     * @return URL
     * @access public
     */
    public function setQuery($query)
    {
        if (!is_array($query)) {
            $parts = explode('&', urldecode($query));
            $pairs = array();
            foreach ($parts as $p) {
                list($k, $v) = explode('=', $p, 2);
                $pairs[$k] = $v;
            }
            $query = $pairs;
        }
        $this->query = $query;
        return $this;
    }

    /**
     * Set a single query parameter for URL.
     *
     * @param string $name  Query param key.
     * @param string $value Query param value.
     *
     * @return URL
     * @access public
     */
    public function setParam($name, $value)
    {
        $this->query[$name] = $value;
        return $this;
    }

    /**
     * Get a query parameter value.
     *
     * @param string $name Query param key.
     *
     * @return string|null String if value set, null otherwise.
     * @access public
     */
    public function getParam($name)
    {
        if (array_key_exists($name, $this->query)) {
            return $this->query[$name];
        }
        return null;
    }

    /**
       Retrieves data from URL.
          @param $cookies array   Cookies for request.
          @param $referer string   Referring URL.
          @param $agent string   User-agent for request.
          @return string
          @public
    **/

    /**
     * Retrieves data from URL.
     *
     * @param array  $cookies Optional array of key=>value cookies.
     * @param string $referer Optional HTTP Referer
     * @param string $agent   Optional User-agent (e.g. Internet Explorer 6.0)
     *
     * @return string|bool FALSE on failure, string otherwise.
     * @access public
     */
    public function get($cookies=array(), $referer=null, $agent=null)
    {
        $headers = '';

        if (is_null($agent)) {
            $agent = 'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)';
        }
        if (!is_null($referer)) {
            $headers .= "Referer: $referer\r\n";
        }
        if (count($cookies)) {
            $cookie_pairs = array();
            foreach ($cookies as $k=>$v) {
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
            $this->toString(), null,
            stream_context_create($opts)
        );
    }

    /**
     * Returns HTML link from current URL.
     *
     * @param string $with_name Optional label for link.
     *
     * @return string
     * @access public
     */
    public function toLink($with_name=null)
    {
        if (is_null($with_name)) {
            $with_name = $this->toString();
        }
        return sprintf('<a href="%s">%s</a>', $this->toString(), $with_name);
    }

    /**
     * Returns URL without path, query, or fragment.
     *
     * @return string
     * @access public
     */
    public function getBaseURL()
    {
        return $this->toString($just_base = true);
    }

    /**
     * Returns full string representation of URL.
     *
     * @param boolean $just_base Return part or entirety of URL.
     *
     * @return string
     * @access public
     */
    public function toString($just_base=false)
    {
        if (empty($this->scheme)) {
            $this->scheme = 'http';
        }
        if (empty($this->path) || $this->path[0] !== '/') {
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
     * Returns string representation of object.
     *
     * @return string
     * @access public
     * @magic
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Assign value to class property.
     *
     * @param string $name  Name of property
     * @param mixed  $value Value to assign to property.
     *
     * @return string
     * @access public
     */
    public function __set($name, $value)
    {
        if (!property_exists($this, $name)) {
            self::$global['CONTEXT'] = $name;
            trigger_error(self::TEXT_NO_PROPERTY);
        }
        $this->$name = $value;
        return $value;
    }

    /**
     * Return value of class property.
     *
     * @param string $name Name of property
     *
     * @return string
     * @access public
     */
    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            self::$global['CONTEXT'] = $name;
            trigger_error(self::TEXT_NO_PROPERTY);
        }
        return $this->$name;
    }

    /**
     * Intercepts method calls to this class.
     *
     * @param string $name Name of method
     * @param array  $args Arguments passed to method
     *
     * @return URL
     * @access public
     * @magic
     */
    public function __call($name, $args)
    {
        if (preg_match('/^set(.+)/', $name, $matches)) {
            $property = strtolower($matches[1]);
            if (property_exists($this, $property)) {
                $this->$property = $args[0];
                return $this;
            }
        } else {
            self::$global['CONTEXT'] = $name;
            trigger_error(self::TEXT_NO_METHOD);
        }
    }

    /**
     * Class constructor
     *
     * @param string $url URL string to build object from.
     *
     * @access public
     * @magic
     */
    public function __construct($url=null)
    {
        if (!is_null($url)) {
            foreach (parse_url($url) as $k=>$v) {
                call_user_func(array($this, "set$k"), $v);
            }
        }
    }

}
