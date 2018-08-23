<?php

namespace App\Service;

use App\Service\Enum\MainMenu;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class MenuBuilder
{
    private $factory;
    private $authorizationChecker;

    /**
     * MenuBuilder constructor.
     * @param FactoryInterface $factory
     * @param AuthorizationChecker $authorizationChecker
     */
    public function __construct(FactoryInterface $factory, AuthorizationChecker $authorizationChecker)
    {
        $this->factory = $factory;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function createMainMenu()
    {
        $menu = $this->factory->createItem('root');
        foreach (MainMenu::ITEMS as $key => $item) {
            $isAdmin = $item['isAdmin'] ?? null;
            if ((!$isAdmin) || (($isAdmin) && ($this->authorizationChecker->isGranted('ROLE_ADMIN')))) {
                $menu
                    ->addChild('main.' . $key, ['route' => $item['route']])
                    ->setLinkAttributes([
                        'class' => 'waves-effect waves-dark',
                        'data-icon' => $item['icon'],
                        'aria-expanded' => false
                    ])
                    ->setExtra('translation_domain', 'menu');
            }
        }

        return $menu;
    }
}