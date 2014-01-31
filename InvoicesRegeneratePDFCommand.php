<?php
namespace BVD\PetroleumBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BVD\PetroleumBundle\Util\TimeZoneUtil;
use BVD\PetroleumBundle\T_chek\Enum\FuelCardTransaction\CardOriginType;
use BVD\PetroleumBundle\T_chek\Enum\FuelCardTransaction\RecordType; 

class InvoicesRecalculateTotalsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setName('invoices:recalculate_totals')
        ->setDescription('Recalculate invoices totals')
        ->addArgument(
                        'start_date',
                        InputArgument::OPTIONAL,
                        'Start timestamp'
        )
        ->addArgument(
                        'end_date',
                        InputArgument::OPTIONAL,
                        'End timestamp'
        )
        ;
    }

    /**
     * http://www.doctrine-project.org/blog/doctrine2-batch-processing.html
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
            $em                  = $this->getContainer()->get('doctrine')->getManager();
            $invoice_serv = $this->getContainer()->get('invoice_service');
            
            $params = array();
            $params['start_date'] = $input->getArgument('start_date') ? new \DateTime($input->getArgument('start_date')) : new \DateTime('2013-06-26');
            $params['end_date']   = $input->getArgument('end_date')   ? new \DateTime($input->getArgument('end_date'))   : new \DateTime("now");
            $params['start_date']->setTimezone(new \DateTimeZone("UTC"));
            $params['end_date']  ->setTimezone(new \DateTimeZone("UTC"));
            
            $queryBuilder = $em->createQueryBuilder()
                ->select('i,t,mt')
                ->from('BVDPetroleumBundle:Invoice','i')
		->leftJoin("i.transactions", "t")
		->leftJoin("i.manual_transactions", "mt")
                //->andWhere('i.issue_date >= :start_date')->setParameter('start_date', $params['start_date'], 'utcdatetime')
                //->andWhere('i.issue_date  <= :end_date')  ->setParameter('end_date',   $params['end_date'],   'utcdatetime');
                //->andWhere("t.price_per_unit_import is not null and t.bvd_company_custom_ppu_set is NULL and t.ppu_import_fuel_ppu != t.fuel_ppu");
		//price_per_unit_import_id is not null and bvd_company_custom_ppu_set is NULL and ppu_import_fuel_ppu != fuel_ppu
		//->andWhere('t.cash > 0 OR t.dash_cash > 0 OR t.record_type <> :fuel_card')->setParameter('fuel_card', RecordType::FuelCard);
		->andWhere("i.id IN (4842)");
            
            $invoices = $queryBuilder->getQuery()->getResult();
            
            $this->getContainer()->get('logger')->info("Recalculating invoices.");

            $i= 0;
               foreach($invoices as $invoice){ 
		$invoice_serv->exportInvoiceToPDF($invoice);
                $i++;
                $this->getContainer()->get('logger')->notice("Invoices recalculated: " . $i);
            }
    }
}
