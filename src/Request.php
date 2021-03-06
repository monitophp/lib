<?php
namespace MonitoLib;

class Request
{
    const VERSION = '3.0.0';
    /**
    * 3.0.0 - 2020-09-18
    * new: static properties and methods
    *
    * 2.0.3 - 2019-12-09
    * new: nullIt() and $emptyAsNull on getJson()
    *
    * 2.0.3 - 2019-10-29
    * new: property/method asDataset
    *
    * 2.0.2 - 2019-09-21
    * fix: starting $params as array
    *
    * 2.0.1 - 2019-06-05
    * fix: getPage and getPerPage to return only valid numbers
    *
    * 2.0.0 - 2019-05-02
    * new: new gets
    *
    * 1.0.0 - 2017-06-26
    * Inicial release
    */

    private static $asDataset = false;
    private static $fields;
    private static $json = [];
    private static $orderBy;
    private static $page;
    private static $params = [];
    private static $perPage;
    private static $post;
    private static $query;
    private static $queryString = [];
    private static $requestUri;

    public static function asDataset()
    {
        return self::$asDataset;
    }
    public static function getFields()
    {
        if (is_null(self::$fields)) {
            if (isset(self::$queryString['fields'])) {
                self::$fields = explode(',', self::$queryString['fields']);
            }
        }

        return self::$fields;
    }
    public static function getJson($emptyAsNull = false, $asArray = false)
    {
        self::$json = json_decode(file_get_contents('php://input'), $asArray, 512, JSON_THROW_ON_ERROR);

        if ($emptyAsNull) {
            return self::nullIt(self::$json);
        }

        return self::$json;
    }
    private static function nullIt($json)
    {
        if ($json instanceof \StdClass) {
            foreach ($json as $k => $v) {
                if ($v === '') {
                    $json->$k = null;
                }
            }
        }

        return $json;
    }
    public static function getOrderBy()
    {
        if (is_null(self::$orderBy) && (isset(self::$queryString['orderBy']))) {
            foreach (self::$queryString['orderBy'] as $value) {
                $p = explode(',', $value);
                self::$orderBy[$p[0]] = $p[1] ?? '';
            }
        }

        return self::$orderBy;
    }
    public static function getPage()
    {
        return (is_numeric(self::$page) && self::$page > 0) ? self::$page : 1;
    }
    public static function getParam($key = null)
    {
        if (is_null($key)) {
            return self::$params;
        } else {
            if (isset(self::$params[$key])) {
                return self::$params[$key];
            } else {
                return null;
            }
        }
    }
    public static function getPerPage()
    {
        return (is_numeric(self::$perPage) && self::$perPage > 0) ? self::$perPage : 0;
    }
    public static function getPost($key = null)
    {
        if (is_null(self::$post)) {
            self::$post = $_POST;
        }
        if (is_null($key)) {
            return self::$post;
        } else {
            if (isset(self::$post[$key])) {
                return self::$post[$key];
            } else {
                return null;
            }
        }
    }
    public static function getQuery()
    {
        return self::$query;
    }
    public static function getQueryString($key = null)
    {
        if (is_null($key)) {
            return self::$query;
        } else {
            if (isset(self::$query[$key])) {
                return self::$query[$key];
            } else {
                return null;
            }
        }
    }
    public static function getRequestUri()
    {
        return self::$requestUri;
    }
    public static function setParams($params)
    {
        self::$params = $params;
    }
    public static function setQueryString($queryString)
    {
        $fields = explode('&', $queryString);

        foreach ($fields as $field) {
            $p = strpos($field, '=');
            $f = substr($field, 0, $p);
            $v = substr($field, $p + 1);

            if (!$p && $field === 'ds') {
                self::$asDataset = true;
            } else {
                if (strcasecmp($f, 'fields') === 0) {
                    self::$queryString['fields'] = $v;
                } elseif (strcasecmp($f, 'page') === 0) {
                    self::$page = $v;
                } elseif (strcasecmp($f, 'perpage') === 0) {
                    self::$perPage = $v;
                } elseif (strcasecmp($f, 'orderby') === 0) {
                    self::$queryString['orderBy'][] = $v;
                } else {
                    self::$query[] = [$f => $v];
                }
            }
        }
    }
    public static function setRequestUri($requestUri)
    {
        self::$requestUri = '/' . $requestUri;
    }
}