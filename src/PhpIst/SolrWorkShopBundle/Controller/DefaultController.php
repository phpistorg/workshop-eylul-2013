<?php

namespace PhpIst\SolrWorkShopBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    public function indexAction($category = '')
    {
        return $this->render('PhpIstSolrWorkShopBundle:Default:index.html.twig', array('name' => $category));
    }

    public function suggestAction($q)
    {
        /** @var \PhpIst\SolrWorkShopBundle\Service\Searcher $searchService */
        $searchService = $this->get('php_ist.solr_work_shop.service.searcher');
        $return = $searchService->ge
        tSuggestions($q);

        $response = new JsonResponse($return);

        return $response;
    }
}
