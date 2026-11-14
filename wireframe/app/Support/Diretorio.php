<?php

namespace App\Support;

/**
 * Camada de leitura do wireframe: lê o seed do v2 (data/seed.json) e monta os índices
 * em memória. Não há banco: o wireframe é descartável e serve para ver a direção visual
 * com dado real. Nada aqui é reaproveitado pela implementação em apps/web.
 */
class Diretorio
{
    private static ?array $d = null;

    /** Nome oficial do município pelo código do IBGE (malha municipal não traz nome). */
    public static function municipioNome(string $ibge): ?string
    {
        static $nomes = null;

        if ($nomes === null) {
            $nomes = json_decode(file_get_contents(base_path('data/municipios.json')), true) ?: [];
        }

        return $nomes[$ibge] ?? null;
    }

    private static function d(): array
    {
        if (self::$d !== null) {
            return self::$d;
        }

        $json = file_get_contents(base_path('data/seed.json'));
        $d = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $d['_foruns'] = [];
        foreach ($d['foruns'] as $f) {
            $d['_foruns'][$f['id']] = $f;
        }

        $d['_comarcas'] = [];
        foreach ($d['comarcas'] as $c) {
            $d['_comarcas'][$c['id']] = $c;
        }

        $d['_nos'] = [];
        $d['_filhos'] = [];
        foreach ($d['nos'] as $n) {
            $d['_nos'][$n['id']] = $n;
            if (!empty($n['pai'])) {
                $d['_filhos'][$n['pai']][] = $n['id'];
            }
        }

        $d['_nos_por_comarca'] = [];
        foreach ($d['nos'] as $n) {
            if (!empty($n['pai'])) {
                continue;
            }
            $cid = !empty($n['comarca']) ? $n['comarca'] : ($d['_foruns'][$n['forum']]['comarca_id'] ?? '');
            if ($cid !== '') {
                $d['_nos_por_comarca'][$cid][] = $n['id'];
            }
        }

        $d['_contatos'] = [];
        $d['_por_no'] = [];
        $d['_por_forum'] = [];
        $d['_por_comarca'] = [];

        foreach ($d['contatos'] as $c) {
            $d['_contatos'][$c['id']] = $c;

            $cid = self::resolveComarca($c, $d);

            if ($cid !== '') {
                $d['_por_comarca'][$cid][] = $c['id'];
            }
            if (!empty($c['uo'])) {
                $d['_por_no'][$c['uo']][] = $c['id'];
            } elseif (!empty($c['forum'])) {
                $d['_por_forum'][$c['forum']][] = $c['id'];
            }
        }

        self::$d = $d;

        return self::$d;
    }

    private static function resolveComarca(array $c, array $d): string
    {
        if (!empty($c['comarca'])) {
            return $c['comarca'];
        }
        if (!empty($c['uo']) && isset($d['_nos'][$c['uo']])) {
            $n = $d['_nos'][$c['uo']];
            if (!empty($n['comarca'])) {
                return $n['comarca'];
            }
            if (!empty($n['forum']) && isset($d['_foruns'][$n['forum']])) {
                return $d['_foruns'][$n['forum']]['comarca_id'];
            }
        }
        if (!empty($c['forum']) && isset($d['_foruns'][$c['forum']])) {
            return $d['_foruns'][$c['forum']]['comarca_id'];
        }

        return '';
    }

    public static function totais(): array
    {
        $d = self::d();

        return [
            'comarcas' => count(array_filter($d['comarcas'], fn ($c) => ($c['tipo'] ?? '') !== 'ORGAO')),
            'locais' => count($d['comarcas']) - 1 + count($d['foruns']) - 1,
            'nos' => count($d['nos']),
            'contatos' => count($d['contatos']),
            'uteis' => count($d['uteis']),
        ];
    }

    public static function comarcas(): array
    {
        $d = self::d();
        $lista = [];

        foreach ($d['comarcas'] as $c) {
            $lista[] = [
                'chave' => $c['id'],
                'nome' => $c['nome'],
                'ibge' => $c['ibge'] ?? '',
                'sede' => ($c['tipo'] ?? '') === 'ORGAO',
                'contatos' => count($d['_por_comarca'][$c['id']] ?? []),
                'foruns' => count(array_filter($d['foruns'], fn ($f) => $f['comarca_id'] === $c['id'])),
                'nos' => count($d['_nos_por_comarca'][$c['id']] ?? []),
            ];
        }

        usort($lista, function ($a, $b) {
            if ($a['sede'] !== $b['sede']) {
                return $a['sede'] ? -1 : 1;
            }
            if ($a['contatos'] !== $b['contatos']) {
                return $b['contatos'] <=> $a['contatos'];
            }

            return strcoll($a['nome'], $b['nome']) ?: strcmp($a['nome'], $b['nome']);
        });

        return $lista;
    }

    public static function comarca(string $chave): ?array
    {
        $d = self::d();
        if (!isset($d['_comarcas'][$chave])) {
            return null;
        }
        $c = $d['_comarcas'][$chave];

        return [
            'chave' => $c['id'],
            'nome' => $c['nome'],
            'ibge' => $c['ibge'] ?? '',
            'sede' => ($c['tipo'] ?? '') === 'ORGAO',
            'contatos' => count($d['_por_comarca'][$c['id']] ?? []),
            'foruns' => self::foruns($c['id']),
            'locais' => self::locaisDaComarca($c['id']),
            'contatos_diretos' => array_map(fn ($id) => $d['_contatos'][$id], $d['_por_comarca'][$c['id']] ?? []),
            'end' => $c['end'] ?? '',
            'cep' => $c['cep'] ?? '',
            'email' => $c['email'] ?? '',
        ];
    }

    public static function foruns(string $comarcaChave): array
    {
        $d = self::d();
        $out = [];

        foreach ($d['foruns'] as $f) {
            if ($f['comarca_id'] !== $comarcaChave) {
                continue;
            }
            $nos = array_values(array_filter($d['nos'], fn ($n) => $n['forum'] === $f['id'] && empty($n['pai'])));
            $out[] = [
                'chave' => $f['id'],
                'nome' => $f['nome'],
                'end' => $f['end'] ?? '',
                'cep' => $f['cep'] ?? '',
                'email' => $f['email'] ?? '',
                'nos' => count($nos),
                'contatos' => self::contarForum($f['id']),
            ];
        }

        usort($out, fn ($a, $b) => strcmp($a['nome'], $b['nome']));

        return $out;
    }

    private static function contarForum(string $forum): int
    {
        $d = self::d();
        $n = count($d['_por_forum'][$forum] ?? []);
        foreach ($d['nos'] as $no) {
            if ($no['forum'] === $forum) {
                $n += count($d['_por_no'][$no['id']] ?? []);
            }
        }

        return $n;
    }

    /** Nós de primeiro nível da comarca (a Sede tem nós sem fórum). */
    public static function locaisDaComarca(string $comarcaChave): array
    {
        $d = self::d();
        $out = [];

        foreach ($d['nos'] as $n) {
            if (!empty($n['pai'])) {
                continue;
            }
            $cid = !empty($n['comarca']) ? $n['comarca'] : ($d['_foruns'][$n['forum']]['comarca_id'] ?? '');
            if ($cid !== $comarcaChave) {
                continue;
            }
            $out[] = self::resumoNo($n);
        }

        return $out;
    }

    private static function resumoNo(array $n): array
    {
        $d = self::d();

        return [
            'chave' => $n['id'],
            'nome' => $n['nome'],
            'tipo' => $n['tipo'] ?? '',
            'forum' => $n['forum'] ?? '',
            'filhos' => count($d['_filhos'][$n['id']] ?? []),
            'contatos' => self::contarNo($n['id']),
        ];
    }

    private static function contarNo(string $id): int
    {
        $d = self::d();
        $n = count($d['_por_no'][$id] ?? []);
        foreach ($d['_filhos'][$id] ?? [] as $filho) {
            $n += count($d['_por_no'][$filho] ?? []);
        }

        return $n;
    }

    public static function local(string $chave): ?array
    {
        $d = self::d();

        if (isset($d['_foruns'][$chave])) {
            $f = $d['_foruns'][$chave];
            $comarca = $d['_comarcas'][$f['comarca_id']];
            $rst = array_values(array_filter($d['nos'], fn ($n) => $n['forum'] === $f['id'] && empty($n['pai'])));

            return [
                'chave' => $f['id'],
                'nome' => $f['nome'],
                'tipo' => 'FORUM',
                'comarca' => ['chave' => $comarca['id'], 'nome' => $comarca['nome']],
                'end' => $f['end'] ?? '',
                'cep' => $f['cep'] ?? '',
                'email' => $f['email'] ?? '',
                'trilha' => [
                    ['rotulo' => $comarca['nome'], 'url' => route('comarca', $comarca['id'])],
                    ['rotulo' => $f['nome'], 'url' => null],
                ],
                'locais' => array_map(fn ($n) => self::resumoNo($n), $rst),
                'contatos' => array_map(fn ($id) => $d['_contatos'][$id], $d['_por_forum'][$f['id']] ?? []),
                'contatos_total' => self::contarForum($f['id']),
            ];
        }

        if (isset($d['_nos'][$chave])) {
            $n = $d['_nos'][$chave];
            $forum = $n['forum'] ? ($d['_foruns'][$n['forum']] ?? null) : null;
            $cid = !empty($n['comarca']) ? $n['comarca'] : ($forum['comarca_id'] ?? '');
            $comarca = $d['_comarcas'][$cid] ?? null;

            $trilha = [];
            if ($comarca) {
                $trilha[] = ['rotulo' => $comarca['nome'], 'url' => route('comarca', $comarca['id'])];
            }
            if ($forum) {
                $trilha[] = ['rotulo' => $forum['nome'], 'url' => route('local', $forum['id'])];
            }
            // ancestrais
            $anc = [];
            $pai = $n['pai'] ?? '';
            while (!empty($pai) && isset($d['_nos'][$pai])) {
                $p = $d['_nos'][$pai];
                $anc[] = ['rotulo' => $p['nome'], 'url' => route('local', $p['id'])];
                $pai = $p['pai'] ?? '';
            }
            $trilha = array_merge($trilha, array_reverse($anc));
            $trilha[] = ['rotulo' => $n['nome'], 'url' => null];

            return [
                'chave' => $n['id'],
                'nome' => $n['nome'],
                'tipo' => $n['tipo'] ?? '',
                'forum' => $forum ? ['chave' => $forum['id'], 'nome' => $forum['nome']] : null,
                'comarca' => $comarca ? ['chave' => $comarca['id'], 'nome' => $comarca['nome']] : null,
                'end' => $n['end'] ?: ($forum['end'] ?? ''),
                'cep' => $n['cep'] ?: ($forum['cep'] ?? ''),
                'email' => $forum['email'] ?? '',
                'trilha' => $trilha,
                'locais' => array_map(fn ($id) => self::resumoNo($d['_nos'][$id]), $d['_filhos'][$n['id']] ?? []),
                'contatos' => array_map(fn ($id) => $d['_contatos'][$id], $d['_por_no'][$n['id']] ?? []),
                'contatos_total' => self::contarNo($n['id']),
            ];
        }

        return null;
    }

    public static function contato(string $chave): ?array
    {
        $d = self::d();
        if (!isset($d['_contatos'][$chave])) {
            return null;
        }
        $c = $d['_contatos'][$chave];

        $local = null;
        $trilha = [];
        if (!empty($c['uo']) && isset($d['_nos'][$c['uo']])) {
            $local = self::local($c['uo']);
        } elseif (!empty($c['forum']) && isset($d['_foruns'][$c['forum']])) {
            $local = self::local($c['forum']);
        } elseif (!empty($c['comarca'])) {
            $local = self::comarca($c['comarca']);
        }

        if ($local && isset($local['trilha'])) {
            $trilha = $local['trilha'];
        } elseif ($local) {
            $trilha = [['rotulo' => $local['nome'], 'url' => null]];
        }
        $trilha = $trilha ?: [['rotulo' => $local['nome'] ?? 'Contato', 'url' => null]];

        return [
            'chave' => $c['id'],
            'tipo' => $c['tipo'],
            'desc' => $c['desc'] ?? '',
            'valor' => $c['valor'],
            'vigencia' => self::vigencia($c),
            'local' => $local,
            'trilha' => $trilha,
            'irmãos' => $local ? array_values(array_filter($local['contatos'] ?? [], fn ($x) => $x['id'] !== $c['id'])) : [],
        ];
    }

    public static function vigencia(array $c): array
    {
        $obs = $c['obs'] ?? '';
        if ($obs === '') {
            return ['chave' => 'base', 'rotulo' => 'base', 'detalhe' => 'sem data declarada'];
        }
        if (mb_stripos($obs, 'provis') !== false) {
            return ['chave' => 'provisorio', 'rotulo' => 'provisório', 'detalhe' => 'ofício de março/2024 · desde 01/03/2024'];
        }

        return ['chave' => 'sede', 'rotulo' => 'volume da Sede', 'detalhe' => 'agosto/2026'];
    }

    public static function busca(string $q, int $limite = 40): array
    {
        $d = self::d();
        $q = trim($q);
        $alvo = self::norm($q);

        $comarcas = [];
        $locais = [];
        $contatos = [];

        if ($alvo !== '') {
            foreach ($d['comarcas'] as $c) {
                if (str_contains(self::norm($c['nome']), $alvo)) {
                    $comarcas[] = ['chave' => $c['id'], 'nome' => $c['nome'], 'contatos' => count($d['_por_comarca'][$c['id']] ?? []), 'ibge' => $c['ibge'] ?? ''];
                }
            }
            foreach ($d['nos'] as $n) {
                if (str_contains(self::norm($n['nome']), $alvo)) {
                    $comarca = !empty($n['comarca']) ? ($d['_comarcas'][$n['comarca']] ?? null) : ($d['_comarcas'][$d['_foruns'][$n['forum']]['comarca_id']] ?? null);
                    $locais[] = ['chave' => $n['id'], 'nome' => $n['nome'], 'tipo' => $n['tipo'], 'comarca' => $comarca['nome'] ?? '', 'contatos' => self::contarNo($n['id'])];
                }
            }
            foreach ($d['contatos'] as $c) {
                $campo = self::norm(($c['valor'] ?? '').' '.($c['desc'] ?? '').' '.($c['tipo'] ?? ''));
                if (str_contains($campo, $alvo)) {
                    $ctx = '';
                    if (!empty($c['uo']) && isset($d['_nos'][$c['uo']])) {
                        $ctx = $d['_nos'][$c['uo']]['nome'];
                    } elseif (!empty($c['forum']) && isset($d['_foruns'][$c['forum']])) {
                        $ctx = $d['_foruns'][$c['forum']]['nome'];
                    } elseif (!empty($c['comarca']) && isset($d['_comarcas'][$c['comarca']])) {
                        $ctx = $d['_comarcas'][$c['comarca']]['nome'];
                    }
                    $c['_ctx'] = $ctx;
                    $contatos[] = $c;
                }
            }
        }

        return [
            'q' => $q,
            'comarcas' => array_slice($comarcas, 0, 8),
            'locais' => array_slice($locais, 0, 12),
            'contatos' => array_slice($contatos, 0, $limite),
            'total_contatos' => count($contatos),
            'total_locais' => count($locais),
        ];
    }

    public static function norm(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);

        return $t !== false ? $t : $s;
    }

    public static function uteis(): array
    {
        return self::d()['uteis'];
    }
}
