<?php

namespace PhpIst\SolrWorkShopBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($category = '')
    {
        return $this->render('PhpIstSolrWorkShopBundle:Default:index.html.twig', array('name' => $category));
    }
}
