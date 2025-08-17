<?php

namespace App\Tests\Entity;

use App\Entity\Pessoas;
use PHPUnit\Framework\TestCase;

class PessoasTest extends TestCase
{
    public function testEntityExists(): void
    {
        $this->assertTrue(class_exists(Pessoas::class));
    }

    public function testCpfGetterReturnsNull(): void
    {
        $pessoa = new Pessoas();
        
        // CPF agora vem da tabela de documentos, então retorna null por padrão
        $this->assertNull($pessoa->getCpf());
    }

    public function testCnpjGetterReturnsNull(): void
    {
        $pessoa = new Pessoas();
        
        // CNPJ agora vem da tabela de documentos, então retorna null por padrão
        $this->assertNull($pessoa->getCnpj());
    }

    public function testIsPessoaFisicaWithFisicaJuridicaFisica(): void
    {
        $pessoa = new Pessoas();
        $pessoa->setFisicaJuridica('fisica');
        
        $this->assertTrue($pessoa->isPessoaFisica());
        $this->assertFalse($pessoa->isPessoaJuridica());
    }

    public function testIsPessoaJuridicaWithFisicaJuridicaJuridica(): void
    {
        $pessoa = new Pessoas();
        $pessoa->setFisicaJuridica('juridica');
        
        $this->assertTrue($pessoa->isPessoaJuridica());
        $this->assertFalse($pessoa->isPessoaFisica());
    }

    public function testIsPessoaFisicaAndJuridicaWithOtherType(): void
    {
        $pessoa = new Pessoas();
        $pessoa->setFisicaJuridica('X'); // Tipo diferente
        
        $this->assertFalse($pessoa->isPessoaFisica());
        $this->assertFalse($pessoa->isPessoaJuridica());
    }

    public function testBasicProperties(): void
    {
        $pessoa = new Pessoas();
        $nome = 'João da Silva';
        $dtCadastro = new \DateTime();
        $tipoPessoa = 1;
        $status = true;
        $fisicaJuridica = 'F';
        
        $pessoa->setNome($nome);
        $pessoa->setDtCadastro($dtCadastro);
        $pessoa->setTipoPessoa($tipoPessoa);
        $pessoa->setStatus($status);
        $pessoa->setFisicaJuridica($fisicaJuridica);
        
        $this->assertEquals($nome, $pessoa->getNome());
        $this->assertEquals($dtCadastro, $pessoa->getDtCadastro());
        $this->assertEquals($tipoPessoa, $pessoa->getTipoPessoa());
        $this->assertEquals($status, $pessoa->getStatus());
        $this->assertEquals($fisicaJuridica, $pessoa->getFisicaJuridica());
    }

    public function testOptionalProperties(): void
    {
        $pessoa = new Pessoas();
        $dataNascimento = new \DateTime('1990-01-01');
        $nomePai = 'João Pai';
        $nomeMae = 'Maria Mãe';
        $renda = '5000.00';
        $observacoes = 'Observações de teste';
        
        $pessoa->setDataNascimento($dataNascimento);
        $pessoa->setNomePai($nomePai);
        $pessoa->setNomeMae($nomeMae);
        $pessoa->setRenda($renda);
        $pessoa->setObservacoes($observacoes);
        
        $this->assertEquals($dataNascimento, $pessoa->getDataNascimento());
        $this->assertEquals($nomePai, $pessoa->getNomePai());
        $this->assertEquals($nomeMae, $pessoa->getNomeMae());
        $this->assertEquals($renda, $pessoa->getRenda());
        $this->assertEquals($observacoes, $pessoa->getObservacoes());
    }

    public function testNullableProperties(): void
    {
        $pessoa = new Pessoas();
        
        $this->assertNull($pessoa->getCpf()); // Vem da tabela de documentos
        $this->assertNull($pessoa->getCnpj()); // Vem da tabela de documentos
        $this->assertNull($pessoa->getDataNascimento());
        $this->assertNull($pessoa->getNomePai());
        $this->assertNull($pessoa->getNomeMae());
        $this->assertNull($pessoa->getRenda());
        $this->assertNull($pessoa->getObservacoes());
    }

    public function testThemeLightProperty(): void
    {
        $pessoa = new Pessoas();
        
        // Valor padrão deve ser true
        $this->assertTrue($pessoa->getThemeLight());
        $this->assertTrue($pessoa->isThemeLight());
        
        // Testar setter
        $pessoa->setThemeLight(false);
        $this->assertFalse($pessoa->getThemeLight());
        $this->assertFalse($pessoa->isThemeLight());
    }
}