<?php

namespace App\Controller;

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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AdminController extends Controller
{
    public function transactions(Request $request, \Swift_Mailer $mailer)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        $transactionStatusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');
        $transactionRepository = $this->getDoctrine()->getRepository('App:Transaction');
        if (($request->request->get('declined')!==null) || ($request->request->get('confirmed')!==null)){
            /** @var Transaction $transactionInfo */
            $transactionInfo = $transactionRepository->find($request->request->get('id'));
            $title=(($transactionInfo->getDirection()=='in') ? 'Пополнение - ' : 'Снятие - ').$transactionInfo->getAccount()->getName();
            if ($request->request->get('declined')!==null){
                $status='Отклонена';
            } else {
                $status='Принята';
            }
            $message="Операция №".$transactionInfo->getId()." - ".$title." - дата ("
                .$transactionInfo->getCreatedAt()->format('d.m.Y').") - время ("
                .$transactionInfo->getCreatedAt()->format('H:i').") - Статус операции: ".$status." / Сумма: ".$transactionInfo->getSum()
            ;
            $configurationRepository = $this->getDoctrine()->getRepository('App:Configuration');
            if(!empty($configurationRepository->findOneByName('adminEmail'))){
                $sysEmail = $configurationRepository->findOneByName('adminEmail')->getValue();
            } else {
                $sysEmail = $this->getParameter('admin_email');
            }
            $userEmail=$transactionInfo->getUser()->getEmail();
            $adminEmail=$this->getUser()->getEmail();
            if ($transactionInfo->getDirection()=='in'){
                $direction='На счёт: '.$transactionInfo->getAccount()->getName();
            } else {
                $direction='Со счёта: '.$transactionInfo->getAccount()->getName();
            }
            $recipients=[];
            if ($sysEmail){
                $recipients[]=$sysEmail;
            }
            if ($userEmail){
                $recipients[]=$userEmail;
            }
            if ($adminEmail){
                $recipients[]=$adminEmail;
            }
            $mail = (new \Swift_Message($message))
                ->setFrom($this->getParameter('send_from'))
                ->setTo($recipients)
                ->setBody(
                    $this->renderView(
                        'email/afterConfirmDecline.html.twig',[
                        'transaction' => $transactionInfo,
                        'direction'=>$direction,
                        'adminId'=>$this->getUser()->getId(),

                    ]),
                    'text/html'
                )
            ;
            $mailer->send($mail);
            $em = $this->getDoctrine()->getManager();

            $notificationStatusRepository = $this->getDoctrine()->getRepository('App:Status');
            $notification = new Notification();
            $notification->setStatus($notificationStatusRepository->find(1));
            $notification->setMessage($message);
            $notification->setUser($transactionInfo->getUser());
            $em->persist($notification);
            if ($transactionInfo->getUser()!==$this->getUser()){
                $notification = new Notification();
                $notification->setStatus($notificationStatusRepository->find(1));
                $notification->setMessage($message);
                $notification->setUser($this->getUser());
                $em->persist($notification);
            }

            $em->flush();
        }

        if ($request->request->get('confirmed')!==null){
            $balance = $this->get(\App\Service\Transaction::class)
                ->getSumUserByAccount($transactionInfo->getUser(), \App\Service\Transaction::ACCOUNT_PERSONAL);
            if (($transactionInfo->getDirection()==\App\Service\Transaction::DIRECTION_IN) || ($balance >= $transactionInfo->getSum())){
                $statusConfirmed = $transactionStatusRepository->find(2);
                $transactionInfo->setStatus($statusConfirmed);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->merge($transactionInfo);
                $entityManager->flush();
                $this->addFlash('success', $this->get('translator')->trans('admin.user.transactions.flash.confirmed.success', [
                '%id%'=>$transactionInfo->getId()
                ], 'admin'));
            } else {
                $this->addFlash('danger', $this->get('translator')->trans('admin.user.transactions.flash.confirmed.error', [
                    '%id%'=>$transactionInfo->getId()
                ], 'admin'));
            }

            if (!empty($transactionInfo->getRequestFrom()) && $transactionInfo->getAccount()->getId() != 1){
                $transaction = new Transaction();
                $accountRepository = $this->getDoctrine()->getRepository('App:Account');
                $currencyRepository = $this->getDoctrine()->getRepository('App:Currency');
                $statusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');
                $accountFrom = $accountRepository->find($transactionInfo->getRequestFrom());

                $transaction->setUser($transactionInfo->getUser());
                $transaction->setAccount($accountFrom);
                $transaction->setCurrency($currencyRepository->find(1));
                $transaction->setStatus($statusRepository->find(2));
                $transaction->setSum($transactionInfo->getSum());

                if ($transactionInfo->getDirection() == 'in'){
                    $transaction->setDirection('out');
                } else {
                    $transaction->setDirection('in');
                }

                $em = $this->getDoctrine()->getManager();
                $em->persist($transaction);
                $em->flush();
            }

            return $this->redirectToRoute('user_admin_transactions');
        }

        if ($request->request->get('declined')!==null){
            $transactionInfo = $transactionRepository->find($request->request->get('id'));
            $statusConfirmed = $transactionStatusRepository->find(3);
            $transactionInfo->setStatus($statusConfirmed);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->merge($transactionInfo);
            $entityManager->flush();
            $this->addFlash('success', $this->get('translator')->trans('admin.user.transactions.flash.declined.success', [
                '%id%'=>$transactionInfo->getId()
            ], 'admin'));

            if (!empty($transactionInfo->getRequestFrom())){
                $accountRepository = $this->getDoctrine()->getRepository('App:Account');
                $accountFrom = $accountRepository->find($transactionInfo->getRequestFrom());
            }

            return $this->redirectToRoute('user_admin_transactions');
        }

        $status = $transactionStatusRepository->find(1);
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $transactionRepository->getQueryAllByStatus($status),
            $request->query->getInt('page', 1),
            $this->getParameter('perpage')
        );

        return $this->render('admin/transactions.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    public function uploadTransactions(Request $request, \Swift_Mailer $mailer)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');
    }

    public function investors(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        $countryRepository = $this->getDoctrine()->getRepository('App:Country');
        $userRepository = $this->getDoctrine()->getRepository('App:User');
        $transactionRepository = $this->getDoctrine()->getRepository('App:Transaction');
        $countries = $countryRepository->findAll();
        $currencyRepository = $this->getDoctrine()->getRepository('App:Currency');
        $currencies = $currencyRepository->findBy([], ['name' => 'ASC']);

        $user = new User();

        $form = $this->createFormBuilder($user, [
                'attr' => [
                    'class' => 'form-horizontal form-material',
                    'id' => 'loginform'
                ]
            ])
            ->add('username', TextType::class,[
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите номер телефона'
                ]
            ])
            ->add('name', TextType::class,[
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите имя'
                ]
            ])
            ->add('surname', TextType::class,[
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите фамилию'
                ]
            ])
            ->add('bitcoinWallet', TextType::class,[
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите bitcoin-кошелек'
                ],
                'required' => false
            ])
            ->add('email', TextType::class,[
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите электронную почту'
                ]
            ])
            ->add('plainPassword', TextType::class,[
                'data' => $userRepository->generatePassword(),
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => true
                ],
            ])
            ->add('parentId', TextType::class,[
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите инвестора'
                ]
            ])
            ->add('profitRate', TextType::class,[
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите процент доходности'
                ]
            ])
            ->add('save', SubmitType::class,[
                'attr' => [
                    'class' => 'btn btn-danger waves-effect waves-light'
                ],
                'label' => 'Сохранить и отправить пароль'
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUsername($request->request->get('country') . preg_replace("/[^0-9]/", '', $user->getUsername()));
            $existUser = $userRepository->findByUsername($user->getUsername());

            if (!$existUser){
                $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $sponsor = $userRepository->findOneByUsername($user->getParentId());
                $user->setParent($sponsor);
                $user->setPassword($password)
                    ->setRoles(['ROLE_USER'])
                    ->setShowInvestor(0);
                $user->setPromoCode($userRepository->generatePromoCode());

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $userRepository->sendPasswordBySMS(
                    preg_replace("/[^0-9]/", '', $user->getUsername()),
                    $user->getPlainPassword());

                return $this->redirectToRoute('user_admin_investors');

            } else {
                $this->addFlash(
                    'error',
                    'Такой пользователь уже существует!'
                );
            }
        }
        $filters = [];

        if (!empty($request->query->get('id'))){
            $filters['id'] = $request->query->get('id');
        }

        if (!empty($request->query->get('name'))){
            $filters['name'] = $request->query->get('name');
        }

        if (!empty($request->query->get('surname'))){
            $filters['surname'] = $request->query->get('surname');
        }

        if (!empty($request->query->get('username'))){
            $filters['username'] = $request->query->get('username');
        }

        if (!empty($request->query->get('parent'))){
            $filters['parent'] = $request->query->get('parent');
        }

        if (!empty($request->query->get('percent'))){
            $filters['percent'] = $request->query->get('percent');
        }

        if (!empty($request->query->get('profit'))){
            $filters['profit'] = $request->query->get('profit');
        }
        $query=$userRepository->getUsersWithFilters($filters);
        $users = $userRepository->getUsersWithFilters($filters)->getResult();
        $paginator = $this->get('knp_paginator');
        if (($request->query->get('option') == 'all') && (($totalRows = $userRepository->getCountUsersWithFilters($filters))>0)){
            $pagination = $paginator->paginate(
                $query,
                1,
                $totalRows
            );
        } else {
            $pagination = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                $this->getParameter('perpage')
            );
        }
        if (!empty($users)){
            $transactionRepository = $this->getDoctrine()->getRepository('App:Transaction');
            $profitRepository = $this->getDoctrine()->getRepository('App:Profit');
            $accountRepository = $this->getDoctrine()->getRepository('App:Account');
            $transactionStatusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');
            $status = $transactionStatusRepository->find(2);
            $lastProfit = $profitRepository->findOneBy([], ['createdAt' => 'DESC']);

            foreach ($users as $key => $user){
                $total['personal'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(1), $status);
                $total['invest'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(2), $status);
                $total['referral'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(3), $status);
                $total['profit'] = $lastProfit->getPercent() - $lastProfit->getPercent()*((100-$user->getProfitRate())/100);
                $user->setBalance($total);

                if (!empty($request->query->get('personal')) && $total['personal'] != $request->query->get('personal')){
                    unset($users[$key]);
                }

                if (!empty($request->query->get('invest')) && $total['invest'] != $request->query->get('invest')){
                    unset($users[$key]);
                }

                if (!empty($request->query->get('referal')) && $total['referral'] != $request->query->get('referal')){
                    unset($users[$key]);
                }

                if (!empty($request->query->get('profit')) && $total['profit'] != $request->query->get('profit')){
                    unset($users[$key]);
                }
            }

        }

        if (null !== $request->request->get('changeSponsor')){
            $user = $userRepository->findOneByUsername($request->request->get('investorUsername'));
            $sponsor = $userRepository->findOneByUsername($request->request->get('sponsorUsername'));

            if (!empty($user) && !empty($sponsor)){
                $user->setParent($sponsor);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->merge($user);
                $entityManager->flush();
            }

           return $this->redirectToRoute('user_admin_investors');
        }

        if (null !== $request->request->get('investorPercent')){
            $user = $userRepository->findOneByUsername($request->request->get('investorPercentUsername'));

            if (!empty($user)){
                $user->setProfitRate($request->request->get('percent'));

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->merge($user);
                $entityManager->flush();
            }

            return $this->redirectToRoute('user_admin_investors');
        }

        if (null !== $request->request->get('inOutBalance')){
            $userInfo = $userRepository->findOneByUsername($request->request->get('username'));
            $transactionStatusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');
            $accountRepository = $this->getDoctrine()->getRepository('App:Account');
            $currencyRepository = $this->getDoctrine()->getRepository('App:Currency');

            $account = $accountRepository->findOneByName('Личный счет');
            $status = $transactionStatusRepository->findOneByName('Выполнено');
            $currency = $currencyRepository->find($request->request->get('currency'));

            $transaction = new Transaction();

            if (!empty($userInfo)){
                $transaction->setUser($userInfo);
                $transaction->setStatus($status);
                $transaction->setDirection($request->request->get('direction'));
                $transaction->setAccount($account);
                $transaction->setSum($request->request->get('sum'));
                $transaction->setCurrency($currency);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($transaction);
                $entityManager->flush();

                $configurationRepository = $this->getDoctrine()->getRepository('App:Configuration');

                if(!empty($configurationRepository->findOneByName('adminEmail'))){
                    $adminEmail = $configurationRepository->findOneByName('adminEmail')->getValue();
                } else {
                    $adminEmail = $this->getParameter('admin_email');
                }
                $accountBalance = $transactionRepository->getTotalUserAccount($userInfo, $account, $status);

                $message = (new \Swift_Message('Операция по личному счету пользователя - успешно.'))
                    ->setFrom($this->getParameter('send_from'))
                    ->setTo([$adminEmail, $this->getUser()->getEmail()])
                    ->setBody(
                        $this->renderView(
                            'email/addWithdrawToAdmin.html.twig',[
                            'investor' => $userInfo,
                            'account' => $account,
                            'direction' => $request->request->get('direction'),
                            'sum' => $request->request->get('sum'),
                            'adminInfo' => $this->getUser(),
                            'balance' => $accountBalance
                        ]),
                        'text/html'
                    )
                ;
                $mailer->send($message);

                $notificationStatusRepository = $this->getDoctrine()->getRepository('App:Status');
                $notificationStatus = $notificationStatusRepository->findOneByName('Новый');

                $notification = new Notification();

                $notification->setUser($this->getUser());
                $notification->setMessage('Операция по личному счету инвестора ' . $userInfo->getUsername() . ' на сумму '
                    . $request->request->get('sum') . 'была проведена успешно');

                $notification->setStatus($notificationStatus);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($notification);
                $entityManager->flush();

                $message = (new \Swift_Message('Ввод / Вывод средств'))
                    ->setFrom($this->getParameter('send_from'))
                    ->setTo($userInfo->getEmail())
                    ->setBody(
                        $this->renderView(
                            'email/addWithdrawToUser.html.twig',[
                            'investor' => $userInfo,
                            'account' => $account,
                            'direction' => $request->request->get('direction'),
                            'sum' => $request->request->get('sum'),
                            'adminInfo' => $this->getUser(),
                            'balance' => $accountBalance
                        ]),
                        'text/html'
                    )
                ;
                $mailer->send($message);

                $notification = new Notification();

                $notification->setUser($userInfo);
                $notification->setMessage('Операция по личному счету на сумму ' . $request->request->get('sum')
                    . 'была проведена успешно');

                $notification->setStatus($notificationStatus);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($notification);
                $entityManager->flush();
            }

            return $this->redirectToRoute('user_admin_investors');
        }

        if (null !== $request->request->get('editUser')) {
            $entityManager = $this->getDoctrine()->getManager();
            $userInfo = $entityManager->getRepository('App:User')->findOneByUsername($request->request->get('username'));

            if (!empty($userInfo)){
                $userInfo->setProfitRate($request->request->get('profitRate'));
                $entityManager->flush();
            }

            return $this->redirectToRoute('user_admin_investors');
        }

        if (null !== $request->request->get('referalIncome')){
            $userInfo = $userRepository->findOneByUsername($request->request->get('username'));
            $transactionStatusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');
            $accountRepository = $this->getDoctrine()->getRepository('App:Account');
            $currencyRepository = $this->getDoctrine()->getRepository('App:Currency');

            $account = $accountRepository->findOneByName($request->request->get('account'));
            $status = $transactionStatusRepository->findOneByName('Выполнено');
            $currency = $currencyRepository->find($request->request->get('currency'));

            $transaction = new Transaction();

            if (!empty($userInfo)){
                $transaction->setUser($userInfo);
                $transaction->setStatus($status);
                $transaction->setDirection('in');
                $transaction->setAccount($account);
                $transaction->setSum($request->request->get('sum'));
                $transaction->setType($request->request->get('type'));
                $transaction->setCurrency($currency);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($transaction);
                $entityManager->flush();

                $notificationStatusRepository = $this->getDoctrine()->getRepository('App:Status');
                $notificationStatus = $notificationStatusRepository->findOneByName('Новый');

                $notification = new Notification();

                $notification->setUser($this->getUser());
                $notification->setMessage('Операция ' . $request->request->get('type') . ' инвестору '
                    . $userInfo->getUsername() . ' на сумму '
                    . $request->request->get('sum') . 'была проведена успешно');

                $notification->setStatus($notificationStatus);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($notification);
                $entityManager->flush();

                $notification = new Notification();

                $notification->setUser($userInfo);
                $notification->setMessage('Операция ' . $request->request->get('type') . ' на сумму '
                    . $request->request->get('sum') . 'была проведена успешно');

                $notification->setStatus($notificationStatus);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($notification);
                $entityManager->flush();
            }

            return $this->redirectToRoute('user_admin_investors');
        }

        if (null !== $request->request->get('transfer')){
            $userInfo = $userRepository->findOneByUsername($request->request->get('username'));
            $transactionStatusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');
            $accountRepository = $this->getDoctrine()->getRepository('App:Account');
            $currencyRepository = $this->getDoctrine()->getRepository('App:Currency');

            $fromAccount = $accountRepository->findOneByName($request->request->get('from'));
            $toAccount = $accountRepository->findOneByName($request->request->get('to'));
            $status = $transactionStatusRepository->findOneByName('Выполнено');
            $currency = $currencyRepository->find($request->request->get('currency'));



            if (!empty($userInfo)){
                $transaction = new Transaction();

                $transaction->setUser($userInfo);
                $transaction->setStatus($status);
                $transaction->setDirection('out');
                $transaction->setAccount($fromAccount);
                $transaction->setSum($request->request->get('sum'));
                $transaction->setCurrency($currency);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($transaction);
                $entityManager->flush();

                $transaction = new Transaction();

                $transaction->setUser($userInfo);
                $transaction->setStatus($status);
                $transaction->setDirection('in');
                $transaction->setAccount($toAccount);
                $transaction->setSum($request->request->get('sum'));
                $transaction->setCurrency($currency);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($transaction);
                $entityManager->flush();

                $configurationRepository = $this->getDoctrine()->getRepository('App:Configuration');

                if(!empty($configurationRepository->findOneByName('adminEmail'))){
                    $adminEmail = $configurationRepository->findOneByName('adminEmail')->getValue();
                } else {
                    $adminEmail = $this->getParameter('admin_email');
                }

                $message = (new \Swift_Message('Транзакция в системе'))
                    ->setFrom($this->getParameter('send_from'))
                    ->setTo([$adminEmail, $this->getUser()->getEmail()])
                    ->setBody(
                        $this->renderView(
                            'email/transferToAdmin.html.twig',[
                            'investor' => $userInfo,
                            'fromAccount' => $fromAccount,
                            'toAccount' => $toAccount,
                            'sum' => $request->request->get('sum'),
                            'adminInfo' => $this->getUser()
                        ]),
                        'text/html'
                    )
                ;
                $mailer->send($message);

//                $notificationStatusRepository = $this->getDoctrine()->getRepository('App:Status');
//                $notificationStatus = $notificationStatusRepository->findOneByName('Новый');
//
//                $notification = new Notification();
//
//                $notification->setUser($this->getUser());
//                $notification->setMessage('Операция по личному счету инвестора ' . $userInfo->getUsername() . ' на сумму '
//                    . $request->request->get('sum') . 'была проведена успешно');
//
//                $notification->setStatus($notificationStatus);
//
//                $entityManager = $this->getDoctrine()->getManager();
//                $entityManager->persist($notification);
//                $entityManager->flush();

                $message = (new \Swift_Message('Транзакция в системе'))
                    ->setFrom($this->getParameter('send_from'))
                    ->setTo($userInfo->getEmail())
                    ->setBody(
                        $this->renderView(
                            'email/transferToUser.html.twig',[
                            'investor' => $userInfo,
                            'fromAccount' => $fromAccount,
                            'toAccount' => $toAccount,
                            'sum' => $request->request->get('sum'),
                            'adminInfo' => $this->getUser()
                        ]),
                        'text/html'
                    )
                ;
                $mailer->send($message);

//                $notification = new Notification();
//
//                $notification->setUser($userInfo);
//                $notification->setMessage('Операция по личному счету на сумму ' . $request->request->get('sum')
//                    . 'была проведена успешно');
//
//                $notification->setStatus($notificationStatus);
//
//                $entityManager = $this->getDoctrine()->getManager();
//                $entityManager->persist($notification);
//                $entityManager->flush();
            }

            return $this->redirectToRoute('user_admin_investors');
        }

        return $this->render('admin/investors.html.twig', [
            'pagination' => $pagination,
            'form' => $form->createView(),
            'countries' => $countries,
            'currencies' => $currencies
        ]);
    }

    public function settings(Request $request, \Swift_Mailer $mailer, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');
        $importForm = $this->createForm(UploadCsvType::class);

        $entityManager = $this->getDoctrine()->getManager();
        $configurationRepository = $this->getDoctrine()->getRepository('App:Configuration');
        $adminEmail = $configurationRepository->findOneByName('adminEmail');
        $transactionRepository = $this->getDoctrine()->getRepository('App:Transaction');
        $profitRepository = $this->getDoctrine()->getRepository('App:Profit');
        $thisMonthProfitTransaction = $transactionRepository->getThisMonthProfitTransaction();
        $profit = $profitRepository->findOneBy([], ['createdAt' => 'DESC']);


        if (null !== $request->request->get('changeAdminEmail')){

            if (!empty($adminEmail)){
                $adminEmail->setValue($request->request->get('email'));
                $entityManager->merge($adminEmail);
            } else {
                $adminEmail = new Configuration();
                $adminEmail->setName('adminEmail')
                    ->setValue($request->request->get('email'));
                $entityManager->persist($adminEmail);
            }
            $entityManager->flush();

            $message = (new \Swift_Message('Почта администратора изменена'))
                ->setFrom($this->getParameter('send_from'))
                ->setTo($adminEmail->getValue())
                ->setBody('Успешно! <br>Системная почта изменена!', 'text/html');
            $mailer->send($message);

            return $this->redirectToRoute('user_admin_settings');
        }

        if (null !== $request->request->get('changeReturnRate')){
            $returnRate = $configurationRepository->findOneByName('returnRate');

            if (!empty($returnRate)){
                $returnRate->setValue((float)$request->request->get('return_rate'));
                $entityManager->merge($returnRate);
            } else {
                $returnRate = new Configuration();
                $returnRate->setName('returnRate')
                    ->setValue((float)$request->request->get('return_rate'));
                $entityManager->persist($returnRate);
            }
            $entityManager->flush();

            $message = (new \Swift_Message('Доходность изменена'))
                ->setFrom($this->getParameter('send_from'))
                ->setTo($adminEmail->getValue())
                ->setBody('Успешно! <br>Новая доходность: '.$returnRate->getValue(). '%', 'text/html');
            $mailer->send($message);

            return $this->redirectToRoute('user_admin_settings');
        }

        if (null !== $request->request->get('setProfit')){

            if ($passwordEncoder->isPasswordValid($this->getUser(), $request->request->get('password')) === true){
                $profit = $profitRepository->getThisMonthProfit();

                if (!empty($profit)){
                    $profit->setSum($request->request->get('sum'));
                    $entityManager->merge($profit);
                } else {
                    $profit = new Profit();
                    $profit->setSum($request->request->get('sum'));
                    $entityManager->persist($profit);
                }
                $entityManager->flush();

                $message = (new \Swift_Message('Изменение данных о прибыли фонда WSI'))
                    ->setFrom($this->getParameter('send_from'))
                    ->setTo($adminEmail->getValue())
                    ->setBody('Данные прибыльности фонда изменены.<br>'
                        .'Имя ответственного: ' . $this->getUser()->getName() . '<br>'
                        .'Фамилия ответственного: ' . $this->getUser()->getSurname() . '<br>'
                        .'Тел. ответственного: ' . $this->getUser()->getUsername() . '<br>'
//                        .'Сумма за прошлый месяц: ' . $this->getUser()->getSurname() . '<br>'
                        .'Сумма изменена на: ' . $request->request->get('sum') . '<br>'
//                        .'Прибыль фонда: ' . $this->getUser()->getSurname() . '<br>'
//                        .'Доходность фонда: (указываем %)'
                        .'С уважением, команда WSI.', 'text/html');
                $mailer->send($message);

                return $this->redirectToRoute('user_admin_settings');
            }
        }

        if (null !== $request->request->get('calculateProfit')){
            $userRepository = $this->getDoctrine()->getRepository('App:User');
            $currencyRepository = $this->getDoctrine()->getRepository('App:Currency');
            $transactionRepository = $this->getDoctrine()->getRepository('App:Transaction');
            $accountRepository = $this->getDoctrine()->getRepository('App:Account');
            $transactionStatusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');
            $status = $transactionStatusRepository->find(2);
            $currentProfit = $profitRepository->getThisMonthProfit();
            $lastProfit = $profitRepository->getPastMonthProfit();
            $totalProfit = ($currentProfit->getSum() - $lastProfit->getSum())/$lastProfit->getSum();

            if ($totalProfit < 0 ){
                $direction = 'out';
            } else {
                $direction = 'in';
            }

            $users = $userRepository->findAll();

            if (!empty($users)){

                foreach ($users as $user){
                    $investBalance = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(2), $status);

                    if ($investBalance > 0){
                        $investorProfitRate = 0;
                        if ($investBalance < 4999){
                            $investorProfitRate = 0.55;
                        } elseif ($investBalance < 49999){
                            $investorProfitRate = 0.60;
                        } elseif($investBalance < 99999){
                            $investorProfitRate = 0.65;
                        } elseif($investBalance < 249999){
                            $investorProfitRate = 0.70;
                        } elseif($investBalance < 499999){
                            $investorProfitRate = 0.75;
                        } elseif($investBalance > 499999){
                            $investorProfitRate = 0.80;
                        }

                        $thisMonthUserProfitTransaction = $transactionRepository->getThisMonthProfitTransaction($user);

                        if (!empty($thisMonthUserProfitTransaction)){
                            $userBalance = $investBalance-$thisMonthUserProfitTransaction->getSum();
                            $thisMonthUserProfitTransaction->setSum(abs($userBalance*$investorProfitRate*$totalProfit));
                            $thisMonthUserProfitTransaction->setDirection($direction);
                            $entityManager->merge($thisMonthUserProfitTransaction);

                            $message = (new \Swift_Message('Изменение данных о вашем доходе в WSI'))
                                ->setFrom($this->getParameter('send_from'))
                                ->setTo($user->getEmail())
                                ->setBody('Прибыль фонда составила: ' . ($currentProfit->getSum() - $lastProfit->getSum()) . '<br>'
                                    .'Доходность фонда составила: ' . ($totalProfit*100) . '<br>'
                                    .'Ваш инвестиционный доход составил: ' . ($userBalance*$investorProfitRate*$totalProfit) . '<br>'
                                    .'Ваш % составил: (указываем процент юзера)' . ($investorProfitRate*$totalProfit) . '<br>'
                                    .'С уважением, команда WSI.', 'text/html');
                            $mailer->send($message);

                        } else {
                            $newUserProfitTransaction = new Transaction();
                            $newUserProfitTransaction->setSum(abs($investorProfitRate*$investBalance*$totalProfit));
                            $newUserProfitTransaction->setStatus($status);
                            $newUserProfitTransaction->setType('Начисление итогов месяца');
                            $newUserProfitTransaction->setAccount($accountRepository->find(2));
                            $newUserProfitTransaction->setUser($user);
                            $newUserProfitTransaction->setCurrency($currencyRepository->find(1));
                            $newUserProfitTransaction->setDirection($direction);
                            $entityManager->persist($newUserProfitTransaction);

                            $message = (new \Swift_Message('Изменение данных о вашем доходе в WSI'))
                                ->setFrom($this->getParameter('send_from'))
                                ->setTo($user->getEmail())
                                ->setBody('Прибыль фонда составила: ' . ($currentProfit->getSum() - $lastProfit->getSum()) . '<br>'
                                    .'Доходность фонда составила: ' . ($totalProfit*100) . '<br>'
                                    .'Ваш инвестиционный доход составил: ' . ($investorProfitRate*$investBalance*$totalProfit) . '<br>'
                                    .'Ваш % составил: (указываем процент юзера)' . ($investorProfitRate*$totalProfit) . '<br>'
                                    .'С уважением, команда WSI.', 'text/html');
                            $mailer->send($message);
                        }
                        $entityManager->flush();
                    }
                }
            }

            return $this->redirectToRoute('user_admin_settings');
        }

        return $this->render('admin/settings.html.twig', [
            'adminEmail' => $adminEmail,
            'thisMonthTransaction' => $thisMonthProfitTransaction,
            'importForm' => $importForm->createView(),
            'profit' => $profit
        ]);
    }

    public function getProfit(Request $request)
    {
        $profitRepository = $this->getDoctrine()->getRepository('App:Profit');

        if (!empty($request->request->get('month')) && !empty($request->request->get('year'))){
            $profit = $profitRepository->getProfitByMonthYear($request->request->get('month'),
                $request->request->get('year'));

            if (!empty($profit)){
                $response = new JsonResponse();
                return $response->setData([
                    'percent' => $profit->getPercent()
                ]);
            }
        }
    }
}
