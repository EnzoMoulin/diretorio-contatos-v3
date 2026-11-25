<?php

namespace Database\Seeders;

use App\Models\Comarca;
use App\Models\Contato;
use App\Models\Local;
use App\Models\No;
use App\Models\TelefoneUtil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DiretorioSeeder extends Seeder
{
    public function run(): void
    {
        $caminho = (string) config('diretorio.seed_json');

        if ($caminho === '') {
            $caminho = dirname(base_path(), 3).'/diretorio-contatos-v2/seed_mascarado.json';
        }

        if (! is_file($caminho)) {
            $this->command?->warn("Seed do v2 não encontrado em {$caminho}; gerando dados sintéticos.");
            $this->sintetico();

            return;
        }

        $this->importar($caminho);
    }

    private function importar(string $caminho): void
    {
        $bruto = file_get_contents($caminho);

        if ($bruto === false) {
            throw new RuntimeException("Não foi possível ler {$caminho}.");
        }

        /** @var array{comarcas: list<array<string, mixed>>, foruns: list<array<string, mixed>>, nos: list<array<string, mixed>>, contatos: list<array<string, mixed>>, uteis: list<array<string, mixed>>} $dados */
        $dados = json_decode($bruto, true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($dados, $caminho): void {
            $comarcas = [];
            $sede = null;

            foreach ($dados['comarcas'] as $linha) {
                if ($linha['id'] === 'MUN0000') {
                    $sede = Local::updateOrCreate(
                        ['chave' => $linha['id']],
                        [
                            'tipo' => 'SEDE',
                            'comarca_id' => null,
                            'nome' => $linha['nome'],
                            'endereco' => $linha['end'] ?? '',
                            'cep' => $linha['cep'] ?? '',
                            'email' => $linha['email'] ?? '',
                            'observacao' => $linha['obs'] ?? '',
                            'ordem_num' => $linha['ordem'] ?? null,
                            'ordem_idx' => $linha['idx'] ?? 0,
                        ],
                    );

                    continue;
                }

                $comarcas[$linha['id']] = Comarca::updateOrCreate(
                    ['chave' => $linha['id']],
                    [
                        'nome' => $linha['nome'],
                        'codigo_ibge' => $linha['ibge'] ?? '',
                        'ordem_num' => $linha['ordem'] ?? null,
                        'ordem_idx' => $linha['idx'] ?? 0,
                    ],
                );
            }

            $sedeLocalId = $sede?->id;
            $locais = [];

            foreach ($dados['foruns'] as $linha) {
                $locais[$linha['id']] = Local::updateOrCreate(
                    ['chave' => $linha['id']],
                    [
                        'tipo' => 'FORUM',
                        'comarca_id' => $comarcas[$linha['comarca_id']]->id,
                        'nome' => $linha['nome'],
                        'endereco' => $linha['end'] ?? '',
                        'cep' => $linha['cep'] ?? '',
                        'email' => $linha['email'] ?? '',
                        'observacao' => $linha['obs'] ?? '',
                        'ordem_num' => $linha['ordem'] ?? null,
                        'ordem_idx' => $linha['idx'] ?? 0,
                    ],
                );
            }

            $nos = [];
            $porId = [];

            foreach ($dados['nos'] as $linha) {
                $porId[$linha['id']] = $linha;
            }

            // O índice ux_nos_nome_irmao é único por (local, pai, nome): inserir
            // todo mundo com pai nulo faria dois "Atendimento" de pais diferentes
            // colidirem. A recursão garante que o pai exista antes do filho.
            $criarNo = function (string $id) use (&$criarNo, &$nos, $porId, $locais, $sedeLocalId): void {
                if (isset($nos[$id])) {
                    return;
                }

                $linha = $porId[$id] ?? throw new RuntimeException("Nó {$id} citado como pai e ausente do seed.");
                $paiId = null;

                if (($linha['pai'] ?? '') !== '') {
                    $criarNo($linha['pai']);
                    $paiId = $nos[$linha['pai']]->id;
                }

                // 179 nós do seed não têm fórum: todos penderam da Sede (MUN0000),
                // que no v3 é um local como outro qualquer.
                $localId = ($linha['forum'] ?? '') !== ''
                    ? $locais[$linha['forum']]->id
                    : $sedeLocalId;

                if ($localId === null) {
                    throw new RuntimeException("Nó {$id} sem fórum e sem Sede para ancorar.");
                }

                $nos[$id] = No::updateOrCreate(
                    ['chave' => $linha['id']],
                    [
                        'local_id' => $localId,
                        'pai_id' => $paiId,
                        'tipo' => $linha['tipo'] ?? 'UNIDADE',
                        'nome' => $linha['nome'],
                        'endereco' => $linha['end'] ?? '',
                        'cep' => $linha['cep'] ?? '',
                        'observacao' => $linha['obs'] ?? '',
                        'selecionavel' => (bool) ($linha['sel'] ?? false),
                        'virtual' => false,
                        'ordem_num' => $linha['ordem'] ?? null,
                        'ordem_idx' => $linha['idx'] ?? 0,
                        'arquivado_em' => ($linha['ativo'] ?? true) ? null : now(),
                    ],
                );
            };

            foreach (array_keys($porId) as $id) {
                $criarNo($id);
            }

            foreach ($dados['contatos'] as $linha) {
                $noId = ($linha['uo'] ?? '') === '' ? null : $nos[$linha['uo']]->id;

                // Contato de fórum traz o fórum; contato da Sede traz só o nó (ou
                // só MUN0000). O local é derivado do nó e, sem nó, é a Sede.
                $localId = ($linha['forum'] ?? '') !== ''
                    ? $locais[$linha['forum']]->id
                    : ($noId !== null ? $nos[$linha['uo']]->local_id : $sedeLocalId);

                if ($localId === null) {
                    throw new RuntimeException("Contato {$linha['id']} sem local para ancorar.");
                }

                Contato::updateOrCreate(
                    ['chave' => $linha['id']],
                    [
                        'local_id' => $localId,
                        'no_id' => $noId,
                        'tipo' => $linha['tipo'],
                        'descricao' => $linha['desc'] ?? '',
                        'valor' => $linha['valor'],
                        'ordem_num' => $linha['ordem'] ?? null,
                        'ordem_idx' => $linha['idx'] ?? 0,
                        'fonte' => basename($caminho),
                    ],
                );
            }

            foreach ($dados['uteis'] as $linha) {
                TelefoneUtil::updateOrCreate(
                    ['chave' => $linha['id']],
                    [
                        'nome' => $linha['nome'],
                        'tipo' => $linha['tipo'] ?? 'Telefone',
                        'valor' => $linha['valor'],
                        'ordem_idx' => $linha['idx'] ?? 0,
                    ],
                );
            }

            if ($sede === null) {
                $this->command?->warn('Seed do v2 sem MUN0000 (Sede); a capa ficará sem a Sede.');
            }
        });
    }

    private function sintetico(): void
    {
        $sede = Local::factory()->sede()->create(['ordem_num' => 1]);

        $vitoria = Comarca::factory()->create(['nome' => 'Vitória', 'ordem_num' => 2]);
        $vilaVelha = Comarca::factory()->create(['nome' => 'Vila Velha', 'ordem_num' => 3]);
        $serra = Comarca::factory()->create(['nome' => 'Serra', 'ordem_num' => 4]);

        $foruns = [
            Local::factory()->create(['nome' => 'Fórum Cível', 'comarca_id' => $vitoria->id]),
            Local::factory()->create(['nome' => 'Fórum Criminal', 'comarca_id' => $vitoria->id]),
            Local::factory()->create(['nome' => 'Fórum da Serra', 'comarca_id' => $serra->id]),
            Local::factory()->create(['nome' => 'Fórum de Vila Velha', 'comarca_id' => $vilaVelha->id]),
        ];

        foreach ($foruns as $forum) {
            $no = No::factory()->create(['local_id' => $forum->id, 'nome' => 'Protocolo • Distribuição']);

            Contato::factory()->create([
                'local_id' => $forum->id,
                'no_id' => $no->id,
                'tipo' => 'Telefone',
                'descricao' => 'Atendimento',
                'valor' => '(27) 3333-4444',
            ]);

            Contato::factory()->create([
                'local_id' => $forum->id,
                'no_id' => $no->id,
                'tipo' => 'Ramal',
                'descricao' => 'Gabinete',
                'valor' => '32323232',
            ]);

            Contato::factory()->create([
                'local_id' => $forum->id,
                'tipo' => 'E-mail',
                'descricao' => 'Institucional',
                'valor' => 'contato@exemplo.jus.br',
            ]);
        }

        foreach (['Central de ajuda', 'Ouvidoria', 'Suporte'] as $nome) {
            TelefoneUtil::factory()->create(['nome' => $nome]);
        }

        $this->command?->info("Dados sintéticos criados: 3 comarcas, 4 fóruns, {$sede->nome}.");
    }
}
