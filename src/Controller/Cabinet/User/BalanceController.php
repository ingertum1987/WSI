<?php

namespace App\Controller\Cabinet\User;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BalanceController extends Controller
{
    /**
     * @Route("/", name="cabinet_user_balance_index")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $transactionRepository = $this->getDoctrine()->getRepository('App:Transaction');
        $accountRepository = $this->getDoctrine()->getRepository('App:Account');
        $transactionStatusRepository = $this->getDoctrine()->getRepository('App:TransactionStatus');
        $status = $transactionStatusRepository->find(2);
        $total['personal'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(1), $status);
        $total['invest'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(2), $status);
        $total['referral'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(3), $status);
        $filters = ['user' => $user];
        if (!empty($request->query->get('id'))){
            $filters['id'] = $request->query->get('id');
        }
        if (!empty($request->query->get('type'))){
            $type = explode('_', $request->query->get('type'))[0];
            $account = $accountRepository->find(explode('_', $request->query->get('type'))[1]);

            if (!empty($type) && !empty($account)){
                $filters['type'] = $type;
                $filters['account'] = $account;
            }
        }
        if (!empty($request->query->get('operationType'))){
            $filters['operationType'] = $request->query->get('operationType');
        }
        if (!empty($request->query->get('status')) && $transactionRepository->find($request->query->get('status')) != null ){
            $filters['status'] = $transactionRepository->find($request->query->get('status'));
        }
        if (!empty($request->query->get('date'))){
            $filters['date'] = new \DateTime($request->query->get('date'));
        }
        if (!empty($request->query->get('sum'))){
            $filters['sum'] = $request->query->get('sum');
        }
        $query = $transactionRepository->getTransactionsWithFilters($filters);
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $this->getParameter('perpage')
        );

        return $this->render('cabinet/user/balance/index.html.twig', [
            'total' => $total,
            'pagination' => $pagination,
            'statuses' => $transactionStatusRepository->findAll()
        ]);
    }
}