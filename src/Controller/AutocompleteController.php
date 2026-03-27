<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/autocomplete', name: 'app_autocomplete_')]
class AutocompleteController extends AbstractController
{
    public function __construct(private Connection $conn) {}

    #[Route('/pessoas', name: 'pessoas', methods: ['GET'])]
    public function pessoas(Request $request): JsonResponse
    {
        return $this->buscar($request, 'pessoas', 'idpessoa', 'nome', 'nome', 2);
    }

    #[Route('/nacionalidades', name: 'nacionalidades', methods: ['GET'])]
    public function nacionalidades(Request $request): JsonResponse
    {
        return $this->buscar($request, 'nacionalidades', 'id', 'descricao', 'descricao', 1);
    }

    #[Route('/naturalidades', name: 'naturalidades', methods: ['GET'])]
    public function naturalidades(Request $request): JsonResponse
    {
        return $this->buscar($request, 'naturalidades', 'id', 'descricao', 'descricao', 2);
    }

    #[Route('/logradouros', name: 'logradouros', methods: ['GET'])]
    public function logradouros(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 2) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT l.id, CONCAT(l.logradouro, ' — CEP: ', COALESCE(l.cep, 'S/N'), ' — ', COALESCE(b.nome, '')) AS label
             FROM logradouros l
             LEFT JOIN bairros b ON b.id = l.id_bairro
             WHERE unaccent(LOWER(l.logradouro)) LIKE unaccent(LOWER(:q))
                OR l.cep LIKE :q
             ORDER BY l.logradouro ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    #[Route('/enderecos', name: 'enderecos', methods: ['GET'])]
    public function enderecos(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 2) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT e.id,
                    CONCAT(COALESCE(l.logradouro, ''), ', ', COALESCE(e.numero, 'S/N'), ' — ', COALESCE(b.nome, ''), ' — CEP: ', COALESCE(l.cep, '')) AS label
             FROM enderecos e
             LEFT JOIN logradouros l ON l.id = e.id_logradouro
             LEFT JOIN bairros b ON b.id = l.id_bairro
             WHERE unaccent(LOWER(l.logradouro)) LIKE unaccent(LOWER(:q))
                OR l.cep LIKE :q
                OR e.numero LIKE :q
             ORDER BY l.logradouro ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    #[Route('/imoveis', name: 'imoveis', methods: ['GET'])]
    public function imoveis(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT i.id, CONCAT(i.codigo_interno, ' — ', COALESCE(l.logradouro, ''), ', ', COALESCE(e.numero, 'S/N')) AS label
             FROM imoveis i
             LEFT JOIN enderecos e ON e.id = i.id_endereco
             LEFT JOIN logradouros l ON l.id = e.id_logradouro
             WHERE unaccent(LOWER(i.codigo_interno)) LIKE unaccent(LOWER(:q))
                OR unaccent(LOWER(l.logradouro)) LIKE unaccent(LOWER(:q))
             ORDER BY i.codigo_interno ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    #[Route('/contratos', name: 'contratos', methods: ['GET'])]
    public function contratos(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT c.id,
                    CONCAT('#', c.id, ' — ', COALESCE(i.codigo_interno, 'S/N'), ' — ', COALESCE(p.nome, 'S/I')) AS label
             FROM imoveis_contratos c
             LEFT JOIN imoveis i ON i.id = c.id_imovel
             LEFT JOIN pessoas p ON p.idpessoa = c.id_pessoa_locatario
             WHERE CAST(c.id AS TEXT) LIKE :q
                OR unaccent(LOWER(i.codigo_interno)) LIKE unaccent(LOWER(:q))
                OR unaccent(LOWER(p.nome)) LIKE unaccent(LOWER(:q))
             ORDER BY c.id DESC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    #[Route('/bancos', name: 'bancos', methods: ['GET'])]
    public function bancos(Request $request): JsonResponse
    {
        return $this->buscar($request, 'bancos', 'id', 'nome', 'nome', 1);
    }

    #[Route('/agencias', name: 'agencias', methods: ['GET'])]
    public function agencias(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT a.id, CONCAT(a.codigo, ' — ', COALESCE(b.nome, '')) AS label
             FROM agencias a
             LEFT JOIN bancos b ON b.id = a.id_banco
             WHERE a.codigo LIKE :q
                OR unaccent(LOWER(b.nome)) LIKE unaccent(LOWER(:q))
             ORDER BY a.codigo ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    #[Route('/contas-bancarias', name: 'contas_bancarias', methods: ['GET'])]
    public function contasBancarias(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT cb.id, CONCAT(cb.descricao, COALESCE(' — ' || cb.titular, '')) AS label
             FROM contas_bancarias cb
             WHERE cb.ativo = true
               AND (unaccent(LOWER(cb.descricao)) LIKE unaccent(LOWER(:q))
                 OR unaccent(LOWER(COALESCE(cb.titular, ''))) LIKE unaccent(LOWER(:q)))
             ORDER BY cb.descricao ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    #[Route('/condominios', name: 'condominios', methods: ['GET'])]
    public function condominios(Request $request): JsonResponse
    {
        return $this->buscar($request, 'condominios', 'id', 'nome', 'nome', 1);
    }

    #[Route('/cidades', name: 'cidades', methods: ['GET'])]
    public function cidades(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT c.id, CONCAT(c.nome, ' — ', COALESCE(e.uf, '')) AS label
             FROM cidades c
             LEFT JOIN estados e ON e.id = c.id_estado
             WHERE unaccent(LOWER(c.nome)) LIKE unaccent(LOWER(:q))
             ORDER BY c.nome ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    #[Route('/ufs', name: 'ufs', methods: ['GET'])]
    public function ufs(Request $request): JsonResponse
    {
        $q = strtoupper(trim($request->query->get('q', '')));
        if (strlen($q) < 1) return new JsonResponse([]);

        $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
        $result = [];
        foreach ($ufs as $uf) {
            if (str_contains($uf, $q)) {
                $result[] = ['id' => $uf, 'label' => $uf];
            }
        }
        return new JsonResponse($result);
    }

    #[Route('/bairros', name: 'bairros', methods: ['GET'])]
    public function bairros(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT b.id, CONCAT(b.nome, ' — ', COALESCE(c.nome, ''), '/', COALESCE(e.uf, '')) AS label
             FROM bairros b
             LEFT JOIN cidades c ON c.id = b.id_cidade
             LEFT JOIN estados e ON e.id = c.id_estado
             WHERE unaccent(LOWER(b.nome)) LIKE unaccent(LOWER(:q))
             ORDER BY b.nome ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    #[Route('/plano-contas', name: 'plano_contas', methods: ['GET'])]
    public function planoContas(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT id, CONCAT(codigo, ' — ', descricao) AS label
             FROM plano_contas
             WHERE ativo = true
               AND (unaccent(LOWER(descricao)) LIKE unaccent(LOWER(:q))
                 OR LOWER(codigo) LIKE LOWER(:q))
             ORDER BY descricao ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    #[Route('/almasa-plano-contas', name: 'almasa_plano_contas', methods: ['GET'])]
    public function almasaPlanoContas(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        $apenasLancamentos = $request->query->get('lancamentos', '0');
        $nivel = $request->query->get('nivel', '');
        if (strlen($q) < 1) return new JsonResponse([]);

        $where = "ativo = true";
        $params = ['q' => '%' . $q . '%'];

        if ($apenasLancamentos === '1') {
            $where .= " AND aceita_lancamentos = true";
        }
        if ($nivel !== '') {
            $where .= " AND nivel = :nivel";
            $params['nivel'] = (int) $nivel;
        }

        $rows = $this->conn->fetchAllAssociative(
            "SELECT id, CONCAT(codigo, ' — ', descricao) AS label, nivel, tipo,
                    CONCAT(codigo, ' — ', descricao) AS nome
             FROM almasa_plano_contas
             WHERE {$where}
               AND (unaccent(LOWER(descricao)) LIKE unaccent(LOWER(:q))
                 OR LOWER(codigo) LIKE LOWER(:q))
             ORDER BY codigo ASC LIMIT 20",
            $params
        );

        return new JsonResponse($rows);
    }

    #[Route('/users', name: 'users', methods: ['GET'])]
    public function users(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT id, COALESCE(nome, email) AS label
             FROM users
             WHERE unaccent(LOWER(COALESCE(nome, email))) LIKE unaccent(LOWER(:q))
             ORDER BY nome ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    #[Route('/configuracoes-api-banco', name: 'configuracoes_api_banco', methods: ['GET'])]
    public function configuracoesApiBanco(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 1) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT c.id, CONCAT(COALESCE(b.nome, ''), ' — ', c.ambiente) AS label
             FROM configuracoes_api_banco c
             LEFT JOIN bancos b ON b.id = c.id_banco
             WHERE c.ativo = true
               AND (unaccent(LOWER(COALESCE(b.nome, ''))) LIKE unaccent(LOWER(:q))
                 OR LOWER(c.ambiente) LIKE LOWER(:q))
             ORDER BY b.nome ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }

    /**
     * Busca generica para tabelas simples (id, campo_texto)
     */
    private function buscar(Request $request, string $tabela, string $colId, string $colLabel, string $colBusca, int $minLen = 2): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < $minLen) return new JsonResponse([]);

        $rows = $this->conn->fetchAllAssociative(
            "SELECT {$colId} AS id, {$colLabel} AS label
             FROM {$tabela}
             WHERE unaccent(LOWER({$colBusca})) LIKE unaccent(LOWER(:q))
             ORDER BY {$colLabel} ASC LIMIT 20",
            ['q' => '%' . $q . '%']
        );

        return new JsonResponse($rows);
    }
}
