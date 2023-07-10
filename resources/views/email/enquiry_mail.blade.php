@component('mail::message')
{!! $data !!}
<br>
Thanks,<br>
{{ config('app.name') }}
@endcomponent
