<?php

namespace App\Controller\Api;

use App\Service\ContactService;
use App\Service\CepService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/contact')]
class ContactController extends AbstractController
{
    public function __construct(
        private ContactService $contactService
    ) {}

    #[Route('/cep/{cep}', name: 'api_cep', methods: ['GET'])]
    public function buscarCEP(string $cep, CepService $cepService): JsonResponse
    {
        $result = $cepService->buscarCEP($cep);
        return new JsonResponse($result);
    }

    #[Route('/telefone/{pessoaId}', name: 'api_telefone_add', methods: ['POST'])]
    public function addTelefone(int $pessoaId, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['numero']) || empty($data['tipo_telefone'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campos obrigatórios: numero, tipo_telefone.'], 422);
        }

        try {
            $id = $this->contactService->addTelefone($pessoaId, $data);
            return new JsonResponse(['success' => true, 'id' => $id]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/email/{pessoaId}', name: 'api_email_add', methods: ['POST'])]
    public function addEmail(int $pessoaId, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['email']) || empty($data['tipo_email'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campos obrigatórios: email, tipo_email.'], 422);
        }

        try {
            $id = $this->contactService->addEmail($pessoaId, $data);
            return new JsonResponse(['success' => true, 'id' => $id]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/endereco/{pessoaId}', name: 'api_endereco_add', methods: ['POST'])]
    public function addEndereco(int $pessoaId, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['numero']) || empty($data['id_logradouro'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campos obrigatórios: numero, id_logradouro.'], 422);
        }

        try {
            $id = $this->contactService->addEndereco($pessoaId, $data);
            return new JsonResponse(['success' => true, 'id' => $id]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/conta/{pessoaId}', name: 'api_conta_add', methods: ['POST'])]
    public function addConta(int $pessoaId, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['id_banco']) || empty($data['id_tipo_conta']) || empty($data['codigo'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campos obrigatórios: id_banco, id_tipo_conta, codigo.'], 422);
        }

        try {
            $id = $this->contactService->addConta($pessoaId, $data);
            return new JsonResponse(['success' => true, 'id' => $id]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/pix/{pessoaId}', name: 'api_pix_add', methods: ['POST'])]
    public function addPix(int $pessoaId, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['id_tipo_chave']) || empty($data['chave_pix'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campos obrigatórios: id_tipo_chave, chave_pix.'], 422);
        }

        try {
            $id = $this->contactService->addPix($pessoaId, $data);
            return new JsonResponse(['success' => true, 'id' => $id]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
