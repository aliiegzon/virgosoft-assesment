@component('mail::message')
    <div style="max-width: 463px; margin-top: 0px; margin-bottom: 40px; margin-right: auto; margin-left: auto;">
        <div style="max-width: 463px; font-family: 'Poppins', sans-serif;">
            <div
                style='font-family: inherit; color: #224473; font-size: 24px; line-height: 36px; font-weight: 600; margin-bottom: 4px;'>
                Hi {{ $name }}
            </div>
            <div
                style='font-family: inherit; color: #065AEB; font-size: 16px; line-height: 24px; font-weight: 600; padding-bottom: 32px'>
                Welcome to JNM Portal!
            </div>
            <div
                style="font-family: inherit; margin-top: 20px; line-height: 24px; color: #224473; font-size: 16px;">
                An account was created for you. Please set your password <a href="{{$url}}"
                                                                            style="font-family: inherit; font-weight: 500; color: #065AEB;">here</a>
                or copy the link below.
            </div>
            <div
                style='font-family: inherit; margin-top: 32px; line-height: 19.5px; font-size: 13px; color: #7185A8; overflow-wrap: break-word;'>
                {{$url}}
            </div>
        </div>
    </div>
@endcomponent
