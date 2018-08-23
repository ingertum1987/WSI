<?php
namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Common\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class ImportUser
{
    private $managerRegistry;
    private $kernel;
    private $passwordEncoder;

    public function __construct(
        ManagerRegistry $managerRegistry,
        KernelInterface $kernel,
        UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->managerRegistry = $managerRegistry;
        $this->kernel = $kernel;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function importUser()
    {
        $existUser = [];

        $userRepository = $this->managerRegistry->getRepository('App:User');

        $inputFileName = $this->kernel->getRootDir() . '/../public/upload/import.xlsx';
        $spreadsheet = IOFactory::load($inputFileName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        foreach ($sheetData as $key => $sheetDatum) {
            $sheetDatum['A'] = preg_replace("/[^0-9]/", '', $sheetDatum['A']);
            $sheetDatum['B'] = preg_replace("/[^0-9]/", '', $sheetDatum['B']);
            $firstDigitA = substr($sheetDatum['A'],0,1);
            $firstDigitB = substr($sheetDatum['B'],0,1);

            if ($firstDigitA == 8) {
                $resultA = substr_replace($sheetDatum['A'], 7, 0, 1);
            } elseif ($firstDigitA == 0) {
                $resultA = '3' . $sheetDatum['A'];
            } else {
                $resultA = $sheetDatum['A'];
            }

            if ($firstDigitB == 8) {
                $resultB = substr_replace($sheetDatum['B'], 7, 0, 1);
            } elseif ($firstDigitB == 0) {
                $resultB = '3' . $sheetDatum['B'];
            } else {
                $resultB = $sheetDatum['B'];
            }
            $user = $userRepository->findOneByUsername($resultA);

            if (empty($user)) {
                $emUser = $this->managerRegistry->getManager();

                $addUser = new User();

                $addUser->setUsername($resultA);
                $plainPassword = $userRepository->generatePassword();
                $password = $this->passwordEncoder->encodePassword($addUser, $plainPassword);
                $addUser->setPassword($password);
                $addUser->setPromoCode($userRepository->generatePromoCode());
                $addUser->setRoles(['ROLE_USER']);
                $addUser->setShowInvestor(0);

                $parentUser = $userRepository->findOneByUsername($resultB);

                if (!empty($parentUser)){
                    $addUser->setParent($parentUser);
                }

                $emUser->persist($addUser);
                $emUser->flush();
                $emUser->clear();

                $userRepository->sendPasswordBySms($addUser->getUsername(), $plainPassword);
            }
        }

        return 'success';
    }

    public function importUserBalances()
    {
        $existUser = [];

        $userRepository = $this->managerRegistry->getRepository('App:User');
        $currencyRepository = $this->managerRegistry->getRepository('App:Currency');
        $accountRepository = $this->managerRegistry->getRepository('App:Account');
        $transactionStatusRepository = $this->managerRegistry->getRepository('App:TransactionStatus');
        $status = $transactionStatusRepository->find(2);

        $inputFileName = $this->kernel->getRootDir() . '/../public/upload/import_balance.xlsx';
        $spreadsheet = IOFactory::load($inputFileName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        foreach ($sheetData as $key => $sheetDatum) {
            $sheetDatum['A'] = preg_replace("/[^0-9]/", '', $sheetDatum['A']);
            $firstDigitA = substr($sheetDatum['A'],0,1);

            if ($firstDigitA == 8) {
                $resultA = substr_replace($sheetDatum['A'], 7, 0, 1);
            } elseif ($firstDigitA == 0) {
                $resultA = '3' . $sheetDatum['A'];
            } else {
                $resultA = $sheetDatum['A'];
            }

            $user = $userRepository->findOneByUsername($resultA);

            if (!empty($user) ) {
                $emInvestTransaction = $this->managerRegistry->getManager();

                $transaction = new Transaction();

                $transaction->setUser($user);
                $transaction->setCurrency($currencyRepository->find(1));
                $transaction->setAccount($accountRepository->find(2));
                $transaction->setDirection('in');
                $transaction->setSum(floatval(str_ireplace([',', ' '], '', $sheetDatum['B'])));
                $transaction->setStatus($status);

                $emInvestTransaction->persist($transaction);
                $emInvestTransaction->flush();


                if (floatval(str_ireplace(',', '', $sheetDatum['C'])) > 0){
                    $emTransaction = $this->managerRegistry->getManager();

                    $transaction = new Transaction();

                    $transaction->setUser($user);
                    $transaction->setCurrency($currencyRepository->find(1));
                    $transaction->setAccount($accountRepository->find(3));
                    $transaction->setDirection('in');
                    $transaction->setSum(floatval(str_ireplace([',', ' '], '', $sheetDatum['C'])));
                    $transaction->setStatus($status);

                    $emTransaction->persist($transaction);
                    $emTransaction->flush();

                }

               // echo $key . 'пользователь: ' . $user->getId() . ' - ' . floatval(str_ireplace(',', '', $sheetDatum['B'])) . ' - ' . $resultA . PHP_EOL;
            }
        }

        return 'success';
    }

    public function correctUserBalances()
    {
        $existUser = [];

        $userRepository = $this->managerRegistry->getRepository('App:User');
        $currencyRepository = $this->managerRegistry->getRepository('App:Currency');
        $accountRepository = $this->managerRegistry->getRepository('App:Account');
        $transactionRepository = $this->managerRegistry->getRepository('App:Transaction');
        $transactionStatusRepository = $this->managerRegistry->getRepository('App:TransactionStatus');
        $status = $transactionStatusRepository->find(2);

        $inputFileName = $this->kernel->getRootDir() . '/../public/upload/correct_balance.xlsx';
        $spreadsheet = IOFactory::load($inputFileName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        foreach ($sheetData as $key => $sheetDatum) {

            if (!empty($sheetDatum['A'])){
                $user = $userRepository->findOneByUsername($sheetDatum['A']);

                if (!empty($user) ) {
                    $total['personal'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(1), $status);
                    $total['invest'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(2), $status);
                    $total['referral'] = $transactionRepository->getTotalUserAccount($user, $accountRepository->find(3), $status);

                    if ($total['personal'] != floatval(str_replace(' ', '', str_replace(',', '.', $sheetDatum['B'])))){
                        $diff = $total['personal'] - floatval(str_replace(' ', '', str_replace(',', '.', $sheetDatum['B'])));

                        if ($diff > 0) {
                            $direction = 'out';
                        } else {
                            $direction = 'in';
                        }

                        $emInvestTransaction = $this->managerRegistry->getManager();

                        $transaction = new Transaction();

                        $transaction->setUser($user);
                        $transaction->setCurrency($currencyRepository->find(1));
                        $transaction->setAccount($accountRepository->find(1));
                        $transaction->setDirection($direction);
                        $transaction->setSum(floatval(abs($diff)));
                        $transaction->setStatus($status);

                        $emInvestTransaction->persist($transaction);
                        $emInvestTransaction->flush();

                        echo $direction . '-' . ($total['personal'] - floatval(str_replace(' ', '', str_replace(',', '.', $sheetDatum['B'])))) . '/' . floatval(abs($diff))  . PHP_EOL;
                    }

                    if ($total['invest'] != floatval(str_replace(' ', '', str_replace(',', '.', $sheetDatum['C'])))){

                        $diff = $total['invest'] - floatval(str_replace(' ', '', str_replace(',', '.', $sheetDatum['C'])));

                        if ($diff > 0) {
                            $direction = 'out';
                        } else {
                            $direction = 'in';
                        }

                        $emInvestTransaction = $this->managerRegistry->getManager();

                        $transaction = new Transaction();

                        $transaction->setUser($user);
                        $transaction->setCurrency($currencyRepository->find(1));
                        $transaction->setAccount($accountRepository->find(2));
                        $transaction->setDirection($direction);
                        $transaction->setSum(floatval(abs($diff)));
                        $transaction->setStatus($status);

                        $emInvestTransaction->persist($transaction);
                        $emInvestTransaction->flush();

                        echo $direction . '-' . ($total['invest'] - floatval(str_replace(' ', '', str_replace(',', '.', $sheetDatum['C'])))) . '/' . floatval(abs($diff))  . PHP_EOL;
                    }

                    if ($total['referral'] != floatval(str_replace(' ', '', str_replace(',', '.', $sheetDatum['D'])))){
                        $diff = $total['referral'] - floatval(str_replace(' ', '', str_replace(',', '.', $sheetDatum['D'])));

                        if ($diff > 0) {
                            $direction = 'out';
                        } else {
                            $direction = 'in';
                        }

                        $emInvestTransaction = $this->managerRegistry->getManager();

                        $transaction = new Transaction();

                        $transaction->setUser($user);
                        $transaction->setCurrency($currencyRepository->find(1));
                        $transaction->setAccount($accountRepository->find(3));
                        $transaction->setDirection($direction);
                        $transaction->setSum(floatval(abs($diff)));
                        $transaction->setStatus($status);

                        $emInvestTransaction->persist($transaction);
                        $emInvestTransaction->flush();

                        echo $direction . '-' . ($total['referral'] - floatval(str_replace(' ', '', str_replace(',', '.', $sheetDatum['D'])))) . '/' . abs($diff)  . PHP_EOL;
                    }

                    echo $key . 'пользователь: '
                        . $sheetDatum['A'] . ' - '
                        . $user->getId() . ' - '
                        . floatval($sheetDatum['B']) . ' - '
                        . floatval($sheetDatum['C']). ' - '
                        . $sheetDatum['D'] . ' - '
                        . implode(',', $total)
                        . PHP_EOL;
                }
            }
        }

        return 'success';
    }
}