<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $rotulo['plural'] === 'WhatsApp' ? 'WhatsApp' : ucfirst($rotulo['plural']) }} · Ramal</title>
    <meta name="description" content="{{ number_format($lista['total'], 0, ',', '.') }} {{ $rotulo['plural'] }} do diretório, com comarca, local, unidade e setor.">
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

        :focus-visible { outline: 2px solid var(--dourado); outline-offset: 3px; border-radius: 4px; }

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
            display: inline-flex;
            align-items: center;
            min-height: 44px;
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
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .botao-imprimir:hover { background: var(--verde-claro); }

        .heroi {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 28px;
            align-items: end;
            padding: 30px 0 22px;
        }

        .olho {
            margin: 0 0 6px;
            font-size: 12px;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--dourado-escuro);
        }

        h1 {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: clamp(30px, 4.4vw, 42px);
            line-height: 1.1;
            color: var(--verde);
        }

        .linha-fina { margin: 12px 0 0; max-width: 60ch; color: var(--suave); }

        .resumo { text-align: right; }
        .resumo strong { display: block; font-family: Georgia, serif; font-weight: 400; font-size: 34px; color: var(--verde); }
        .resumo span { font-size: 12px; letter-spacing: .1em; text-transform: uppercase; color: var(--suave); }

        .canais {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin: 0 0 18px;
            padding: 0;
            border-bottom: 1px solid var(--linha);
            list-style: none;
        }

        .canais a {
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            min-height: 44px;
            padding: 10px 14px;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            text-decoration: none;
            color: var(--suave);
            font-size: 14px;
        }

        .canais a:hover { color: var(--verde); }
        .canais a[aria-current="page"] { color: var(--verde); border-bottom-color: var(--dourado); font-weight: 600; }
        .canais .conta { font-family: var(--mono); font-size: 12px; color: var(--suave); }

        .filtros {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.3fr) minmax(0, 1fr) auto;
            gap: 12px;
            align-items: end;
            padding: 18px;
            border: 1px solid var(--linha);
            border-radius: 14px;
            background: #fffdf8;
        }

        .filtros label { display: block; margin-bottom: 5px; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--suave); }

        .filtros select,
        .filtros input {
            width: 100%;
            min-height: 44px;
            padding: 0 12px;
            border: 1px solid var(--linha);
            border-radius: 10px;
            background: #fff;
            color: var(--tinta);
            font: inherit;
            font-size: 15px;
        }

        .filtros input[type="search"] { font-family: var(--mono); }

        .filtros .acoes-filtro { display: flex; align-items: center; gap: 12px; }

        .botao-busca {
            min-height: 44px;
            padding: 0 22px;
            border: 0;
            border-radius: 10px;
            background: var(--verde);
            color: #fff;
            font: inherit;
            font-size: 14px;
            letter-spacing: .03em;
            cursor: pointer;
        }

        .botao-busca:hover { background: #0c322a; }

        .limpar {
            display: inline-flex;
            align-items: center;
            min-height: 44px;
            padding: 0 4px;
            font-size: 13px;
            color: var(--suave);
        }

        .resultado-topo {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            margin: 26px 0 10px;
        }

        .resultado-topo h2 { margin: 0; font-family: Georgia, serif; font-weight: 400; font-size: 22px; color: var(--verde); }
        .resultado-topo span { font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--suave); }

        .tabela {
            width: 100%;
            border-collapse: collapse;
            background: #fffdf8;
            border: 1px solid var(--linha);
            border-radius: 14px;
            overflow: hidden;
        }

        .tabela th {
            padding: 11px 14px;
            text-align: left;
            font-size: 11px;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--suave);
            border-bottom: 1px solid var(--linha);
            background: #f1ede3;
        }

        .tabela td { padding: 11px 14px; border-bottom: 1px solid #ece7db; vertical-align: top; }
        .tabela tr:last-child td { border-bottom: 0; }
        .tabela tbody tr:hover { background: var(--verde-claro); }

        .contato { display: flex; align-items: center; gap: 10px; }
        .valor { font-family: var(--mono); font-size: 15px; font-weight: 600; letter-spacing: .01em; }
        .mascarado .valor { color: var(--suave); font-weight: 500; }

        .link-canal { color: var(--verde); text-decoration: none; border-bottom: 1px solid rgba(15, 61, 51, .25); }
        .link-canal:hover { border-bottom-color: var(--verde); }

        .copiar {
            min-height: 44px;
            padding: 0 14px;
            border: 1px solid var(--linha);
            border-radius: 999px;
            background: #fff;
            color: var(--suave);
            font: inherit;
            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .copiar:hover { border-color: var(--verde); color: var(--verde); }

        .descricao { display: block; margin-top: 2px; font-size: 12px; color: var(--suave); }

        .cel-unidade { max-width: 26ch; }
        .cel-setor, .cel-local { color: var(--suave); font-size: 14px; }
        .vazio { color: var(--linha); }

        .estado-vazio {
            padding: 34px 24px;
            border: 1px dashed var(--linha);
            border-radius: 14px;
            text-align: center;
            color: var(--suave);
        }

        .estado-vazio strong { display: block; margin-bottom: 6px; font-family: Georgia, serif; font-weight: 400; font-size: 20px; color: var(--verde); }
        .estado-vazio p { margin: 0; }

        .paginacao {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 18px;
            font-size: 14px;
        }

        .paginacao .paginas { display: flex; gap: 10px; }

        .paginacao a,
        .paginacao span.parado {
            display: inline-flex;
            align-items: center;
            min-height: 44px;
            padding: 0 16px;
            border: 1px solid var(--linha);
            border-radius: 999px;
            text-decoration: none;
            color: var(--verde);
            background: #fffdf8;
        }

        .paginacao span.parado { color: var(--linha); }

        .rodape {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-top: 44px;
            padding-top: 14px;
            border-top: 1px solid var(--linha);
            font-size: 12px;
            color: var(--suave);
        }

        .sr-apenas {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
            border: 0;
        }

        .impressao-topo, .impressao-rodape { display: none; }

        @media (max-width: 820px) {
            .pagina { padding: 20px 18px 40px; }
            .heroi { grid-template-columns: 1fr; gap: 14px; }
            .resumo { text-align: left; }
            .filtros { grid-template-columns: 1fr; }
            .tabela thead { display: none; }
            .tabela, .tabela tbody, .tabela tr, .tabela td { display: block; width: 100%; }
            .tabela tr { border-bottom: 1px solid var(--linha); padding: 8px 0; }
            .tabela td { border: 0; padding: 4px 14px; }
            .tabela td::before {
                content: attr(data-rotulo) ": ";
                font-size: 11px;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: var(--suave);
            }
            .cel-unidade { max-width: none; }
        }

        @media print {
            @page { size: A4; margin: 14mm 12mm; }

            body { background: #fff; font-size: 10pt; }
            .pagina { max-width: none; padding: 0; }
            .topo, .heroi, .canais, .filtros, .paginacao, .rodape, .copiar { display: none !important; }

            .impressao-topo {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                padding-bottom: 6mm;
                margin-bottom: 4mm;
                border-bottom: 1px solid #999;
                font-size: 9pt;
            }

            .impressao-rodape {
                display: block;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                font-size: 8pt;
                color: #555;
                border-top: 1px solid #bbb;
                padding-top: 2mm;
            }

            .resultado-topo { margin: 0 0 3mm; }
            .resultado-topo h2 { font-size: 14pt; }

            .tabela { border: 1px solid #bbb; border-radius: 0; background: #fff; }
            .tabela thead { display: table-header-group; }
            .tabela th { background: #fff; border-bottom: 1px solid #999; font-size: 8pt; }
            .tabela td { border-bottom: 1px solid #ddd; padding: 1.6mm 2mm; }
            .tabela tr { break-inside: avoid; }
            .tabela tbody tr:nth-child(even) { background: #fafafa; }
            .valor { font-size: 9.5pt; }
            .descricao, .cel-setor, .cel-local { font-size: 8.5pt; }
            .link-canal { border: 0; color: inherit; }
            a { text-decoration: none; color: inherit; }
        }
    </style>
</head>
<body>
    @php
        $filtros = $lista['filtros'];
        $temMascara = collect($lista['itens'])->contains(fn (array $i): bool => str_contains($i['valor'], 'X'));
        $temFiltro = $filtros['comarca'] !== null || $filtros['local'] !== null || $filtros['q'] !== '';
        $plural = $rotulo['plural'];
        $tituloResultado = $temFiltro
            ? number_format($lista['total'], 0, ',', '.').' '.($lista['total'] === 1 ? $rotulo['singular'] : $plural).' no recorte'
            : number_format($lista['total'], 0, ',', '.').' '.$plural;
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
                <a href="{{ route('ramais') }}">Ramais</a>
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
            <span>Ramal · {{ $plural }}</span>
            <span>{{ now()->timezone('America/Sao_Paulo')->format('d/m/Y') }} · {{ number_format($lista['total'], 0, ',', '.') }} {{ $plural }}</span>
        </div>

        <main>
            <section class="heroi">
                <div>
                    <p class="olho">Consulta por canal</p>
                    <h1>{{ $rotulo['plural'] === 'WhatsApp' ? 'WhatsApp' : ucfirst($plural) }}</h1>
                    <p class="linha-fina">
                        Quem atende, onde atende e em que extensão — com a comarca, o local e o setor
                        na mesma linha, para copiar e ligar sem trocar de tela.
                    </p>
                </div>
                <div class="resumo">
                    <strong>{{ number_format($lista['total'], 0, ',', '.') }}</strong>
                    <span>no recorte</span>
                </div>
            </section>

            <nav class="canais" aria-label="Canais do diretório">
                @foreach ($canais as $item)
                    <a href="{{ route($item['rota'], array_filter(['comarca' => $filtros['comarca'], 'local' => $filtros['local'], 'q' => $filtros['q']])) }}"
                       @if ($item['atual']) aria-current="page" @endif>
                        {{ $item['rotulo'] }}
                        @if ($item['total'] !== null)
                            <span class="conta">{{ number_format($item['total'], 0, ',', '.') }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <form class="filtros" method="get" action="{{ route('canal.'.$canal) }}" role="search">
                <div>
                    <label for="comarca">Comarca</label>
                    <select id="comarca" name="comarca">
                        <option value="">Todas</option>
                        @foreach ($comarcas as $comarca)
                            <option value="{{ $comarca['chave'] }}" @selected($filtros['comarca'] === $comarca['chave'])>{{ $comarca['nome'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="local">Local</label>
                    <select id="local" name="local">
                        <option value="">Todos</option>
                        @foreach ($locais as $grupo => $itens)
                            <optgroup label="{{ $grupo }}">
                                @foreach ($itens as $item)
                                    <option value="{{ $item['chave'] }}" @selected($filtros['local'] === $item['chave'])>{{ $item['nome'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="q">Busca</label>
                    <input id="q" name="q" type="search" value="{{ $filtros['q'] }}" placeholder="Unidade, setor, número…" maxlength="80" autocomplete="off">
                </div>

                <div class="acoes-filtro">
                    <button class="botao-busca" type="submit">Filtrar</button>
                    @if ($temFiltro)
                        <a class="limpar" href="{{ route('canal.'.$canal) }}">limpar</a>
                    @endif
                </div>
            </form>

            <div class="resultado-topo">
                <h2>{{ $tituloResultado }}</h2>
                <span>{{ $lista['paginas'] > 1 ? 'página '.$lista['pagina'].' de '.$lista['paginas'] : 'página única' }}</span>
            </div>

            @if ($lista['total'] === 0)
                <div class="estado-vazio" role="status">
                    <strong>Nada com esses filtros</strong>
                    <p>
                        @if ($filtros['q'] !== '')
                            Nenhum {{ $rotulo['singular'] }} casa com “{{ $filtros['q'] }}” neste recorte —
                            <a href="{{ route('canal.'.$canal, array_filter(['comarca' => $filtros['comarca'], 'local' => $filtros['local']])) }}">tirar a busca</a>.
                        @else
                            <a href="{{ route('canal.'.$canal) }}">Ver a lista inteira</a>.
                        @endif
                    </p>
                </div>
            @else
                <table class="tabela">
                    <caption class="sr-apenas">{{ ucfirst($plural) }} com comarca, local, unidade e setor</caption>
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">{{ $rotulo['plural'] === 'WhatsApp' ? 'WhatsApp' : ucfirst($rotulo['singular']) }}</th>
                            <th scope="col">Unidade</th>
                            <th scope="col">Setor</th>
                            <th scope="col">Comarca · local</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lista['itens'] as $item)
                            @php
                                $mascarado = str_contains($item['valor'], 'X');
                                $digitos = preg_replace('/\D+/', '', $item['valor']);
                                $href = match ($canal) {
                                    'emails' => 'mailto:'.$item['valor'],
                                    'whatsapp' => 'https://wa.me/55'.$digitos,
                                    default => 'tel:'.$digitos,
                                };
                            @endphp
                            <tr @class(['mascarado' => $mascarado])>
                                <td data-rotulo="{{ ucfirst($rotulo['singular']) }}">
                                    <div class="contato">
                                        @if ($mascarado)
                                            <span class="valor">{{ $item['valor'] }}</span>
                                            <span class="sr-apenas">valor mascarado na demonstração</span>
                                        @else
                                            <a class="valor link-canal" href="{{ $href }}">{{ $item['valor'] }}</a>
                                        @endif
                                        <button class="copiar" type="button" data-copiar="{{ $item['valor'] }}" aria-label="Copiar {{ $rotulo['singular'] }} {{ $item['valor'] }}">
                                            <span class="rotulo-copiar">copiar</span>
                                        </button>
                                    </div>
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
                                    <span class="descricao">{{ $item['local'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($lista['paginas'] > 1)
                    <nav class="paginacao" aria-label="Paginação">
                        <span>{{ number_format($lista['total'], 0, ',', '.') }} {{ $plural }} · {{ $lista['por_pagina'] }} por página</span>
                        <div class="paginas">
                            @if ($lista['pagina'] > 1)
                                <a rel="prev" href="{{ request()->fullUrlWithQuery(['pagina' => $lista['pagina'] - 1]) }}">← anterior</a>
                            @else
                                <span class="parado">← anterior</span>
                            @endif

                            @if ($lista['pagina'] < $lista['paginas'])
                                <a rel="next" href="{{ request()->fullUrlWithQuery(['pagina' => $lista['pagina'] + 1]) }}">próxima →</a>
                            @else
                                <span class="parado">próxima →</span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </main>

        <footer class="rodape">
            <span>Ramal · diretório de contatos · Laravel 13 · Postgres 16</span>
            <span>{{ $temMascara ? 'Dados de demonstração mascarados' : 'Dados do catálogo' }}</span>
        </footer>
    </div>

    <div class="impressao-rodape">
        Ramal · diretório de contatos · {{ $rotulo['plural'] }}
    </div>

    <script>
        document.addEventListener('click', function (evento) {
            var botao = evento.target.closest('[data-copiar]');
            if (!botao) { return; }

            var rotulo = botao.querySelector('.rotulo-copiar');
            var original = rotulo.textContent;

            var pronto = function () {
                rotulo.textContent = 'copiado';
                window.setTimeout(function () { rotulo.textContent = original; }, 1600);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(botao.getAttribute('data-copiar')).then(pronto);
                return;
            }

            var campo = document.createElement('textarea');
            campo.value = botao.getAttribute('data-copiar');
            document.body.appendChild(campo);
            campo.select();
            document.execCommand('copy');
            document.body.removeChild(campo);
            pronto();
        });
    </script>
</body>
</html>
