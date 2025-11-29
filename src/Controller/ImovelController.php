<?php

namespace App\Controller;

use App\Entity\Imoveis;
use App\Form\ImovelFormType;
use App\Repository\ImoveisRepository;
use App\Service\ImovelService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/imovel', name: 'app_imovel_')]
class ImovelController extends AbstractController
{
    private ImovelService $imovelService;
    private LoggerInterface $logger;

    public function __construct(
        ImovelService $imovelService,
        LoggerInterface $logger
    ) {
        $this->imovelService = $imovelService;
        $this->logger = $logger;
    }

    /**
     * Lista todos os imóveis
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        // ✅ Thin Controller: Delega para Service
        $imoveis = $this->imovelService->listarImoveisEnriquecidos();

        return $this->render('imovel/index.html.twig', [
            'imoveis' => $imoveis,
        ]);
    }

    /**
     * Cadastro de novo imóvel
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $form = $this->createForm(ImovelFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $imovel = $form->getData();
                $requestData = $request->request->all();

                // ✅ Thin Controller: Delega para Service
                $this->imovelService->salvarImovel($imovel, $requestData);

                $this->addFlash('success', 'Imóvel cadastrado com sucesso!');
                return $this->redirectToRoute('app_imovel_index');

            } catch (\Exception $e) {
                $this->logger->error('Erro ao salvar imóvel: ' . $e->getMessage());
                $this->addFlash('error', 'Erro ao salvar imóvel: ' . $e->getMessage());
            }
        }

        return $this->render('imovel/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Edição de imóvel existente
     */
    #[Route('/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Imoveis $imovel): Response
    {
        $form = $this->createForm(ImovelFormType::class, $imovel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $requestData = $request->request->all();

                // ✅ Thin Controller: Delega para Service
                $this->imovelService->atualizarImovel($imovel, $requestData);

                $this->addFlash('success', 'Imóvel atualizado com sucesso!');
                return $this->redirectToRoute('app_imovel_index');

            } catch (\Exception $e) {
                $this->logger->error('Erro ao atualizar imóvel: ' . $e->getMessage());
                $this->addFlash('error', 'Erro ao atualizar imóvel: ' . $e->getMessage());
            }
        }

        // ✅ Thin Controller: Delega para Service
        $dadosCompletos = $this->imovelService->carregarDadosCompletos($imovel->getId());

        return $this->render('imovel/edit.html.twig', [
            'form' => $form,
            'imovel' => $imovel,
            'dadosCompletos' => $dadosCompletos,
        ]);
    }

    /**
     * Busca imóvel por código interno (AJAX)
     */
    #[Route('/buscar', name: 'buscar', methods: ['GET'])]
    public function buscar(Request $request): JsonResponse
    {
        $codigoInterno = $request->query->get('codigo_interno');

        if (!$codigoInterno) {
            return new JsonResponse(['error' => 'Código interno não informado'], 400);
        }

        try {
            // ✅ Thin Controller: Delega para Service
            $resultado = $this->imovelService->buscarPorCodigoInterno($codigoInterno);

            if ($resultado) {
                return new JsonResponse($resultado);
            }

            return new JsonResponse(['error' => 'Imóvel não encontrado'], 404);

        } catch (\Exception $e) {
            $this->logger->error('Erro ao buscar imóvel: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erro ao buscar imóvel'], 500);
        }
    }

    /**
     * DELETE de foto (AJAX)
     */
    #[Route('/foto/{id}', name: 'delete_foto', methods: ['DELETE'])]
    public function deleteFoto(int $id): JsonResponse
    {
        try {
            // ✅ Thin Controller: Delega para Service
            $this->imovelService->deletarFoto($id);

            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar foto: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE de medidor (AJAX)
     */
    #[Route('/medidor/{id}', name: 'delete_medidor', methods: ['DELETE'])]
    public function deleteMedidor(int $id): JsonResponse
    {
        try {
            // ✅ Thin Controller: Delega para Service
            $this->imovelService->deletarMedidor($id);

            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar medidor: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE de propriedade do imóvel (AJAX)
     */
    #[Route('/propriedade/{idImovel}/{idPropriedade}', name: 'delete_propriedade', methods: ['DELETE'])]
    public function deletePropriedade(int $idImovel, int $idPropriedade): JsonResponse
    {
        try {
            // ✅ Thin Controller: Delega para Service
            $this->imovelService->deletarPropriedade($idImovel, $idPropriedade);

            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar propriedade: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar propriedades disponíveis (AJAX)
     */
    #[Route('/propriedades/catalogo', name: 'propriedades_catalogo', methods: ['GET'])]
    public function propriedadesCatalogo(): JsonResponse
    {
        try {
            // ✅ Thin Controller: Delega para Service
            $propriedades = $this->imovelService->listarPropriedadesCatalogo();

            return new JsonResponse($propriedades);

        } catch (\Exception $e) {
            $this->logger->error('Erro ao listar propriedades: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erro ao carregar propriedades'], 500);
        }
    }
}
