<?php

namespace App\Controller\Api;

use App\Entity\Telefones;
use App\Entity\PessoasTelefones;
use App\Entity\Emails;
use App\Entity\PessoasEmails;
use App\Entity\Enderecos;
use App\Entity\Logradouros;
use App\Entity\TiposEnderecos;
use App\Entity\TiposTelefones;
use App\Entity\TiposEmails;
use App\Entity\ContasBancarias;
use App\Entity\Bancos;
use App\Entity\Agencias;
use App\Entity\TiposContasBancarias;
use App\Entity\ChavesPix;
use App\Entity\Pessoas;
use App\Service\CepService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/contact')]
class ContactController extends AbstractController
{
    #[Route('/cep/{cep}', name: 'api_cep', methods: ['GET'])]
    public function buscarCEP(string $cep, CepService $cepService): JsonResponse
    {
        $result = $cepService->buscarCEP($cep);
        return new JsonResponse($result);
    }

    #[Route('/telefone/{pessoaId}', name: 'api_telefone_add', methods: ['POST'])]
    public function addTelefone(int $pessoaId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['numero']) || empty($data['tipo_telefone'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campos obrigatórios: numero, tipo_telefone.'], 422);
        }

        $pessoa = $em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            return new JsonResponse(['success' => false, 'message' => 'Pessoa não encontrada.'], 404);
        }

        $tipoTelefone = $em->getRepository(TiposTelefones::class)->find((int) $data['tipo_telefone']);
        if (!$tipoTelefone) {
            return new JsonResponse(['success' => false, 'message' => 'Tipo de telefone não encontrado.'], 404);
        }

        $em->beginTransaction();
        try {
            $telefone = new Telefones();
            $telefone->setNumero($data['numero']);
            $telefone->setTipo($tipoTelefone);
            $em->persist($telefone);
            $em->flush();

            $vinculo = new PessoasTelefones();
            $vinculo->setIdPessoa($pessoaId);
            $vinculo->setIdTelefone($telefone->getId());
            $em->persist($vinculo);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            return new JsonResponse(['success' => false, 'message' => 'Erro ao salvar telefone.'], 500);
        }

        return new JsonResponse(['success' => true, 'id' => $telefone->getId()]);
    }

    #[Route('/email/{pessoaId}', name: 'api_email_add', methods: ['POST'])]
    public function addEmail(int $pessoaId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['email']) || empty($data['tipo_email'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campos obrigatórios: email, tipo_email.'], 422);
        }

        $pessoa = $em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            return new JsonResponse(['success' => false, 'message' => 'Pessoa não encontrada.'], 404);
        }

        $tipoEmail = $em->getRepository(TiposEmails::class)->find((int) $data['tipo_email']);
        if (!$tipoEmail) {
            return new JsonResponse(['success' => false, 'message' => 'Tipo de email não encontrado.'], 404);
        }

        $em->beginTransaction();
        try {
            $email = new Emails();
            $email->setEmail($data['email']);
            $email->setTipo($tipoEmail);
            $em->persist($email);
            $em->flush();

            $vinculo = new PessoasEmails();
            $vinculo->setIdPessoa($pessoaId);
            $vinculo->setIdEmail($email->getId());
            $em->persist($vinculo);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            return new JsonResponse(['success' => false, 'message' => 'Erro ao salvar email.'], 500);
        }

        return new JsonResponse(['success' => true, 'id' => $email->getId()]);
    }

    #[Route('/endereco/{pessoaId}', name: 'api_endereco_add', methods: ['POST'])]
    public function addEndereco(int $pessoaId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['numero']) || empty($data['id_logradouro'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campos obrigatórios: numero, id_logradouro.'], 422);
        }

        $pessoa = $em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            return new JsonResponse(['success' => false, 'message' => 'Pessoa não encontrada.'], 404);
        }

        $logradouro = $em->getRepository(Logradouros::class)->find((int) $data['id_logradouro']);
        if (!$logradouro) {
            return new JsonResponse(['success' => false, 'message' => 'Logradouro não encontrado.'], 404);
        }

        $tipoEndereco = null;
        if (!empty($data['id_tipo_endereco'])) {
            $tipoEndereco = $em->getRepository(TiposEnderecos::class)->find((int) $data['id_tipo_endereco']);
        }
        if (!$tipoEndereco) {
            $tipoEndereco = $em->getRepository(TiposEnderecos::class)->find(1);
        }
        if (!$tipoEndereco) {
            return new JsonResponse(['success' => false, 'message' => 'Tipo de endereço não encontrado.'], 422);
        }

        $endereco = new Enderecos();
        $endereco->setPessoa($pessoa);
        $endereco->setLogradouro($logradouro);
        $endereco->setTipo($tipoEndereco);
        $endereco->setEndNumero((int) $data['numero']);
        $endereco->setComplemento($data['complemento'] ?? null);
        $em->persist($endereco);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $endereco->getId()]);
    }

    #[Route('/conta/{pessoaId}', name: 'api_conta_add', methods: ['POST'])]
    public function addConta(int $pessoaId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['id_banco']) || empty($data['id_tipo_conta']) || empty($data['codigo'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campos obrigatórios: id_banco, id_tipo_conta, codigo.'], 422);
        }

        $pessoa = $em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            return new JsonResponse(['success' => false, 'message' => 'Pessoa não encontrada.'], 404);
        }

        $banco = $em->getRepository(Bancos::class)->find((int) $data['id_banco']);
        if (!$banco) {
            return new JsonResponse(['success' => false, 'message' => 'Banco não encontrado.'], 404);
        }

        $tipoConta = $em->getRepository(TiposContasBancarias::class)->find((int) $data['id_tipo_conta']);
        if (!$tipoConta) {
            return new JsonResponse(['success' => false, 'message' => 'Tipo de conta não encontrado.'], 404);
        }

        $agencia = null;
        if (!empty($data['id_agencia'])) {
            $agencia = $em->getRepository(Agencias::class)->find((int) $data['id_agencia']);
        }

        $conta = new ContasBancarias();
        $conta->setIdPessoa($pessoa);
        $conta->setIdBanco($banco);
        $conta->setIdTipoConta($tipoConta);
        $conta->setIdAgencia($agencia);
        $conta->setCodigo($data['codigo']);
        $conta->setDigitoConta($data['digito_conta'] ?? null);
        $conta->setPrincipal(false);
        $conta->setAtivo(true);
        $conta->setRegistrada(false);
        $conta->setAceitaMultipag(false);
        $conta->setUsaEnderecoCobranca(false);
        $conta->setCobrancaCompartilhada(false);
        $em->persist($conta);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $conta->getId()]);
    }

    #[Route('/pix/{pessoaId}', name: 'api_pix_add', methods: ['POST'])]
    public function addPix(int $pessoaId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['id_tipo_chave']) || empty($data['chave_pix'])) {
            return new JsonResponse(['success' => false, 'message' => 'Campos obrigatórios: id_tipo_chave, chave_pix.'], 422);
        }

        $pessoa = $em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            return new JsonResponse(['success' => false, 'message' => 'Pessoa não encontrada.'], 404);
        }

        $pix = new ChavesPix();
        $pix->setIdPessoa($pessoaId);
        $pix->setIdTipoChave((int) $data['id_tipo_chave']);
        $pix->setChavePix($data['chave_pix']);
        $pix->setPrincipal(false);
        $pix->setAtivo(true);
        $em->persist($pix);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $pix->getId()]);
    }
}
