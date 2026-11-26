<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Folha A4 · {{ $rotulo['plural'] === 'WhatsApp' ? 'WhatsApp' : ucfirst($rotulo['plural']) }} · Ramal</title>
    <meta name="robots" content="noindex">
    <style>
        :root {
            --tinta: #17211d;
            --verde: #0f3d33;
            --verde-claro: #e7efe9;
            --dourado: #b98a3c;
            --dourado-escuro: #7d5a22;
            --papel: #f6f3ec;
            --linha: #d8d2c4;
            --suave: #5d6b63;
            --mono: ui-monospace, "SF Mono", "Cascadia Mono", "Roboto Mono", Menlo, Consolas, monospace;
        }

        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }

        body {
            margin: 0;
            background: var(--papel);
            color: var(--tinta);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.45;
        }

        a { color: var(--verde); }

        :focus-visible { outline: 2px solid var(--dourado); outline-offset: 3px; border-radius: 4px; }

        .barra {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            width: 210mm;
            max-width: calc(100% - 32px);
            margin: 20px auto 0;
            padding: 12px 16px;
            border: 1px solid var(--linha);
            border-radius: 14px;
            background: #fffdf8;
        }

        .barra .voltar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 8px;
            font-size: 13px;
            letter-spacing: .04em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--suave);
        }

        .barra .voltar:hover { color: var(--verde); }

        .barra .dica { margin: 0; font-size: 13px; color: var(--suave); }

        .barra .acoes { display: flex; align-items: center; gap: 12px; }

        .botao-imprimir {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 18px;
            border: 1px solid var(--verde);
            border-radius: 999px;
            background: var(--verde);
            color: #fff;
            font: inherit;
            font-size: 13px;
            letter-spacing: .05em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .botao-imprimir:hover { background: #0c322a; }

        .folha {
            width: 210mm;
            min-height: 297mm;
            margin: 18px auto 40px;
            padding: 14mm 13mm 18mm;
            background: #fff;
            border: 1px solid #e4dfd3;
            box-shadow: 0 14px 40px rgba(23, 33, 29, .12);
        }

        .folha-topo {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 8px;
            border-bottom: 1.4px solid var(--verde);
            font-size: 9pt;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--verde);
        }

        .folha-topo b {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 13pt;
            font-weight: 400;
            letter-spacing: .01em;
            text-transform: none;
        }

        .folha-titulo {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
            margin: 7mm 0 4mm;
        }

        .olho {
            margin: 0 0 3px;
            font-size: 8pt;
            letter-spacing: .15em;
            text-transform: uppercase;
            color: var(--dourado-escuro);
        }

        h1 {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: 22pt;
            line-height: 1.08;
            color: var(--verde);
        }

        .contagem {
            margin: 0;
            text-align: right;
            font-size: 9pt;
            color: var(--suave);
        }

        .contagem strong {
            display: block;
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: 20pt;
            line-height: 1;
            color: var(--verde);
            font-variant-numeric: tabular-nums;
        }

        .folha-recorte {
            margin: 0 0 5mm;
            padding: 7px 10px;
            border-left: 2.5px solid var(--dourado);
            background: #f6f2e7;
            font-size: 9pt;
            color: var(--verde);
            overflow-wrap: break-word;
        }

        .folha-recorte b {
            font-size: 7.5pt;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--dourado-escuro);
        }

        .tabela { width: 100%; border-collapse: collapse; table-layout: fixed; }

        .tabela caption { text-align: left; }

        .tabela th {
            padding: 0 6px 5px 0;
            border-bottom: 1px solid var(--verde);
            text-align: left;
            font-size: 7.5pt;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--suave);
        }

        .tabela td {
            padding: 4.2px 7px 4.2px 0;
            border-bottom: .4pt solid #ded9cd;
            vertical-align: top;
            font-size: 9pt;
        }

        .tabela tr:last-child td { border-bottom: 0; }

        .cel-valor { width: 26%; }
        .cel-unidade { width: 29%; }
        .cel-setor { width: 19%; color: var(--suave); }
        .cel-local { width: 26%; color: var(--suave); }

        .valor {
            font-family: var(--mono);
            font-size: 9pt;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            overflow-wrap: break-word;
        }

        .mascarado .valor { color: var(--suave); font-weight: 500; }

        .descricao { display: block; margin-top: 1px; font-size: 7.5pt; color: var(--suave); }

        .local { display: block; margin-top: 1px; font-size: 7.5pt; }

        .vazio { color: #a3ada6; }

        .estado-vazio {
            padding: 30mm 10mm;
            border: 1px dashed var(--linha);
            text-align: center;
            color: var(--suave);
        }

        .estado-vazio strong {
            display: block;
            margin-bottom: 4px;
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: 15pt;
            color: var(--verde);
        }

        .estado-vazio p { margin: 0; font-size: 10pt; }

        .folha-rodape { display: none; }

        .corrente { display: none; }

        .sr-apenas {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        @media screen and (max-width: 720px) {
            .barra { flex-wrap: wrap; }
            .barra .dica { display: none; }
            .folha { width: auto; min-height: 0; margin: 14px 12px 30px; padding: 18px 16px 24px; }
            .folha-titulo { align-items: flex-start; flex-direction: column; gap: 6px; }
            .contagem { text-align: left; }
            .tabela thead { display: none; }
            .tabela, .tabela tbody, .tabela tr, .tabela td { display: block; width: auto; }
            .tabela tr { padding: 7px 0; border-bottom: .4pt solid #ded9cd; break-inside: avoid; }
            .tabela td { border: 0; padding: 1px 0; }
            .tabela td::before {
                content: attr(data-rotulo) " · ";
                font-size: 7.5pt;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: var(--suave);
            }
            .tabela td.cel-valor::before { content: none; }
        }

        @media print {
            @page { size: A4 portrait; margin: 12mm 11mm 15mm; }

            body { background: #fff; color: #000; }
            .barra { display: none !important; }
            .folha {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
            }

            .folha-topo { border-bottom-color: #000; color: #000; }
            .folha-topo b { font-size: 12pt; }
            .olho, .folha-recorte b { color: #333; }
            h1 { font-size: 18pt; color: #000; }
            .contagem, .contagem strong { color: #000; }
            .folha-recorte { display: none; }

            .corrente { display: table-row; }
            .corrente th {
                padding: 0 0 4px;
                border-bottom: 1px solid #000;
                background: transparent;
                font-size: 7.5pt;
                font-weight: 400;
                letter-spacing: 0;
                text-transform: none;
                color: #000;
            }
            .corrente .corrente-rotulo {
                font-weight: 600;
                letter-spacing: .08em;
                text-transform: uppercase;
            }
            .corrente .corrente-conta { float: right; }

            .tabela thead { display: table-header-group; }
            .tabela tr { break-inside: avoid; page-break-inside: avoid; }
            .tabela th { border-bottom-color: #000; color: #000; }
            .tabela td { border-bottom: .4pt solid #bbb; }
            .tabela tbody tr:nth-child(even) { background: #f7f7f7; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .cel-setor, .cel-local, .descricao, .local, .mascarado .valor { color: #333; }
            .valor { color: #000; }

            .folha-rodape {
                display: block;
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                padding-top: 3px;
                border-top: .4pt solid #000;
                text-align: right;
                font-size: 7.5pt;
                color: #444;
            }
        }
    </style>
</head>
<body>
    @php
        $filtros = $lista['filtros'];
        $temMascara = collect($lista['itens'])->contains(fn (array $i): bool => str_contains($i['valor'], 'X'));
        $plural = $rotulo['plural'];
        $titulo = $plural === 'WhatsApp' ? 'WhatsApp' : ucfirst($plural);
        $queryVolta = array_filter([
            'comarca' => $filtros['comarca'],
            'local' => $filtros['local'],
            'no' => $filtros['no'],
            'q' => $filtros['q'],
        ]);
        $textoRecorte = $recorte === [] ? 'Todo o diretório' : implode(' · ', $recorte);
        $data = now()->timezone('America/Sao_Paulo')->format('d/m/Y');
    @endphp

    <div class="barra">
        <a class="voltar" href="{{ route('canal.'.$canal, $queryVolta) }}">← Voltar à lista</a>
        <p class="dica">Esta é a folha do recorte inteiro — {{ number_format($lista['total'], 0, ',', '.') }} {{ $plural }}, sem os controles da tela.</p>
        <div class="acoes">
            <button class="botao-imprimir" type="button" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 9V4h10v5M7 18H5a1 1 0 0 1-1-1v-6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1h-2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 14h10v6H7z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                </svg>
                Imprimir
            </button>
        </div>
    </div>

    <main class="folha">
        <header class="folha-topo">
            <span><b>Ramal</b> · diretório de contatos</span>
            <span>{{ $data }}</span>
        </header>

        <div class="folha-titulo">
            <div>
                <p class="olho">Folha A4 · {{ $plural }}</p>
                <h1>{{ $titulo }}</h1>
            </div>
            <p class="contagem">
                <strong>{{ number_format($lista['total'], 0, ',', '.') }}</strong>
                {{ $lista['total'] === 1 ? $rotulo['singular'] : $plural }} no recorte
            </p>
        </div>

        <p class="folha-recorte"><b>Recorte</b> {{ $textoRecorte }}</p>

        @if ($lista['total'] === 0)
            <div class="estado-vazio">
                <strong>Nada neste recorte</strong>
                <p>Volte à lista e limpe o filtro para gerar a folha.</p>
            </div>
        @else
            <table class="tabela">
                <caption class="sr-apenas">{{ $titulo }} do recorte, com unidade, setor e comarca · local</caption>
                <colgroup>
                    <col class="cel-valor">
                    <col class="cel-unidade">
                    <col class="cel-setor">
                    <col class="cel-local">
                </colgroup>
                <thead>
                    <tr class="corrente">
                        <th colspan="4" scope="colgroup">
                            <span class="corrente-rotulo">{{ $titulo }}</span>
                            · {{ $textoRecorte }}
                            <span class="corrente-conta">{{ number_format($lista['total'], 0, ',', '.') }} {{ $plural }}</span>
                        </th>
                    </tr>
                    <tr>
                        <th scope="col">{{ $plural === 'WhatsApp' ? 'WhatsApp' : ucfirst($rotulo['singular']) }}</th>
                        <th scope="col">Unidade</th>
                        <th scope="col">Setor</th>
                        <th scope="col">Comarca · local</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lista['itens'] as $item)
                        <tr @class(['mascarado' => str_contains($item['valor'], 'X')])>
                            <td class="cel-valor" data-rotulo="{{ ucfirst($rotulo['singular']) }}">
                                <span class="valor">{{ $item['valor'] }}</span>
                                @if ($item['descricao'] !== '')
                                    <span class="descricao">{{ $item['descricao'] }}</span>
                                @endif
                            </td>
                            <td class="cel-unidade" data-rotulo="Unidade">{{ $item['unidade'] }}</td>
                            <td class="cel-setor" data-rotulo="Setor">
                                @if ($item['setor'] === null)
                                    <span class="vazio" aria-hidden="true">—</span><span class="sr-apenas">sem setor</span>
                                @else
                                    {{ $item['setor'] }}
                                @endif
                            </td>
                            <td class="cel-local" data-rotulo="Comarca · local">
                                {{ $item['comarca'] ?? 'Sede' }}
                                <span class="local">{{ $item['local'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="folha-rodape">
            Ramal · diretório de contatos · {{ $temMascara ? 'dados de demonstração mascarados' : 'dados do catálogo' }}
        </div>
    </main>
</body>
</html>
