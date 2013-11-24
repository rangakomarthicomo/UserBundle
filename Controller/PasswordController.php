<?php

namespace Nedwave\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Nedwave\MandrillBundle\Message;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Password controller.
 *
 * @Route("/password")
 */
class PasswordController extends Controller
{
    /**
     * @Route("/change", name="user_password_change")
     * @Template()
     */
    public function changeAction(Request $request)
    {        
        $userManager = $this->get('nedwave_user.user_manager');
        $entity = $userManager->getRepository()->find($this->getUser()->getId());

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }
        
        $form = $this->createForm('nedwave_user_change_password', $entity, array(
            'action' => $this->generateUrl('user_password_change'),
            'method' => 'PUT',
        ));
        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $userManager->updatePassword($entity);
            $userManager->updateUser($entity);
            
            $this->get('session')->getFlashBag()->add('notice', 'password.change.success');
            return $this->redirect($this->generateUrl('user_password_change'));
        }
        
        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/reset/{confirmationToken}", name="user_password_reset")
     * @Template()
     */
    public function resetAction(Request $request, $confirmationToken)
    {
        $userManager = $this->get('nedwave_user.user_manager');
        $entity = $userManager->getRepository()->findOneByConfirmationToken($confirmationToken);
        
        if (!$entity) {
            $this->get('session')->getFlashBag()->add('notice', 'password.reset.token_not_found');
            return $this->redirect($this->generateUrl('user_password_request'));
        }
        
        if (!$entity->isPasswordRequestNonExpired()) {
            $this->get('session')->getFlashBag()->add('notice', 'password.reset.token_is_expired');
            return $this->redirect($this->generateUrl('user_password_request'));
        }
        
        $form = $this->createForm('nedwave_user_change_password', $entity, array(
            'action' => $this->generateUrl('user_password_reset', array('confirmationToken' => $confirmationToken)),
            'method' => 'PUT',
        ));
        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $entity->setActive(true);
            $entity->setConfirmed(true);
            $entity->setConfirmationToken(null);
            $entity->setPasswordRequestedAt(null);
            $userManager->updatePassword($entity);
            $userManager->updateUser($entity);

            $token = new UsernamePasswordToken($entity, null, 'main', $entity->getRoles());
            $this->get('security.context')->setToken($token);

            return $this->container->get('authentication_handler')->onAuthenticationSuccess($request, $token);
        }
        
        return array(
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/request", name="user_password_request")
     * @Template()
     */
    public function requestAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('email', 'email', array('attr' => array('placeholder' => 'Email')))
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $data = $form->getData();
            
            $userManager = $this->get('nedwave_user.user_manager');
            $entity = $userManager->getRepository()->findOneByEmail(strtolower($data['email']));
            
            if (!$entity) {
                $this->get('session')->getFlashBag()->add('notice', 'password.request.email_not_found');
            
                return array(
                    'form' => $form->createView()
                );
            }
            
            $entity->setConfirmationToken(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36));
            $entity->setPasswordRequestedAt(new \DateTime());
            
            $userManager->updateUser($entity);
            
            $dispatcher = $this->get('nedwave_mandrill.dispatcher');
            $template = $this->get('twig')->loadTemplate('NedwaveUserBundle:Email:Password/request.html.twig');
            $parameters = array(
                'user' => $entity,
                'url' => $this->generateUrl('user_password_reset', array('confirmationToken' => $entity->getConfirmationToken()), true)
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

            //Becomes useless and annoying when not truncating the flashbag...
            //$this->get('session')->getFlashBag()->add('notice', 'password.request.success');
            
            return array(
                'form' => $form->createView(),
                'success' => true,
                'email' => $entity->getEmail()
            );
        }
        
        return array(
            'form' => $form->createView()
        );
    }

}
