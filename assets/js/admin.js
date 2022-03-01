jQuery(document).ready(function ($) {

    let directory = '/wp-content/plugins/vabs-wp-plugin/core/ajax';

    /////////////////
    /// Settings ////
    /////////////////

    //Variables
    let error = false;
    let btnSave = $('#btnSave');
    let btnLoadAGBS = $('#btnLoadAGBS');
    let btnLoadDSGVO = $('#btnLoadDSGVO');
    let responseNode = $('#response');
    let loading = $('.loading');

    //Events
    btnSave.click(SaveSettings);
    btnLoadAGBS.click(LoadAGBS);
    btnLoadDSGVO.click(LoadDSGVO);

    //Functions
    function SaveSettings () {

        loading.show();
        responseNode.html('');

        let apiToken = $('#apiToken').val();
        let apiURL = $('#apiURL').val();
        let apiClientId = $('#apiClientId').val();
        let dsgvoLink = $('#dsgvoLink').val();
        let agbLink = $('#agbLink').val();
        let referrerId = $('#referrerId').val();
        let redirectLink = $('#redirectLink').val();
        let payPal = $('#payPal').prop('checked');
        let payPalSandbox = $('#payPalSandbox').prop('checked');
        let payPalClientId = $('#payPalClientId').val();
        let payPalClientSecret = $('#payPalClientSecret').val();
        let textBeforeBooking = $('#textBeforeBooking').val();
        let zoom = $('#zoom').val();
        let latCenter = $('#latCenter').val();
        let lonCenter = $('#lonCenter').val();

        console.log({payPal});
        console.log({payPalSandbox});
        console.log({payPalClientId});
        console.log({payPalClientSecret});

        if(payPal){
            if(payPalClientId.length == 0){
                ShowErrorMessage("Fehler", 'Sie haben PayPal ausgewählt aber keine CLIENT ID eingegeben. Diese erhalten Sie in Ihrer PaylPal Entwicklerkonsole');
                error = true;
            }
            if (payPalClientSecret.length == 0) {
                ShowErrorMessage("Fehler", 'Sie haben PayPal ausgewählt aber kein ClientSecret eingegeben. Diese erhalten Sie in Ihrer PaylPal Entwicklerkonsole');
                error = true;
            }

        }

        if(error === false){

            $.ajax({

                url: directory + "/ajax.php",

                type: "POST",

                data: {
                    method: 'SaveSettings',
                    apiURL: apiURL,
                    apiToken: apiToken,
                    apiClientId: apiClientId,
                    dsgvoLink: dsgvoLink,
                    agbLink: agbLink,
                    referrerId: referrerId,
                    redirectLink: redirectLink,
                    payPal: payPal ? 1 : 0,
                    payPalSandbox: payPalSandbox ? 1 : 0,
                    payPalClientId: payPalClientId,
                    payPalClientSecret: payPalClientSecret,
                    textBeforeBooking: textBeforeBooking,
                    zoom: zoom,
                    latCenter: latCenter,
                    lonCenter: lonCenter,

                },

                dataType: "json",

                async: true,

                success: function (response) {

                    let error = response.error;
                    let data = response.data;

                    if (error === "") {

                        ShowErrorMessage("Erfolg", 'Das hat geklappt. Die Einstellungen wurden gespeichert');
                        $('#settingsReferrerId').val(referrerId);
                        //LoadReferrerSelection();

                    } else {
                        ShowErrorMessage("Fehler", error);
                    }

                },
                error: function (error) {
                    ShowErrorMessage("Fehler", error);
                }

            });

            loading.hide();

        }


    }

    function LoadDSGVO() {


        $.ajax({

            url: directory + "/ajax.php",

            type: "POST",

            data: {
                method: "GetDSGVO",
            },

            dataType: "json",

            async: true,

            success: function (response) {

                let error = response.error;
                let data = response.data;

                if(error === ""){

                    if(data.length){
                        $('#dsgvoLink').val(data);
                    } else {
                        ShowErrorMessage('Fehler', 'Keine Daten von der API erhalten');
                    }

                }else{
                    ShowErrorMessage('Fehler', error);
                }

            },
            error: function(error){
                ShowErrorMessage('Fehler', error);
            }

        });

    }

    function LoadAGBS() {

        $.ajax({

            url: directory + "/ajax.php",

            type: "POST",

            data: {
                method: "GetAGBS",
            },

            dataType: "json",

            async: true,

            success: function (response) {

                let error = response.error;
                let data = response.data;

                if(error === ""){

                    if (data.length) {
                        $('#agbLink').val(data);
                    }else{
                        ShowErrorMessage('Fehler', 'Keine Daten von der API erhalten');
                    }

                }else{
                    ShowErrorMessage('Fehler', error);
                }

            },
            error: function(error){
                ShowErrorMessage('Fehler', error);
            }

        });

    }

    function LoadReferrerSelection() {

        let settingsReferrerId = $('#settingsReferrerId').val();

        if($('#referrerId')){

            $.ajax({

                url: directory + "/ajax.php",

                type: "POST",

                data: {
                    method: "GetReferrer",
                    settingsReferrerId: settingsReferrerId,
                },

                dataType: "json",

                async: true,

                success: function (response) {

                    let error = response.error;
                    let data = response.data;

                    if (error === "") {

                        $('#referrerId').html(data);

                    } else {
                        ShowErrorMessage('Fehler', error);
                    }

                },
                error: function (error) {
                    ShowErrorMessage('Fehler', error);
                }

            });

        }

    }

    //Init Calls
    LoadReferrerSelection();

    /////////////////
    /// Shortcode ///
    /////////////////

    //Variables
    let btnGenerateShortCode = $('#btnGenerateShortCode');

    //Events
    btnGenerateShortCode.click(GenerateShortCode);

    function GenerateShortCode () {

        let formType = $("input[name='formType']:checked").val();
        let redirectLink = $("#redirectLink").val();

        if(formType == 'beachchair_booking' || formType == 'voucher' || formType == 'contact'){

            $.ajax({

                url: directory + "/ajax.php",

                type: "POST",

                data: {
                    method: "GenerateShortCode",
                    formType: formType,
                    redirectLink: redirectLink,
                },

                dataType: "json",

                success: function (response) {

                    let error = response.error;
                    let data = response.data;

                    if (error === "") {

                        $('#shortCodeOutput').val(data);

                    } else {
                        ShowErrorMessage('Fehler', error);
                    }

                },
                error: function (error) {
                    ShowErrorMessage('Fehler', error);
                }

            });

        }else{

            ShowErrorMessage("Fehler", "Eine CheckBox muss schon geklickt werden");

        }



    }

    function ShowErrorMessage(title, message, delay = 5) {

        $('#backendErrorMessage').removeClass('alert-danger').removeClass('alert-warning').removeClass('alert-success').removeClass('alert-info').html(message);

        if (title == "Fehler") {
            $('#backendErrorMessage').addClass('alert-danger');
        } else if (title == "Warnung") {
            $('#backendErrorMessage').addClass('alert-warning');
        } else if (title == "Hinweis") {
            $('#backendErrorMessage').addClass('alert-info');
        }else if (title == "Erfolg") {
            $('#backendErrorMessage').addClass('alert-success');
        }

        $('#backendErrorMessage').show();

    }

});
