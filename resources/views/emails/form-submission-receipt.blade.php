<x-mail::message>
# Recibimos tu solicitud

Hola, {{ $submission->user->name }}:

Registramos tu solicitud de **{{ $form->label() }}** (radicado #{{ $submission->id }}) el {{ $submission->created_at->format('d/m/Y H:i') }}.

El equipo de {{ strtoupper($form->section()) }} la revisará y te contactará si necesita información adicional.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
