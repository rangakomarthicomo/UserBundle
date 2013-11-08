<?php

namespace Nedwave\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Nedwave\MandrillBundle\Message;

/**
 * Registration controller.
 */
class RegistrationController extends Controller
{
    /**
     * @Route("/register", name="user_registration_register")
     * @Template()
     */
    public function registerAction(Request $request)
    {
        $userManager = $this->get('nedwave_user.user_manager');
        $entity = $userManager->createUser();
                
        $form = $this->createForm('nedwave_user_registration', $entity, array(
            'action' => $this->generateUrl('user_registration_register'),
            'method' => 'POST',
        ));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $userManager->updatePassword($entity);
            $entity->setActive(false);
            $entity->setConfirmationToken(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36));
            
            $userManager->updateUser($entity);
            
            $dispatcher = $this->get('nedwave_mandrill.dispatcher');
            $template = $this->get('twig')->loadTemplate('NedwaveUserBundle:Email:Registration/register.html.twig');
            $parameters = array(
                'user' => $entity,
                'url' => $this->generateUrl('user_registration_confirm', array('confirmationToken' => $entity->getConfirmationToken()), true)
            );
            
            $subject = $template->renderBlock('subject', $parameters);
            $bodyHtml = $template->renderBlock('body_html', $parameters);
            $bodyText = $template->renderBlock('body_text', $parameters);
            
            $message = new Message();
            $message
                ->addTo($entity->getEmail())
                ->setSubject($subject)
                ->setHtml($bodyHtml)
                ->setText($bodyText);
            $result = $dispatcher->send($message);
            
            $this->get('session')->getFlashBag()->add('notice', 'registration.register.success');

            return array(
                'form'   => $form->createView(),
            );
        }

        return array(
            'form'   => $form->createView(),
        );
    }

    /**
     * @Route("/confirm/{confirmationToken}", name="user_registration_confirm")
     * @Template()
     */
    public function confirmAction(Request $request, $confirmationToken)
    {
        $userManager = $this->get('nedwave_user.user_manager');
        $entity = $userManager->getRepository()->findOneByConfirmationToken($confirmationToken);
        
        if (!$entity) {
            return array(
                'message' => 'registration.confirm.token_not_found'
            );
        }
        
        if ($entity->getUpdatePasswordOnConfirmation()) {
        
            $form = $this->createForm('nedwave_user_change_password', $entity, array(
                'action' => $this->generateUrl('user_registration_confirm', array('confirmationToken' => $confirmationToken)),
                'method' => 'POST',
            ));
            $form->handleRequest($request);
            
            if ($form->isValid()) {
                $entity->setActive(true);
                $entity->setConfirmed(true);
                $entity->setConfirmationToken(null);
                $entity->getUpdatePasswordOnConfirmation(null);

                $userManager->updatePassword($entity);
                $userManager->updateUser($entity);
                
                return array(
                    'message' => 'registration.confirm.success'
                );
            }
            
            return array(
                'message' => 'registration.confirm.update_password',
                'form' => $form->createView()
            );
            
        } else {
        
            $entity->setActive(true);
            $entity->setConfirmed(true);
            $entity->setConfirmationToken(null);
            
            $userManager->updateUser($entity);
                    
            $token = new UsernamePasswordToken($entity, null, $this->container->getParameter('nedwave_user.firewall_name'), $entity->getRoles());
            $this->get('security.context')->setToken($token);
    
            $event = new InteractiveLoginEvent($this->getRequest(), $token);
            $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
            
            return array(
                'message' => 'registration.confirm.success'
            );
        }
    }

}
