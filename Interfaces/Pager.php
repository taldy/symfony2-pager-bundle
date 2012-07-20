<?php

namespace PunkAve\PagerBundle\Interfaces;

use Doctrine\ORM\QueryBuilder as QueryBuilder;
use Symfony\Component\HttpFoundation\Request as Request;

interface Pager
{
    /**
     * Sets the default router for route generation
     */
    public function setRouter($router);

    /**
     *
     * Sets the query to paginate. If a custom query builder should be used to
     * get the count, supply it as the second argument. If you don't do this, the
     * implementation should clone your main query builder and modify it to
     * query for a row count as best it can. In some implementations this won't
     * work with all queries (notably DoctrineORM when there are custom select aliases
     * required for the query to work). If the count query builder results in a query
     * that returns nothing, 0 is assumed.
     *
     * @param \Doctrine\ORM\QueryBuilder
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder, QueryBuilder $countQueryBuilder = null);

    /**
     * 
     * Takes a route name and a set of route parameters
     * and uses this as a basis for generating pagination
     * links
     *
     * @param string
     * @param array
     */
    public function setRoute($routeName, $routeParams = array());

    /**
     * 
     * Set the maximum number of results to display per page
     *
     * @param int
     */
    public function setMaxPerPage($maxPerPage = 20);

    public function setCurrentPage($pageNumber = 1);

    public function getCurrentPage();

    public function getNumResults();

    public function getResults();

    public function getMaxPages();

    public function getPageLink($pageNumber = 1);

    public function getFirstPageLink();

    public function getPreviousPageLink();

    public function getLastPageLink();

    public function getNextPageLink();

    /**
     * Should return true if the page number is <= 1
     */
    public function isFirstPage();

    /**
     * Should return true if the page number is >= getMaxPages
     */
    public function isLastPage();

    public function getPageLinks();

    /**
     * This method takes a Symfony\Component\HttpFoundation\Request. It will
     * set the current route for the pager as well as the current page.
     */
    public function bindRequest(Request $request);
}