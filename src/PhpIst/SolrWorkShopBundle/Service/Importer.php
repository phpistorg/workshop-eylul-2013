<?php
/**
 * Created by JetBrains PhpStorm.
 * User: volkanaltan
 * Date: 9/18/13
 * Time: 3:27 PM
 * To change this template use File | Settings | File Templates.
 */

namespace PhpIst\SolrWorkShopBundle\Service;

use Solarium\QueryType\Update\Query\Document\Document;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Importer
{
    /** @var  ContainerInterface */
    protected $container;

    /** @var \Solarium\Core\Client\Client  */
    protected $solrClient;

    protected $chunkSize = 1000;

    public function __construct($container, $solrClient)
    {
        $this->container = $container;
        $this->solrClient = $solrClient;
    }

    public function fullImport()
    {
        $data     = $this->productImport();

        $this->buildSpellCheckIndexForSuggest();
        $this->buildSpellCheckIndexForBrowse();

        return array(
            'count' => $data['count'],
            'isDelete' => $data['isDelete']
        );
    }

    protected function productImport()
    {
        /** @var \PhpIst\SolrWorkShopBundle\Entity\ProductRepository $productRepository */
        $productRepository = $this->getContainer()->get('php_ist.solr_work_shop.repository.product');
        $productPrepare = $productRepository->prepareProductForSolr();

        $isDelete = $this->deleteAllData();
        foreach (array_chunk($productPrepare, $this->chunkSize) as $productChunk) {
            $this->sendToSolr($productChunk);
        }

        return array(
            'count' => count($productPrepare),
            'isDelete' => $isDelete
        );
    }

    protected function sendToSolr($productList)
    {
        $products = $this->productMapping($productList);

        $update = $this->solrClient->createUpdate();
        foreach ($products as $product) {
            /** @var Document $doc */
            $doc = $update->createDocument($product);
            $update->addDocument($doc);
        }

        $update->addCommit();

        $this->solrClient->update($update);
    }

    public function productMapping($productList)
    {
        /** @var \PhpIst\SolrWorkShopBundle\Entity\Product $product */
        foreach ($productList as &$product) {
            $price = $product->getPrice() ? $product->getPrice() : 0.00;
            $product = array(
                'id' => $product->getId(),
                'name' => $product->getName(),
                'category' => explode('>', $product->getCategoryTree()),
                'price' => $price,
                'description' => $product->getDescription(),
                'color' => explode(',', $product->getColor()),
                'created_at' => $product->getCreatedAt(),
            );
        }

        return $productList;
    }

    protected function buildSpellCheckIndexForSuggest()
    {
        $query = $this->solrClient->createSelect();
        $query->setHandler('suggest');
        $query->setQuery('q');
        $query->setRows(0);

        $spellCheck = $query->getSpellcheck();
        $spellCheck->setBuild(true);
        $this->solrClient->select($query);
    }

    protected function buildSpellCheckIndexForBrowse()
    {
        $query = $this->solrClient->createSelect();
        $query->setHandler('browse');
        $query->setQuery('q');
        $query->setRows(0);

        $spellCheck = $query->getSpellcheck();
        $spellCheck->setBuild(true);
        $this->solrClient->select($query);
    }

    public function deleteAllData()
    {
        $update = $this->solrClient->createUpdate();
        $update->addDeleteQuery('*');
        $update->addCommit();

        $this->solrClient->update($update);

        return true;
    }

    /**
     * @param mixed $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param mixed $solrClient
     */
    public function setSolrClient($solrClient)
    {
        $this->solrClient = $solrClient;
    }

    /**
     * @return mixed
     */
    public function getSolrClient()
    {
        return $this->solrClient;
    }
}