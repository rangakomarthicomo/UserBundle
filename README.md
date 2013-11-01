UserBundle
==========

User management for Symfony2. Compatible with Doctrine ORM

Features:

 * Mandrill mailer
 * HWIOAuthBundle integration: facebook, twitter, google login by default


## Installation

Install package with composer 
``` json
"nedwave/user-bundle": "*"
```

Register bundles in AppKernel
``` php
new Hip\MandrillBundle\HipMandrillBundle(),
new HWI\Bundle\OAuthBundle\HWIOAuthBundle(),
new Nedwave\UserBundle\NedwaveUserBundle(),
```

Update config.yml
``` yaml
# Nedwave User Bundle
nedwave_user:
    user_class: Nedwave\MainBundle\Entity\User
    firewall_name: main

hip_mandrill:
    api_key: %mandrill_api_key%
    default:
        sender: info@nedwave.com
        sender_name: Nedwave

hwi_oauth:
    firewall_name: main
    
    resource_owners:
        facebook:
            type: facebook
            client_id: %facebook_client_id%
            client_secret: %facebook_client_secret%
            scope: email
        
        twitter:
            type: twitter
            client_id: %twitter_client_id%
            client_secret: %twitter_client_secret%
        
        google:
            type: google
            client_id: %google_client_id%
            client_secret: %google_client_secret%
            scope: "https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile"
    
    connect:
        confirmation: false
```

Update parameters.yml.dist
``` yaml
facebook_client_id: ~
facebook_client_secret: ~
twitter_client_id: ~
twitter_client_secret: ~
google_client_id: ~
google_client_secret: ~
```

Update security.yml
``` yaml
security:
    encoders:
        Nedwave\MainBundle\Entity\User: sha512

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
            
            oauth:
                resource_owners:
                    facebook: login_facebook
                    twitter: login_twitter
                    google: login_google
                login_path: login
                failure_path: login

                oauth_user_provider:
                    service: nedwave_user.user_provider

    access_control:
        - { path: ^/%locale%/login, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/%locale%/password/reset, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/%locale%/password/request, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/%locale%/password/change, role: IS_AUTHENTICATED_FULLY }
        - { path: ^/%locale%/dashboard, role: IS_AUTHENTICATED_FULLY }
```
