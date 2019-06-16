<?php

namespace mouse\container;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use mouse\support\hashmap\Arr;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{   
    /**
     * current Container
     *
     * @var Object
     */
    protected static $instance = null;

    /**
     * Get bind Container
     *
     * @return Container
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * Set Bind Container
     *
     * @param ContainerInterface $container
     * @return ContainerInterface
     */
    public static function setInstance(ContainerInterface $container)
    {
        static::$instance = $container;
        return static::$instance;
    }

    /**
     * 绑定在容器内的对象
     *
     * @var array
     */
    protected $instances = null;

    /**
     * 对象tags
     *
     * @var array
     */
    protected $tag = null;

    /**
     * 对象别名
     *
     * @var array
     */
    protected $alias = null;

    /**
     * 待生成对象的绑定
     *
     * @var array
     */
    protected $pend = null;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->instances = new Arr();
        $this->tags = new Arr();
        $this->alias = new Arr();
        $this->pend = new Arr();
    }
    
    /**
     * 获取id的Object
     *
     * @param string $id
     * @return Object
     */
    public function get(string $id)
    {
        // 判定是否存在别名
        if (isset($this->alias[$id])) {
            $id = $this->alias[$id];
        }
        // 判定对象池中是否存在
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        // 判定待生成的对象中是否存在
        if (isset($this->pend[$id])) {
            // 生成对象
            
        }
        return null;
    }

    /**
     * 判断Id是否存在
     *
     * @param string $id
     * @return boolean
     */
    public function has(string $id)
    {
        return !is_null($this->get($id));
    }
    /**
     * 将对象存入容器池
     *
     * @param Object $instance
     * @param string $key
     * @param bool $force
     * @return void
     */
    public function setInstances(Object $instance, string $key = '', bool $force = false)
    {
        $class = get_class($instance);
        if (!$force) {
            if ( (!empty($key) && isset($this->alias[$key])) || isset($this->instances[$class]) || isset($this->pend[$key]) ) {
                throw new ContainerException("container presence key:{$key} class:{$class} Object");
            }
        } 
        if (!empty($key)) {
            $this->alias[$key] = $class;
        }
        $this->instances[$class] = $instance;
    }

    /**
     * 获取方法的参数
     *
     * @param ReflectionMethod $method
     * @return array
     */
    protected function getParams(ReflectionMethod $method)
    {
        $params = [];
        $reflectionParams = $method->getParams();
        foreach($reflectionParams as $param) {
            $params[] = $this->getParam($param);
        }
        return $params;
    }

    /**
     * 获取依赖注入参数
     *
     * @param ReflectionParameter $param
     * @return void
     */
    protected function getParam(ReflectionParameter $param)
    {
        if ($cls = $param->getClass()) {
            $className = $cls->getName();
            // 1. 从对象池中获取
            if (isset($this->instances[$className])) {
                return $this->instances($className);
            }
            return $this->generate($className);
        }
        // 其他形式获取参数
        $paramName = $param->getName();
        // @todo 通过param获取上下文
    }

    /**
     * 根据参数生成一个对象
     *
     * @param string $class 类名
     * @return Object
     * @throws NotFoundException class is not found
     * @throws ContainerException
     */
    protected function generate($class)
    {
        if(!class_exists($class)) {
            throw new NotFoundException("class:{$class} is not found");
        }
        $reflect = new ReflectionClass($class);
        // 依赖注入的参数
        $paramArr = [];
        // 对象是否能实例化
        if(!$reflect->isInstantiable()) {
            throw new ContainerException("class:{$class} is not Instantiable");
        }
        if(is_null($construct = $reflect->getConstructor())) {
            return $reflect->newInstanceArgs();
        }
        $parms = $this->getParams($construct);
        return $reflect->newInstanceArgs($params);
    }
}