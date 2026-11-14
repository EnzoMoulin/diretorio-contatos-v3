@php
    $c = $contato;
    $tel = preg_replace('/[^0-9+]/', '', $c['valor']);
    $ehTelefone = in_array(mb_strtolower($c['tipo']), ['telefone', 'ramal', 'whatsapp'], true);
    $ehEmail = mb_strtolower($c['tipo']) === 'e-mail';
    $local = $c['local'];
@endphp

<div class="px-1.5 pt-1">
    <p class="rotulo">
        {{ $c['tipo'] }}
        @if ($c['desc'])
            · {{ $c['desc'] }}
        @endif
    </p>

    <p class="num mt-2 break-words text-[1.25rem] leading-tight">{!! str_replace('@', '<wbr>@', e($c['valor'])) !!}</p>

    <div class="mt-3 flex flex-wrap items-center gap-1.5">
        <button type="button" @click="copiar(@js($c['valor']))"
            class="flex h-9 items-center gap-2 rounded-lg bg-accent px-3 text-[0.8125rem] font-medium text-[#03211d] transition-colors hover:bg-accent-soft">
            <x-icone nome="copiar" classe="h-3.5 w-3.5" x-show="copiado !== @js($c['valor'])" x-cloak />
            <span x-text="copiado === @js($c['valor']) ? 'copiado' : 'copiar'">copiar</span>
        </button>

        @if ($ehTelefone)
            <a href="tel:{{ $tel }}"
                class="flex h-9 items-center gap-2 rounded-lg border border-accent/20 px-3 text-[0.8125rem] text-ink transition-colors hover:border-accent/50 hover:text-accent-soft">
                <x-icone nome="telefone" classe="h-3.5 w-3.5" />
                ligar
            </a>
        @endif

        @if ($ehEmail)
            <a href="mailto:{{ $c['valor'] }}"
                class="flex h-9 items-center gap-2 rounded-lg border border-accent/20 px-3 text-[0.8125rem] text-ink transition-colors hover:border-accent/50 hover:text-accent-soft">
                <x-icone nome="email" classe="h-3.5 w-3.5" />
                abrir e-mail
            </a>
        @endif
    </div>

    <div class="mt-3">
        <x-selo :vigencia="$c['vigencia']" />
    </div>
</div>

@if ($local)
    <div class="secao px-1.5">
        <span class="rotulo">onde fica</span>
    </div>
    <div class="px-1.5">
        <div class="cartao">
            <a href="{{ route('local', $local['chave']) }}"
                class="flex items-center justify-between gap-2 text-[0.875rem] transition-colors hover:text-accent-soft">
                <span class="truncate">{{ $local['nome'] }}</span>
                <x-icone nome="chevron" classe="h-3.5 w-3.5 shrink-0 text-muted/50" />
            </a>
            @if (! empty($local['end']))
                <p class="mt-1.5 text-[0.6875rem] leading-snug text-muted">
                    {{ $local['end'] }}
                    @if (! empty($local['cep']))
                        <span class="num">· {{ $local['cep'] }}</span>
                    @endif
                </p>
            @endif
            @if (! empty($local['comarca']))
                <p class="mt-1.5 text-[0.6875rem] text-muted">
                    comarca de
                    <a href="{{ route('comarca', $local['comarca']['chave']) }}" class="text-ink hover:text-accent-soft">{{ $local['comarca']['nome'] }}</a>
                </p>
            @endif
        </div>
    </div>
@endif

@if (! empty($c['irmãos']))
    <div class="secao px-1.5">
        <span class="rotulo">outros contatos neste local</span>
    </div>
    <div class="space-y-px px-0.5 pb-2">
        @foreach (array_slice($c['irmãos'], 0, 6) as $outro)
            <x-contato-linha :contato="$outro" compacto />
        @endforeach
    </div>
@endif
