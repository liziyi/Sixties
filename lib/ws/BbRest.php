<?php
/**
 * This file is part of Sixties, a set of classes extending XMPPHP, the PHP XMPP library from Nathanael C Fritz
 *
 * Copyright (C) 2009  Clochix.net
 *
 * Sixties is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Sixties is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Sixties; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   WS
 * @package    Sixties
 * @subpackage Rest
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @link       https://labo.clochix.net/projects/show/sixties
 */

/**
 * Common classes
 */
require_once $basepath . '/lib/bb/BbCommon.php';

/**
 * Define a new UrlMap annotation to map parameters in the url with parameters of the method
 */
require_once $basepath . '/lib/vendors/addendum/annotations.php';
class UrlMap extends Annotation {}

/**
 * BbRest : base for RESTful web services
 *
 * @category   WS
 * @package    Sixties
 * @subpackage Rest
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class BbRest extends BbBase
{
    const HTTP_OK                 = 200;
    const HTTP_BAD_REQUEST        = 400;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_FOUND          = 404;
    const HTTP_NOT_ACCEPTABLE     = 406;
    const HTTP_INTERNAL           = 500;
    const HTTP_NOT_IMPLEMENTED    = 501;
    const HTTP_UNAVAILABLE        = 503;

    /**
     * @var array $_mapping mapping between modules and classes
     */
    private $_mapping = array();
    /**
     * @var array $_config some config
     */
    private $_config = array();

    /**
     * @var string format
     */
    public $format = 'xml';

    /**
     * Initialize object
     *
     * @return void
     */
    public function __construct($mapping, $config) {
        parent::__construct();
        $this->_mapping = (is_array($mapping) ? $mapping : array());
        $this->_config  = (is_array($config) ? $config : array());
        // content negociation
        $this->format = 'xml'; // Default format is XML
        $accepted = explode(',', $_SERVER["HTTP_ACCEPT"]);
        foreach ($accepted as $format) {
            $tmp = explode(';', $format);
            $format = trim(strtolower($format));
            if ($format == 'text/xml') {
                $this->format = 'xml';
                break;
            } elseif ($format == 'application/json') {
                $this->format = 'json';
                break;
            }
        }
    }

    /**
     * Update the current configuration
     *
     * @params mixed $config the new configuration
     *
     * @return BbRest $this
     */
    public function setConfig($config) {
        $this->_config = $config;
        return $this;
    }

    /**
     * Handle a request
     *
     * @return void
     */
    public function handle() {
        try {
            $request = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(dirname($_SERVER['PHP_SELF'])) + 1);
            $path    = explode('/', $request);
            $method  = ucFirst(strtolower($_SERVER['REQUEST_METHOD']));
            if (count($path) == 1 && strlen($path[0]) == 0) {
                if ($method == 'Options') {
                    // Get the list of available modules
                    //@TODO : manage security
                    $this->renderResponse(array_keys($this->_mapping));
                    return true;
                }
            }
            switch ($method) {
            case 'Get':
                $args = $_GET;
                break;
            case 'Post':
                $args = $_POST;
                break;
            case 'Put':
                parse_str(file_get_contents('php://input'), $args);
                break;
            case 'Delete':
                parse_str(file_get_contents('php://input'), $args);
                break;
            case 'Options':
                parse_str(file_get_contents('php://input'), $args);
                break;
            default:
                $this->renderResponse("Method $method not implemented", self::HTTP_NOT_IMPLEMENTED);
                return false;
                break;
            }
            if (!isset($this->_mapping[$path[0]])) {
                $this->renderResponse("Module {$path[0]} not implemented", self::HTTP_NOT_IMPLEMENTED);
                return false;
            }
            $classname = $this->_mapping[$path[0]];
            if (!class_exists($classname)) {
                include_once $classname . '.php';
                if (!class_exists($classname)) {
                    $this->renderResponse("Module {$path[0]} not implemented", self::HTTP_NOT_IMPLEMENTED);
                    return false;
                }
            }
            // for generic methods
            if (count($path) == 1) $path[1] = '';
            $methodname = $path[1] . $method;
            if (!in_array($methodname, get_class_methods($classname))) {
                $this->renderResponse("No action for method $method on this module", self::HTTP_METHOD_NOT_ALLOWED);
                return false;
            }

            // Are there parameters in the URL ? try to map them according to annotations
            if (count($path) > 2) {
                $reflection = new ReflectionAnnotatedMethod($classname, $methodname);
                $urlmap = $reflection->getAnnotation('UrlMap');
                if ($urlmap) {
                    if (!is_array($urlmap->value)) $urlmap->value = array($urlmap->value);
                    foreach ($urlmap->value as $num => $key) {
                        if (isset($path[$num+2]) && !isset($args[$key])) $args[$key] = $path[$num+2];
                    }
                }
            }

            // Create the class and call the method
            $class = new $classname($this->_config, $this->loggerGet());

            $res = $class->$methodname($args);
            if ($res instanceof BbResponse) {
                // try to recover on error
                if ($res->code == WsResponse::KO) {
                    if ($res->message['code'] == self::HTTP_UNAVAILABLE) {
                        // service unavailable, wait a little and let it a second chance
                        usleep(1000);
                        $res = $class->$methodname($args);
                    }
                }
            }

        } catch (WsException $e) {
            // error of the application, not the service itself => return code will be ok (200)
            $this->renderResponse(new WsResponse($e->getMessage(), $e->getCode()), self::HTTP_OK);
            return false;
        } catch (Exception $e) {
            // unexpected error => 500
            $this->renderResponse($e->getMessage(), self::HTTP_INTERNAL);
            return false;
        }
        $this->renderResponse($res);
        return true;
    }

    /**
     * Render the anwser
     *
     * @return void
     */
    public function renderResponse($content, $code = self::HTTP_OK) {
        @header("HTTP/1.1 $code");
        @header("Status: $code");
        switch ($this->format) {
        case 'xml':
            @header("Content-Type: text/xml;");
            $res = sprintf('<?xml version="1.0" encoding="UTF-8"?><response><code>%d</code><message>%s</message></response>',
                        $code, self::_xmlise($content));
            echo $res;
            break;
        case 'json':
            @header("Content-Type: application/json;");
            $res = new StdClass();
            $res->code    = $code;
            $res->message = $content;
            echo json_encode($res);
            break;
        default:
            echo "Unknown format " . $this->format;
            break;
        }
    }

    /**
     * Serialize an object into XML
     *
     * @param mixed $object : array or object
     *
     * @return string
     */
    private static function _xmlise($obj) {
        if (is_object($obj)) {
            $vars = get_object_vars($obj);
            $name = get_class($obj);
            $res = '';
            foreach ($vars as $key => $val) {
                if (is_array($val) || is_object($val)) $val = self::_xmlise($val);
                else $val = "<val><![CDATA[$val]]></val>";
                $res .= "<item><key><![CDATA[$key]]></key>$val</item>";
            }
            return "<$name>$res</$name>\n";
        } elseif (is_array($obj)) {
            $res = '';
            foreach ($obj as $key => $val) {
                if (is_array($val) || is_object($val)) $val = self::_xmlise($val);
                else $val = "<val><![CDATA[$val]]></val>";
                $res .= "<item><key><![CDATA[$key]]></key>$val</item>";
            }
            return "<items>$res</items>\n";
        } else {
            return (string)$obj;
        }
    }

}
