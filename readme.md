# PHPSecureLogin
[![CodeFactor](https://www.codefactor.io/repository/github/merchizm/phpsecurelogin/badge)](https://www.codefactor.io/repository/github/merchizm/phpsecurelogin)
[![PHP version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://github.com/phpredis/phpredis)

By this project, you will have a more secure and strong authentication system. 

The project runs on PHP 8.1, uses redis as session handler. Using Redis as a session handler benefits us in many ways. 
* Sessions on native PHP are kept through files. Redis, on the other hand, keeps data in memory, making it easier to access. 
* Information held with Redis is collected in a central area. In this way, all application nodes can share session information in this environment and session information can be preserved when switching between products.(Example: Youtube & Adsense)

Additionally, there is two-factor authentication on the system.

### What are the benefits of using two-factor authentication? <sub><sup>[source](https://uoa.custhelp.com/app/answers/detail/a_id/12689/~/benefits-of-using-two-factor-authentication#:~:text=Two%2Dfactor%20authentication%20works%20as,harder%20and%20reduce%20fraud%20risks.)</sup></sub>
Two-factor authentication is an authentication mechanism to double check your identity is legitimate.

When you want to sign into your account, you are prompted to authenticate with a username and a password - that's the first verification layer. Two-factor authentication works as an extra step in the process, a second security layer, that will re-confirm your identity. Its purpose is to make attackers' life harder and reduce fraud risks.

If you already follow basic password security measures, two-factor authentication will make it more difficult for cyber criminals to breach your account because it is hard to get the second authentication factor, they would have to be much closer to you. This drastically reduces their chances to succeed.


## Prerequisites
* A PHP 8.1 web server running LAMP or LEMP on a server or your own device.
* Where [Redis](https://redis.io/) is installed a server or your own device.

## Requirments
* PHP PDO Extension
* PHP IMAP Extension
* PHP [Redis](https://github.com/phpredis/phpredis) Extension
* [Composer](https://getcomposer.org/download/)

## Installing/Configuring
..
## Tests
..