<?php

namespace App\Controller\Cabinet\User\Balance;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class InputController extends Controller
{
    /**
     * @Route("/personal", name="cabinet_user_balance_input_personal")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param \Swift_Mailer $mailer
     * @return Response
     */
    public function personal(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer)
    {
        $response = new Response('Content',
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
        /** @var User $user */
        $user = $this->getUser();
        $bool = $passwordEncoder->isPasswordValid($user, $request->request->get('password'));
        if ($bool == true) {
            $configurationRepository = $this->getDoctrine()->getRepository('App:Configuration');
            if (!empty($configurationRepository->findOneByName('adminEmail'))) {
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
            $transaction->setType($this->get('translator')->trans('page.user.balance.index.input_personal.type'));
            $transaction->setStatus($statusRepository->find(1));
            $transaction->setSum($request->request->get('sum'));
            $transaction->setDirection('in');
            $em = $this->getDoctrine()->getManager();
            $em->persist($transaction);
            $em->flush();
            $operationName = $this->get('translator')->trans('page.user.balance.index.input_personal.operation');
            $message = (new \Swift_Message($this->get('translator')->trans('page.user.balance.index.input_personal.operationNum') .
                ' ' . $transaction->getId() . ' - ' . $operationName . ' - (' .
                $user->getName() . ' ' . $user->getSurname() . ') - (' . $transaction->getCreatedAt()->format('d.m.Y H:i') . ')'))
                ->setFrom($this->getParameter('send_from'))
                ->setTo($adminEmail)
                ->setBody(
                    $this->renderView(
                        'email/balanceToAdmin.html.twig', [
                        'sum' => $request->request->get('sum'),
                        'operationName' => $operationName,
                        'transaction' => $transaction,
                        'currency' => $transaction->getCurrency()->getName(),
                        'wallet' => $request->request->get('wallet') ?: null,
                        'from' => $request->request->get('wallet'),
                        'to' => $this->get('translator')->trans('page.user.balance.index.input_personal.personal')

                    ]),
                    'text/html'
                );
            $mailer->send($message);
            if ($user->getEmail()) {
                $message = (new \Swift_Message($this->get('translator')->trans('page.user.balance.index.input_personal.request')))
                    ->setFrom($this->getParameter('send_from'))
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->renderView(
                            'email/balanceToUser.html.twig', [
                            'sum' => $request->request->get('sum'),
                            'currency' => $transaction->getCurrency()->getName(),
                            'direction' => $this->get('translator')->trans('page.user.balance.index.input_personal.direction'),
                            'from' => $request->request->get('wallet'),
                            'to' => $this->get('translator')->trans('page.user.balance.index.input_personal.personal')
                        ]),
                        'text/html'
                    );

                $mailer->send($message);
            }

            return $response->setContent('success');
        } else {
            return $response->setContent('pass_error');
        }
    }

    /**
     * @Route("/invest", name="cabinet_user_balance_input_invest")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param \Swift_Mailer $mailer
     * @return Response
     */
    public function invest(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer)
    {
        $response = new JsonResponse();

        $user = $this->getUser();
        $bool = $passwordEncoder->isPasswordValid($user, $request->request->get('password'));

        if ($bool == true) {
            $transactionRepository = $this->getDoctrine()->getRepository('App:Transaction');
            $accountRepository = $this->getDoctrine()->getRepository('App:Account');
            $statusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');

            $userFromAccountBalance = $transactionRepository->getTotalUserAccount($user,
                $accountRepository->find($request->request->get('account')),
                $statusRepository->find(2));

            if ($userFromAccountBalance >= $request->request->get('sum')) {
                $configurationRepository = $this->getDoctrine()->getRepository('App:Configuration');

                if (!empty($configurationRepository->findOneByName('adminEmail'))) {
                    $adminEmail = $configurationRepository->findOneByName('adminEmail')->getValue();
                } else {
                    $adminEmail = $this->getParameter('admin_email');
                }

                $em = $this->getDoctrine()->getManager();
                $sum = $request->request->get('sum');
                // Транзакция поступления
                $transaction = $this->get(\App\Service\Transaction::class)->create(
                    $user,
                    \App\Service\Transaction::ACCOUNT_INVEST,
                    \App\Service\Transaction::DIRECTION_IN,
                    $sum
                );
                $em->persist($transaction);
                // Транзакция списания
                $transactionOut = $this->get(\App\Service\Transaction::class)->create(
                    $user,
                    $request->request->get('account'),
                    \App\Service\Transaction::DIRECTION_OUT,
                    $sum
                );
                $em->persist($transactionOut);
                $em->flush();

                $operationName = $this->get('translator')->trans('page.user.balance.index.input_invest.operation');
                $message = (new \Swift_Message($this->get('translator')->trans('page.user.balance.index.input_invest.operation') .
                    ' ' . $transaction->getId() . ' - ' . $operationName . ' - (' .
                    $user->getName() . ' ' . $user->getSurname() . ') - (' . $transaction->getCreatedAt()->format('d.m.Y H:i') . ')'))
                    ->setFrom($this->getParameter('send_from'))
                    ->setTo($adminEmail)
                    ->setBody(
                        $this->renderView(
                            'email/balanceToAdmin.html.twig', [
                            'sum' => $request->request->get('sum'),
                            'operationName' => $operationName,
                            'transaction' => $transaction,
                            'currency' => $transaction->getCurrency()->getName(),
                            'wallet' => $request->request->get('wallet') ?: null,
                            'from' => $this->getDoctrine()->getRepository(Account::class)->find($request->request->get('account'))->getName(),
                            'to' => $this->get('translator')->trans('page.user.balance.index.input_invest.invest')

                        ]),
                        'text/html'
                    );

                $mailer->send($message);

                if ($user->getEmail()) {
                    $message = (new \Swift_Message($this->get('translator')->trans('page.user.balance.index.input_invest.request')))
                        ->setFrom($this->getParameter('send_from'))
                        ->setTo($user->getEmail())
                        ->setBody(
                            $this->renderView(
                                'email/balanceToUser.html.twig', [
                                'sum' => $request->request->get('sum'),
                                'currency' => $transaction->getCurrency()->getName(),
                                'direction' => $this->get('translator')->trans('page.user.balance.index.input_invest.direction'),
                                'from' => $this->getDoctrine()->getRepository(Account::class)->find($request->request->get('account'))->getName(),
                                'to' => $this->get('translator')->trans('page.user.balance.index.input_invest.invest')
                            ]),
                            'text/html'
                        );

                    $mailer->send($message);
                }

                $response->setData(['status' => 'success']);
            } else {
                $response->setData([
                    'status' => 'sum_error',
                    'message' => $this->get('translator')->trans('page.user.balance.index.input_invest.error', ['%sum%' => $userFromAccountBalance])
                ]);
            }
        } else {
            $response->setData([
                'status' => 'pass_error',
                'message' => $this->get('translator')->trans('page.user.balance.index.input_invest.wrong_password')
            ]);
        }
        return $response;
    }
}