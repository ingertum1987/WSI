<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class LoginListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->getMethod() == 'POST'
            && !empty($request->request->get('phoneCode'))
            && $request->request->get('phoneNumber')){


            $phoneCode = $request->request->get('phoneCode');
            $phoneNumber = $request->request->get('phoneNumber');
            $username = $phoneCode . $phoneNumber;

            $request->request->set('_username', $username);        }
    }
}