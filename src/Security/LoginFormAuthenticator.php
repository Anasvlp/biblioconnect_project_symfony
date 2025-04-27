<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        // Récupère les informations du formulaire
        $email = $request->get('email'); // Utilise get() pour récupérer les champs du formulaire
        $password = $request->get('password');
        $csrfToken = $request->get('_csrf_token'); // Assure-toi que le champ _csrf_token est bien dans ton formulaire

        // Enregistrer le dernier email dans la session pour l'affichage du nom d'utilisateur
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // Retourner le passport avec l'utilisateur, les credentials et les badges (CSRF et "remember me")
        return new Passport(
            new UserBadge($email), // Utilise l'email comme identifiant unique
            new PasswordCredentials($password), // Utilise le mot de passe fourni
            [
                new CsrfTokenBadge('authenticate', $csrfToken), // Valide le token CSRF
                new RememberMeBadge(), // Permet l'option "Se souvenir de moi"
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Si un chemin cible est enregistré dans la session (par exemple, une page précédente demandée)
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Si aucune page précédente, redirige vers la page d'accueil ou autre route spécifique
        return new RedirectResponse($this->urlGenerator->generate('app_homepage')); // Remplace 'app_homepage' par la route de ton choix
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
