@props(['contato', 'compacto' => false])

@php
    $icone = match (mb_strtolower($contato['tipo'])) {
        'ramal' => 'ramal',
        'e-mail' => 'email',
        'whatsapp' => 'whatsapp',
        default => 'telefone',
    };
    $valor = $contato['valor'];
@endphp

<div class="grupo flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-accent/[0.06]">
    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-md border border-accent/15 bg-base/40 text-accent-soft">
        <x-icone :nome="$icone" classe="h-3.5 w-3.5" />
    </span>

    <a href="{{ route('contato', $contato['id']) }}" class="min-w-0 flex-1">
        <span class="num block truncate {{ $compacto ? 'text-[0.8125rem]' : 'text-[0.9375rem]' }}">{{ $valor }}</span>
        <span class="block truncate text-[0.6875rem] text-muted">
            {{ $contato['tipo'] }}@if (! empty($contato['desc']))
                · {{ $contato['desc'] }}
            @endif
        </span>
    </a>

    <button type="button" @click="copiar(@js($valor))"
        class="grid h-8 w-8 shrink-0 place-items-center rounded-md text-muted transition-colors hover:bg-accent/10 hover:text-accent-soft"
        :aria-label="copiado === @js($valor) ? 'Copiado' : 'Copiar {{ $contato['tipo'] }}'">
        <x-icone nome="copiar" classe="h-3.5 w-3.5" x-show="copiado !== @js($valor)" x-cloak />
        <svg x-show="copiado === @js($valor)" x-cloak class="h-3.5 w-3.5 text-accent" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m5 12.5 4.5 4.5L19 7.5" />
        </svg>
    </button>

    <x-icone nome="chevron" classe="h-3.5 w-3.5 shrink-0 text-muted/40" />
</div>
