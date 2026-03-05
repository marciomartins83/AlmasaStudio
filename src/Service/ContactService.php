<?php

namespace App\Service;

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
use Doctrine\ORM\EntityManagerInterface;

class ContactService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function addTelefone(int $pessoaId, array $data): int
    {
        $pessoa = $this->em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            throw new \RuntimeException('Pessoa não encontrada.');
        }

        $tipoTelefone = $this->em->getRepository(TiposTelefones::class)->find((int) $data['tipo_telefone']);
        if (!$tipoTelefone) {
            throw new \RuntimeException('Tipo de telefone não encontrado.');
        }

        $this->em->beginTransaction();
        try {
            $telefone = new Telefones();
            $telefone->setNumero($data['numero']);
            $telefone->setTipo($tipoTelefone);
            $this->em->persist($telefone);

            $vinculo = new PessoasTelefones();
            $vinculo->setIdPessoa($pessoaId);
            $vinculo->setIdTelefone($telefone->getId());
            $this->em->persist($vinculo);

            $this->em->flush();
            $this->em->commit();

            return $telefone->getId();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \RuntimeException('Erro ao salvar telefone: ' . $e->getMessage());
        }
    }

    public function addEmail(int $pessoaId, array $data): int
    {
        $pessoa = $this->em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            throw new \RuntimeException('Pessoa não encontrada.');
        }

        $tipoEmail = $this->em->getRepository(TiposEmails::class)->find((int) $data['tipo_email']);
        if (!$tipoEmail) {
            throw new \RuntimeException('Tipo de email não encontrado.');
        }

        $this->em->beginTransaction();
        try {
            $email = new Emails();
            $email->setEmail($data['email']);
            $email->setTipo($tipoEmail);
            $this->em->persist($email);

            $vinculo = new PessoasEmails();
            $vinculo->setIdPessoa($pessoaId);
            $vinculo->setIdEmail($email->getId());
            $this->em->persist($vinculo);

            $this->em->flush();
            $this->em->commit();

            return $email->getId();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \RuntimeException('Erro ao salvar email: ' . $e->getMessage());
        }
    }

    public function addEndereco(int $pessoaId, array $data): int
    {
        $pessoa = $this->em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            throw new \RuntimeException('Pessoa não encontrada.');
        }

        $logradouro = $this->em->getRepository(Logradouros::class)->find((int) $data['id_logradouro']);
        if (!$logradouro) {
            throw new \RuntimeException('Logradouro não encontrado.');
        }

        $tipoEndereco = null;
        if (!empty($data['id_tipo_endereco'])) {
            $tipoEndereco = $this->em->getRepository(TiposEnderecos::class)->find((int) $data['id_tipo_endereco']);
        }
        if (!$tipoEndereco) {
            $tipoEndereco = $this->em->getRepository(TiposEnderecos::class)->find(1);
        }
        if (!$tipoEndereco) {
            throw new \RuntimeException('Tipo de endereço não encontrado.');
        }

        $endereco = new Enderecos();
        $endereco->setPessoa($pessoa);
        $endereco->setLogradouro($logradouro);
        $endereco->setTipo($tipoEndereco);
        $endereco->setEndNumero((int) $data['numero']);
        $endereco->setComplemento($data['complemento'] ?? null);
        $this->em->persist($endereco);
        $this->em->flush();

        return $endereco->getId();
    }

    public function addConta(int $pessoaId, array $data): int
    {
        $pessoa = $this->em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            throw new \RuntimeException('Pessoa não encontrada.');
        }

        $banco = $this->em->getRepository(Bancos::class)->find((int) $data['id_banco']);
        if (!$banco) {
            throw new \RuntimeException('Banco não encontrado.');
        }

        $tipoConta = $this->em->getRepository(TiposContasBancarias::class)->find((int) $data['id_tipo_conta']);
        if (!$tipoConta) {
            throw new \RuntimeException('Tipo de conta não encontrado.');
        }

        $agencia = null;
        if (!empty($data['id_agencia'])) {
            $agencia = $this->em->getRepository(Agencias::class)->find((int) $data['id_agencia']);
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
        $this->em->persist($conta);
        $this->em->flush();

        return $conta->getId();
    }

    public function addPix(int $pessoaId, array $data): int
    {
        $pessoa = $this->em->getRepository(Pessoas::class)->find($pessoaId);
        if (!$pessoa) {
            throw new \RuntimeException('Pessoa não encontrada.');
        }

        $pix = new ChavesPix();
        $pix->setIdPessoa($pessoaId);
        $pix->setIdTipoChave((int) $data['id_tipo_chave']);
        $pix->setChavePix($data['chave_pix']);
        $pix->setPrincipal(false);
        $pix->setAtivo(true);
        $this->em->persist($pix);
        $this->em->flush();

        return $pix->getId();
    }
}
