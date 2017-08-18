<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use ArrayIterator;
use yii\base\InvalidCallException;
use yii\base\Object;

/**CookieCollection维护了当前请求中可以获得的cookie
 * CookieCollection maintains the cookies available in the current request.
 * 详情请查看官网guide
 * For more details and usage information on CookieCollection, see the [guide article on handling cookies](guide:runtime-sessions-cookies).
 *
 * @property int $count The number of cookies in the collection. This property is read-only.
 * @property ArrayIterator $iterator An iterator for traversing the cookies in the collection. This property
 * is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class CookieCollection extends Object implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var bool whether this collection is read only.
     */
    public $readOnly = false;

    /**
     * @var Cookie[] the cookies in this collection (indexed by the cookie names)
     */
    private $_cookies = [];


    /**
     * Constructor.
     * @param array $cookies the cookies that this collection initially contains. This should be
     * an array of name-value pairs.
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($cookies = [], $config = [])
    {
        $this->_cookies = $cookies;
        parent::__construct($config);
    }

    /**
     * Returns an iterator for traversing the cookies in the collection.
     * This method is required by the SPL interface [[\IteratorAggregate]].
     * It will be implicitly called when you use `foreach` to traverse the collection.
     * @return ArrayIterator an iterator for traversing the cookies in the collection.
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_cookies);
    }

    /**
     * Returns the number of cookies in the collection.
     * This method is required by the SPL `Countable` interface.
     * It will be implicitly called when you use `count($collection)`.
     * @return int the number of cookies in the collection.
     */
    public function count()
    {
        return $this->getCount();
    }

    /**
     * Returns the number of cookies in the collection.
     * @return int the number of cookies in the collection.
     */
    public function getCount()
    {
        return count($this->_cookies);
    }

    /**
     * Returns the cookie with the specified name.
     * @param string $name the cookie name
     * @return Cookie the cookie with the specified name. Null if the named cookie does not exist.
     * @see getValue()
     */
    public function get($name)
    {
        return isset($this->_cookies[$name]) ? $this->_cookies[$name] : null;
    }

    /**
     * Returns the value of the named cookie.
     * @param string $name the cookie name
     * @param mixed $defaultValue the value that should be returned when the named cookie does not exist.
     * @return mixed the value of the named cookie.
     * @see get()
     */
    public function getValue($name, $defaultValue = null)
    {
        return isset($this->_cookies[$name]) ? $this->_cookies[$name]->value : $defaultValue;
    }

    /**
     * Returns whether there is a cookie with the specified name.
     * Note that if a cookie is marked for deletion from browser, this method will return false.
     * @param string $name the cookie name
     * @return bool whether the named cookie exists
     * @see remove()
     */
    public function has($name)
    {
        return isset($this->_cookies[$name]) && $this->_cookies[$name]->value !== ''
            && ($this->_cookies[$name]->expire === null || $this->_cookies[$name]->expire >= time());
    }

    /**
     * 为cookie集合里添加一个cookie对象暂存，待最终响应给客户端时再加入到http中
     * Adds a cookie to the collection.
     * 如果cookie同名的话会覆盖之前的
     * If there is already a cookie with the same name in the collection, it will be removed first.
     * @param Cookie $cookie the cookie to be added
     * @throws InvalidCallException if the cookie collection is read only
     */
    public function add($cookie)
    {
        if ($this->readOnly) {
            throw new InvalidCallException('The cookie collection is read only.');
        }
        $this->_cookies[$cookie->name] = $cookie;
    }

    /**
     * 删除一个cookie。
     * Removes a cookie. 
     * 如果$removeFromBrowser是true，则这个cookie交给浏览器来删除
     * If `$removeFromBrowser` is true, the cookie will be removed from the browser.
     * 一般第二个参数是true,代表从浏览器端删除。那么浏览器端如何删除呢？就是设置过期的expire即可，
     * In this case, a cookie with outdated expiry will be added to the collection.
     * 比如本例把expire字段设置为1
     * @param Cookie|string $cookie the cookie object or the name of the cookie to be removed.
     * @param bool $removeFromBrowser whether to remove the cookie from browser
     * @throws InvalidCallException if the cookie collection is read only
     */
    public function remove($cookie, $removeFromBrowser = true)
    {
        if ($this->readOnly) {
            throw new InvalidCallException('The cookie collection is read only.');
        }
        if ($cookie instanceof Cookie) {
            $cookie->expire = 1;
            $cookie->value = '';
        } else {
            $cookie = new Cookie([
                'name' => $cookie,
                'expire' => 1,
            ]);
        }
        //交给浏览器删除这个cookie
        if ($removeFromBrowser) {
            $this->_cookies[$cookie->name] = $cookie;
        //删除本次请求中带过来的cookie,有可能客户端还有
        } else {
            unset($this->_cookies[$cookie->name]);
        }
    }

    /**
     * 删除所有cookie，就是把cookie集合清空，本次http响应中不会再向浏览器种植cookie
     * Removes all cookies.
     * @throws InvalidCallException if the cookie collection is read only
     */
    public function removeAll()
    {
        if ($this->readOnly) {
            throw new InvalidCallException('The cookie collection is read only.');
        }
        $this->_cookies = [];
    }

    /**
     * Returns the collection as a PHP array.
     * @return array the array representation of the collection.
     * The array keys are cookie names, and the array values are the corresponding cookie objects.
     */
    public function toArray()
    {
        return $this->_cookies;
    }

    /**
     * Populates the cookie collection from an array.
     * @param array $array the cookies to populate from
     * @since 2.0.3
     */
    public function fromArray(array $array)
    {
        $this->_cookies = $array;
    }

    /**
     * Returns whether there is a cookie with the specified name.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `isset($collection[$name])`.
     * @param string $name the cookie name
     * @return bool whether the named cookie exists
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Returns the cookie with the specified name.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$cookie = $collection[$name];`.
     * This is equivalent to [[get()]].
     * @param string $name the cookie name
     * @return Cookie the cookie with the specified name, null if the named cookie does not exist.
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * Adds the cookie to the collection.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$collection[$name] = $cookie;`.
     * This is equivalent to [[add()]].
     * @param string $name the cookie name
     * @param Cookie $cookie the cookie to be added
     */
    public function offsetSet($name, $cookie)
    {
        $this->add($cookie);
    }

    /**
     * Removes the named cookie.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `unset($collection[$name])`.
     * This is equivalent to [[remove()]].
     * @param string $name the cookie name
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }
}
