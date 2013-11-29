<?php

namespace Nedwave\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PromoteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('nedwave:user:promote')
            ->setDescription('Promote a User to admin')
            ->addArgument('email', InputArgument::REQUIRED, 'User email to promote')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userManager = $this->getContainer()->get('nedwave_user.user_manager');
        $user = $userManager->getRepository()->findOneBy(array('email' => $input->getArgument('email')));
        
        if ($user) {
            $user->addRole('ROLE_ADMIN');
            $userManager->updateUser($user);
            
            $output->writeln('User with email \'' . $input->getArgument('email') . '\' is promoted to admin.');
        } else {
            $output->writeln('User with email \'' . $input->getArgument('email') . '\' not found.');
        }

    }
}