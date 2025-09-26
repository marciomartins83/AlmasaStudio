<?php

namespace App\Controller;

use App\Entity\PessoasFiadores;
use App\Entity\Pessoas;
use App\Entity\PessoasDocumentos;
use App\Entity\TiposDocumentos;
use App\Form\PessoaFiadorFormType;
use App\Repository\PessoaFiadorRepository;
use App\Repository\PessoaRepository;
use App\Service\CepService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pessoa-fiador', name: 'app_pessoa_fiador_')]
class PessoaFiadorController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PessoaFiadorRepository $pessoaFiadorRepository): Response
    {
        return $this->render('pessoa_fiador/index.html.twig', [
            'pessoa_fiadores' => $pessoaFiadorRepository->findAll(),
        ]);
    }

    #[Route('/search-pessoa', name: 'search_pessoa', methods: ['POST'])]
    public function searchPessoa(Request $request, PessoaRepository $pessoaRepository): JsonResponse
    {
        $searchTerm = $request->request->get('searchTerm');
        
        if (empty($searchTerm)) {
            return new JsonResponse(['error' => 'Termo de busca não informado'], 400);
        }

        $pessoas = $pessoaRepository->searchPessoa($searchTerm);
        
        $result = [];
        foreach ($pessoas as $pessoa) {
            // Buscar CPF e CNPJ através da tabela de documentos
            $cpf = $pessoaRepository->getCpfByPessoa($pessoa->getIdpessoa());
            $cnpj = $pessoaRepository->getCnpjByPessoa($pessoa->getIdpessoa());
            
            $result[] = [
                'id' => $pessoa->getIdpessoa(),
                'nome' => $pessoa->getNome(),
                'cpf' => $cpf,
                'cnpj' => $cnpj,
                'fisicaJuridica' => $pessoa->getFisicaJuridica(),
                'dataNascimento' => $pessoa->getDataNascimento() ? $pessoa->getDataNascimento()->format('Y-m-d') : null,
                'estadoCivil' => $pessoa->getEstadoCivil() ? $pessoa->getEstadoCivil()->getId() : null,
                'nacionalidade' => $pessoa->getNacionalidade() ? $pessoa->getNacionalidade()->getId() : null,
                'naturalidade' => $pessoa->getNaturalidade() ? $pessoa->getNaturalidade()->getId() : null,
                'nomePai' => $pessoa->getNomePai(),
                'nomeMae' => $pessoa->getNomeMae(),
                'renda' => $pessoa->getRenda(),
                'observacoes' => $pessoa->getObservacoes(),
            ];
        }
        
        return new JsonResponse(['pessoas' => $result]);
    }

    #[Route('/search-pessoa-advanced', name: 'app_pessoa_fiador_search_pessoa_advanced', methods: ['POST'])]
    public function searchPessoaAdvanced(Request $request, PessoaRepository $pessoaRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Dados JSON inválidos'], 400);
            }

            $criteria = $data['criteria'] ?? '';
            $value = $data['value'] ?? '';
            $additionalDoc = $data['additionalDoc'] ?? null;
            $additionalDocType = $data['additionalDocType'] ?? null;

            if (empty($criteria) || empty($value)) {
                return new JsonResponse(['success' => false, 'message' => 'Critério e valor são obrigatórios'], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erro ao processar requisição: ' . $e->getMessage()], 500);
        }

        try {
            $pessoa = null;
            
                        switch ($criteria) {
                case 'cpf':
                case 'CPF':
                case 'CPF (Pessoa Física)':
                    $pessoa = $pessoaRepository->findByCpf($value);
                    break;
                
                case 'cnpj':
                case 'CNPJ':
                case 'CNPJ (Pessoa Jurídica)':
                    $pessoa = $pessoaRepository->findByCnpj($value);
                    break;
                
                case 'id':
                case 'ID':
                case 'Id Pessoa':
                    $pessoa = $pessoaRepository->find((int)$value);
                    break;
                
                case 'nome':
                case 'Nome':
                case 'Nome Completo':
                    // Busca por nome + documento adicional
                    if ($additionalDoc && $additionalDocType) {
                        // Normalizar tipo de documento adicional
                        $isDocCpf = (stripos($additionalDocType, 'cpf') !== false);
                        $isDocCnpj = (stripos($additionalDocType, 'cnpj') !== false);
                        
                        if ($isDocCpf) {
                            $pessoaPorDoc = $pessoaRepository->findByCpf($additionalDoc);
                        } elseif ($isDocCnpj) {
                            $pessoaPorDoc = $pessoaRepository->findByCnpj($additionalDoc);
                        } else {
                            $pessoaPorDoc = null;
                        }

                        // Verificar se o nome confere
                        if ($pessoaPorDoc && stripos($pessoaPorDoc->getNome(), $value) !== false) {
                            $pessoa = $pessoaPorDoc;
                        }
                    } else {
                        // Busca apenas por nome (pode retornar múltiplos, pegamos o primeiro)
                        $pessoas = $pessoaRepository->findByNome($value);
                        if (count($pessoas) === 1) {
                            $pessoa = $pessoas[0];
                        } elseif (count($pessoas) > 1) {
                            return new JsonResponse([
                                'success' => false, 
                                'message' => 'Múltiplas pessoas encontradas com este nome. Por favor, informe o CPF ou CNPJ para especificar.'
                            ]);
                        }
                    }
                    break;
                
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Critério de busca inválido'], 400);
            }

            if ($pessoa) {
                // Buscar documentos da pessoa
                $cpf = $pessoaRepository->getCpfByPessoa($pessoa->getIdpessoa());
                $cnpj = $pessoaRepository->getCnpjByPessoa($pessoa->getIdpessoa());
                
                return new JsonResponse([
                    'success' => true,
                    'pessoa' => [
                        'id' => $pessoa->getIdpessoa(),
                        'nome' => $pessoa->getNome(),
                        'cpf' => $cpf,
                        'cnpj' => $cnpj,
                        'fisicaJuridica' => $pessoa->getFisicaJuridica(),
                        'dataNascimento' => $pessoa->getDataNascimento() ? $pessoa->getDataNascimento()->format('Y-m-d') : null,
                        'estadoCivil' => $pessoa->getEstadoCivil() ? $pessoa->getEstadoCivil()->getId() : null,
                        'nacionalidade' => $pessoa->getNacionalidade() ? $pessoa->getNacionalidade()->getId() : null,
                        'naturalidade' => $pessoa->getNaturalidade() ? $pessoa->getNaturalidade()->getId() : null,
                        'nomePai' => $pessoa->getNomePai(),
                        'nomeMae' => $pessoa->getNomeMae(),
                        'renda' => $pessoa->getRenda(),
                        'observacoes' => $pessoa->getObservacoes(),
                    ]
                ]);
            } else {
                return new JsonResponse([
                    'success' => true,
                    'pessoa' => null,
                    'message' => 'Pessoa não encontrada'
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PessoaRepository $pessoaRepository): Response
    {
        $form = $this->createForm(PessoaFiadorFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $requestData = $request->request->all();
            
            // Validar CPF/CNPJ
            $cpf = $data['cpf'] ?? $requestData['cpf'] ?? null;
            $cnpj = $data['cnpj'] ?? $requestData['cnpj'] ?? null;
            
            if (empty($cpf) && empty($cnpj)) {
                $this->addFlash('error', 'É obrigatório informar CPF ou CNPJ.');
                return $this->render('pessoa_fiador/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            
            if ($cpf && $cnpj) {
                $this->addFlash('error', 'Informe apenas CPF ou CNPJ, não ambos.');
                return $this->render('pessoa_fiador/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Verificar se já existe pessoa com esse CPF/CNPJ
            $pessoaExistente = $pessoaRepository->existsByCpfOrCnpj($cpf, $cnpj);
            
            if ($pessoaExistente) {
                // Se pessoa já existe, usar ela
                $pessoa = $pessoaExistente;
                
                // Verificar se já é fiador
                $fiadorExistente = $entityManager->getRepository(PessoasFiadores::class)
                    ->findOneBy(['idPessoa' => $pessoa->getIdpessoa()]);
                    
                if ($fiadorExistente) {
                    $this->addFlash('error', 'Esta pessoa já está cadastrada como fiador.');
                    return $this->render('pessoa_fiador/new.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
                
                $this->addFlash('info', 'Pessoa já existente encontrada. Criando vínculo de fiador.');
            } else {
                // Criar nova pessoa
                $pessoa = new Pessoas();
                $pessoa->setDtCadastro(new \DateTime());
                $pessoa->setStatus(true);
                $pessoa->setTipoPessoa(4); // ID para 'fiador'
                
                // Definir tipo da pessoa baseado no documento
                $fisicaJuridica = $cpf ? 'fisica' : 'juridica';
                
                // Preencher dados básicos
                $pessoa->setNome($data['nome']);
                $pessoa->setFisicaJuridica($fisicaJuridica);
                
                // Campos específicos para pessoa física
                if ($fisicaJuridica === 'fisica') {
                    $pessoa->setDataNascimento($data['dataNascimento']);
                    $pessoa->setEstadoCivil($data['estadoCivil']);
                    $pessoa->setNacionalidade($data['nacionalidade']);
                    $pessoa->setNaturalidade($data['naturalidade']);
                    $pessoa->setNomePai($data['nomePai']);
                    $pessoa->setNomeMae($data['nomeMae']);
                }
                
                $pessoa->setRenda($data['renda']);
                $pessoa->setObservacoes($data['observacoes']);
                
                $entityManager->persist($pessoa);
                $entityManager->flush();
                
                // Criar documento CPF ou CNPJ
                $tipoDocumento = $cpf ? 'CPF' : 'CNPJ';
                $numeroDocumento = $cpf ?: $cnpj;
                
                // Buscar o ID do tipo de documento
                $tipoDocumentoEntity = $entityManager->getRepository(TiposDocumentos::class)
                    ->findOneBy(['tipo' => $tipoDocumento]);
                
                if ($tipoDocumentoEntity) {
                    $pessoaDocumento = new PessoasDocumentos();
                    $pessoaDocumento->setIdPessoa($pessoa->getIdpessoa());
                    $pessoaDocumento->setIdTipoDocumento($tipoDocumentoEntity->getId());
                    $pessoaDocumento->setNumeroDocumento($numeroDocumento);
                    $pessoaDocumento->setAtivo(true);
                    
                    $entityManager->persist($pessoaDocumento);
                    $entityManager->flush();
                }
            }
            
            // Criar o fiador
            $pessoaFiador = new PessoasFiadores();
            $pessoaFiador->setIdPessoa($pessoa->getIdpessoa());
            $pessoaFiador->setIdConjuge($data['idConjuge']);
            $pessoaFiador->setMotivoFianca($data['motivoFianca']);
            $pessoaFiador->setJaFoiFiador($data['jaFoiFiador'] ?? false);
            $pessoaFiador->setConjugeTrabalha($data['conjugeTrabalha'] ?? false);
            $pessoaFiador->setOutros($data['outros']);
            $pessoaFiador->setIdFormaRetirada($data['idFormaRetirada']);
            
            $entityManager->persist($pessoaFiador);
            $entityManager->flush();

            // Processar e salvar dados múltiplos
            $this->processarDadosMultiplos($pessoa->getIdpessoa(), $requestData, $entityManager);

            $this->addFlash('success', 'Fiador criado com sucesso!');
            return $this->redirectToRoute('app_pessoa_fiador_index');
        }

        return $this->render('pessoa_fiador/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(PessoasFiadores $pessoaFiador): Response
    {
        return $this->render('pessoa_fiador/show.html.twig', [
            'pessoa_fiador' => $pessoaFiador,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, PessoasFiadores $pessoaFiador, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaFiadorFormType::class, $pessoaFiador);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Fiador atualizado com sucesso!');
            return $this->redirectToRoute('app_pessoa_fiador_index');
        }

        return $this->render('pessoa_fiador/edit.html.twig', [
            'pessoa_fiador' => $pessoaFiador,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, PessoasFiadores $pessoaFiador, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pessoaFiador->getIdPessoa(), $request->request->get('_token'))) {
            $entityManager->remove($pessoaFiador);
            $entityManager->flush();
            $this->addFlash('success', 'Fiador excluído com sucesso!');
        }

        return $this->redirectToRoute('app_pessoa_fiador_index');
    }

    #[Route('/load-tipos/{entidade}', name: 'load_tipos', methods: ['GET'])]
    public function loadTipos(string $entidade, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $tipos = [];
            
            switch ($entidade) {
                case 'telefone':
                    $tipos = $entityManager->getRepository(\App\Entity\TiposTelefones::class)->findAll();
                    break;
                case 'endereco':
                    $tipos = $entityManager->getRepository(\App\Entity\TiposEnderecos::class)->findAll();
                    break;
                case 'email':
                    $tipos = $entityManager->getRepository(\App\Entity\TiposEmails::class)->findAll();
                    break;
                case 'chave-pix':
                    $tipos = $entityManager->getRepository(\App\Entity\TiposChavesPix::class)->findAll();
                    break;
                case 'documento':
                    $tipos = $entityManager->getRepository(\App\Entity\TiposDocumentos::class)->findAll();
                    break;
                default:
                    return new JsonResponse(['error' => 'Entidade não reconhecida'], 400);
            }
            
            $tiposArray = [];
            foreach ($tipos as $tipo) {
                $tiposArray[] = [
                    'id' => $tipo->getId(),
                    'tipo' => $tipo->getTipo()
                ];
            }
            
            return new JsonResponse(['tipos' => $tiposArray]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/salvar-tipo/{entidade}', name: 'salvar_tipo', methods: ['POST'])]
    public function salvarTipo(string $entidade, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data || !isset($data['tipo'])) {
                return new JsonResponse(['success' => false, 'message' => 'Dados inválidos'], 400);
            }
            
            $tipoNome = trim($data['tipo']);
            if (empty($tipoNome)) {
                return new JsonResponse(['success' => false, 'message' => 'Nome do tipo é obrigatório'], 400);
            }
            
            $novoTipo = null;
            
            switch ($entidade) {
                case 'telefone':
                    $novoTipo = new \App\Entity\TiposTelefones();
                    break;
                case 'endereco':
                    $novoTipo = new \App\Entity\TiposEnderecos();
                    break;
                case 'email':
                    $novoTipo = new \App\Entity\TiposEmails();
                    break;
                case 'chave-pix':
                    $novoTipo = new \App\Entity\TiposChavesPix();
                    break;
                case 'documento':
                    $novoTipo = new \App\Entity\TiposDocumentos();
                    break;
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Entidade não reconhecida'], 400);
            }
            
            $novoTipo->setTipo($tipoNome);
            
            $entityManager->persist($novoTipo);
            $entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'tipo' => [
                    'id' => $novoTipo->getId(),
                    'tipo' => $novoTipo->getTipo()
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    private function processarDadosMultiplos(int $pessoaId, array $requestData, EntityManagerInterface $entityManager): void
    {
        // Processar Telefones
        if (isset($requestData['telefones']) && is_array($requestData['telefones'])) {
            foreach ($requestData['telefones'] as $telefoneData) {
                if (!empty($telefoneData['tipo']) && !empty($telefoneData['numero'])) {
                    // Criar telefone
                    $telefone = new \App\Entity\Telefones();
                    $telefone->setTipo($entityManager->getReference(\App\Entity\TiposTelefones::class, (int)$telefoneData['tipo']));
                    $telefone->setNumero($telefoneData['numero']);
                    $entityManager->persist($telefone);
                    $entityManager->flush(); // Para obter o ID
                    
                    // Criar relação pessoa-telefone
                    $pessoaTelefone = new \App\Entity\PessoasTelefones();
                    $pessoaTelefone->setIdPessoa($pessoaId);
                    $pessoaTelefone->setIdTelefone($telefone->getId());
                    $entityManager->persist($pessoaTelefone);
                }
            }
        }

        // Processar Endereços
        if (isset($requestData['enderecos']) && is_array($requestData['enderecos'])) {
            foreach ($requestData['enderecos'] as $enderecoData) {
                if (!empty($enderecoData['tipo']) && !empty($enderecoData['numero'])) {
                    // Buscar ou criar logradouro
                    $logradouroId = $this->buscarOuCriarLogradouro($enderecoData, $entityManager);
                    
                    // Criar endereço
                    $endereco = new \App\Entity\Enderecos();
                    //$endereco->setIdPessoa($pessoaId);
                    $endereco->setLogradouro($entityManager->getReference(\App\Entity\Logradouros::class, $logradouroId));
                    $endereco->setTipo($entityManager->getReference(\App\Entity\TiposEnderecos::class, (int)$enderecoData['tipo']));
                    $endereco->setEndNumero((int)$enderecoData['numero']);
                    if (!empty($enderecoData['complemento'])) {
                        $endereco->setComplemento($enderecoData['complemento']);
                    }
                    $entityManager->persist($endereco);
                }
            }
        }

        // Processar Emails
        if (isset($requestData['emails']) && is_array($requestData['emails'])) {
            foreach ($requestData['emails'] as $emailData) {
                if (!empty($emailData['tipo']) && !empty($emailData['email'])) {
                    // Criar email
                    $email = new \App\Entity\Emails();
                    $email->setEmail($emailData['email']);
                    $email->setTipo($entityManager->getReference(\App\Entity\TiposEmails::class, (int)$emailData['tipo']));
                    $entityManager->persist($email);
                    $entityManager->flush(); // Para obter o ID
                    
                    // Criar relação pessoa-email
                    $pessoaEmail = new \App\Entity\PessoasEmails();
                    $pessoaEmail->setIdPessoa($pessoaId);
                    $pessoaEmail->setIdEmail($email->getId());
                    $entityManager->persist($pessoaEmail);
                }
            }
        }

        // Processar Chaves PIX
        if (isset($requestData['chaves_pix']) && is_array($requestData['chaves_pix'])) {
            foreach ($requestData['chaves_pix'] as $pixData) {
                if (!empty($pixData['tipo']) && !empty($pixData['chave'])) {
                    $chavePix = new \App\Entity\ChavesPix();
                    $chavePix->setIdPessoa($pessoaId);
                    $chavePix->setIdTipoChave((int)$pixData['tipo']);
                    $chavePix->setChavePix($pixData['chave']);
                    $chavePix->setPrincipal(!empty($pixData['principal']));
                    $chavePix->setAtivo(true);
                    $entityManager->persist($chavePix);
                }
            }
        }

        // Processar Documentos
        if (isset($requestData['documentos']) && is_array($requestData['documentos'])) {
            foreach ($requestData['documentos'] as $documentoData) {
                if (!empty($documentoData['tipo']) && !empty($documentoData['numero'])) {
                    $documento = new \App\Entity\PessoasDocumentos();
                    $documento->setIdPessoa($pessoaId);
                    $documento->setIdTipoDocumento((int)$documentoData['tipo']);
                    $documento->setNumeroDocumento($documentoData['numero']);
                    
                    if (!empty($documentoData['orgao_emissor'])) {
                        $documento->setOrgaoEmissor($documentoData['orgao_emissor']);
                    }
                    
                    if (!empty($documentoData['data_emissao'])) {
                        $documento->setDataEmissao(new \DateTime($documentoData['data_emissao']));
                    }
                    
                    if (!empty($documentoData['data_vencimento'])) {
                        $documento->setDataVencimento(new \DateTime($documentoData['data_vencimento']));
                    }
                    
                    if (!empty($documentoData['observacoes'])) {
                        $documento->setObservacoes($documentoData['observacoes']);
                    }
                    
                    $documento->setAtivo(true);
                    $entityManager->persist($documento);
                }
            }
        }

        // Salvar todas as alterações
        $entityManager->flush();
    }

    private function buscarOuCriarLogradouro(array $enderecoData, EntityManagerInterface $entityManager): int
    {
        // Buscar bairro ou criar
        $bairroRepository = $entityManager->getRepository(\App\Entity\Bairros::class);
        $bairro = $bairroRepository->findOneBy(['nome' => $enderecoData['bairro']]);
        
        if (!$bairro) {
            // Buscar cidade ou criar
            $cidadeRepository = $entityManager->getRepository(\App\Entity\Cidades::class);
            $cidade = $cidadeRepository->findOneBy(['nome' => $enderecoData['cidade']]);
            
            if (!$cidade) {
                // Por simplicidade, criar cidade genérica (idealmente buscar por CEP)
                $cidade = new \App\Entity\Cidades();
                $cidade->setNome($enderecoData['cidade']);
                // Buscar o estado de São Paulo (ID 1)
                $estado = $entityManager->getRepository(\App\Entity\Estados::class)->find(1);
                $cidade->setEstado($estado); // São Paulo como padrão
                $entityManager->persist($cidade);
                $entityManager->flush();
            }
            
            // Criar bairro
            $bairro = new \App\Entity\Bairros();
            $bairro->setNome($enderecoData['bairro']);
            $bairro->setCidade($cidade);
            $entityManager->persist($bairro);
            $entityManager->flush();
        }
        
        // Buscar logradouro ou criar
        $logradouroRepository = $entityManager->getRepository(\App\Entity\Logradouros::class);
        $logradouro = $logradouroRepository->findOneBy([
            'logradouro' => $enderecoData['logradouro'],
            'idBairro' => $bairro->getId()
        ]);
        
        if (!$logradouro) {
            $logradouro = new \App\Entity\Logradouros();
            $logradouro->setLogradouro($enderecoData['logradouro']);
            //$logradouro->setIdBairro($bairro->getId());
            if (!empty($enderecoData['cep'])) {
                $logradouro->setCep(preg_replace('/\D/', '', $enderecoData['cep']));
            } else {
                $logradouro->setCep('00000000'); // CEP padrão se não informado
            }
            $entityManager->persist($logradouro);
            $entityManager->flush();
        }
        
        return $logradouro->getId();
    }

    #[Route('/search-conjuge', name: 'app_pessoa_fiador_search_conjuge', methods: ['POST'],
    options: ['expose' => true])]
    public function searchConjuge(Request $request, PessoaRepository $pessoaRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data || !isset($data['termo'])) {
                return new JsonResponse(['success' => false, 'message' => 'Termo de busca não informado'], 400);
            }

            $termo = trim($data['termo']);
            
            if (strlen($termo) < 3) {
                return new JsonResponse(['success' => false, 'message' => 'Digite pelo menos 3 caracteres para buscar'], 400);
            }

            // Buscar pessoas com nome similar ao termo
            $pessoas = $pessoaRepository->createQueryBuilder('p')
                ->where('p.nome LIKE :termo')
                ->setParameter('termo', '%'.$termo.'%')
                ->andWhere('p.fisicaJuridica = :fisica')
                ->setParameter('fisica', 'fisica')
                ->getQuery()
                ->getResult();

            $result = [];
            foreach ($pessoas as $pessoa) {
                // Buscar CPF da pessoa
                $cpf = $pessoaRepository->getCpfByPessoa($pessoa->getIdpessoa());
                
                $result[] = [
                    'id' => $pessoa->getIdpessoa(),
                    'nome' => $pessoa->getNome(),
                    'cpf' => $cpf
                ];
            }
            
            return new JsonResponse([
                'success' => true,
                'pessoas' => $result
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erro na busca: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/buscar-cep', name: 'buscar_cep', methods: ['POST'])]
    public function buscarCep(Request $request, CepService $cepService): JsonResponse
    {
        // Adicionar headers CORS para desenvolvimento
        $response = new JsonResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');

        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            return $response;
        }

        try {
            $data = json_decode($request->getContent(), true);
            $cep = $data['cep'] ?? null;

            if (!$cep) {
                $response->setData(['success' => false, 'message' => 'CEP não informado']);
                $response->setStatusCode(400);
                return $response;
            }

            $endereco = $cepService->buscarEpersistirEndereco($cep);
            $response->setData([
                'success' => true,
                'logradouro' => $endereco['logradouro'],
                'bairro' => $endereco['bairro'],
                'cidade' => $endereco['cidade'],
                'estado' => $endereco['estado']
            ]);
            return $response;

        } catch (\Exception $e) {
            $response->setData([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $response->setStatusCode(500);
            return $response;
        }
    }
}
