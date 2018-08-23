<?php

namespace App\Controller\Admin;

use App\Entity\Configuration;
use App\Entity\Currency;
use App\Entity\Notification;
use App\Entity\Profit;
use App\Entity\Transaction;
use App\Entity\User;
use App\Form\Transaction\UploadCsvType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ImportController extends Controller
{
    const NOT_FOUND = 'Не найден';
    const CURRENCY = 'USD';

    /**
     * @param Request $request
     * @param \Swift_Mailer $mailer
     * @return Response
     */
    public function preview(Request $request, \Swift_Mailer $mailer)
    {
        $form = $this->createForm(UploadCsvType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data=$form->getData();
            /** @var UploadedFile $file */
            $file = $data['file'];
            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            // moves the file to the directory where brochures are stored
            $file->move($this->getParameter('docs_directory'), $fileName);
            $countErrors = 0;
            $returnRate=0;
            $rows = $this->_prepareImportRows($fileName, $countErrors, $returnRate);

            return $this->render('admin/import/preview.html.twig', array(
                'rows' => $rows,
                'countErrors'=>$countErrors,
                'returnRate'=>$returnRate,
                'date'=>$data['date'],

            ));
        }
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function go(Request $request)
    {
        $data=$request->request->all();
        $userRepo = $this->getDoctrine()->getManager()->getRepository(User::class);
        foreach ($data['phone'] as $phone) {
            $user = $userRepo->findOneByUsername($phone);
            if ($user instanceof User){
                $transactionOutRefferal=null;
                $transactionInInvest=null;
                $transactionInRefferal=null;
                $this->get('doctrine')->getEntityManager()->beginTransaction();
                if (($referral = $this->get(\App\Service\Transaction::class)->getSumUserByAccount($user, \App\Service\Transaction::ACCOUNT_REFFERAL))>0) {
                    $transactionOutRefferal = $this->get(\App\Service\Transaction::class)->create(
                        $user,
                        \App\Service\Transaction::ACCOUNT_REFFERAL,
                        \App\Service\Transaction::DIRECTION_OUT,
                        $referral
                    );
                }
                if (($referral+$data['investSum'][$phone])>0){
                    $transactionInInvest = $this->get(\App\Service\Transaction::class)->create(
                        $user,
                        \App\Service\Transaction::ACCOUNT_INVEST,
                        \App\Service\Transaction::DIRECTION_IN,
                        ($referral+$data['investSum'][$phone])
                    );
                }
                if ($data['referralSum'][$phone]>0){
                    $transactionInRefferal = $this->get(\App\Service\Transaction::class)->create(
                        $user,
                        \App\Service\Transaction::ACCOUNT_REFFERAL,
                        \App\Service\Transaction::DIRECTION_IN,
                        $data['referralSum'][$phone]
                    );
                }
                try{
                    if ($transactionOutRefferal){
                        $this->get('doctrine')->getManager()->persist($transactionOutRefferal);
                    }
                    if ($transactionInInvest){
                        $this->get('doctrine')->getManager()->persist($transactionInInvest);
                    }
                    if ($transactionInRefferal){
                        $this->get('doctrine')->getManager()->persist($transactionInRefferal);
                    }
                    $this->get('doctrine')->getManager()->flush();
                    $this->get('doctrine')->getEntityManager()->commit();
                } catch (\Exception $exception){
                    $this->get('doctrine')->getEntityManager()->rollback();
                }
            }
        }
        $date=date('Y-m-d', strtotime($data['date']));
        $start = new \DateTime($date);
        $end = new \DateTime($date);
        $start->modify('first day of this month');
        $end->modify('first day of next month');
        $profit=$this->getDoctrine()->getRepository(Profit::class)->getInThisMonth($start->format('Y-m-d'), $end->format('Y-m-d'));
        if ($profit instanceof Profit){
            $profit->setPercent($data['returnRate']);
        } else {
            $profit=new Profit();
            $profit->setPercent($data['returnRate']);
            $profit->setCreatedAt($start);
        }
        $this->getDoctrine()->getManager()->persist($profit);
        /** @var Configuration $configProfit */
        $configProfit=$this->getDoctrine()->getRepository(Configuration::class)->findOneByName('returnRate');
        $configProfit->setValue($data['returnRate']);
        $this->getDoctrine()->getManager()->persist($configProfit);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('user_admin_settings');
    }

    private function _prepareImportRows($fileName, &$countErrors, &$returnRate) {
        $file = fopen($this->_getFilePath($fileName), 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            $rows[] = $line;
        }
        fclose($file);
        array_shift($rows);
        $returnRate=(float)str_replace(',', '.', $rows[0][1]);
        $userRepo = $this->getDoctrine()->getManager()->getRepository(User::class);
        $data = [];
        foreach ($rows as $row) {
            $user = $userRepo->findOneByUsername($row[0]);
            if ($user instanceof User){
                $invest=$this->get(\App\Service\Transaction::class)->getSumUserByAccount($user, \App\Service\Transaction::ACCOUNT_INVEST);
                $referral=$this->get(\App\Service\Transaction::class)->getSumUserByAccount($user, \App\Service\Transaction::ACCOUNT_REFFERAL);
                $investSum=(float)str_replace(',', '.', $row[3]);
                $investNew=(float)$invest+(float)$referral+$investSum;
                $referralNew=(float)str_replace(',', '.', $row[4]);
                $data[$row[0]] = [
                    'error'=>false,
                    'name'=>$user->getName(),
                    'surname'=>$user->getSurname(),
                    'profitRate'=>$user->getProfitRate().'%',
                    'profitRateNew'=>$row[2],
                    'invest'=>$invest,
                    'investSum'=>$investSum,
                    'investNew'=>$investNew,
                    'referral'=>$referral,
                    'referralNew'=>$referralNew,
                ];
            } else {
                $countErrors++;
                $data[$row[0]] = [
                    'error'=>true,
                ];
            }
        }

        return $data;
    }

    private function _getFilePath($fileName) {
        return $this->getParameter('docs_directory') . '/' . $fileName;
    }
}
