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

class ImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('solr:import')
            ->setDescription('Solr import')
            ->addArgument(
                'import_type',
                InputArgument::REQUIRED,
                'Please enter an import type full|partial'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $importType = $input->getArgument('import_type');
        $text = '';

        /** @var \PhpIst\SolrWorkShopBundle\Service\Importer $importer */
        $importer = $this->getContainer()->get('php_ist.solr_work_shop.service.importer');
        $result = $importer->fullImport();

        if ($result['isDelete']) {
            $text .= 'Önceki solr datası silindi...' . PHP_EOL;
        }

        $text .= 'Gönderilen döküman sayısı :' . $result['count'] . PHP_EOL;

        $output->writeln($text);
    }
}