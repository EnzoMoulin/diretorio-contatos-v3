<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * O DDL do diretório continua em `db/schema.sql`, na raiz do repositório:
     * é a fonte única das invariantes e o arquivo que o CI aplica com `psql -1`.
     * Esta migration só o executa dentro da transação do migrator — por isso o
     * arquivo não tem `BEGIN`/`COMMIT`.
     */
    public function up(): void
    {
        // __DIR__ = apps/web/database/migrations; quatro níveis acima = raiz do repo.
        $caminho = dirname(__DIR__, 4).'/db/schema.sql';

        if (! is_file($caminho)) {
            throw new RuntimeException("Baseline não encontrado em {$caminho}");
        }

        DB::unprepared(file_get_contents($caminho));
    }

    /**
     * O baseline é o chão do banco: o rollback derruba as sete tabelas de dado
     * e as funções que elas usam. As extensões ficam (podem ser compartilhadas
     * com outro schema) e não são removidas por migration.
     */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TABLE IF EXISTS historico_mensal, historico, telefones_uteis, contatos, nos, locais, comarcas CASCADE;
DROP FUNCTION IF EXISTS marca_atualizacao();
DROP FUNCTION IF EXISTS so_digitos(TEXT);
DROP FUNCTION IF EXISTS norm_texto(TEXT);
SQL);
    }
};
