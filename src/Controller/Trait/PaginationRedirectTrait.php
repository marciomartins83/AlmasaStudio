<?php

namespace App\Controller\Trait;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

trait PaginationRedirectTrait
{
    /**
     * Redireciona para o index preservando página, ordenação e filtros.
     * Usa a URL salva na session pelo PaginationService.
     */
    protected function redirectToIndex(Request $request, string $indexRoute): RedirectResponse
    {
        $session = $request->getSession();
        $savedUrl = $session->get('_pagination_return_url_' . $indexRoute);

        if ($savedUrl) {
            return new RedirectResponse($savedUrl);
        }

        return $this->redirectToRoute($indexRoute);
    }
}
