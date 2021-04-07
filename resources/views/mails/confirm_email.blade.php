@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => "https://www.google.com.ar/"])
            {{ env('APP_NAME') }}
        @endcomponent
    @endslot
    Welcome {{$name}} to the user crud , please in order to continue confirm your email.
    @component('mail::button', ['url' => config('constants.base_api') .  "/confirm-email?email=$email&code=$code"])
        Confirm email
    @endcomponent
    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ env('APP_NAME') }}.
        @endcomponent
    @endslot
@endcomponent
