# PHPSecureLogin
[![CodeFactor](https://www.codefactor.io/repository/github/merchizm/phpsecurelogin/badge)](https://www.codefactor.io/repository/github/merchizm/phpsecurelogin)
[![PHP version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://github.com/phpredis/phpredis)

By this project, you will have a more secure and strong authentication system. 

The project runs on PHP 8.1, uses redis as session handler. Using Redis as a session handler benefits us in many ways. 
* Sessions on native PHP are kept through files. Redis, on the other hand, keeps data in memory, making it easier to access. 
* Information held with Redis is collected in a central area. In this way, all application nodes can share session information in this environment and session information can be preserved when switching between products.(Example: Youtube & Adsense)

Additionally, there is two-factor authentication and hCaptcha Integration on the system.

### What are the benefits of using two-factor authentication?
Two-factor authentication is an authentication mechanism to double check your identity is legitimate.

When you want to sign into your account, you are prompted to authenticate with a username and a password - that's the first verification layer. Two-factor authentication works as an extra step in the process, a second security layer, that will re-confirm your identity. Its purpose is to make attackers' life harder and reduce fraud risks.

If you already follow basic password security measures, two-factor authentication will make it more difficult for cyber criminals to breach your account because it is hard to get the second authentication factor, they would have to be much closer to you. This drastically reduces their chances to succeed.

### What are the benefits of using (h)Captcha?
Essentially captchas deter hackers from abusing online services because they block robot software from submitting fake or nefarious online requests. Implementing captcha as an anti-spam tool is very effective and easy to install. It is available for free and provides websites with an extra layer of security in 3 different major areas:

* Protecting the registration form of the website from receiving useless information and bot accounts
* Preventing spam comments in the form of advertisement and messages
* Demonstrating customers to take security precautions in a profound way when it comes to sensitive information.

Other benefits of using a captcha form on your website are as follows:

* It protects the integrity of online polls by stopping the submission of repeated false responses.
* It prevents hackers from signing up for multiple email accounts that they plan to use for nefarious purposes.
* It prevents cybercriminals from spamming the contents of news or blog pages with dodgy comments and links to other websites.

## Prerequisites
* A PHP 8.1 web server running LAMP or LEMP on a server or your own device.
* Where [Redis](https://redis.io/) is installed a server or your own device.
* [hCaptcha](https://dashboard.hcaptcha.com/signup) Account 
* [Composer](https://getcomposer.org/download/)

## Requirments
* PHP PDO Extension
* PHP [Redis](https://github.com/phpredis/phpredis) Extension

## Installing/Configuring
..
## Tests
..

## Sources
* [Benefits of using two-factor authentication](https://uoa.custhelp.com/app/answers/detail/a_id/12689/~/benefits-of-using-two-factor-authentication)
* [What are the benefits of a CAPTCHA form for a website?](https://www.quora.com/What-are-the-benefits-of-a-CAPTCHA-form-for-a-website)
* [What is the best way to implement "remember me" for a website?](https://stackoverflow.com/questions/244882/what-is-the-best-way-to-implement-remember-me-for-a-website?noredirect=1&lq=1)
  * [Implementing Secure User Authentication in PHP Applications with Long-Term Persistence (Login with "Remember Me" Cookies)](https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence)
* [Introduction to JSON Web Tokens](https://jwt.io/introduction)
* [RFC 7519 - JSON Web Token (JWT)](https://datatracker.ietf.org/doc/html/rfc7519)
* [lcobucci/jwt](https://lcobucci-jwt.readthedocs.io/en/latest/)