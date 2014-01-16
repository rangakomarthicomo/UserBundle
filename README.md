UserBundle
==========

User management for Symfony2. Compatible with Doctrine ORM

Features:

 * [MandrillBundle](https://github.com/Nedwave/MandrillBundle) integration

The default routing of this bundle expects the following parameters in parameters.yml, the values are customizable

```
locale: en
required_locales: en|nl
```


## Installation

Install package with composer 
``` json
"nedwave/user-bundle": "*"
```

Register bundles in AppKernel
``` php
new Nedwave\MandrillBundle\NedwaveMandrillBundle(),
new Nedwave\UserBundle\NedwaveUserBundle(),
```

Extend the User Entity from the bundle
``` php
<?php

namespace Acme\DemoBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Nedwave\UserBundle\Entity\User as BaseUser;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity()
 */
class User extends BaseUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
}
```

Update config.yml
``` yaml
# Nedwave User Bundle
nedwave_user:
    user_class: Acme\DemoBundle\Entity\User # Your user class
    firewall_name: main

nedwave_mandrill:
    api_key: %mandrill_api_key%
    default:
        sender: info@nedwave.com # Your sender e-mail
        sender_name: Nedwave # Your sender name
```

Update parameters.yml and fill in your app settings
``` yaml
locale: en
required_locales: en|nl

mandrill_api_key: <secret>
```

Update parameters.yml.dist
``` yaml
locale: en
required_locales: en|nl

mandrill_api_key: ~
```

Update security.yml
``` yaml
security:
    encoders:
        Acme\DemoBundle\Entity\User: sha512 # Your user class

    role_hierarchy:
        ROLE_ADMIN:       ROLE_USER
        ROLE_SUPER_ADMIN: [ROLE_USER, ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
    
    access_decision_manager:
        strategy: unanimous

    providers:
        doctrine:
            id: nedwave_user.user_provider

    firewalls:            
        main:
            pattern:    ^/
            anonymous:  ~
            context: application
            form_login:
                login_path:  login
                check_path:  login_check
            
            logout:
                path:   logout
                target: /

    access_control:
        - { path: ^/%locale%/login, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/%locale%/password/reset, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/%locale%/password/request, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/%locale%/password/change, role: IS_AUTHENTICATED_FULLY }
        - { path: ^/%locale%/dashboard, role: IS_AUTHENTICATED_FULLY }
```

Update routing.yml
```yml
nedwave_user:
    resource: "@NedwaveUserBundle/Resources/config/routing.yml"
    prefix:   /
```
