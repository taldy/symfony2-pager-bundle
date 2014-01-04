<?php

namespace PunkAve\PagerBundle\Factories;

class PagerFactory
{
    protected $router;
    protected $options;

    public function __construct($router, array $options)
    {
        $this->router = $router;
        $this->options = $options;
    }

    /**
     * Instanciates a new Pager of the type specified.
     * Defaults to DoctrineORM.
     *
     * @param string $type
     * @return \PunkAve\PagerBundle\Interfaces\Pager
     */
    public function createPager($type = "DoctrineORM")
    {
        $class = isset($this->options['pager_classes'][$type]) ? $this->options['pager_classes'][$type] : 'PunkAve\PagerBundle\DoctrineORM\Pager';
        return $this->instanciatePager($class);
    }

    /**
     * Returns a new pager with its dependencies injected.
     *
     * @param string $class
     * @return \PunkAve\PagerBundle\Interfaces\Pager
     */
    public function instanciatePager($class = 'PunkAve\PagerBundle\DoctrineORM\Pager')
    {
        $pager = new $class();
        $pager->setRouter($this->router);

        return $pager;
    }
    
}
