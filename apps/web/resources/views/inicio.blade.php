<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diretório de Contatos</title>
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
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--papel);
            color: var(--tinta);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.55;
        }

        .pagina { max-width: 960px; margin: 0 auto; padding: 32px 24px 56px; }

        header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid var(--linha);
            padding-bottom: 18px;
        }

        .marca {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 21px;
            letter-spacing: .01em;
            color: var(--verde);
        }

        .selo {
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--suave);
            border: 1px solid var(--linha);
            border-radius: 999px;
            padding: 4px 10px;
            white-space: nowrap;
        }

        .capa { padding: 56px 0 40px; max-width: 640px; }

        .olho {
            font-size: 12px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--dourado-escuro);
            margin: 0 0 10px;
        }

        h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: clamp(32px, 5vw, 48px);
            line-height: 1.12;
            letter-spacing: -.015em;
            color: var(--verde);
            margin: 0 0 16px;
        }

        .linha-fina { font-size: 17px; color: var(--suave); margin: 0; }

        .numeros {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--linha);
            border: 1px solid var(--linha);
            border-radius: 10px;
            overflow: hidden;
        }

        .numero { background: #fffdf8; padding: 20px 18px; }

        .numero strong {
            display: block;
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: 30px;
            color: var(--verde);
            font-variant-numeric: tabular-nums;
        }

        .numero small {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--suave);
        }

        section { margin-top: 40px; }

        h2 {
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: 21px;
            color: var(--verde);
            margin: 0 0 14px;
        }

        ol { list-style: none; margin: 0; padding: 0; columns: 2; column-gap: 32px; }

        li {
            break-inside: avoid;
            display: flex;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid var(--linha);
            font-size: 15px;
        }

        li b {
            font-weight: 400;
            color: var(--dourado-escuro);
            font-variant-numeric: tabular-nums;
            min-width: 22px;
        }

        .nota {
            margin-top: 40px;
            padding: 14px 16px;
            border-left: 3px solid var(--dourado);
            background: var(--verde-claro);
            color: var(--verde);
            font-size: 14px;
            border-radius: 0 6px 6px 0;
        }

        .atalho {
            margin: 18px 0 0;
            padding: 16px 18px;
            border: 1px solid var(--linha);
            border-radius: 10px;
            background: #fffdf8;
        }

        .atalho { transition: background-color .15s ease, border-color .15s ease; }

        .atalho:hover { background: var(--verde-claro); border-color: #b9c9bd; }

        .atalho a {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            color: var(--verde);
            font-family: Georgia, "Times New Roman", serif;
            font-size: 18px;
            text-decoration: none;
        }

        .atalho a:hover { text-decoration: underline; text-underline-offset: 3px; }

        .atalho small {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 12px;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--suave);
            white-space: nowrap;
        }

        footer {
            margin-top: 44px;
            padding-top: 16px;
            border-top: 1px solid var(--linha);
            font-size: 12px;
            color: var(--suave);
        }

        @media (max-width: 640px) {
            .numeros { grid-template-columns: repeat(2, 1fr); }
            ol { columns: 1; }
            .capa { padding-top: 36px; }
        }
    </style>
</head>
<body>
    <div class="pagina">
        <header>
            <div class="marca">Diretório de Contatos</div>
            <div class="selo">v3 · esqueleto</div>
        </header>

        <main>
            <div class="capa">
                <p class="olho">Espírito Santo</p>
                <h1>O diretório do tribunal, em uma árvore só.</h1>
                <p class="linha-fina">
                    Comarca, local, unidade e contato no mesmo caminho — do jeito que a pessoa
                    que procura um telefone pensa.
                </p>
            </div>

            <div class="numeros">
                <div class="numero">
                    <strong>{{ number_format($hierarquia['locais'], 0, ',', '.') }}</strong>
                    <small>locais</small>
                </div>
                <div class="numero">
                    <strong>{{ number_format($hierarquia['foruns_total'], 0, ',', '.') }}</strong>
                    <small>fóruns</small>
                </div>
                <div class="numero">
                    <strong>{{ number_format($hierarquia['nos_total'], 0, ',', '.') }}</strong>
                    <small>unidades e setores</small>
                </div>
                <div class="numero">
                    <strong>{{ number_format($hierarquia['uteis'], 0, ',', '.') }}</strong>
                    <small>telefones úteis</small>
                </div>
            </div>

            <p class="atalho">
                <a href="{{ route('ramais') }}">
                    <span>Índice de ramais — quem é o ramal 1234?</span>
                    <small>Consultar →</small>
                </a>
            </p>

            <p class="atalho">
                <a href="{{ route('canal.telefones') }}">
                    <span>Telefones, WhatsApp e e-mails — por comarca e unidade</span>
                    <small>Consultar →</small>
                </a>
            </p>

            @php
                $sede = $hierarquia['ordem'][0] ?? null;
                $comarcas = array_slice($hierarquia['ordem'], $sede ? 1 : 0, 12);
            @endphp

            <section>
                <h2>Comarcas</h2>
                <ol>
                    @foreach ($comarcas as $indice => $comarca)
                        <li><b>{{ str_pad($indice + 1, 2, '0', STR_PAD_LEFT) }}</b> {{ $comarca }}</li>
                    @endforeach
                </ol>
            </section>

            <p class="nota">
                Primeira leitura ligada no banco: a capa consome
                <code>GET /api/v1/hierarquia</code>, no formato do contrato do v2.
            </p>
        </main>

        <footer>
            Laravel 13 · Postgres 16 · dados de demonstração mascarados
        </footer>
    </div>
</body>
</html>
