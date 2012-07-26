<?php

namespace PunkAve\PagerBundle\DoctrineORM;

use Doctrine\ORM\QueryBuilder as QueryBuilder;
use Symfony\Component\HttpFoundation\Request as Request;
use PunkAve\PagerBundle\Interfaces\Pager as PagerInterface;

class Pager implements PagerInterface {

	/**
	 * 
	 *
	 * @var int
	 */
	protected $numResults = null;

	/**
	 * 
	 *
	 * @var array
	 */
	protected $results = null;

	/**
	 * 
	 * @var int
	 */
	protected $maxPerPage = 20;

	/**
	 * 
	 *
	 * @var int
	 */
	protected $pageNumber = 1;

	/**
	 * Sets the default router for route generation
	 */
	public function setRouter($router)
	{
		$this->router = $router;
	}

	/**
	 *
	 * Sets the query to paginate
	 *
     * If you do not pass a $countQueryBuilder the main query builder is cloned and the select() clause is 
     * changed to a COUNT DISTINCT of the id of the root alias, which works for most cases.
     * If you have custom aliases in your select clause that are necessary for other clauses of your
     * query to work you'll need to supply your own count query builder, which should return a
     * single scalar containing the count. If the count query returns nothing 0 is assumed.
     *
	 * @param \Doctrine\ORM\QueryBuilder
	 */
	public function setQueryBuilder(QueryBuilder $queryBuilder, QueryBuilder $countQueryBuilder = null)
	{
		$this->queryBuilder = $queryBuilder;
		$this->countQueryBuilder = $countQueryBuilder;
	}

	/**
	 * 
	 * Takes a route name and a set of route parameters
	 * and uses this as a basis for generating pagination
	 * links
	 *
	 * @param string
	 * @param array
	 */
	public function setRoute($routeName, $routeParams = array())
	{
		$this->routeName = $routeName;
		$this->routeParams = $routeParams;
	}

	/**
	 * 
	 * Set the maximum number of results to display per page
	 *
	 * @param int
	 */
	public function setMaxPerPage($maxPerPage = 20)
	{
		$this->maxPerPage = $maxPerPage;
	}

	public function setCurrentPage($pageNumber = 1)
	{
		$this->pageNumber = $pageNumber;
	}

	public function getCurrentPage()
	{
		return $this->pageNumber;
	}

	public function getNumResults()
	{
		if (is_null($this->numResults)) {
			$this->computeResults();
		}

		return $this->numResults;
	}

	/**
	 * Call this before getNumResults() and getResults() if our standard method
	 * of computing the total number of results is incompatible
	 * with your query (queries using HAVING, or aliases in ORDER BY,
	 * will be incompatible).
	 */
	public function setNumResults($n)
	{
		$this->numResults = $n;
	}

	public function getResults()
	{
		if (is_null($this->results))
		{
			$this->computeResults();
		}
		
		return $this->results;
	}

	public function getMaxPages()
	{
		$numResults = $this->getNumResults();

		return ceil($numResults / $this->maxPerPage);
	}

	public function getPageLink($pageNumber = 1)
	{
		$routeParams = $this->routeParams;
		$routeParams['page'] = $pageNumber;

		return $this->router->generate($this->routeName, $routeParams);
	}

	public function getFirstPageLink()
	{
		return $this->getPageLink(1);
	}

	public function getPreviousPageLink()
	{
		$currentPage = $this->getCurrentPage();

		if ($currentPage > 1)
		{
			return $this->getPageLink($currentPage - 1);
		}

		return null;
	}

	public function getLastPageLink()
	{
		$lastPage = $this->getMaxPages(); 

		return $this->getPageLink($lastPage);
	}

	public function getNextPageLink()
	{
		$currentPage = $this->getCurrentPage();

		if ($currentPage < $this->getMaxPages())
		{
			return $this->getPageLink($currentPage + 1);
		}

		return null;
	}

	/**
	 * Should return true if the page number is <= 1
	 */
	public function isFirstPage()
	{
		return ($this->getCurrentPage() <= 1);
	}

	/**
	 * Should return true if the page number is >= getMaxPages
	 */
	public function isLastPage()
	{
		return ($this->getCurrentPage() >= $this->getMaxPages());
	}

	/**
	 *
	 * Returns an array of links for pagination
	 *
	 * @return array
	 */
	public function getPageLinks()
	{
		// get first page link
		// get previous page link

		// get 2 previous page numbers
		// get current page
		// get 2 next page numbers

		// get next page link
		// get last page link

		$links = array();

		$links['first'] = array(
			'href' => $this->getFirstPageLink(),
			'active' => $this->isFirstPage()
		);

		$links['previous'] = array(
			'href' => $this->getPreviousPageLink(),
			'active' => $this->isFirstPage()
		);

		$adjacentLinks = array();
		foreach ($this->getAdjacentPageNumbers() as $pageNumber)
		{
			$adjacentLinks["$pageNumber"] = array(
				'href' => $this->getPageLink($pageNumber),
				'active' => ($pageNumber == $this->getCurrentPage())
			);
		}
		$links['adjacent'] = $adjacentLinks;

		$links['next'] = array(
			'href' => $this->getNextPageLink(),
			'active' => $this->isLastPage()
		);

		$links['last'] = array(
			'href' => $this->getLastPageLink(),
			'active' => $this->isLastPage()
		);

		return $links;
	}

	/**
	 * 
	 * Return an array of two previous and two next page numbers
	 *
	 * @return array
	 */
	public function getAdjacentPageNumbers()
	{
		$pageNumbers = array();

		$n = 1;
		$i = $this->getCurrentPage() - 2;

		$diff = $this->getMaxPages() - $this->getCurrentPage(); // +1 accounts for 0-indexing
		if ($diff < 2)
		{
			$i -= 2 - $diff;
		}


		while (($n <= 5) && ($i <= $this->getMaxPages()))
		{
			if ($i >= 1)
			{
				$pageNumbers[] = $i;
				$n++;
			}

			$i++;
		}
		
		return $pageNumbers;
	}

	/**
	 * This method takes a Symfony\Component\HttpFoundation\Request. It will
	 * set the current route for the pager as well as the current page.
	 */
	public function bindRequest(Request $request)
	{
		$this->setCurrentPage(($request->query->has('page'))? $request->query->get('page') : 1);
		$this->setRoute($request->get('_route'), $request->query->all());
	}

	/**
	 * This method will perform the query and cache the results
	 */
	protected function computeResults()
	{
		$pageNumber = $this->getCurrentPage();
		if ($pageNumber < 1)
		{
			// Avoid SQL errors
			$pageNumber = 1;
		}

		$paginatedQb = clone $this->queryBuilder;
		$paginatedQb->setMaxResults($this->maxPerPage);
		$paginatedQb->setFirstResult(($pageNumber - 1) * $this->maxPerPage); // -1 accounts for 1-indexing
		$this->results = $paginatedQb->getQuery()->getResult();

		/**
		 * Check whether setNumResults was used to avoid this method of counting rows
		 */
		if (is_null($this->numResults))
		{
			$countQb = $this->countQueryBuilder;
			if (!$countQb)
			{
				// Efficiently compute the count without fetching everything. Works great unless you
				// have custom aliases without which your query bombs. A Doctrine 2.2 paginator would be better 
				// since that uses a subquery and doesn't get tripped up by removing aliases in the main query
				$countQb = clone $this->queryBuilder;
				$countQb->select('COUNT(' . $countQb->getRootAlias() . '.id) AS count_rows');
			}
			// If there are no matches and GROUP BY is present, getSingleScalarResult will throw an exception
			// because mysql does not return a count. Don't have a cow in that situation. This doesn't
			// happen in the absence of GROUP BY. Interesting, no?
			try
			{
				$this->numResults = $countQb->getQuery()->getSingleScalarResult();
			} catch (\Doctrine\ORM\NoResultException $e)
			{
				// This happens when zero rows are returned
				$this->numResults = 0;
			}
		}

		if ($this->getCurrentPage() > $this->getMaxPages())
		{
			$this->getCurrentPage = $this->getMaxPages();
		}
	}
}
