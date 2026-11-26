<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Identidade e escopo são do app, no mesmo banco do diretório (ADR-010).
     * O papel decide *se entra no painel*; `acessos` decide *o que pode editar*.
     * As chaves de escopo são as chaves lógicas do baseline — nunca `id`.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('email', 180)->unique();
            $table->timestampTz('email_verificado_em')->nullable();
            $table->string('senha');                       // hash do Laravel; o login do v3.0 é e-mail/senha no Filament
            $table->enum('papel', ['ADMIN', 'GESTOR_COMARCA', 'EDITOR', 'DEMO'])->default('EDITOR');
            $table->boolean('ativo')->default(true);
            $table->rememberToken();                       // nome exigido pelo framework
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
        });

        // Tabela padrão do broker de senha do Laravel; o Filament usa a mesma.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('acessos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->enum('nivel', ['comarca', 'local', 'no']);
            $table->string('alvo_chave', 40);
            $table->foreignId('concedido_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampTz('criado_em')->useCurrent();
            $table->unique(['usuario_id', 'nivel', 'alvo_chave']);
            $table->index(['nivel', 'alvo_chave']);
        });

        DB::statement(<<<'SQL'
ALTER TABLE acessos ADD CONSTRAINT acessos_alvo_formato CHECK (
  (nivel = 'comarca' AND alvo_chave ~ '^MUN[0-9]{4}$|^MUN-[0-9A-HJKMNP-TV-Z]{26}$')
  OR (nivel = 'local' AND alvo_chave ~ '^FOR[0-9]{4}$|^MUN[0-9]{4}$|^LOC-[0-9A-HJKMNP-TV-Z]{26}$')
  OR (nivel = 'no' AND alvo_chave ~ '^UNI[0-9]{4}$|^NO-[0-9A-HJKMNP-TV-Z]{26}$')
)
SQL);

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('email', 180);
            $table->string('motivo', 500)->default('');
            $table->string('motivo_recusa', 500)->default(''); // vazio enquanto pendente/aprovado
            $table->enum('nivel', ['comarca', 'local', 'no'])->default('comarca');
            $table->string('alvo_chave', 40)->nullable(); // nulo = pedido de acesso amplo, sem escopo
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete(); // preenchido se o pedido veio de sessão
            $table->enum('status', ['pendente', 'aprovado', 'recusado'])->default('pendente');
            $table->foreignId('decidido_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampTz('decidido_em')->nullable();
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
            $table->index(['status', 'criado_em']);
        });

        DB::statement(<<<'SQL'
ALTER TABLE pedidos ADD CONSTRAINT pedidos_alvo_formato CHECK (
  alvo_chave IS NULL
  OR (nivel = 'comarca' AND alvo_chave ~ '^MUN[0-9]{4}$|^MUN-[0-9A-HJKMNP-TV-Z]{26}$')
  OR (nivel = 'local' AND alvo_chave ~ '^FOR[0-9]{4}$|^MUN[0-9]{4}$|^LOC-[0-9A-HJKMNP-TV-Z]{26}$')
  OR (nivel = 'no' AND alvo_chave ~ '^UNI[0-9]{4}$|^NO-[0-9A-HJKMNP-TV-Z]{26}$')
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE pedidos ADD CONSTRAINT pedidos_recusa_motivo CHECK (
  (status = 'recusado' AND btrim(motivo_recusa) <> '')
  OR (status <> 'recusado' AND motivo_recusa = '')
)
SQL);

        // Auditoria das tabelas do app (usuario, acesso, pedido). O `historico`
        // do baseline guarda o dado; esta tabela guarda o rastro de quem mexeu
        // em quem, com antes/depois, e não entra no expurgo de 180 dias.
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ator_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('acao', 60);
            $table->string('entidade', 30);
            $table->string('entidade_chave', 60)->default('');
            $table->jsonb('antes')->nullable();
            $table->jsonb('depois')->nullable();
            $table->timestampTz('criado_em')->useCurrent();
            $table->index(['entidade', 'entidade_chave', 'criado_em']);
        });

        DB::statement(<<<'SQL'
ALTER TABLE auditoria ADD CONSTRAINT auditoria_tem_retrato CHECK (
  antes IS NOT NULL OR depois IS NOT NULL
)
SQL);

        // Mesmo trigger do baseline para `atualizado_em` (a função nasce lá).
        foreach (['usuarios', 'pedidos'] as $tabela) {
            DB::statement("CREATE TRIGGER tg_{$tabela}_atualiza BEFORE UPDATE ON {$tabela}
                           FOR EACH ROW EXECUTE FUNCTION marca_atualizacao()");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('acessos');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
    }
};
