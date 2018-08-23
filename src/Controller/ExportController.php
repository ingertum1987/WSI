<?php

namespace App\Controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ExportController extends Controller
{
    public function exportUsersWithBalances()
    {
        $userRepository = $this->getDoctrine()->getRepository('App:User');
        $currencyRepository = $this->getDoctrine()->getRepository('App:Currency');
        $accountRepository = $this->getDoctrine()->getRepository('App:Account');
        $transactionRepository = $this->getDoctrine()->getRepository('App:Transaction');

        $users = $userRepository->findAll();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();



        foreach ($users as $key => $user) {
            $total['personal'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(1));
            $total['invest'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(2));
            $total['referral'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(3));
            dump($key+1);
            $sheet->setCellValue('A' . ($key+1), $user->getUsername());
            $sheet->setCellValue('B' . ($key+1), $total['personal']);
            $sheet->setCellValue('C' . ($key+1), $total['invest']);
            $sheet->setCellValue('D' . ($key+1), $total['referral']);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($this->get('kernel')->getRootDir() . '/../public/upload/export_balance.xlsx');

        return $this->render('user/balance.html.twig', [
            'total' => $total
        ]);
    }
}
