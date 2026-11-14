<?php

namespace App\Http\Controllers;

use App\Support\Diretorio;

class PainelController extends Controller
{
    public function inicio()
    {
        return $this->pagina('inicio', [
            'selecionada' => null,
            'trilha' => [],
            'contexto' => 'Comarcas',
            'foco' => null,
            'tituloPagina' => 'Diretório · comarcas e contatos',
        ]);
    }

    public function comarca(string $chave)
    {
        $comarca = Diretorio::comarca($chave);
        abort_if($comarca === null, 404);

        return $this->pagina('comarca', [
            'selecionada' => $comarca,
            'trilha' => [['rotulo' => $comarca['nome'], 'url' => null]],
            'contexto' => $comarca['nome'],
            'foco' => $comarca['chave'],
            'tituloPagina' => 'Comarca · '.$comarca['nome'],
        ]);
    }

    public function local(string $chave)
    {
        $local = Diretorio::local($chave);
        abort_if($local === null, 404);

        return $this->pagina('local', [
            'selecionada' => null,
            'local' => $local,
            'trilha' => $local['trilha'],
            'contexto' => $local['nome'],
            'foco' => $local['comarca']['chave'] ?? null,
            'tituloPagina' => $local['nome'],
        ]);
    }

    public function contato(string $chave)
    {
        $contato = Diretorio::contato($chave);
        abort_if($contato === null, 404);

        return $this->pagina('contato', [
            'selecionada' => null,
            'contato' => $contato,
            'trilha' => $contato['trilha'],
            'contexto' => $contato['valor'],
            'foco' => $contato['local']['comarca']['chave'] ?? ($contato['local']['chave'] ?? null),
            'tituloPagina' => $contato['tipo'].' · '.$contato['valor'],
        ]);
    }

    public function busca()
    {
        $q = (string) request('q', '');
        $resultado = Diretorio::busca($q);

        return $this->pagina('busca', [
            'selecionada' => null,
            'resultado' => $resultado,
            'buscaQ' => $q,
            'trilha' => [['rotulo' => 'Busca', 'url' => route('inicio')], ['rotulo' => $q === '' ? '—' : $q, 'url' => null]],
            'contexto' => 'Busca',
            'foco' => $resultado['foco'] ?? null,
            'tituloPagina' => 'Busca · '.($q === '' ? 'o que você procura' : $q),
        ]);
    }

    /** Município do ES sem comarca própria (estado 4 do briefing). */
    public function municipio(string $ibge)
    {
        $nome = Diretorio::municipioNome($ibge);

        return $this->pagina('sem-comarca', [
            'selecionada' => null,
            'ibge' => $ibge,
            'nome' => $nome,
            'trilha' => [['rotulo' => $nome ? $nome.' · sem comarca' : 'Município '.$ibge, 'url' => null]],
            'contexto' => 'Sem comarca',
            'foco' => null,
            'focoIbge' => $ibge,
            'tituloPagina' => 'Município sem comarca · '.($nome ?: $ibge),
        ]);
    }

    private function pagina(string $estado, array $dados)
    {
        $comarcas = Diretorio::comarcas();

        $mapa = array_map(fn ($c) => [
            'chave' => $c['chave'],
            'nome' => $c['nome'],
            'ibge' => $c['ibge'],
            'contatos' => $c['contatos'],
            'sede' => $c['sede'],
            'url' => $c['sede'] ? null : route('comarca', $c['chave']),
        ], $comarcas);

        return view('layouts.app', array_merge([
            'estado' => $estado,
            'comarcas' => $comarcas,
            'totais' => Diretorio::totais(),
            'uteis' => Diretorio::uteis(),
            'mapa' => $mapa,
            'alturaInicial' => request('folha', 'meia'),
            'focoIbge' => null,
        ], $dados));
    }
}
