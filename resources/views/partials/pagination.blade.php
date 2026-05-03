@props(['paginator'])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 py-3">
    <div class="text-sm text-gray-600">
        @if($paginator->total() > 0)
            Mostrando <b>{{ $paginator->firstItem() }}</b>–<b>{{ $paginator->lastItem() }}</b>
            de <b>{{ $paginator->total() }}</b>
        @else
            Sin registros.
        @endif
    </div>
    <div>
        @if($paginator->hasPages())
            {{ $paginator->links() }}
        @endif
    </div>
</div>
