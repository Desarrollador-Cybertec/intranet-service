<x-mail::message>
# Nueva solicitud: {{ $form->label() }}

**Solicitante:** {{ $submission->user->name }} ({{ $submission->user->email }})
**Fecha:** {{ $submission->created_at->format('d/m/Y H:i') }}
**Radicado:** #{{ $submission->id }}

@foreach ($form->fields() as $field)
**{{ $field['label'] }}:** {{ $submission->payload[$field['name']] ?? '—' }}
@endforeach

@if ($submission->attachments->isNotEmpty())
**Adjuntos:** {{ $submission->attachments->count() }} archivo(s){{ $submission->attachments->sum('size') > 8 * 1024 * 1024 ? ' (superan 8 MB, descárgalos desde la intranet)' : '' }}
@endif

<x-mail::button :url="$frontendUrl">
Ir a la intranet
</x-mail::button>

Puedes responder directamente a este correo: llegará a {{ $submission->user->email }}.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
