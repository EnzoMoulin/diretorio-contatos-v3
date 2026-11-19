#!/usr/bin/env python3
"""Varredura de privacidade: dado real nao entra no repositorio.

Versao de bolso do teste do v2 (`tests/test_sem_telefone_real.py`): roda em
qualquer arvore, sem servidor e sem banco, e serve para CI.

Uso:
    python3 scripts/varrer_privacidade.py                  # varre o repo inteiro
    python3 scripts/varrer_privacidade.py docs/x.md        # varre um arquivo
    python3 scripts/varrer_privacidade.py --seed ../diretorio-contatos-v2/seed_mascarado.json
    python3 scripts/varrer_privacidade.py --autoteste      # prova que o detector pega

Codigo de saida 1 se achar telefone ou e-mail fora do padrao sintetico -> e o
que faz o CI ficar vermelho antes de o repositorio ser publicado.

Padrao sintetico aceito: mascara com `X`; os blocos de exemplo `3333-00NN`,
WhatsApp `99999-000N`, placeholder `0000-0000`; os dominios `exemplo.jus.br`
e `empresa-exemplo.com.br`.

`.privacidade-sinteticos` na raiz do repo e a allowlist de fixture: uma linha por
valor literal, ou `re:<regex>`, `#` comenta. Ela existe porque fixture de teste
precisa de telefone com formato variado — as quatro grafias que
`db/verificar-schema.sql` usa para testar o normalizador terminam em `000N`, mas
o formato varia de proposito — e o padrao sintetico so cobre os blocos fixos.
Regra: so fixture de teste, nunca para calar dado real. E ela nao e silenciosa:
o script imprime quantos valores declarados abafaram achado, quais, e qual
declaracao nunca casou com nada (candidata a sair) — linha nova aparece no log
do CI em vez de sumir dentro de um arquivo de configuracao.

Marca institucional, referencia a documento interno e honorifico saem como
aviso, nao como falha: sao decisao de identidade do dono, nao vazamento.

`--seed` fecha o ponto cego do seed: ele vive no repo da v2 e nao e versionado
no da v3, entao a varredura do repo da v3 nunca o le. O modo le o JSON do app,
roda o mesmo teste de telefone/e-mail nos valores e procura **nome de pessoa**
nos nomes de no, de contato e de telefone util — a base de demonstracao nao
deve ter nenhum, e os gabinetes entram como "Gabinete de Desembargador 01".

Limite consciente: so le texto versionado. Planilha, PDF, dump e imagem ficam
de fora por extensao; esses se auditam na origem, com a mascara.
"""
import argparse
import json
import os
import re
import sys
import unicodedata

PULAR_DIR = {".git", "node_modules", "__pycache__", "backups", "dist",
             ".venv", "vendor"}
PULAR_EXT = {".png", ".jpg", ".jpeg", ".gif", ".mp4", ".pdf", ".ico",
             ".woff", ".woff2", ".db", ".sqlite", ".zip", ".pyc", ".xlsx",
             ".ndjson", ".docx", ".pptx", ".otf", ".ttf", ".map"}

TELEFONE = re.compile(
    r"(?<![\w-])(?:\(?\d{2}\)?[\s.\-]?)?\d{4,5}[\s.\-]?\d{4}(?![\w-])")
EMAIL = re.compile(r"[\w.+-]+@[\w-]+(?:\.[\w-]+)+")
BRAND = re.compile(r"\bTJES\b|\bPJES\b|tjes\.jus\.br|Poder Judici[áa]rio|"
                   r"Tribunal de Justi[çc]a", re.I)
DOC_INTERNO = re.compile(r"\bSEI\b|SEI[_-]\d|Of[íi]cio\s*\d|n[º°]\s*\d{3,}/\d{4}")
NOME = re.compile(r"\b(?:Des\.|Desembargador[a]?|Comiss[áa]ri[oa])\s+"
                  r"[A-ZÁÉÍÓÚÂÊÔÃÕÇ][a-zá-ú]+")

# blocos de exemplo que o proprio projeto usa em seed, placeholder e docstring
SINTETICO_TEL = re.compile(
    r"(?:\(27\) )?(?:3333-00\d\d|99999-000\d|0000-0000)")
DOMINIOS_FICTICIOS = {"exemplo.jus.br", "empresa-exemplo.com.br",
                      "example.com", "exemplo.com"}
ARQUIVO_ALLOWLIST = ".privacidade-sinteticos"
ESTE_ARQUIVO = os.path.abspath(__file__)

# --- detector de nome de pessoa (modo --seed) --------------------------------
# Um nome de pessoa no seed aparece como "Alexandre Puppim" ou "Desembargador
# Fulano de Tal". Nome de unidade nunca e assim: ou tem numero/ordinal ("1a
# Vara"), ou tem palavra institucional ("Secretaria", "Gabinete", "Forum"), ou
# e nome de cidade (campo de comarca, que nao entra aqui).
CONECTORES = {"de", "da", "do", "das", "dos", "e"}
INSTITUCIONAIS = {
    "secretaria", "secao", "seção", "setor", "nucleo", "núcleo", "gabinete",
    "assessoria", "contadoria", "coordenadoria", "coordenacao", "coordenação",
    "diretoria", "divisao", "divisão", "servico", "serviço", "turma", "vara",
    "juizado", "cartorio", "cartório", "oficio", "ofício", "sala", "equipe",
    "unidade", "departamento", "gerencia", "gerência", "central", "atendimento",
    "apoio", "protocolo", "plenario", "plenário", "comissao", "comissão",
    "ouvidoria", "escola", "presidencia", "presidência", "vice", "corregedoria",
    "conselho", "tribunal", "forum", "fórum", "contatos", "geral", "gerais",
    "institucional", "judiciario", "judiciário", "predio", "prédio", "anexo",
    "sede", "justica", "justiça", "telefone", "email", "e-mail", "whatsapp",
    "ramal", "ajuda", "central", "publico", "público", "recepcao", "recepção",
    "almoxarifado", "patrimonio", "patrimônio", "arquivo", "biblioteca",
    "auditorio", "auditório", "distribuicao", "distribuição", "certidoes",
    "certidões", "controle", "planejamento", "gestao", "gestão", "projetos",
    "estagio", "estágio", "pesquisa", "estatistica", "estatística", "imprensa",
    "cerimonial", "seguranca", "segurança", "copa", "balcao", "balcão",
    "guiche", "guichê", "cep", "endereco", "endereço", "telefonia",
    "informatica", "informática", "sistemas", "redes", "financeiro", "compras",
    "contratos", "licitacao", "licitação", "orcamento", "orçamento", "pessoal",
    "folha", "pagamento", "precatorio", "precatório", "magistrados", "chefia",
    "assistente", "assistentes", "analista", "agentes", "adjunto", "adjunta",
    "jecrim", "ceja", "nupemec", "pid", "help", "desk", "caixa", "fila",
    "infancia", "infância", "juventude", "criminais", "civeis", "cíveis",
    "reunidas", "manutencao", "manutenção", "predial", "instalacoes",
    "instalações", "eletricas", "elétricas", "transporte", "agendamento",
    "certidao", "certidão", "negativa", "escolta", "presos", "ordem",
    "advogados", "salao", "salão", "juri", "júri", "assessor", "juiz",
}
PROPRIA = re.compile(r"^[A-ZÁÉÍÓÚÂÊÔÃÕÇ][a-zà-ÿ]+\.?$")

# Pré-nomes comuns em português. É o sinal que separa "Alexandre Puppim" de
# "Manutenção Predial": as duas têm duas palavras capitalizadas e nenhuma
# palavra institucional, mas só a primeira tem prenome. Sem esta lista o
# detector acusa todo rótulo de duas palavras do catálogo (eram 15 falsos
# positivos em 856 nós).
NOMES_PROPRIOS = set("""
alexandre arthur aldary dair debora debora eder eliana ewerton fabio fernando
helimar heloisa janete jorge jose julio luiz marcos marianne namyr pedro rachel
raphael robson samuel sergio ubiratan walace willian patricia simone ana andre
antonio bruno carlos catarina cristina daniel daniela davi diana diego eduardo
elaine elias emerson enzo estevão evandro felipe filipe gabriel gabriela gilberto
giovana gustavo helena henrique hugo igor isabel ivan jair joao joaquim juliana
karina laura leandro leonardo leticia lucas luciana manoel marcelo marcia maria
marcio mateus matheus mauro miguel monica nelson nicolas otavio paulo rafael
renata ricardo roberto rodrigo roger rogerio ronaldo rosa sandra silvana taina
tatiana teresa thiago tiago valeria vanessa veronica vera vinicius vitor wagner
wesley yuri rafaela camila carla claudia denise elisa fernanda flavia giovanna
iara joana kim lais larissa lorena lucia marta miriam nadia natalia priscila rita
sonia sueli tania viviane""".split())
HONORIFICOS = {"des", "desembargador", "desembargadora", "comissario",
               "comissaria", "dr", "dra", "juiz", "juiza"}


def sem_acento(texto):
    decomposto = unicodedata.normalize("NFKD", texto)
    return "".join(c for c in decomposto if not unicodedata.combining(c)).lower()


def pessoa_provavel(nome):
    """True quando o rotulo tem cara de nome de pessoa, nao de unidade.

    Precisa de prenome: duas palavras capitalizadas sozinhas nao bastam, senao
    "Manutenção Predial" e "Cíveis Reunidas" entram como nome de pessoa.
    Honorifico tambem conta ("Comissária Simone"), e honorifico mascarado sai
    pelo teste de digito ("Comissário 03").
    """
    if not nome or any(c.isdigit() for c in nome):
        return False
    palavras = [p for p in re.split(r"[\s]+", nome.strip()) if p]
    if not 2 <= len(palavras) <= 6:
        return False
    # compara sem acento: "Comissária Patrícia" tem de casar com as listas
    simples = [sem_acento(p).strip(".") for p in palavras]
    if any(p in INSTITUCIONAIS for p in simples):
        return False
    if not all(PROPRIA.match(p) or re.fullmatch(r"[A-ZÁÉÍÓÚÂÊÔÃÕÇ]\.", p)
               or p.lower() in CONECTORES for p in palavras):
        return False
    if simples[0] in HONORIFICOS:
        return True
    return any(p in NOMES_PROPRIOS for p in simples)


def carregar_allowlist(raiz):
    """Valores sinteticos declarados pelo repo; literal ou `re:<regex>`."""
    exatos, regexes = set(), []
    caminho = os.path.join(raiz, ARQUIVO_ALLOWLIST)
    try:
        with open(caminho, encoding="utf-8") as f:
            linhas = f.read().splitlines()
    except OSError:
        return exatos, regexes
    for linha in linhas:
        linha = linha.strip()
        if not linha or linha.startswith("#"):
            continue
        if linha.startswith("re:"):
            regexes.append(re.compile(linha[3:].strip()))
        else:
            exatos.add(linha)
    return exatos, regexes


def listar_alvos(caminho):
    if os.path.isfile(caminho):
        return [caminho]
    alvos = []
    for base, dirs, arquivos in os.walk(caminho):
        dirs[:] = sorted(d for d in dirs if d not in PULAR_DIR)
        for nome in arquivos:
            if os.path.splitext(nome)[1].lower() in PULAR_EXT:
                continue
            alvos.append(os.path.join(base, nome))
    return sorted(alvos)


def varrer(caminho, raiz_repo):
    exatos, regexes = carregar_allowlist(raiz_repo)
    # A allowlist de fixture nao pode calar dado real em silencio: o que ela
    # abafa entra no relatorio. `usados` guarda o que casou nesta varredura e o
    # que nunca casou sai como candidato a sair.
    usados = set()

    def sintetico(valor):
        if "X" in valor or SINTETICO_TEL.fullmatch(valor):
            return True
        if valor in exatos:
            usados.add(valor)
            return True
        for r in regexes:
            if r.fullmatch(valor):
                usados.add("re:" + r.pattern)
                return True
        return False

    achados = {"telefone": [], "email": [], "marca": [], "doc": [], "nome": []}
    raiz = caminho if os.path.isdir(caminho) else os.path.dirname(caminho)
    for p in listar_alvos(caminho):
        if os.path.abspath(p) == ESTE_ARQUIVO:
            continue  # o proprio detector nao e dado
        try:
            with open(p, encoding="utf-8") as f:
                texto = f.read()
        except (OSError, UnicodeDecodeError):
            continue
        rel = os.path.relpath(p, raiz)
        for i, linha in enumerate(texto.splitlines(), 1):
            for m in TELEFONE.finditer(linha):
                valor = m.group().strip()
                if not sintetico(valor):
                    achados["telefone"].append(f"{rel}:{i}: {valor}")
            for m in EMAIL.finditer(linha):
                valor = m.group()
                dom = valor.split("@")[-1].lower()
                if dom in DOMINIOS_FICTICIOS or re.fullmatch(r"\d+\.\d+\.\d+", dom):
                    continue  # versao de pacote npm nao e e-mail
                achados["email"].append(f"{rel}:{i}: {valor}")
            if BRAND.search(linha):
                achados["marca"].append(f"{rel}:{i}: {linha.strip()[:110]}")
            for m in DOC_INTERNO.finditer(linha):
                achados["doc"].append(f"{rel}:{i}: {m.group()}")
            for m in NOME.finditer(linha):
                achados["nome"].append(f"{rel}:{i}: {m.group()}")
    for chave, itens in achados.items():
        print(f"\n## {chave}: {len(itens)}")
        for item in itens[:60]:
            print("  " + item)
        if len(itens) > 60:
            print(f"  ... +{len(itens) - 60}")

    # Relatorio da allowlist: o que ela abafou nesta varredura e o que nunca
    # casou. Varredura de um arquivo so nao julga declaracao morta — sem o repo
    # inteiro, "nunca usado" e falso positivo.
    completo = (os.path.isdir(caminho)
                and os.path.abspath(caminho) == os.path.abspath(raiz_repo))
    declarados = sorted(exatos) + ["re:" + r.pattern for r in regexes]
    if usados or (completo and declarados):
        print(f"\n## allowlist ({ARQUIVO_ALLOWLIST}): {len(usados)} de "
              f"{len(declarados)} valores declarados abafaram achado")
        for valor in sorted(usados):
            print("  usado: " + valor)
        if completo:
            for valor in declarados:
                if valor not in usados:
                    print("  NUNCA usado no repo (candidato a sair): " + valor)
    return achados


# ------------------------------------------------------------------- modo seed
CAMPOS_DE_NOME = {("nos", "nome"), ("contatos", "desc"), ("uteis", "nome"),
                  ("comarcas", "nome"), ("foruns", "nome")}
CAMPOS_DE_VALOR = {("contatos", "valor"), ("uteis", "valor")}


def varrer_seed(caminho):
    """Telefone/e-mail mascarado e nome de pessoa ausente no JSON do app.

    Nome de comarca e de forum fica de fora do detector de pessoa: comarca e
    nome de cidade e forum oficial pode homenagear desembargador — os dois sao
    informacao publica e o projeto aceita (sai como aviso, nao como falha).
    """
    with open(caminho, encoding="utf-8") as f:
        seed = json.load(f)

    sintetico = re.compile(r"^[\d\s().\-X]*$|X")
    achados = {"telefone": [], "email": [], "nome_de_pessoa": []}

    for colecao, campo in CAMPOS_DE_VALOR:
        for item in seed.get(colecao, []):
            valor = str(item.get(campo, ""))
            if "@" in valor:
                dom = valor.split("@")[-1].lower()
                if dom not in DOMINIOS_FICTICIOS:
                    achados["email"].append(f"{colecao}:{item.get('id')}: {valor}")
            elif valor and not sintetico.search(valor):
                achados["telefone"].append(f"{colecao}:{item.get('id')}: {valor}")

    # O detector roda nos nomes de nó, nos rótulos de contato e nos telefones
    # úteis. Nome de comarca e de fórum fica de fora: comarca é nome de cidade
    # e fórum oficial pode homenagear desembargador — os dois são informação
    # pública e o projeto aceita.
    for colecao in ("nos", "contatos", "uteis"):
        campo = "desc" if colecao == "contatos" else "nome"
        for item in seed.get(colecao, []):
            rotulo = str(item.get(campo, "")).strip()
            if not pessoa_provavel(rotulo):
                continue
            achados["nome_de_pessoa"].append(
                f"{colecao}:{item.get('id')}: {rotulo}")

    print(f"\nSEED: {os.path.basename(caminho)}")
    for chave, itens in achados.items():
        print(f"  ## {chave}: {len(itens)}")
        for item in itens[:40]:
            print("    " + item)
        if len(itens) > 40:
            print(f"    ... +{len(itens) - 40}")
    return achados


def autoteste():
    """Prova que o detector de pessoa pega o que deve e ignora o que nao deve."""
    deve_pegar = ["Alexandre Puppim", "Janete Vargas Simões",
                  "Débora Maria Ambos C. da Silva", "Comissária Patrícia Dantas",
                  "Aldary Nunes Júnior", "Namyr Carlos de Souza Filho",
                  "Comissário José Paulo", "Walace Pandolpho Kiffer"]
    deve_passar = ["Gabinete de Desembargador 01", "Comissário 03", "1ª Vara Cível",
                   "10ª Secretaria Inteligente", "Contatos Gerais",
                   "Fórum Desembargador Cassiano Ribeiro", "Presidência",
                   "PID • Central de Atendimento", "Subsecretaria Geral",
                   "Vara de Execuções Penais", "Atendimento geral",
                   "Central de ajuda", "Sede Administrativa",
                   "Criminais Reunidas", "Cíveis Reunidas", "Manutenção Predial",
                   "Instalações Elétricas", "Transporte Agendamento",
                   "Certidão Negativa", "Escolta de Presos", "Ordem dos Advogados",
                   "Comissários da Infância e Juventude", "Salão do Júri",
                   "Infância e Juventude", "Assessor do Juiz"]
    falhas = [n for n in deve_pegar if not pessoa_provavel(n)]
    falsos = [n for n in deve_passar if pessoa_provavel(n)]
    for nome in falhas:
        print(f"  FALHOU (nome de pessoa passou): {nome}")
    for nome in falsos:
        print(f"  FALSO POSITIVO (unidade acusada): {nome}")
    total = len(deve_pegar) + len(deve_passar)
    print(f"autoteste: {total - len(falhas) - len(falsos)}/{total} casos corretos")
    return 0 if not falhas and not falsos else 1


def main():
    raiz_repo = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    if not os.path.isdir(os.path.join(raiz_repo, ".git")):
        raiz_repo = os.getcwd()

    ap = argparse.ArgumentParser(description="Varredura de privacidade do repo.")
    ap.add_argument("alvos", nargs="*", help="arquivo ou pasta (padrao: o repo)")
    ap.add_argument("--seed", help="JSON do seed do app (modo ponto cego da v2)")
    ap.add_argument("--autoteste", action="store_true",
                    help="roda os casos do detector de nome de pessoa")
    args = ap.parse_args()

    if args.autoteste:
        return autoteste()

    total = {"telefone": 0, "email": 0}
    achados = None
    for alvo in (args.alvos or [raiz_repo]):
        print("=" * 70)
        print("ALVO:", alvo)
        resultado = varrer(alvo, raiz_repo)
        for chave in total:
            total[chave] += len(resultado[chave])

    if args.seed:
        print("=" * 70)
        achados = varrer_seed(args.seed)

    dados = total["telefone"] + total["email"]
    pessoas = len(achados["nome_de_pessoa"]) if achados else 0
    print(f"\nDADO REAL (telefone+e-mail): {dados}")
    if args.seed:
        print(f"NOME DE PESSOA NO SEED: {pessoas}")
    return 1 if dados or pessoas else 0


if __name__ == "__main__":
    sys.exit(main())
