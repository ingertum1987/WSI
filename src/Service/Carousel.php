<?php
namespace App\Service;


class Carousel
{
    public function getCarouselImages($rootDir)
    {
        $carouselImages = scandir($rootDir .'/assets/carousel/');
        unset($carouselImages[0]);
        unset($carouselImages[1]);
        natsort($carouselImages);
        $carouselImages = array_values($carouselImages);

        return $carouselImages;
    }
}