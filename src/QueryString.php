<?php
namespace MonitoLib;

class QueryString
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-07-09
    * Initial release
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
    public static function getJson(?bool $emptyAsNull = false, ?bool $asArray = false)
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
    public static function getQueryString($key = null) : string
    {
        return self::$queryString;

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
    public static function getRequestUri() : string
    {
        return self::$requestUri;
    }
    public static function setParams($params) : void
    {
        self::$params = $params;
    }
    public static function parse(string $queryString) : void
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
    public static function setRequestUri($requestUri) : void
    {
        self::$requestUri = '/' . $requestUri;
    }
}