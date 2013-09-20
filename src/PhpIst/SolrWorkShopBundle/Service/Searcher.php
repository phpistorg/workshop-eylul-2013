<?php
/**
 * Created by JetBrains PhpStorm.
 * User: volkanaltan
 * Date: 9/20/13
 * Time: 9:49 PM
 * To change this template use File | Settings | File Templates.
 */

namespace PhpIst\SolrWorkShopBundle\Service;

use Solarium\Core\Query\Result\Result;
use Solarium\QueryType\Select\Query\Query;

class Searcher
{
    const LIMIT = 12;
    /** @var  ContainerInterface */
    private $container;

    /** @var \Solarium\Core\Client\Client */
    protected $solrClient;

    protected $sort = 'score';

    protected $sortDirection = 'desc';

    protected $defaultScoring = 'sum(product(0.6,score_product_boost),product(0.05,score_random))';

    protected $isIndent = false;

    public function __construct($container, $solrClient)
    {
        $this->setContainer($container);
        $this->setSolrClient($solrClient);
    }

    /**
     * @param string $q
     * @param array $criteria
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function search($q = '*', $criteria = array(), $offset = 0, $limit = self::LIMIT)
    {

        $query = $this->solrClient->createSelect();
        $query->setHandler('browse')->setQuery($q)->setStart($offset)->setRows($limit);

        //$query->createFilterQuery('de');

        if ($this->isIndent) {
            $query->addParam('indent','on');
        }

        if ($q == '*') {
            $query->getEDisMax();
            $query->setSorts(array($this->defaultScoring => $this->sortDirection));
        } else {
            $dismax = $query->getDisMax();
            $query->setSorts(array($this->sort => $this->sortDirection));
        }

        $this->addSearchCriteria($query, $criteria);
        $this->setFacets($query);

        /** @var Result $resultset */
        $resultset = $this->solrClient->select($query);

        $result['resultset'] = $resultset;
        $result['paginationData'] = $this->getPaginationData($result['resultset'], $offset, $limit);
        $result['facets'] = $this->getFacets($resultset);

        return $result;
    }


    public function getSuggestions($q)
    {
        // get a suggester query instance
        $query = $this->solrClient->createSuggester();
        $query->setHandler('suggest');
        $query->setQuery($q);

        $resultSet = $this->solrClient->suggester($query);

        $collation = $resultSet->getCollation();
        $result = array(
            'resultset' => null
        );

        $i = 0;
        foreach ($resultSet as $term => $termResult) {
            $i++;
        }

        if ($collation && is_array($collation[0])) {
            foreach ($collation as $key => $values) {
                $result['resultset'][] = $values[1];
            }
        } else if(is_array($collation)) {
            $result['resultset'][] = $collation[1];
        }

        return $result;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param \Solarium\Core\Client\Client $solrClient
     */
    public function setSolrClient($solrClient)
    {
        $this->solrClient = $solrClient;
    }

    /**
     * @return \Solarium\Core\Client\Client
     */
    public function getSolrClient()
    {
        return $this->solrClient;
    }

    protected function addSearchCriteria(Query $query, $criteria)
    {
        foreach ($criteria as $key => $value) {
            if (is_array($value)){
                $value = implode('|', $value);
            }
            $values = explode("|", $value);
            $filterArr = [];

            foreach($values as $v) {
                $filterArr[] = "(" . $key . ':' . $v . ')';
            }

            $filterStr = "(" . implode(" OR ", $filterArr) . ")";

            $query->addFilterQuery(array('key' => $key, 'query' => $filterStr));
        }
    }

    protected function getAvailableFacets()
    {
        $facet = array(
            'id' => array(
                'param' => 'product-id',
                'facet_search' => 'id',
                'facet_field' => false
            ),
            'name' => array(
                'param' => 'name',
                'facet_search' => 'name',
                'facet_field' => false
            ),
            'category' => array(
                'param' => 'category-name',
                'facet_search' => 'category',
                'facet_field' => true
            ),
            'price' => array(
                'param' => 'price',
                'facet_search' => 'price',
                'facet_field' => true,
                'ranges' => array(
                    'max' => 5000,
                ),
                'stats' => true
            ),
            'description' => array(
                'param' => 'description',
                'facet_search' => 'description',
                'facet_field' => false
            ),
            'color' => array(
                'param' => 'color',
                'facet_search' => 'color',
                'facet_field' => true
            ),
        );
    }

    protected function setFacets(Query $query)
    {
        $facets = $this->getAvailableFacets();

        $facetSet = $query->getFacetSet();
        $stats = false;
        foreach ($facets as $facet) {
            if ($facet['facet_field'] == true) {
                /** TODO Fix it */
                if (isset($facet['ranges'])) {
                    $facetSet->createFacetRange($facet['param'])->setField($facet['facet_search'])->setStart(0)
                        ->setGap(100)
                        ->setEnd($facet['ranges']['max']);
                    if ($facet['stats']) {
                        $stats = $query->getStats();
                        $stats->createField($facet['facet_search'])->addFacet($facet['facet_search']);
                    }
                } else {
                    $facetSet->createFacetField($facet['param'])->setField($facet['facet_search']);
                }
            }
        }
    }


    protected function getFacets($resultset)
    {
        $resultsetData = $resultset->getData();
        $facets = array();
        foreach ($resultsetData['facet_counts'] as $value) {
            foreach ($value as $facetKey => $facet) {
                if (sizeof($facet) > 0) {
                    $facets[$facetKey] = $facet;
                }
            }
        }
        return $facets;
    }

    public function getPaginationData(Result $resultSet, $offset, $limit)
    {
        $paginationData = array(
            'totalPage' => 0,
            'currentPage' => 0,
            'totalCount' => 0
        );
        $searchResult = json_decode($resultSet->getResponse()->getBody());
        $totalRows = $searchResult->response->numFound;
        if ($totalRows > 0) {
            $paginationData['totalPage'] = ceil($totalRows / $limit);
            $paginationData['currentPage'] = ($limit + $offset) / $limit;
            $paginationData['totalCount'] = $totalRows;
        }

        return $paginationData;
    }

    /**
     * @param string|void $defaultScoring
     */
    public function setDefaultScoring($defaultScoring)
    {
        $this->defaultScoring = $defaultScoring;
    }

    /**
     * @return string|void
     */
    public function getDefaultScoring()
    {
        return $this->defaultScoring;
    }

    /**
     * @param string $sort
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param string $sortDirection
     */
    public function setSortDirection($sortDirection)
    {
        $this->sortDirection = $sortDirection;
    }

    /**
     * @return string
     */
    public function getSortDirection()
    {
        return $this->sortDirection;
    }

    /**
     * @param boolean $isIndent
     */
    public function setIsIndent($isIndent)
    {
        $this->isIndent = $isIndent;
    }

    /**
     * @return boolean
     */
    public function getIsIndent()
    {
        return $this->isIndent;
    }
}
