New enquiry from the website about a property.

Property: {{ $propertyTitle }}
@if ($propertyReference)
Reference: {{ $propertyReference }}
@endif
Link: {{ $propertyUrl }}

Name:  {{ $senderName }}
Email: {{ $senderEmail }}
@if ($senderPhone !== '')
Phone: {{ $senderPhone }}
@endif

Message:
{{ $body }}
