<?php

namespace App\Controller\Api;

use App\Entity\PessoasTelefones;
use App\Entity\PessoasEmails;
use App\Entity\Enderecos;
use App\Entity\ContasBancarias;
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
        $data = json_decode($request->getContent(), true);
        
        $pessoa = $em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            return new JsonResponse(['success' => false, 'message' => 'Pessoa não encontrada']);
        }

        $telefone = new PessoasTelefones();
        $telefone->setIdPessoa($pessoaId);
        $telefone->setNumero($data['numero']);
        $telefone->setIdTipoTelefone($data['tipo_telefone']);
        
        $em->persist($telefone);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $telefone->getId()]);
    }

    #[Route('/email/{pessoaId}', name: 'api_email_add', methods: ['POST'])]
    public function addEmail(int $pessoaId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $pessoa = $em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            return new JsonResponse(['success' => false, 'message' => 'Pessoa não encontrada']);
        }

        $email = new PessoasEmails();
        $email->setIdPessoa($pessoaId);
        $email->setEmail($data['email']);
        $email->setIdTipoEmail($data['tipo_email']);
        
        $em->persist($email);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $email->getId()]);
    }

    #[Route('/endereco/{pessoaId}', name: 'api_endereco_add', methods: ['POST'])]
    public function addEndereco(int $pessoaId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $pessoa = $em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            return new JsonResponse(['success' => false, 'message' => 'Pessoa não encontrada']);
        }

        $endereco = new Enderecos();
        $endereco->setIdPessoa($pessoaId);
        $endereco->setCep($data['cep']);
        $endereco->setNumero($data['numero']);
        $endereco->setComplemento($data['complemento'] ?? null);
        $endereco->setIdLogradouro($data['id_logradouro']);
        
        $em->persist($endereco);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $endereco->getId()]);
    }

    #[Route('/conta/{pessoaId}', name: 'api_conta_add', methods: ['POST'])]
    public function addConta(int $pessoaId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $pessoa = $em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            return new JsonResponse(['success' => false, 'message' => 'Pessoa não encontrada']);
        }

        $conta = new ContasBancarias();
        $conta->setIdPessoa($pessoaId);
        $conta->setIdBanco($data['id_banco']);
        $conta->setIdTipoConta($data['id_tipo_conta']);
        $conta->setAgencia($data['agencia']);
        $conta->setConta($data['conta']);
        $conta->setIdChavePix($data['id_chave_pix'] ?? null);
        
        $em->persist($conta);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $conta->getId()]);
    }

    #[Route('/pix/{pessoaId}', name: 'api_pix_add', methods: ['POST'])]
    public function addPix(int $pessoaId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $pessoa = $em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            return new JsonResponse(['success' => false, 'message' => 'Pessoa não encontrada']);
        }

        $pix = new ChavesPix();
        $pix->setIdPessoa($pessoaId);
        $pix->setIdTipoChavePix($data['id_tipo_chave_pix']);
        $pix->setChave($data['chave']);
        
        $em->persist($pix);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $pix->getId()]);
    }
}
