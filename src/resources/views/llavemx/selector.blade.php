@extends('layouts.app')

@section('content')

    <div class="container mt-5 mb-5">

        @include('layouts.messages')

        <div class="row justify-content-center">
            <div class="col-md-12">
                <h2 class="title-llavemx">Seleccione su cuenta de ingreso</h2>
                <div class="separador-llavemx"><span></span></div>
            </div>
            <div class="col-12 mt-1 mb-4">
                <button class="btn btn-stps float-right" data-toggle="modal" data-target="#newAccountSelectorModal">Registrar cuenta <i class="icon icon-mas"></i> </button>
            </div>
            <div class="row text-center justify-content-center">
                @foreach($users as $key => $user)
                @php $isEnabled = llaveMXAccountEnabled($user->email) @endphp
                <div class="card mb-3 mr-1 ml-1 col-4 @if($isEnabled) btn-llavemx @else btn-llavemx-dissabled @endif" 
                    @if ($isEnabled)
                    style="cursor: pointer;" onclick="location.href='{{ route('llavemx.loginSelector', ['hash_user_id' => llaveMXEncryptString($user->id)]) }}'"
                    @else
                    data-toggle="tooltip" data-placement="top" title="Aún no confirmas tu correo electrónico dando clic en el enlace que te hemos enviado"
                    @endif
                >
                    <div class="card-body">
                        <img src="{{ asset('images/user_llaveMX.png') }}" style="width: 80px;">
                        <h5 class="card-title">{{ $user->email }}</h5>
                        <p class="card-text" style="position: absolute; bottom: 10px; left: 0%; width: 100%;"><small><small>{{ $user->roles->pluck('show_name')->first() }}</small></small></p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @include('llavemx.partials.new_account', ['modal_name' => 'newAccountSelectorModal'])
@endsection
