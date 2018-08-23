<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Document;
use App\Entity\Profit;
use App\Entity\Transaction;
use App\Entity\User;
use App\Form\User\MessageType;
use App\Form\User\PartnerSearchType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserController extends Controller
{
    public function contacts(Request $request, \Swift_Mailer $mailer)
    {
        $form=$this->createForm(MessageType::class);
        $form->handleRequest($request);
        $configurationRepository = $this->getDoctrine()->getRepository('App:Configuration');
        if(!empty($configurationRepository->findOneByName('adminEmail'))){
            $adminEmail = $configurationRepository->findOneByName('adminEmail')->getValue();
        } else {
            $adminEmail = $this->getParameter('admin_email');
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $data=$form->getData();
            $message = (new \Swift_Message('Письмо от пользователя'))
                ->setFrom($this->getParameter('send_from'))
                ->setTo($adminEmail)
                ->setBody(
                    $this->renderView(
                        'email/sendContactForm.html.twig',[
                        'user' => $this->getUser(),
                        'subject' => $data['subject'],
                        'message' => $data['message']
                    ]),
                    'text/html'
                )
            ;

            $mimeTypes = [
                "application/pdf",
                "image/jpeg",
                "image/png",
                "image/pjpeg",
                "application/msword",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "application/vnd.ms-excel",
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                "application/vnd.ms-powerpoint",
                "application/vnd.openxmlformats-officedocument.presentationml.presentation"
            ];

            if (null !== $data['file']){

                foreach ($data['file'] as $file) {

                    if (in_array($file->getClientMimeType(), $mimeTypes)){
                        $message->attach(\Swift_Attachment::fromPath($file)->setFilename($file->getClientOriginalName()));
                    }
                }
            }

            if ($mailer->send($message)){
                return $this->redirectToRoute('user_contacts', ['send'=>'success']);
            }
        }

        return $this->render('user/contacts.html.twig', [
            'form'=>$form->createView(),
            'adminEmail'=>$adminEmail
        ]);
    }

    public function partner(Request $request)
    {
        $user = $this->getUser();
        $transactionStatusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');
        $incomeRepository = $this->getDoctrine()->getRepository('App:Transaction');
        $status = $transactionStatusRepository->find(2);
        $filters = $request->query->get('partner_search');
        $invitedIncomes = $incomeRepository->getInvitedIncomes($user, $status, $filters);
        $searchForm = $this->createForm(PartnerSearchType::class);

        $transactionRepository = $this->getDoctrine()->getRepository('App:Transaction');
        $accountRepository = $this->getDoctrine()->getRepository('App:Account');
        $transactionStatusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');

        return $this->render('user/partner.html.twig', [
            'invitedIncomes' => $invitedIncomes,
            'searchForm' => $searchForm->createView(),
            'account'=>$accountRepository->find(3),
            'status'=>$transactionStatusRepository->find(2),
            'transactionRepo'=>$transactionRepository

        ]);
    }


    public function briefcase()
    {
        return $this->render('user/briefcase.html.twig', [
            'invitedIncomes' => ''
        ]);
    }

    public function statistics(Request $request, AuthorizationCheckerInterface $authChecker)
    {
        $series = [];
        $maxArray = [0];
        $months = [
            1 => 'Январь' ,
            'Февраль' ,
            'Март' ,
            'Апрель' ,
            'Май' ,
            'Июнь' ,
            'Июль' ,
            'Август' ,
            'Сентябрь' ,
            'Октябрь' ,
            'Ноябрь' ,
            'Декабрь' ];

        if (!empty($request->query->get('period'))
            && $request->query->get('period') == 6
            && $request->query->get('period') == 12) {
            $limit = $request->query->get('period');
        }  else {
            $limit = null;
        }
        $profitRepository = $this->getDoctrine()->getRepository('App:Profit');
        $profitData = $profitRepository->findBy([],['createdAt' => 'DESC'], $limit);

        if (!empty($profitData)){
            $total = null;
            $profitData = array_reverse($profitData);

            foreach ($profitData as $key => $item) {

//                if ($request->query->get('period') == 'increase'){
//                    $total = $total + $item->getPercent();
//                    $series[] = $total;
//                } else if($request->query->get('period') == '2018') {
//
//                    if ($item->getCreatedAt()->format('n') == 1) {
//                        $total = $item->getPercent();
//                    } else if ($item->getCreatedAt()->format('n') == 12) {
//                        $maxArray[] = $total + $item->getPercent();
//                    } else {
//                        $total = $total + $item->getPercent();
//                    }
//                    $series[] = $total;
//                } else {
//                    $total = $item->getPercent();
//                    $series[] = $total;
//                }
                $total = round($total + $item->getPercent(), 2);
                $series[] = $total;
//                if ((count($profitData)-1) == $key){
//                    $series[] = $total;
//                }
            }
            $maxValue = $series[count($series)-1]*1.1;

//            if ($request->query->get('period') == 'increase'){
//                $maxValue = $series[count($series)-1];
//            } else if($request->query->get('period') == '2018'){
//                $maxValue = max($maxArray);
//            } else {
//                $maxValue = max($series);
//            }
        }

        if (!empty($profitData)){

            foreach ($profitData as $item) {
                $month = $item->getCreatedAt()->format('n');
                $period[] = $months[$month] . ' ' . $item->getCreatedAt()->format('Y');
            }
        }

//        if($request->query->get('period') == 12){
//
//            if (count($profitData) < 12 ){
//                $monthQty = count($profitData);
//            } else {
//                $monthQty = 12;
//            }
//        } else if($request->query->get('period') == 6) {
//            if (count($profitData) < 6 ){
//                $monthQty = count($profitData);
//            } else {
//                $monthQty = 6;
//            }
//        }
//
//        if ($request->query->get('period') == 12){
//            $month = time();
//
//            for ($i = 1; $i <= $monthQty; $i++) {
//                $month = strtotime('this month', $month);
//                $monthArray[] = $months[date( 'n', $month )] . ' ' . date('Y', $month);
//                $month = strtotime('last month', $month);
//            }
//            $period = array_reverse($monthArray);
//
//        } else if($request->query->get('period') == 6) {
//            $month = time();
//
//            for ($i = 1; $i <= $monthQty; $i++) {
//                $month = strtotime('this month', $month);
//                $monthArray[] = $months[date( 'n', $month )] . ' ' . date('Y', $month);
//                $month = strtotime('last month', $month);
//            }
//            $period = array_reverse($monthArray);
//        } else {
//
//            if (!empty($profitData)){
//
//                foreach ($profitData as $item) {
//                    $month = $item->getCreatedAt()->format('n');
//                    $period[] = $months[$month] . ' ' . $item->getCreatedAt()->format('Y');
//                }
//            }
//        }

        if (null !== $request->request->get('updatePercent')){
            $profitRepository = $this->getDoctrine()->getRepository('App:Profit');
            $entityManager = $this->getDoctrine()->getManager();
            $profit = $profitRepository->getProfitByMonthYear($request->request->get('month'), $request->request->get('year'));

            if (!empty($profit)){
                $profit->setPercent($request->request->get('percent'));
                $entityManager->merge($profit);
            } else {
                $profit = new Profit();

                if (strlen($request->request->get('month')) == 2){
                    $separator = $request->request->get('month');
                } else {
                    $separator = '0' . $request->request->get('month');
                }

                $profit->setPercent($request->request->get('percent'));
                $profit->setCreatedAt(new \DateTime($request->request->get('year') . '-' .  $separator . '-01'));
                $entityManager->persist($profit);
            }
            $entityManager->flush();

            return $this->redirectToRoute('user_statistics');
        }

        if (null !== $request->request->get('removePercent')){
            $profitRepository = $this->getDoctrine()->getRepository('App:Profit');
            $entityManager = $this->getDoctrine()->getManager();
            $profit = $profitRepository->getProfitByMonthYear($request->request->get('month'), $request->request->get('year'));

            if (!empty($profit)){
                $entityManager->remove($profit);
                $entityManager->flush();

                return $this->redirectToRoute('user_statistics');
            }
        }

        return $this->render('user/statistics.html.twig', [
            'period' => $period,
            'series' => $series,
            'months' => $months,
            'maxValue' => $maxValue,
            'lastValue' => end($profitData)
        ]);
    }

    public function sendShowSponsorRequest(Request $request, \Swift_Mailer $mailer)
    {
        $response = new Response('Content',
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
        $userRepository = $this->getDoctrine()->getRepository('App:User');
        $user = $userRepository->find($request->request->get('id'));

        if (!empty($user->getEmail())){
            $hash=md5(time());
            $user->setHashShowInvestor($hash);
            $user->setReceivedRequestShowInvestor(true);
            $this->get('doctrine')->getManager()->persist($user);
            $this->get('doctrine')->getManager()->flush();
            $configurationRepository = $this->getDoctrine()->getRepository('App:Configuration');
            if(!empty($configurationRepository->findOneByName('adminEmail'))){
                $adminEmail = $configurationRepository->findOneByName('adminEmail')->getValue();
            } else {
                $adminEmail = $this->getParameter('admin_email');
            }
            $message = (new \Swift_Message('Запрос данных'))
                ->setFrom($this->getParameter('send_from'))
                ->setTo([$user->getEmail(), $adminEmail])
                ->setBody(
                    $this->renderView(
                        'email/sendShowSponsorRequest.html.twig',[
                        'sponsor'=>$user->getParent(),
                        'hash'=>$hash
                    ]),
                    'text/html'
                )
            ;

            $mailer->send($message);
        }

        return $response->setContent('success');
    }

    /*public function sendInvoice(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer)
    {
        $response = new Response('Content',
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
        $user = $this->getUser();
        $bool = $passwordEncoder->isPasswordValid($user, $request->request->get('password'));

        if ($bool == true){
            $configurationRepository = $this->getDoctrine()->getRepository('App:Configuration');

            if(!empty($configurationRepository->findOneByName('adminEmail'))){
                $adminEmail = $configurationRepository->findOneByName('adminEmail')->getValue();
            } else {
                $adminEmail = $this->getParameter('admin_email');
            }
            $transaction = new Transaction();
            $accountRepository = $this->getDoctrine()->getRepository('App:Account');
            $currencyRepository = $this->getDoctrine()->getRepository('App:Currency');
            $statusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');

            $transaction->setUser($user);
            $transaction->setAccount($accountRepository->find(1));
            $transaction->setCurrency($currencyRepository->find(1));
            $transaction->setType('Транзакция пользователя');
            $transaction->setStatus($statusRepository->find(1));
            $transaction->setSum($request->request->get('sum'));
            $transaction->setDirection('in');

            $em = $this->getDoctrine()->getManager();
            $em->persist($transaction);
            $em->flush();

            $operationName="Заявка на пополнение личного счета";
            $message = (new \Swift_Message('Операция № '.$transaction->getId().' - '.$operationName.' - ('.
                $user->getName().' '.$user->getSurname().') - ('.$transaction->getCreatedAt()->format('d.m.Y H:i').')'))
                ->setFrom($this->getParameter('send_from'))
                ->setTo($adminEmail)
                ->setBody(
                    $this->renderView(
                        'email/balanceToAdmin.html.twig',[
                            'sum' => $request->request->get('sum'),
                            'operationName' => $operationName,
                            'transaction' => $transaction,
                            'currency'=>$transaction->getCurrency()->getName(),
                            'wallet'=>$request->request->get('wallet') ?: null,
                            'from'=>$request->request->get('wallet'),
                            'to'=>'Личный'

                        ]),
                    'text/html'
                )
            ;

            $mailer->send($message);

            if ($user->getEmail()){
                $message = (new \Swift_Message('Вы оставили заявку в системе WSI'))
                    ->setFrom($this->getParameter('send_from'))
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->renderView(
                            'email/balanceToUser.html.twig',[
                            'sum' => $request->request->get('sum'),
                            'currency'=>$transaction->getCurrency()->getName(),
                            'direction'=>'Перевод',
                            'from'=>$request->request->get('wallet'),
                            'to'=>'Личный'
                        ]),
                        'text/html'
                    )
                ;

                $mailer->send($message);
            }

            return $response->setContent('success');
        } else {
            return $response->setContent('pass_error');
        }
    }*/

    public function changeAvatar()
    {
        $extension = pathinfo($_FILES['avatar']['name'])['extension'];
        move_uploaded_file($_FILES['avatar']['tmp_name'], $this->get('kernel')->getRootDir()
            . '/../public/assets/avatars/' . $_POST['id'] . '.' . $extension);
        $response = new JsonResponse();
        $response->setData(json_encode($extension));
        //$response->setData(json_encode($extension));

        return $response;
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }

    public function getInvestors()
    {
        $userRepository = $this->getDoctrine()->getRepository('App:User');
        $users = $userRepository->getInvestorsJson();

        $response = new JsonResponse();
        $response->setData(['data' => $users]);

        return $response;
    }

    public function getInvestorInfo(Request $request)
    {
        $userRepository = $this->getDoctrine()->getRepository('App:User');
        $userInfo = $userRepository->findOneByUsername($request->request->get('username'));
        $transactionRepository = $this->getDoctrine()->getRepository('App:Transaction');
        $accountRepository = $this->getDoctrine()->getRepository('App:Account');
        $personalAccount = $accountRepository->findOneByName('Личный счет');
        $investAccount = $accountRepository->findOneByName('Инвестиционный счет');
        $referalAccount = $accountRepository->findOneByName('Реферальный счет');
        $transactionStatusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');
        $status = $transactionStatusRepository->find(2);
        $personalBalance = $transactionRepository->getTotalUserAccount($userInfo, $personalAccount, $status);
        $investBalance = $transactionRepository->getTotalUserAccount($userInfo, $investAccount, $status);
        $referalBalance = $transactionRepository->getTotalUserAccount($userInfo, $referalAccount, $status);

        $response = new JsonResponse();

        $response->setData([
            'wallet' => $userInfo->getBitcoinWallet(),
            'name' => $userInfo->getName(),
            'surname' => $userInfo->getSurname(),
            'personalAccount' => $personalBalance,
            'investAccount' => $investBalance,
            'referalAccount' => $referalBalance,
            'parent' => (!empty($userInfo->getParent()) ? $userInfo->getParent()->getUsername() : ''),
            'parentName' => (!empty($userInfo->getParent()) ? $userInfo->getParent()->getName() : ''),
            'parentSurname' => (!empty($userInfo->getParent()) ? $userInfo->getParent()->getSurname() : ''),
            'profitRate' => $userInfo->getProfitRate()
        ]);

        return $response;
    }


    /**
     * @param bool $active
     * @param string $hash
     * @return Response
     */
    public function switchShowSponsor(bool $active, string $hash, \Swift_Mailer $mailer)
    {
        /** @var User $user */
        $user=$this->getUser();
        if ($user->getHashShowInvestor()!==$hash){
            throw $this->createAccessDeniedException();
        }
        $configurationRepository = $this->getDoctrine()->getRepository('App:Configuration');
        if(!empty($configurationRepository->findOneByName('adminEmail'))){
            $adminEmail = $configurationRepository->findOneByName('adminEmail')->getValue();
        } else {
            $adminEmail = $this->getParameter('admin_email');
        }
        $user->setHashShowInvestor();
        $user->setShowInvestor($active);
        if ($active){
            $text="Добрый день!<br>
            Ваш запрос на предоставление данных - принят.Теперь вам доступны обороты инвестора: ".$user->getName()." ".$user->getSurname()." ".$user->getUsername().".<br> 
            
            С уважением, команда WSI.";
        } else {
            $text="Добрый день!<br>
            Ваш запрос на предоставление данных о оборотах инвестора ".$user->getName()." ".$user->getSurname()." ".$user->getUsername()."- Отклонен.<br>

            С уважением, команда WSI.";
        }
        $message = (new \Swift_Message('Запрос данных'))
            ->setFrom($this->getParameter('send_from'))
            ->setTo([$user->getParent()->getEmail(), $adminEmail])
            ->setBody(
                $text,
                'text/html'
            )
        ;

        $mailer->send($message);
        $this->get('doctrine')->getManager()->persist($user);
        $this->get('doctrine')->getManager()->flush();

        return $this->render('user/switch_show_sponsor.html.twig', [
            'active'=>$active
        ]);
    }
}
