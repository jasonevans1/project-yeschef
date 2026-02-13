<x-mail::message>
# You've Been Invited!

**{{ $ownerName }}** has shared {{ $contentDescription }} with you on YesChef.

To view the shared content, create a free account using the link below:

<x-mail::button :url="$registerUrl">
Create Your Account
</x-mail::button>

Once you register with this email address, the shared content will be available immediately.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
