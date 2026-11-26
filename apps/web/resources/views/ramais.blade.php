<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Índice de ramais · Ramal</title>
    <meta name="description" content="Os {{ number_format($total_geral, 0, ',', '.') }} ramais do diretório em ordem numérica, com comarca, unidade e setor.">
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
            line-height: 1.55;
        }

        a { color: var(--verde); }

        .pagina { max-width: 1080px; margin: 0 auto; padding: 26px 28px 56px; }

        .topo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid var(--linha);
            padding-bottom: 16px;
        }

        .marca { display: flex; align-items: center; gap: 10px; text-decoration: none; }

        .marca svg { display: block; color: var(--verde); }

        .marca b {
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: 22px;
            letter-spacing: .01em;
            color: var(--verde);
            line-height: 1;
        }

        .marca small {
            display: block;
            margin-top: 3px;
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--suave);
        }

        .acoes { display: flex; align-items: center; gap: 18px; }

        .acoes a {
            font-size: 13px;
            letter-spacing: .04em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--suave);
        }

        .acoes a:hover { color: var(--verde); }

        .botao-imprimir {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 16px;
            border: 1px solid var(--verde);
            border-radius: 999px;
            background: transparent;
            color: var(--verde);
            font: inherit;
            font-size: 13px;
            letter-spacing: .05em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .botao-imprimir:hover { background: var(--verde); color: var(--papel); }

        .heroi { display: grid; grid-template-columns: 1fr auto; gap: 28px; align-items: end; padding: 44px 0 26px; }

        .olho {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--dourado-escuro);
            margin: 0 0 10px;
        }

        h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: clamp(32px, 4.6vw, 46px);
            line-height: 1.08;
            letter-spacing: -.015em;
            color: var(--verde);
            margin: 0 0 12px;
        }

        .linha-fina { max-width: 620px; margin: 0; font-size: 17px; color: var(--suave); }

        .resumo {
            min-width: 190px;
            border-left: 1px solid var(--linha);
            padding-left: 22px;
            text-align: left;
        }

        .resumo strong {
            display: block;
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: 44px;
            line-height: 1;
            color: var(--verde);
            font-variant-numeric: tabular-nums;
        }

        .resumo span {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--suave);
        }

        .busca {
            display: flex;
            gap: 10px;
            align-items: stretch;
            margin: 6px 0 18px;
            padding: 8px;
            border: 1px solid var(--linha);
            border-radius: 14px;
            background: #fffdf8;
            box-shadow: 0 1px 0 rgba(23, 33, 29, .03);
        }

        .busca label {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        .busca input {
            flex: 1;
            min-width: 0;
            border: 0;
            background: transparent;
            padding: 8px 12px;
            font: inherit;
            font-size: 17px;
            color: var(--tinta);
        }

        .busca input::placeholder { color: #6b7870; }

        .busca input:focus { outline: none; }

        .busca:focus-within { border-color: var(--verde); box-shadow: 0 0 0 3px rgba(15, 61, 51, .12); }

        .botao-busca {
            min-height: 44px;
            padding: 0 22px;
            border: 0;
            border-radius: 10px;
            background: var(--verde);
            color: var(--papel);
            font: inherit;
            font-size: 13px;
            letter-spacing: .08em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .botao-busca:hover { background: #0b2f28; }

        .limpar {
            align-self: center;
            padding: 0 8px;
            font-size: 13px;
            color: var(--suave);
            text-decoration: none;
            white-space: nowrap;
        }

        .limpar:hover { color: var(--verde); text-decoration: underline; }

        .nota {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin: 0 0 26px;
            padding: 13px 16px;
            border-left: 3px solid var(--dourado);
            background: var(--verde-claro);
            color: var(--verde);
            font-size: 14px;
            border-radius: 0 8px 8px 0;
        }

        .nota b { font-weight: 600; }

        .nota p { margin: 0; }

        .resultado-topo {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--verde);
        }

        .resultado-topo h2 {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: 21px;
            color: var(--verde);
        }

        .resultado-topo span { font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: var(--suave); }

        .tabela { width: 100%; border-collapse: collapse; table-layout: fixed; }

        .sr-apenas {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        .tabela th {
            padding: 10px 12px 8px 0;
            border-bottom: 1px solid var(--linha);
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .13em;
            text-transform: uppercase;
            color: var(--suave);
        }

        .tabela td {
            padding: 11px 12px 11px 0;
            border-bottom: 1px solid var(--linha);
            vertical-align: top;
            font-size: 15px;
        }

        .tabela tbody tr:hover { background: rgba(231, 239, 233, .55); }

        .cel-ramal { width: 16%; }

        .cel-comarca { width: 22%; color: var(--suave); }

        .cel-unidade { width: 30%; }

        .cel-setor { width: 32%; color: var(--suave); }

        .local {
            display: block;
            margin-top: 1px;
            font-size: 12px;
            color: var(--suave);
        }

        .numero {
            font-family: var(--mono);
            font-size: 17px;
            font-variant-numeric: tabular-nums;
            letter-spacing: .01em;
            color: var(--verde);
            white-space: nowrap;
        }

        .mascarado .numero { color: var(--suave); letter-spacing: .08em; }

        .vazio { color: #a3ada6; }

        .estado-vazio {
            padding: 56px 24px;
            border: 1px dashed var(--linha);
            border-radius: 14px;
            background: #fffdf8;
            text-align: center;
        }

        .estado-vazio strong {
            display: block;
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: 24px;
            color: var(--verde);
            margin-bottom: 6px;
        }

        .estado-vazio p { margin: 0; color: var(--suave); font-size: 15px; }

        .rodape {
            margin-top: 40px;
            padding-top: 16px;
            border-top: 1px solid var(--linha);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
            color: var(--suave);
        }

        .impressao-topo { display: none; }

        .impressao-rodape { display: none; }

        @media (max-width: 680px) {
            .pagina { padding: 18px 16px 40px; }

            .heroi { grid-template-columns: 1fr; gap: 18px; padding: 30px 0 22px; }

            .resumo { border-left: 0; border-top: 1px solid var(--linha); padding: 14px 0 0; }

            .resumo strong { font-size: 34px; }

            .acoes a { display: none; }

            .botao-imprimir { padding: 0 12px; }

            .resultado-topo { flex-wrap: wrap; }

            .resultado-topo span { display: none; }

            .tabela thead { display: none; }

            .tabela, .tabela tbody, .tabela tr, .tabela td { display: block; width: auto; }

            .tabela tr {
                margin: 0 0 10px;
                padding: 12px 14px 13px;
                border: 1px solid var(--linha);
                border-radius: 12px;
                background: #fffdf8;
            }

            .tabela tbody tr:hover { background: #fffdf8; }

            .tabela td { padding: 2px 0; border: 0; font-size: 14px; }

            .tabela td::before {
                content: attr(data-rotulo) " · ";
                font-size: 10px;
                letter-spacing: .1em;
                text-transform: uppercase;
                color: var(--suave);
            }

            .tabela td.cel-ramal { padding-bottom: 7px; }

            .tabela td.cel-ramal::before { content: none; }

            .numero { font-size: 21px; }
        }

        @media print {
            @page { size: A4 portrait; margin: 12mm 11mm 15mm; }

            body { background: #fff; color: #000; }

            .pagina { max-width: none; margin: 0; padding: 0; }

            .topo, .heroi, .busca, .nota, .rodape { display: none !important; }

            .impressao-rodape {
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

            .impressao-topo {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                padding-bottom: 8px;
                border-bottom: 1.5px solid #000;
                font-size: 9pt;
                letter-spacing: .06em;
                text-transform: uppercase;
            }

            .resultado-topo { padding-bottom: 6px; border-bottom: 1px solid #000; }

            .resultado-topo h2 { font-size: 14pt; }

            .resultado-topo span { font-size: 8pt; }

            .tabela { table-layout: fixed; }

            .tabela thead { display: table-header-group; }

            .tabela tr { break-inside: avoid; page-break-inside: avoid; }

            .tabela th {
                padding: 5px 6px 4px 0;
                border-bottom: 1px solid #000;
                font-size: 7.5pt;
                color: #000;
            }

            .tabela td {
                padding: 3.4px 6px 3.4px 0;
                border-bottom: .4pt solid #bbb;
                font-size: 8.4pt;
                line-height: 1.25;
            }

            .cel-ramal { width: 15%; }
            .cel-comarca { width: 21%; }
            .cel-unidade { width: 31%; }
            .cel-setor { width: 33%; }

            .local { font-size: 7.2pt; color: #444; }

            .numero { font-size: 9pt; color: #000; }

            .mascarado .numero, .cel-comarca, .cel-setor { color: #333; }

            .tabela tbody tr:nth-child(even) { background: #f3f3f3; }

            .tabela tbody { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    @php
        $temBusca = $busca !== '';
        $semDigito = $temBusca && $digitos === '';
        $ordemRotulo = $mascarados === $total_geral ? 'ordem do catálogo' : 'ordem numérica';
        $tituloResultado = $temBusca
            ? ($semDigito ? 'Busca sem número' : $total.' '.($total === 1 ? 'ramal' : 'ramais').' para “'.$busca.'”')
            : number_format($total_geral, 0, ',', '.').' ramais';
    @endphp

    <div class="pagina">
        <header class="topo">
            <a class="marca" href="{{ route('inicio') }}">
                <svg width="30" height="30" viewBox="0 0 30 30" fill="none" aria-hidden="true">
                    <path d="M6 26V15a4 4 0 0 1 4-4h10a4 4 0 0 0 4-4V5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <circle cx="6" cy="26" r="1.9" fill="currentColor"/>
                    <circle cx="24" cy="5" r="1.9" fill="currentColor"/>
                </svg>
                <span>
                    <b>Ramal</b>
                    <small>Diretório de contatos</small>
                </span>
                <span class="sr-apenas"> — voltar à capa</span>
            </a>

            <div class="acoes">
                <a href="{{ route('inicio') }}">Capa</a>
                <button class="botao-imprimir" type="button" onclick="window.print()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M7 9V4h10v5M7 18H5a1 1 0 0 1-1-1v-6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1h-2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 14h10v6H7z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    </svg>
                    Imprimir
                </button>
            </div>
        </header>

        <div class="impressao-topo">
            <span>Ramal · índice de ramais</span>
            <span>{{ now()->timezone('America/Sao_Paulo')->format('d/m/Y') }} · {{ number_format($total_geral, 0, ',', '.') }} ramais</span>
        </div>

        <main>
            <section class="heroi">
                <div>
                    <p class="olho">Consulta pelo número</p>
                    <h1>Índice de ramais</h1>
                    <p class="linha-fina">
                        De trás para frente: o número que está no papel ao lado do telefone, com a
                        comarca, a unidade e o setor onde ele atende.
                    </p>
                </div>
                <div class="resumo">
                    <strong>{{ number_format($total_geral, 0, ',', '.') }}</strong>
                    <span>{{ $ordemRotulo }}</span>
                </div>
            </section>

            <form class="busca" method="get" action="{{ route('ramais') }}" role="search">
                <label for="ramal">Número do ramal</label>
                <input
                    id="ramal"
                    name="q"
                    type="search"
                    value="{{ $busca }}"
                    placeholder="Ramal, prefixo ou final — ex.: 1234"
                    inputmode="numeric"
                    autocomplete="off"
                    maxlength="20"
                >
                <button class="botao-busca" type="submit">Buscar</button>
                @if ($temBusca)
                    <a class="limpar" href="{{ route('ramais') }}">limpar</a>
                @endif
            </form>

            @if ($mascarados > 0)
                <div class="nota">
                    <p>
                        <b>Base de demonstração.</b>
                        {{ number_format($mascarados, 0, ',', '.') }} de {{ number_format($total_geral, 0, ',', '.') }}
                        ramais estão mascarados (XXXX). Enquanto o número não existe, a lista segue a ordem do
                        catálogo e a busca por número não encontra esses registros — no banco real, a ordem e a
                        busca são pelo número.
                    </p>
                </div>
            @endif

            <div class="resultado-topo">
                <h2>{{ $tituloResultado }}</h2>
                <span>{{ $ordemRotulo }} · {{ number_format($total_geral, 0, ',', '.') }} no índice</span>
            </div>

            @if ($total === 0)
                <div class="estado-vazio" role="status">
                    <strong>{{ $semDigito ? 'Digite um número' : 'Nenhum ramal com esse número' }}</strong>
                    <p>
                        @if ($semDigito)
                            A busca do índice é por número — tente “1234”, “123” ou o final do ramal.
                        @elseif ($mascarados === $total_geral)
                            Nesta demonstração os números estão mascarados; limpe a busca para ver o índice pela ordem do catálogo.
                        @else
                            Confira o número ou <a href="{{ route('ramais') }}">veja o índice inteiro</a>.
                        @endif
                    </p>
                </div>
            @else
                <table class="tabela">
                    <caption class="sr-apenas">Ramais em {{ $ordemRotulo }}, com comarca, unidade e setor</caption>
                    <colgroup>
                        <col class="cel-ramal">
                        <col class="cel-comarca">
                        <col class="cel-unidade">
                        <col class="cel-setor">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">Ramal</th>
                            <th scope="col">Comarca</th>
                            <th scope="col">Unidade</th>
                            <th scope="col">Setor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($itens as $ramal)
                            <tr @class(['mascarado' => $ramal['digitos'] === ''])>
                                <td class="cel-ramal" data-rotulo="Ramal">
                                    <span class="numero">{{ $ramal['digitos'] === '' ? 'XXXX' : $ramal['valor'] }}</span>
                                </td>
                                <td class="cel-comarca" data-rotulo="Comarca">
                                    {{ $ramal['comarca'] }}
                                    @if ($ramal['local_tipo'] === 'FORUM')
                                        <span class="local">{{ $ramal['local'] }}</span>
                                    @elseif ($ramal['local_tipo'] !== 'SEDE')
                                        <span class="local">{{ $ramal['local'] }}</span>
                                    @endif
                                </td>
                                <td class="cel-unidade" data-rotulo="Unidade">{{ $ramal['unidade'] }}</td>
                                <td class="cel-setor" data-rotulo="Setor">
                                    @if ($ramal['setor'] === null)
                                        <span class="vazio" aria-hidden="true">—</span><span class="sr-apenas">sem setor</span>
                                    @else
                                        {{ $ramal['setor'] }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </main>

        <footer class="rodape">
            <span>Ramal · diretório de contatos · Laravel 13 · Postgres 16</span>
            <span>{{ $mascarados > 0 ? 'Dados de demonstração mascarados' : 'Dados do catálogo' }}</span>
        </footer>
    </div>

    <div class="impressao-rodape">
        Ramal · diretório de contatos · {{ $mascarados > 0 ? 'dados de demonstração mascarados' : 'dados do catálogo' }}
    </div>
</body>
</html>
