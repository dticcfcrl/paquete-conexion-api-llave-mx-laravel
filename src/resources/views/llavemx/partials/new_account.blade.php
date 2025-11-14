<div class="modal fade" id="{{ $modal_name ?? 'newAccountModal' }}" tabindex="-1" role="dialog" aria-labelledby="newAccountModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-stps modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Registro de nueva cuenta</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="form-{{ $modal_name ?? 'newAccountModal' }}" action="{{ route('llavemx.newAccount') }}">
                    {{ csrf_field() }}
                    <div class="col-12">
                        <div class="form-group">
                            <label for="correo_{{ $modal_name ?? 'newAccountModal' }}"><span class="color-primary font-weight-bold">*</span>&nbsp;Correo electrónico:</label>
                            <input type="text" class="form-input w-100" id="correo_{{ $modal_name ?? 'newAccountModal' }}" name="correo_newAccount" value=""/>
                            <span class="text-danger" id="error_correo_{{ $modal_name ?? 'newAccountModal' }}"></span>
                        </div>
                    </div>
                    <div class="col-12">
                        @include('layouts.notas', 
                                ['title' => 'Información', 
                                'message' => 'Se retomará el nombre y datos de la cuenta abierta para completar el registro pero el correo elenctrónico no debe estar previamente registrado.', 
                                'class' => 'mt-0 mb-3'
                                ])
                    
                    </div>
                    <div class="text-small text-right">
                        <span class="color-primary font-weight-bold">*</span>&nbsp;Campos requeridos 
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-llavemx-cancel" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-llavemx-primary float-right ml-1" form="form-{{ $modal_name ?? 'newAccountModal' }}">Registrar</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('form-{{ $modal_name ?? 'newAccountModal' }}').addEventListener('submit', function(event) {
        const correo = document.getElementById('correo_{{ $modal_name ?? 'newAccountModal' }}').value.trim();
        document.getElementById('error_correo_{{ $modal_name ?? 'newAccountModal' }}').innerHTML  = '';
        if (correo === '') {
            document.getElementById('error_correo_{{ $modal_name ?? 'newAccountModal' }}').innerHTML  = 'El correo electrónico es obligatorio.';
            event.preventDefault();
        }else if (!correo.match(/^[^@\s]+@[^@\s]+\.[^@\s]+$/)) {
            document.getElementById('error_correo_{{ $modal_name ?? 'newAccountModal' }}').innerHTML  = 'El correo electrónico no es válido.';
            event.preventDefault();
        }
    });
</script>
