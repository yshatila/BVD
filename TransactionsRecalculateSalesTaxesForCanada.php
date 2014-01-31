<?php
namespace BVD\PetroleumBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BVD\PetroleumBundle\Util\TimeZoneUtil;
use BVD\PetroleumBundle\T_chek\Enum\FuelCardTransaction\CardOriginType;
use BVD\PetroleumBundle\Entity\Country;

class TransactionsRecalculateTotalsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setName('transactions:recalculate_totals')
        ->setDescription('Recalculate transactions totals')
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
            
            $params = array();
            $params['start_date'] = $input->getArgument('start_date') ? new \DateTime($input->getArgument('start_date')) : new \DateTime('2013-01-01');
            $params['end_date']   = $input->getArgument('end_date')   ? new \DateTime($input->getArgument('end_date'))   : new \DateTime("now");
            $params['start_date']->setTimezone(new \DateTimeZone("UTC"));
            $params['end_date']  ->setTimezone(new \DateTimeZone("UTC"));
            
		$transactions_repo = $em->getRepository('BVDPetroleumBundle:FuelCardTransaction');
		$logger = $this->getContainer()->get('logger');

		$queryBuilder = $em->createQueryBuilder()
		->select('t,c')
		->from('BVDPetroleumBundle:FuelCardTransaction','t')
		->leftJoin("t.country", "c")
		->where('c.abbreviation = :country')
		->andWhere('t.source = :source')
		->andWhere('t.id >= 100000 and t.id <110000')
		->setParameter("country", Country::Canada)
		->setParameter("source", CardOriginType::IOL_KeyToTheHighway);


		$transactions = $queryBuilder->getQuery()->getResult();

		$i = 0;
		foreach($transactions as $transaction){
			$transactions_repo->calculateSalesTaxesForCanadianIOLTTransTransaction($transaction);
			$em->persist($transaction);
			$i++;
			if($i%30 == 0){
				$logger->notice("Transactions recalculated: " . $i);
				$em->flush();
			}
		}
		$em->flush();

    }
}
