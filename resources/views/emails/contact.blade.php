New enquiry from the website contact form.

Name:  {{ $senderName }}
Email: {{ $senderEmail }}
@if ($senderPhone !== '')
Phone: {{ $senderPhone }}
@endif

Message:
{{ $body }}
