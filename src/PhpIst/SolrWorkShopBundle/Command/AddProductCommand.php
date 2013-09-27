<?php
/**
 * Created by JetBrains PhpStorm.
 * User: volkanaltan
 * Date: 9/18/13
 * Time: 4:40 PM
 * To change this template use File | Settings | File Templates.
 */

namespace PhpIst\SolrWorkShopBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PhpIst\SolrWorkShopBundle\Entity\Product;

class AddProductCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('solr:add-product')
            ->setDescription('Add product')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $product = new Product;
        $product->setName('name');
        $product->setDescription('description');
        $product->setPrice(0.0);
        $product->setBoost(-100);

        /** @var \PhpIst\SolrWorkShopBundle\Entity\ProductRepository $productRepository */
        $productRepository = $this->getContainer()->get('php_ist.solr_work_shop.repository.product');
        $productRepository->save($product);

        $output->writeln('Ekleme başarılı');
    }
}