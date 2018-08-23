<?php
namespace App\Service;

use App\Entity\Country;
use Doctrine\Common\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpKernel\KernelInterface;


class ImportCountry
{
    private $managerRegistry;
    private $kernel;

    public function __construct(
        ManagerRegistry $managerRegistry,
        KernelInterface $kernel)
    {
        $this->managerRegistry = $managerRegistry;
        $this->kernel = $kernel;
    }

    public function importCountry()
    {
        $existCountry = [];

        $countryRepository = $this->managerRegistry->getRepository('App:Country');

        $inputFileName = $this->kernel->getRootDir() . '/../public/upload/import_countries.xlsx';
        $spreadsheet = IOFactory::load($inputFileName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        foreach ($sheetData as $key => $sheetDatum) {

                $emCountry = $this->managerRegistry->getManager();

                $addCountry = new Country();

                $addCountry->setName($sheetDatum['A']);
                $addCountry->setPhoneCode($sheetDatum['B']);

                $emCountry->persist($addCountry);
                $emCountry->flush();
                $emCountry->clear();
        }

        return 'success';
    }
}