<?php

namespace mouse\container;

use ReflectionClass;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{   
    protected static $instance = null;

    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * 绑定的对象
     * 数据格式 $key => $class
     *
     * @var array
     */
    protected $bindClass = [];

    /**
     * 完整类名绑定key
     * 数据格式 $class => $object
     *
     * @var array
     */
    protected $bindObject = [];

    /**
     * 实例化Container
     */
    public function __construct()
    {
        $class = get_class($this);
        $this->bindObject[$class] = $this;
        $this->bindClass['container'] = $class;
    }

    /**
     * 获取别名为key的对象
     *
     * @param string $key
     * @return object|null
     */
    public function get($key)
    {
        if(!$this->has($key)) {
            return null;
        }
        $class = $this->bindClass[$key];
        return $this->generate($class);
    }

    /**
     * 判断是否存在key
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->bindClass[$key]);
    }

    /**
     * 设置对象
     *
     * @param string|array $key
     * @param string $class
     * @param array $argv 实例化的参数
     * @return void
     */
    public function set($key, $class = null, $argv = [])
    {   
        if(is_string($key) && !empty($class)) {
            $key = [ $key => [$class, $argv]];
        }
        if(is_array($key)) {
            foreach($key as $k => $val) {
                if(is_array($val) && count($val) == 2) {
                    $class = $val[0];
                    $param = $val[1];
                } else {
                    $class = $val;
                    $param = [];
                }
                $class = ltrim($class, "\\");
                $this->bindClass[$k] = $class;
                $this->generate($class, $param);
            }
        }
    }

    /**
     * 生成Object
     *
     * @param string $class
     * @param array $param 额外参数
     * @param boolean $type 是否重新生成
     * @return Object
     */
    public function generate($class, $param = [], $type = false)
    {
        $class = ltrim($class, '\\');
        if(!isset($this->bindObject[$class]) || $type) {
            $this->bindObject[$class] = $this->make($class, $param);
        }
        return $this->bindObject[$class];
    }

    /**
     * 依赖注入生成对象
     *
     * @param string $class
     * @param array $arguments 额外参数
     * @return mixed
     */
    protected function make($class, $arguments = [])
    {
        if(!class_exists($class)) {
            throw new NotFoundException("类{$class}不存在");
        }
        $reflect = new ReflectionClass($class);
        // 依赖注入的参数
        $paramArr = [];
        if($reflect->isInstantiable()) {
            // 对象能实例化
            $construct = $reflect->getConstructor();
            // 构造函数没写
            if(is_null($construct)) {
                return $reflect->newInstanceArgs();
            }
            if(!$construct->isPublic()) {
                throw new ContainerException("类{$class}无法实例化");
            }
        } elseif($reflect->hasMethod('getInstance')) {
            // 单例
            $construct = $reflect->getMethod('getInstance');
            if(!$construct->isStatic()) {
                throw new ContainerException("类{$class}无法实例化");
            }
        } else { 
            throw new ContainerException("类{$class}无法实例化");
        }
        // 获取构造函数的参数
        $param = $construct->getParameters();
        if(count($param) > 0) {
            foreach($param as $key => $val) {
                // 如果是类 则注入
                if($cls = $val->getClass()) {
                    $className = $cls->getName();
                    $paramArr[] = $this->generate($className);
                }
            }
        }
        if(false == empty($arguments)) {
            $paramArr = array_merge_recursive($paramArr, $arguments);
        }
        if($reflect->isInstantiable()) {
            return $reflect->newInstanceArgs($paramArr);
            // return new $class(...$paramArr);
        } else {
            return $class::getInstance(...$paramArr);
        }
    }
}