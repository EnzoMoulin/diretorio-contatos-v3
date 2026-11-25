<?php

namespace Database\Seeders;

use App\Models\Sinonimo;
use Illuminate\Database\Seeder;

/**
 * Léxico de busca (ideia 04 do backlog): o que o cidadão digita → o vocabulário
 * que a base usa.
 *
 * Regras do arquivo, porque `termo` tem `CHECK` de normalizado no banco:
 *   - `termo` em minúscula e sem acento (`norm_texto`), uma linha por forma;
 *   - `expansao` são frases canônicas, também normalizadas. Dentro da frase as
 *     palavras valem juntas (E); frases diferentes são alternativas (OU).
 *
 * O tamanho é de propósito: a base inteira tem 619 palavras alfabéticas (medido em
 * 25/11/2026), então o léxico é curado à mão e revisável — não é modelo, não tem
 * custo e não aprende sozinho. Termo novo entra por decisão, com a consulta real
 * que o motivou.
 */
class SinonimoSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    public const LEXICO = [
        // Cartório e registro: o cidadão nomeia o serviço, a base nomeia o setor.
        'reconhecimento de firma' => ['cartorio'],
        'firma reconhecida' => ['cartorio'],
        'tabelionato' => ['cartorio'],
        'escritura publica' => ['cartorio'],
        'registro de imoveis' => ['registro'],
        'certidao de nascimento' => ['registro'],
        // Família.
        'pensao alimenticia' => ['familia'],
        'divorcio' => ['familia'],
        'guarda de filho' => ['familia'],
        // Sucessões e órfãos.
        'heranca' => ['sucessoes'],
        'inventario' => ['sucessoes'],
        'testamento' => ['sucessoes'],
        'tutela de menor' => ['orfaos'],
        // Infância e juventude.
        'conselho tutelar' => ['infancia juventude'],
        'crianca e adolescente' => ['infancia juventude'],
        'menor infrator' => ['socioeducativo'],
        // Trabalho.
        'justica do trabalho' => ['trabalho'],
        'processo do trabalho' => ['trabalho'],
        // Previdência.
        'inss' => ['previdencia'],
        'aposentadoria' => ['previdencia'],
        'pericia medica' => ['previdencia'],
        'auxilio doenca' => ['previdencia'],
        // Oficiais de justiça e audiências.
        'intimacao' => ['mandados'],
        'oficial de justica' => ['oficiais de justica'],
        'marcar audiencia' => ['audiencias'],
        // Juizado.
        'pequenas causas' => ['juizado'],
        'juizado de pequenas causas' => ['juizado'],
        // Violência doméstica: a base separa por núcleo, não por assunto.
        'medida protetiva' => ['violencia mulher'],
        // Penal.
        'crime' => ['criminal'],
        'juri popular' => ['juri'],
        'tribunal do juri' => ['juri'],
        // Execução e fazenda pública.
        'divida ativa' => ['execucao fiscal'],
        'divida' => ['execucao'],
        'iptu' => ['fazenda'],
        'tributo' => ['fazenda'],
        // Atendimento e plantão.
        'balcao' => ['atendimento'],
        'atendimento ao publico' => ['atendimento'],
        'plantao judiciario' => ['plantao'],
        'urgencia' => ['plantao'],
    ];

    public function run(): void
    {
        foreach (self::LEXICO as $termo => $expansao) {
            Sinonimo::query()->updateOrCreate(['termo' => $termo], ['expansao' => $expansao]);
        }
    }
}
