@extends('layouts.partenaire-layout')

@section('content')
<style type="text/css">
    #loade {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        background: rgba(0, 0, 0, 0.75) url("{{asset('/images/loading2.gif')}}") no-repeat center center;
        z-index: 10000;
    }
</style>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="portlet" style="border : none;">
                    <div class="portlet-header portlet-header-bordered">
                        <h2 class="portlet-title">Recharger une carte</h2>
                    </div>

                    <form action="">
                        <div class="portlet-body d-flex justify-content-center">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_banque" class="font-weight-bold">
                                        Sélectionner la banque <span class="text-danger">*</span>
                                    </label>
                                    <select name="id_banque" id="id_banque" class="form-control" required>
                                        <option value="">Aucune banque sélectionnée</option>
                                        @foreach($banques as $banque)
                                        <option value="{{ $banque->id_group_carte }}">{{ $banque->name_grp }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="nro_carte" class="font-weight-bold">
                                        Numéro de la carte <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="nro_carte" name="nro_carte" onKeyUp="this.value=this.value.replace(/\D/g,'')" class="form-control" maxlength="16" minlength="10">
                                </div>
                                <div class="form-group" id="bloc_segment" style="display: none;">
                                    <label for="bloc_segment" class="font-weight-bold">
                                        Segment de la carte UBA<span class="text-danger">*</span>
                                        <a data-toggle="tooltip" data-placement="top" data-html="true" class="btn" style="color: #fff; background-color:#2196f3;" title="<img src='{{asset('/images/image.jpg')}}'>">
                                            <i class="fa fa-info-circle"></i>
                                        </a>
                                    </label>
                                    <input type="text" id="segment" name="segment" onKeyUp="this.value=this.value.replace(/\D/g,'')" class="form-control col-2" maxlength="4" minlength="4">
                                </div>
                                <div class="form-group">
                                    <label for="montant_recharge" class="font-weight-bold">
                                        Montant à recharger <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="montant_recharge" name="montant_recharge" maxlength="9" minlength="5" class="form-control montant-format">
                                </div>
                                <div class="form-group">
                                    <label for="nom_carte" class="font-weight-bold">
                                        Nom complet du titulaire de la carte(Nom et prénoms)<span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="nom_carte" name="nom_carte" maxlength="50" minlength="5" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="numero_assoc" class="font-weight-bold">
                                        N° Téléphone du titulaire de la carte <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="numero_assoc" onKeyUp="this.value=this.value.replace(/\D/g,'')" name="numero_assoc" maxlength="9" minlength="9" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="email_client" class="font-weight-bold">
                                        Email du client
                                    </label>
                                    <input type="email" name="email_client" pattern="[^@]+@[^@]+\.[a-zA-Z]{2,6}" id="email_client" maxlength="100" minlength="8" class="form-control" id="nro_carte">
                                </div>


                            </div>

                        </div>

                        <div class="text-center mb-3 mr-3">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" id="submit">
                                Valider
                            </button>

                            <a style="background-color: #9e9e9e!important;" class="btn btn-secondary" href="{{ url('partenaire/dashboard') }}">Quitter</a>
                        </div>
                    </form>
                </div>

                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form id="recharger_carte_form">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h3 class="modal-title text-white" id="exampleModalLabel">
                                        Confirmation de rechargement
                                        </h5>
                                </div>

                                <div class="modal-body">
                                    <div id="reponse_res"></div>
                                    <div class="form-group">
                                        <label for="nom_carte" class="font-weight-bold">
                                            Banque
                                        </label>
                                        <input id="banque" class="form-control bg-transparent border-0" name="banque" type="text" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="nom_carte" class="font-weight-bold">
                                            N° de la carte
                                        </label>
                                        <input id="carte" class="form-control bg-transparent border-0" name="carte" type="text" required readonly>
                                    </div>
                                    <div class="form-group" id="segment_bloc" style="display: none;">
                                        <label for="segment_bloc" class="font-weight-bold">
                                            Segment de la carte UBA
                                        </label>
                                        <input id="segment_carte" class="form-control bg-transparent border-0" name="segment_carte" type="text" required readonly>
                                    </div>
                                    <input id="banque_id" name="banque_id" type="hidden" required>
                                    <div class="form-group">
                                        <label for="nom_carte" class="font-weight-bold">
                                            Montant à recharger
                                        </label>
                                        <input id="montant" class="form-control bg-transparent border-0" name="montant" type="text" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="nom_carte" class="font-weight-bold">
                                            Nom de la carte
                                        </label>
                                        <input id="nom_client" class="form-control bg-transparent border-0" name="nom_client" type="text" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="numero_associ" class="font-weight-bold">
                                            N° Téléphone du client
                                        </label>
                                        <input required id="numero_associ" class="form-control bg-transparent border-0" name="numero_associ" type="text" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="nom_carte" class="font-weight-bold">
                                            Email du client
                                        </label>
                                        <input id="email_carte" class="form-control bg-transparent border-0" name="email_carte" type="text" readonly>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" id="envoyer" class="btn btn-primary">
                                            Envoyer
                                        </button>
                                        <button type="button" class="btn btn-secondary ml-2" data-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
<div id="loade"></div>
</div>

<script src="{{asset('build/scripts/jquery.min.js')}}"></script>

<script type="text/javascript">
    $(".montant-format").on({
        "focus": function(event) {
            $(event.target).select();
        },
        "keyup": function(event) {
            $(event.target).val(function(index, value) {
                return value.replace(/\D/g, "")
                    .replace(/([0-9])([0-9]{*})$/, '$1')
                    .replace(/\B(?=(\d{3})+(?!\d)\.?)/g, " ");
            });
        }
    });

    //afficher bloc segment
    $('#id_banque').on('change', function() {
        var id_banque = $("#id_banque").val();
        if (id_banque == '1' || id_banque == 1) {
            $("#bloc_segment").css("display", "block");
        } else {
            $("#bloc_segment").css("display", "none");
        }
    });


    $("#submit").click(function() {
        // var name = $("#id_banque").val();
        var nom_banque = $("#id_banque option:selected").text();
        var id_banque = $("#id_banque").val();
        var nro_carte = $("#nro_carte").val();
        var montant_recharge = $("#montant_recharge").val();
        var nom_carte = $("#nom_carte").val();
        var email_client = $("#email_client").val();
        var numero_assoc = $("#numero_assoc").val();

        if (id_banque == '1' || id_banque == 1) {
            var segment = $("#segment").val();
            $("#segment_carte").val(segment);
            $("#segment_bloc").css("display", "block");
        } else {
            $("#segment_bloc").css("display", "none");
        }

        $("#banque").val(nom_banque);
        $("#banque_id").val(id_banque);
        $("#carte").val(nro_carte);
        $("#montant").val(montant_recharge);
        $("#nom_client").val(nom_carte);
        $("#email_carte").val(email_client);
        $("#numero_associ").val(numero_assoc);
        
    });
    var spinner = $('#loade');

    $('#recharger_carte_form').submit(function(event) {
        event.preventDefault();
        let nom_client = $("#nom_client").val();
        let banque_id = $("#banque_id").val();
        let nomBanque = $("#banque").val();
        let carte = $("#carte").val();
        let montant = $("#montant").val();
        let email_carte = $("#email_carte").val();
        let numero_associe = $("#numero_associ").val();
        let _token = $("input[name=_token]").val();
        let segment = $("#segment_carte").val();

        if (banque_id != '1' || banque_id != 1) {
            segment = null;
        }

        //alert(segment)

       spinner.show();
        $.ajax({
            url: "{{ route('recharger-carte') }}",
            type: "POST",
            data: {
                banque_id: banque_id,
                nomBanque: nomBanque,
                nom_client: nom_client,
                carte: carte,
                montant: montant,
                _token: _token,
                email_carte: email_carte,
                numero_associe: numero_associe,
                segment : segment
            },
            success: function(response) {
                console.log(response);
                spinner.hide();
                if (response.error) {
                    $('#reponse_res').append("<div class='alert alert-danger fade show'><div class='alert-icon'></div><div class='alert-content'>" + response.error + "</div><button type='button' style='color: white;' class='btn btn-text-primary btn-icon alert-dismiss' data-dismiss='alert'><i class='fa fa-times'></i></button></div>")
                } else if (response.success) {
                    $('#reponse_res').append("<div class='alert alert-success fade show'><div class='alert-icon'></div><div class='alert-content'>" + response.success + "</div><button type='button' style='color: white;' class='btn btn-text-primary btn-icon alert-dismiss' data-dismiss='alert'><i class='fa fa-times'></i></button></div>")
                    window.location.href = "{{ route('liste-recharge-carte') }}";
                }
            },
            error: function(request, status, error) {
                //alert(request.responseText);
                spinner.hide();
                $('#reponse_res').append("<div class='alert alert-danger fade show'><div class='alert-icon'></div><div class='alert-content'>Erreur, envoi de requete echouée contactez l'administrateur</div><button type='button' style='color: white;' class='btn btn-text-primary btn-icon alert-dismiss' data-dismiss='alert'><i class='fa fa-times'></i></button></div>")
            }
        });
    });
</script>
@stop