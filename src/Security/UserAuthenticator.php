<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class UserAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        // 🔍 DEBUG - Primeiro dump logo no início
        dump("=== MÉTODO AUTHENTICATE INICIADO ===");
        
        try {
            // 🔍 DEBUG - Verificar se request existe
            dump("Request recebido:", $request);
            
            // 🔍 DEBUG - Verificar dados do request
            dump("Request data:", $request->request->all());
            
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $csrfToken = $request->request->get('_csrf_token');
            
            // 🔍 DEBUG - Verificar valores recebidos
            dump("Email recebido:", $email);
            dump("Password recebido:", $password);
            dump("CSRF Token recebido:", $csrfToken);
            
            // 🔍 DEBUG - Verificar se são vazios
            dump("Email vazio?", empty($email));
            dump("Password vazio?", empty($password));
            
            if (empty($email) || empty($password)) {
                dump("=== ERRO: Email ou senha vazios ===");
                throw new \Exception('O e-mail ou a senha não foram preenchidos.');
            }
            
            dump("=== CRIANDO PASSPORT ===");
            
            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
            
            $passport = new Passport(
                new UserBadge($email),
                new PasswordCredentials($password),
                [
                    new CsrfTokenBadge('authenticate', $csrfToken),
                    new RememberMeBadge(),
                ]
            );
            
            dump("=== PASSPORT CRIADO COM SUCESSO ===");
            
            return $passport;
            
        } catch (\Exception $e) {
            dump("=== EXCEPTION NO AUTHENTICATE ===");
            dump("Erro:", $e->getMessage());
            dump("Trace:", $e->getTraceAsString());
            throw $e;
        }
    }


    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
