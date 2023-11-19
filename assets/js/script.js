jQuery(document).ready(function ($) {

    //Declare Variables
    let directory = '/wp-content/plugins/vabs-wp-plugin/core/ajax';

    let allBeachChairs = [];
    let freeBeachChairs = [];
    let bookableChairs = [];
    let beachChairTypes = [];

    let globalStartDate = '';
    let globalStartDateFormatted = '';
    let globalEndDate = '';
    let globalEndDateFormatted = '';
    let globalDate = [];

    let map = null;
    let mapOutput = '';

    let shoppingCart = [];

    let currentMode = '';

    //declare nodes

    let dateFrom = $(".dateFrom");
    let vabs__flexHeadline = $('.vabs__flexHeadline');
    let vabs__flexRows = $('.vabs__flexRows');
    let flexRow = $('.flexRow');
    let locationId = $('.locationId');
    let chairCard = $('#vabs__chair-card');

    let successMessage = $('#successMessage');
    let errorMessage = $('#errorMessage');
    let vabs__bookingContainer = $('#vabs__bookingContainer');
    let node = $('#vabs__bookingContainer'); //Must be declared twice otherwise it wont work :(
    let vabs__chairCardBtnAddToShoppingCart = $('#vabs__chairCardBtnAddToShoppingCart');
    let vabs__chairCardBtnRemoveFromShoppingCart = $('#vabs__chairCardBtnRemoveFromShoppingCart');
    let chairCardHeader = $('.vabs__chair-header');
    let vabs__chairCardType = $('#vabs__chairCardType');
    let vabs__chairCardName = $('#vabs__chairCardName');

    let vabs__shoppingCartList = $('#vabs__shoppingCartList');
    let shoppingCartDateTimeRange = $('#shoppingCartDateTimeRange');

    let btnRefresh = $('#btnRefresh');

    //Object-Templates
    let directions = {
        1: "L2R",
        2: "R2L",
        3: "Center",
    };
    let beachLocation = {
        id: 0,
        name: '',
        latitude: '',
        longitude: '',
        seasonFromFormatted: '',
        seasonToFormatted: '',
    };
    let chairTemplate = {
        id: 0,
        name: '',
        pos: 0,
        beachChairTypeId: '',
        beachChairTypeName: '',
        locationId: '',
        beachChairLocationName: '',
        beachRowId: '',
        beachRowName: '',
        rowDirection: '',
        rowDirectionName: '',
        dateFrom: '',
        dateFromFormatted: '',
        dateTo: '',
        dateToFormatted: '',
        unitPrice: '',
        bookable: '',
        beachChairBlockedDates: '',
        seasonsOutput: [],
        priceCalculation: {seasons:[]},
    };
    let seasonsOutputTemplate = {

        singlePrice: 0,
        tax: 0,
        model: '',
        seasonName: '',
        seasonPrice: 0,
        case: 0,
        amountOfDaysBookedInThatSeason: 0,
        caseDescription: '',
        seasonDateFrom: '',
        seasonDateTo: '',
        usedDateFrom: '',
        usedDateTo: '',
    }
    let currentBeachChair = {
        id: 0,
        name: '',
        pos: 0,
        beachChairTypeId: '',
        beachChairTypeName: '',
        locationId: '',
        beachChairLocationName: '',
        beachRowId: '',
        beachRowName: '',
        rowDirection: '',
        rowDirectionName: '',
        dateFrom: '',
        dateFromFormatted: '',
        dateTo: '',
        dateToFormatted: '',
        unitPrice: '',
        bookable: '',
        beachChairBlockedDates: '',
        seasonsOutput: [],
        priceCalculation: {seasons: []},
    };

    let beachChairTypeImageBasePath = '';

    const MAPBOX_API_TOKEN = 'pk.eyJ1Ijoia3VnZWxzY2hyZWliZXIiLCJhIjoiY2xqY2tnOHAzMDJreTNrbW9iemI2eTRvayJ9._85fNj_z8Woc4vLnkFUXHQ';

    //Methods

    /********
     * INIT *
     ********/

    let addDays = 0;

    let Init = function () {

        console.log("Init");

        $.ajax({

            url: directory + "/ajax.php",

            type: "POST",

            data: {
                method: 'LoadAdditionalStartDaysValue',
            },

            dataType: "json"

        }).done(function () {

            //HideLoadingOverlay();

        }).then(function (response) {

            let error = response.error;
            if (error != "") {


            }
            addDays = response.data;

            const date = new Date(Date.now() + (addDays * 3600 * 1000 * 24));

            if (addDays > 0) {

                let dateText = moment(date).format('DD.MM.YYYY');

                $('#additionalCalendarStartDaysHint').show();
                $('#additionalCalendarStartDate').text(dateText);
                $('#additionalCalendarStartDate2').text(dateText);
            }

            flatpickr('.dateFrom', {
                minDate: date,
                dateFormat: 'd.m.Y',
                locale: 'de',
                mode: "range",
                onChange: function (dates, dateStr, instance) {




                },
                onClose: function (dates, dateStr) {

                    if (typeof dates[1] === "undefined") {
                        console.log("OnClose: Date 2 = undefined");
                        dates[1] = dates[0];
                        dateFrom.val(dateStr + " bis " + dateStr);
                        dates = [dates[0], dates[1]];
                    }

                    globalDate = dates;

                    HandleDateChange(dates); //dates will be an object date

                }
            });

            node.on('change', '.locationId', HandleLocationChange);
            node.on('click', '.vabs__flexBtnBack', ShowLocationtMap);
            node.on('click', '.flexChair', HandleMapChairClick);
            node.on('click', '.vabs__btnChairClose', CloseBeachChairPopupCard);
            node.on('click', '#vabs__chairCardBtnAddToShoppingCart', TriggerAddOrRemoveToOrFromShoppingCart);
            node.on('click', '#vabs__chairCardBtnRemoveFromShoppingCart', TriggerAddOrRemoveToOrFromShoppingCart);
            node.on('click', '#vabs__btnOrderNow', ValidateAndSendOrder);
            node.on('click', '#btnLogShoppingCart', LogShoppingCart);

        }).fail(function (error) {

            ShowErrorMessage("Fehler", error);

        });

        btnRefresh.click(HandleDateChange);

        LoadBeachChairTypes();

        //Get System PATHS
        $.ajax({

            url: directory + "/ajax.php",

            type: "POST",

            data: {
                method: 'GetConstants',
            },

            dataType: "json"

        }).done(function () {

            //HideLoadingOverlay();

        }).then(function (response) {

            let error = response.error;
            if (error != "") {
                console.log(error);
            }
            beachChairTypeImageBasePath = response['BEACHCHAIR_TYPES_BASE_PATH'];

        }).fail(function (error) {

            ShowErrorMessage("Fehler", error);

        });


    }

    let LoadBeachChairTypes = function () {

        $.ajax({

            url: directory + "/ajax.php",

            type: "POST",

            data: {
                method: 'GetBeachChairTypes',
            },

            dataType: "json"

        }).done(function () {

            HideLoadingOverlay();

        }).then(function (response) {

            beachChairTypes = response.data;

        }).fail(function (error) {

            ShowErrorMessage("Fehler", error);

        });

    }

    //Handles

    let HandleDateChange = function (dates) {

        HideAlertMessage();
        $('#vacancyList').html('');

        try {

            if (dates.length === 2) {
                globalStartDate = dates[0];
                globalEndDate = dates[1];
            }else if(globalDate.length === 2){
                globalStartDate = globalDate[0];
                globalEndDate = globalDate[1];
            }else{
                throw "Es wurde noch kein Datum gewählt";
            }

            SetModus('normal');

            $('#vabs__leafLetMap').hide();
            $('#vabs__flexMap').hide();

            //dates is a date object
            globalStartDateFormatted = globalStartDate.ddmmyyyy();
            globalEndDateFormatted = globalEndDate.ddmmyyyy();

            shoppingCartDateTimeRange.html(globalStartDateFormatted + '-' + globalEndDateFormatted);


            let bookableLocationsArray = [];

            if (globalEndDate === '' || typeof globalEndDate === 'undefined') {
                ShowAlertMessage('danger', 'Das End-Datum konnte nicht ermittelt werden');
            }

            ShowLoadingOverlay('Suche nach buchbaren Strandabschnitten');

            $.ajax({

                url: directory + "/ajax.php",

                type: "POST",

                data: {
                    method: 'GetLocations',
                    dateFrom: globalStartDateFormatted,
                    dateTo: globalEndDateFormatted,
                },

                dataType: "json"

            }).done(function () {

                HideLoadingOverlay();

            }).fail(function (error) {

                ShowErrorMessage("Fehler", error);

            }).then(function (response) {

                let error = response.error;
                let data = response.data;

                let length = data.length;
                let output = '';

                if (error.length > 0) {
                    throw error;
                }

                if (length === 0) {
                    ShowAlertMessage('warning', 'Schade!', 'Leider sind unsere Strandabschnitte im gewählten Zeitraum noch nicht buchbar');
                } else {

                    $.ajax({

                        url: directory + "/ajax.php",

                        type: "POST",

                        data: {
                            method: 'GetBookableLocations',
                            dateFrom: globalStartDateFormatted,
                            dateTo: globalEndDateFormatted,
                        },

                        dataType: "json",

                        async: false,

                    }).done(function () {

                        console.log('Calling GetBookableLocations with success');

                    }).fail(function (error) {

                        ShowErrorMessage("Fehler", error);

                    }).then(function (response) {

                        console.log(response.data.noSeason);

                        let error = response.error;
                        let noSeason = response.data.noSeason ?? false;
                        let locationsSeasonBookable = response.data.data;

                        if (error !== "" || error.length !== 0) {
                            throw error;
                        }

                        $('#vabs__locationSelectContainerNormal').hide();

                        if(noSeason === 1){

                            ShowAlertMessage('warning', 'Schade!', 'Leider sind unsere Strandabschnitte im gewählten Zeitraum noch nicht buchbar');

                            return;

                        }else{

                            if (locationsSeasonBookable.length > 0) {

                                output += '<select class="p-3 border bg-light locationId">';
                                output += '<option value="0" disabled selected>Auswahl Strandabschnitt</option>';

                                for (let i = 0; i < length; i++) {
                                    output += '<option value="' + data[i].id + '">' + data[i].name + ' (Saison: ' + data[i]["seasonFromFormatted"] + ' - ' + data[i]["seasonToFormatted"] + ')</option>';
                                }

                                output += '</select>';
                                $('.locationSelect').html(output);

                                bookableLocationsArray = locationsSeasonBookable;

                                ShowLocationtMap();

                                //Draw MAP
                                DrawLeafLetMap('vabs__leafLetMap', data, bookableLocationsArray);
                                map.invalidateSize();

                                SetModus('normal');

                                $('#vabs__locationSelectContainerNormal').show();

                            } else {

                                GetBeachHopping(0, 0);

                            }

                        }



                    });

                }

            })

        } catch (error) {

            console.log(error);
            ShowErrorMessage("Fehler", error);

        }

    }

    let HandleLocationChange = function () {

        console.log('HandleLocationChange');

        locationId = $(this).val();
        flexRow.html('');

        HideAlertMessage();
        ShowLoadingOverlay('Lade Korbanordnung und Übersicht. Dies kann einen Moment dauern....');

        $.ajax({

            url: directory + "/ajax.php",

            type: "POST",

            data: {
                method: 'GetBeachChairs',
                locationId: locationId,
            },

            dataType: "json",

            async: true,

            success: function (response1) {

                ShowBeachChairMap();

                let error = response1.error;
                let data = response1.data;

                if (error === "") {

                    allBeachChairs = data;

                    $.ajax({

                        url: directory + "/ajax.php",

                        type: "POST",

                        data: {
                            method: 'GetFreeChairs',
                            dateFrom: globalStartDateFormatted,
                            dateTo: globalEndDateFormatted,
                            locationId: locationId,
                        },

                        async: true,

                        dataType: "json",

                        success: function (response2) {

                            let error = response2.error;
                            let data = response2.data;

                            if (error === "") {

                                freeBeachChairs = data;
                                bookableChairs = [];

                                //Now we run through all beachChairs and checking if they are bookable
                                for (let i = 0; i < allBeachChairs.length; i++) {

                                    //As we already choosed a location we can display the name of the location in the leaf let map
                                    // this needs to be done only once
                                    if(i == 0) {
                                        vabs__flexHeadline.html(allBeachChairs[i]['beachChairLocationName']);
                                    }

                                    //if the chair is active and online we can iterate through the free beach chairs otherwise we continue the loop
                                    if (allBeachChairs[i].active === 1 && allBeachChairs[i].online === 1) {

                                        //Now we walk through the free beach chairs and check if the id of current beach chair from the loop is in the list of free beach chairs
                                        for (let j = 0; j < freeBeachChairs.length; j++) {

                                            //If the id of the current beach chair is in the list of free beach chairs we add it to the bookable chairs
                                            if (Number(allBeachChairs[i].id) === Number(freeBeachChairs[j].id)) {
                                                bookableChairs.push(allBeachChairs[i]);
                                                break;
                                            }

                                        }

                                    }

                                }

                                let rows;

                                $.ajax({

                                    url: directory + "/ajax.php",

                                    type: "POST",

                                    data: {
                                        method: 'GetRows',
                                        locationId: locationId
                                    },

                                    dataType: "json",

                                    success: function (response3) {

                                        rows = response3.data;

                                        //Assign all chairs to the consolidated array and filter out later on
                                        let consolidatedChairs = allBeachChairs;

                                        //Create an empty template for the currentBeachChair
                                        let currentBeachChair = Object.create(chairTemplate);
                                        //Now we run through all beachChairs and checking if they are bookable
                                        for (let k = 0; k < bookableChairs.length; k++) {

                                            //Get the current chair from the bookable chair loop and assign it to the current chair
                                            currentBeachChair = bookableChairs[k];
                                            //Gets the id of the current chair
                                            let id = Number(currentBeachChair.id);
                                            //Gets the index of the current chair in the consolidated chairs array
                                            let index = consolidatedChairs.findIndex(x => x.id === id);
                                            //Mark the consolidated chair as bookable
                                            consolidatedChairs[index]['bookable'] = 1;

                                        }

                                        let directionClass;
                                        let bookableClass;
                                        let isBookable;
                                        let isOnline;
                                        let isActive;
                                        let isBlocked;
                                        let chairId;
                                        let dataId;
                                        let indexShoppingCart = -1;
                                        let icon = '';
                                        let bookableValue = 0;
                                        let orderId = 0; //1 => left 2 = right
                                        mapOutput = '';

                                        //Run through all rows
                                        for (let r = 0; r < rows.length; r++) {

                                            //Get the beach chair starting position (L2R or R2L) and assign it to an css class
                                            directionClass = directions[rows[r].direction];
                                            //Get the order id (1 = count from left, 2 = count from right)
                                            orderId = rows[r].orderId;
                                            mapOutput += '<div class="flexRow ' + directionClass + ' flexRowDrawed">';

                                            //Based on the beach row order id we sort the chairs particular for that the row
                                            if (orderId == 1) {
                                                consolidatedChairs.sort((a, b) => parseFloat(a.pos) - parseFloat(b.pos));
                                            } else {
                                                consolidatedChairs.sort((a, b) => parseFloat(b.pos) - parseFloat(a.pos));
                                            }

                                            //Now we run through all beachChairs and assign their position in the row
                                            for (let c = 0; c < consolidatedChairs.length; c++) {

                                                if (Number(consolidatedChairs[c]['beachRowId']) === Number(rows[r]['id'])) {

                                                    currentBeachChair = consolidatedChairs[c];

                                                    isActive = currentBeachChair.active;
                                                    isOnline = currentBeachChair.online;
                                                    isBlocked = IsBlocked(currentBeachChair.beachChairBlockedDates, globalStartDate, globalEndDate, false);

                                                    isBookable = currentBeachChair.bookable;
                                                    bookableClass = Number(isBookable) === 1 ? '' : ' booked';
                                                    bookableClass = Number(isBlocked) === 1 ? ' blocked' : bookableClass;
                                                    bookableClass = Number(isOnline) === 1 ? bookableClass : ' offline';
                                                    bookableClass = Number(isActive) === 1 ? bookableClass : ' inactive';
                                                    bookableValue = isBookable ? 1 : 0; //as bookable value in the result could be undefined!

                                                    chairId = currentBeachChair.id;
                                                    dataId = Number(isBookable) === 1 ? currentBeachChair.id : '';
                                                    indexShoppingCart = IsInShoppingCart(chairId);
                                                    icon = indexShoppingCart !== -1 ? '<i></i>' : '';

                                                    mapOutput +=
                                                        '<div ' +
                                                        'id="beachChair_' + currentBeachChair.id + '" ' +
                                                        'title="' + bookableClass + '" ' +
                                                        'class="flexChair' + bookableClass + '" ' +
                                                        'data-id="' + currentBeachChair.id + '" ' +
                                                        'data-name="' + currentBeachChair.name + '" ' +
                                                        'data-pos="' + currentBeachChair.pos + '" ' +
                                                        'data-beachChairTypeId="' + currentBeachChair.beachChairTypeId + '" ' +
                                                        'data-beachChairTypeName="' + currentBeachChair.beachChairTypeName + '" ' +
                                                        'data-beachChairLocationName="' + currentBeachChair.beachChairLocationName + '" ' +
                                                        'data-beachRowId="' + currentBeachChair.beachRowId + '" ' +
                                                        'data-beachRowName="' + currentBeachChair.beachRowName + '" ' +
                                                        'data-dateFrom="' + globalStartDate.yyyymmdd() + '" ' +
                                                        'data-dateTo="' + globalEndDate.yyyymmdd() + '" ' +
                                                        'data-dateFromFormatted="' + globalStartDateFormatted + '" ' +
                                                        'data-dateToFormatted="' + globalEndDateFormatted + '" ' +
                                                        'data-unitPrice="' + currentBeachChair.unitPrice + '" ' +
                                                        'data-isActive="' + isActive + '" ' +
                                                        'data-isOnline="' + isOnline + '" ' +
                                                        'data-bookable="' + bookableValue + '">' +
                                                        icon +
                                                        '<span class="flexChairNumber">' + currentBeachChair.name + '</span>' +
                                                        '<span class="flexChairType">' + currentBeachChair.beachChairTypeName + '</span>' +
                                                        '<span class="flexChairRowName">' + currentBeachChair.beachRowName + '</span>' +
                                                        '</div>\n';
                                                }

                                            }

                                            mapOutput += '</div>\n';

                                        }

                                        vabs__flexRows.html(mapOutput);
                                        //Remove empty lines

                                        $('.flexRowDrawed').filter(function () {
                                            return $.trim($(this).text()) === '';
                                        }).remove();

                                        HideLoadingOverlay();

                                    },
                                    error: function (error) {
                                        ShowErrorMessage('Fehler', error);
                                        HideLoadingOverlay();
                                    }

                                });


                            } else {
                                ShowErrorMessage('Fehler', error);
                                HideLoadingOverlay();
                            }

                        },
                        error: function (error) {
                            ShowErrorMessage('Fehler', error);
                            HideLoadingOverlay();
                        }

                    });

                } else {
                    ShowErrorMessage('Fehler', error);
                    HideLoadingOverlay();
                }

            },
            error: function (error) {
                ShowErrorMessage('Fehler', error);
                HideLoadingOverlay();
            }

        });

    }

    let HandleMapChairClick = function () {

        HideErrorMessage();

        let id = $(this).attr('data-id');
        let name = $(this).attr('data-name');
        let beachChairTypeId = $(this).attr('data-beachChairTypeId');
        let beachChairTypeName = $(this).attr('data-beachChairTypeName');
        let locationId = $(this).attr('data-locationId');
        let beachChairLocationName = $(this).attr('data-beachChairLocationName');
        let beachRowName = $(this).attr('data-beachRowName');
        let rowDirection = $(this).attr('data-rowDirection');
        let rowDirectionName = $(this).attr('data-rowDirectionName');
        let dateFrom = $(this).attr('data-dateFrom');
        let dateFromFormatted = $(this).attr('data-dateFromFormatted');
        let dateTo = $(this).attr('data-dateTo');
        let dateToFormatted = $(this).attr('data-dateToFormatted');
        let unitPrice = $(this).attr('data-unitPrice');
        let bookable = $(this).attr('data-bookable');

        if(id != null && Number(id) !== 0 && id != '' && typeof id !== "undefined"){

            //let index = IsInShoppingCart(id);

            if (bookable == 1) {

                ShowBeachChairPopupCard(id, name, beachChairTypeId, beachChairTypeName, locationId, beachChairLocationName, beachRowName, rowDirection, rowDirectionName, dateFrom, dateFromFormatted, dateTo, dateToFormatted, unitPrice, bookable);

            } else {
                currentBeachChair.id = id; //For Deletion
                currentBeachChair.bookable = 0;
                //Remove Chair form Shopping Card, in case someone else has booked in the meanwhile
                TriggerAddOrRemoveToOrFromShoppingCart();
                //Show Error message
                ShowErrorMessage("Fehler", "Dieser Korb kann nicht gebucht werden.");
            }

        }else{
            ShowErrorMessage("Fehler", "Dieser Korb kann nicht gebucht werden.");
        }


    }

    let ShowBeachChairMap = function () {
        $('#vabs__flexMap').show();
        $('#vabs__leafLetMap').hide();
    }

    let ShowLocationtMap = function () {
        vabs__flexRows.html('');
        vabs__flexHeadline.html('');
        $('#vabs__leafLetMap').show();
        $('#vabs__flexMap').hide();
    }

    let ShowBeachChairPopupCard = function (id, name, beachChairTypeId, beachChairTypeName, locationId, beachChairLocationName, beachRowName, rowDirection, rowDirectionName, dateFrom, dateFromFormatted, dateTo, dateToFormatted, unitPrice, bookable) {

        LogShoppingCart();

        currentBeachChair = Object.create(chairTemplate);
        currentBeachChair.id = id;
        currentBeachChair.name = name;
        currentBeachChair.beachChairTypeId = beachChairTypeId;
        currentBeachChair.beachChairTypeName = beachChairTypeName;
        currentBeachChair.locationId = locationId;
        currentBeachChair.beachChairLocationName = beachChairLocationName;
        currentBeachChair.beachRowName = beachRowName;
        currentBeachChair.rowDirection = rowDirection;
        currentBeachChair.rowDirectionName = rowDirectionName;
        currentBeachChair.dateFrom = dateFrom;
        currentBeachChair.dateFromFormatted = dateFromFormatted;
        currentBeachChair.dateTo = dateTo;
        currentBeachChair.dateToFormatted = dateToFormatted;
        currentBeachChair.unitPrice = unitPrice;
        currentBeachChair.bookable = bookable;

        vabs__chairCardName.text(name);
        vabs__chairCardType.text(beachChairTypeName);
        let imageUrl = '';
        if (beachChairTypes.length > 0) {
            for (let i = 0; i < beachChairTypes.length; i++) {
                if (beachChairTypes[i]['id'] == beachChairTypeId) {
                    imageUrl = beachChairTypes[i]["picture"] != "" ? beachChairTypeImageBasePath + '/' + beachChairTypes[i]["picture"] : '';
                    break;
                }
            }
        }

        chairCardHeader.css("background-image", "url(" + imageUrl + ")");

        let index = IsInShoppingCart(id);

        //Is already in Shopping Cart
        if (index !== -1) {
            vabs__chairCardBtnRemoveFromShoppingCart.show();
            vabs__chairCardBtnAddToShoppingCart.hide();
        } else {
            vabs__chairCardBtnRemoveFromShoppingCart.hide();
            vabs__chairCardBtnAddToShoppingCart.show();
        }

        chairCard.show();

    }

    let CloseBeachChairPopupCard = function () {

        chairCard.hide();
        vabs__chairCardBtnRemoveFromShoppingCart.hide();
        vabs__chairCardBtnAddToShoppingCart.hide();

    }

    let TriggerBeachHopping = function () {

        EmptyShoppingCart();

        let locationIds = $('#hoppingLocationId').val();
        let beachChairTypeIds = $('#hoppingBeachChairTypeId').val();

        GetBeachHopping(locationIds, beachChairTypeIds);

    }

    let GetBeachHopping = function (locationIds, beachChairTypeIds) {

        console.log('GetBeachHopping');

        HideAlertMessage();

        $.ajax({

            url: directory + "/ajax.php",

            type: "POST",

            data: {
                method: 'GetVacancy',
                dateFrom: globalStartDateFormatted,
                dateTo: globalEndDateFormatted,
                locationIds: locationIds,
                beachChairTypeIds: beachChairTypeIds,
            },

            dataType: "json",

        }).done(function () {

            //console.log('Calling GetVacancy with success');

        }).then(function (response) {

                let error = response.error;
                let chairs = response.data.data; //As we are passing data as dat node but the response contains a data property as well!

                if (error === "") {

                    SetModus('hopping');

                    if (chairs.length === 0) {

                        ShowAlertMessage('danger', 'Oje!', '<b>Es tut uns Leid!</b> Aber wir sind in diesem Zeitraum <b>restlos ausgebucht</b>. Wählen Sie einen anderen Zeitraum oder Abschnitt (wenn noch möglich), um eventuell einer unserer letzen Körbe buchen zu können');

                    } else {

                        ShowAlertMessage('info', 'Noch mal Glück gehabt!', 'Wir haben zwar leider <b>keinen einzelnen Korb</b> mehr für Ihren gewählten Buchungszeitraum. <br>Aber wir können Ihnen (noch) <b>mehrere Körbe</b> für Ihren Zeitraum anbieten, wobei Sie dann allerdings an verschiedenen Tagen eventuell in verschieden Körben sitzen werden. <br>Wir nennen das dann liebevoll: \'<b>Strandkorbhopping</b>\' :). <br>Aber immer noch besser als keinen Korb. <br>Übrigens haben wir Ihnen eine Auswahl bereits in den Warenkorb gelegt.');
                        AddBeachChairHoppingResultsToShoppingCart(chairs);

                    }


                } else {
                    ShowErrorMessage('Fehler', error);
                }

            }
        ).fail(function (error) {

            ShowErrorMessage("Fehler", error);

        });

    }

    let LoadBeachHoppingFilters = function () {

        console.log('LoadBeachHoppingFilters');

        //Unbind Events from Selects to prevent a call stack miximum issue
        UnBindSelects();

        //Clear Error Message
        HideAlertMessage();

        //Locations
        $.ajax({

            url: directory + "/ajax.php",

            type: "POST",

            data: {
                method: 'GetLocations',
            },

            dataType: "json"

        }).done(function () {

            HideLoadingOverlay();

        }).then(function (response) {

            let error = response.error;
            let locationsSeasonBookable = response.data;

            if (error !== "" || error.length !== 0) {
                throw error;
            }

            let output;

            if (locationsSeasonBookable.length > 0) {

                output = '';

                output += '<lable for="hoppingLocationId">Strandabschnitt:</lable>';
                output += '<select class="p-3 border bg-light" id="hoppingLocationId" multiple>';
                output += '<option value="0" disabled>Alle</option>';

                for (let i = 0; i < locationsSeasonBookable.length; i++) {
                    output += '<option value="' + locationsSeasonBookable[i].id + '">' + locationsSeasonBookable[i].name + '</option>';
                }

                output += '</select>';
                $('#hoppingBeachLocationsSelectContainer').html(output);

            }

            if (beachChairTypes.length > 0) {

                output = '';

                output = '<lable for="hoppingBeachChairTypeId">Korb-Typ:</lable>';
                output += '<select class="p-3 border bg-light" id="hoppingBeachChairTypeId" multiple>';
                output += '<option value="0" disabled>Auswahl Strandabschnitt</option>';

                for (let i = 0; i < beachChairTypes.length; i++) {
                    output += '<option value="' + beachChairTypes[i].id + '">' + beachChairTypes[i].name + '</option>';
                }

                output += '</select>';
                $('#hoppingBeachChairTypeSelectContainer').html(output);

                //Bind Events to the created selects
                BindSelects();

            }

        }).fail(function (error) {

            ShowErrorMessage("Fehler", error);

        });


    }

    /*****************
     * SHOPPING CART *
     *****************/

    let TriggerAddOrRemoveToOrFromShoppingCart = function () {

        HandleAndOrRemoveToOrFromShoppingCart();
        CloseBeachChairPopupCard();

    }

    let AddBeachChairHoppingResultsToShoppingCart = function (chairs) {

        //empty shopping cart
        shoppingCart = [];

        for (let i = 0; i < chairs.length; i++) {

            shoppingCart.push(chairs[i]);

        }

        RenderShoppingCart();

    }

    let HandleAndOrRemoveToOrFromShoppingCart = function () {

        let id = currentBeachChair.id;
        let bookable = currentBeachChair.bookable;

        try {

            if (id == 0 || id == '' || bookable == 0) {
                throw "Dieser Korb kann nicht gebucht werden";
            }

            let node = $('#beachChair_' + id);

            let index = IsInShoppingCart(id);
            let isInCart = index !== -1;

            let price = 0;
            let seasons = [];

            if (isInCart) {

                //Remove Mark Icon
                node.find('i').remove();

                //Remove From Shopping Cart
                shoppingCart.splice(index, 1);

                RenderShoppingCart();

            } else {

                //Add Mark Icon
                node.prepend("<i/>");

                //Get Price
                $.ajax({

                    url: directory + "/ajax.php",

                    type: "POST",

                    data: {
                        method: "GetPrice",
                        id: id,
                        dateFrom: globalStartDateFormatted,
                        dateTo: globalEndDateFormatted,

                    },

                    dataType: "json",

                    success: function (response) {

                        let error = response.error;
                        let data = response.data;

                        if (error === "") {

                            price = parseFloat(data["price"]);
                            seasons = typeof data["seasons"] == "undefined" ? [] : data["seasons"];

                            currentBeachChair.unitPrice = price;
                            currentBeachChair.seasonsOutput = seasons;
                            shoppingCart.push(currentBeachChair);
                            RenderShoppingCart();

                        } else {
                            ShowErrorMessage('Fehler', error);
                        }

                    },
                    error: function (error) {
                        ShowErrorMessage('Fehler', error);
                    }

                });

            }

        } catch (e) {
            ShowErrorMessage("Fehler", e.message);
        }

    }

    let RenderShoppingCart = function () {

        let totalAmount = 0;
        let lineHtml = '';
        let output;
        let chair = Object.create(chairTemplate);
        let seasonsOutput;

        for (let i = 0; i < shoppingCart.length; i++) {
            chair = shoppingCart[i];
            console.log(chair);

            lineHtml += '' +
                '<tr>' +
                '   <td><b># ' + chair.name + '</b></td>' +
                '   <td>Typ: <b>' + chair.beachChairTypeName + '</b><br>Abschnitt: <b>' + chair.beachChairLocationName + '</b><br>Reihe: <b>' + chair.beachRowName + '</b></td>' +
                '   <td>' + chair.dateFromFormatted + ' - ' + chair.dateToFormatted + '</td>' +
                '   <td>' + FormatToPrice(chair.unitPrice) + '</td>' +
                '</tr>';

            if(chair.priceCalculation){
                if (chair.priceCalculation.seasons) {
                    for (let j = 0; j < chair.priceCalculation.seasons.length; j++) {
                        seasonsOutput = Object.create(seasonsOutputTemplate);
                        seasonsOutput = chair.priceCalculation.seasons[j];
                        lineHtml += '' +
                            '<tr class="seasonDescription">' +
                            '   <td>&nbsp;</td>' +
                            '   <td colspan="3">' +
                            '       ' + seasonsOutput.seasonName + ': ' + seasonsOutput.amountOfDaysBookedInThatSeason + ' x ' + seasonsOutput.singlePrice + ' &euro;</b>' + ' = <b>' + seasonsOutput.seasonPrice + ' &euro;</b>' +
                            '   </td>' +
                            '</tr>';
                    }
                }
            }

            totalAmount += parseFloat(chair.unitPrice);
        }

        output = '' +
            '<div class="table-responsive">' +
            '<table class="table table-sm table-responsive">' +
            '  <thead class="table-dark">' +
            '    <tr>' +
            /*'      <th scope="col">#</th>' +*/
            '      <th scope="col">Korb-Nr.</th>' +
            '      <th scope="col">Info</th>' +
            '      <th scope="col">Datum</th>' +
            '      <th scope="col">Preis</th>' +
            '    </tr>' +
            '  </thead>' +
            '  <tbody>' +
            lineHtml +

            '  </tbody>' +
            '  <tfoot>' +
            '   <tr class="table-dark">' +
            '       <td colspan="2"></td>' +
            '       <th scope="row">Total:</th>' +
            '       <th>' + FormatToPrice(totalAmount) + '</th>' +
            '   </tr>' +
            '  </tfoot>' +
            '</table>';
        '</div>';

        let shoppingCartLength = shoppingCart.length;

        vabs__shoppingCartList.html(output);

        if (shoppingCartLength > 0) {
            $('#vabs__personalDataContainer').show();
            $('#vabs__shoppingCartContainerWrapper').show();
        } else {
            $('#vabs__personalDataContainer').hide();
            $('#vabs__shoppingCartContainerWrapper').hide();

        }


    }

    let ValidateAndSendOrder = function () {

        HideErrorMessage();

        let button = $("#vabs__btnOrderNow");
        button.hide();

        ShowLoadingOverlay('Buchungsüberprüfung. Bitte haben Sie einen Moment Geduld. Dies kann einen Moment dauern.');

        let formData = $('#form').serializeArray();
        let data = {};
        $(formData).each(function (index, obj) {
            data[obj.name] = obj.value;
        });

        $.ajax({

            url: directory + "/ajax.php",

            type: "POST",

            data: {
                method: 'ValidateAndSendOrder',
                formData: JSON.stringify(data),
                dateFrom: globalStartDateFormatted,
                dateTo: globalEndDateFormatted,
                shoppingCart: JSON.stringify(shoppingCart)

            },

            dataType: "json",

            success: function (response) {

                let error = response.error;
                let redirectLink = response.redirectLink;
                let confirmationUrl = response["confirmationUrl"];

                if (error === "") {

                    //Pay per PayPal
                    if (confirmationUrl) {
                        window.location.replace(confirmationUrl);
                    //Pay per Invoice
                    } else if (redirectLink != '') {
                        window.open(redirectLink, '_self');
                    //Hide Form
                    } else {
                        vabs__bookingContainer.remove();
                        successMessage.show();
                    }

                } else {
                    ShowErrorMessage('Fehler', error);
                    button.show();
                    HideLoadingOverlay();
                }

            },
            error: function (error) {
                ShowErrorMessage('Fehler', error);
                button.show();
            }

        });

        button.show();

    }

    /****************
     * LEAF LET MAP *
     ****************/

    let markers = [];

    let DrawLeafLetMap = function (targetNodeId, data, bookableLocationArray) {

        if (map != null) {
            map.remove();
        }

        map = L.map(targetNodeId, {
            //center: [latCenter, lonCenter],
            //zoom: zoomLevel,
            //dragging: false,
            scrollWheelZoom: false,
            bounds: null,
        });

        L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=' + MAPBOX_API_TOKEN, {
            //maxZoom: maxZoomLevel,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            id: 'mapbox/streets-v11',
            tileSize: 512,
            zoomOffset: -1
        }).addTo(map);

        Object.entries(data).forEach(entry => {
            const [key, value] = entry;
            //clone object to template to use dot notation
            beachLocation = value;
            AddMarker(beachLocation.id, beachLocation.name, beachLocation.latitude, beachLocation.longitude, beachLocation.seasonFromFormatted, beachLocation.seasonToFormatted, bookableLocationArray);
        });


        setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
        }, 100);

        map.fitBounds(markers);

    }

    let AddMarker = function (id, title, lat, lng, seasonFromFormatted, seasonToFormatted, bookableLineArray) {

        //cut the year from the date
        seasonFromFormatted = seasonFromFormatted.substring(0, 6);
        seasonToFormatted = seasonToFormatted.substring(0, 6);

        let textBookable = '<p><b>' + title + '</b><br>Saison: ' + seasonFromFormatted + '-' + seasonToFormatted + '</p>';
        let textNotBookable = '<p><b>' + title + '</b><br>Saison: ' + seasonFromFormatted + '-' + seasonToFormatted + ' <br><span style="color: red">(In Ihrem Zeitraum nicht buchbar oder ausgebucht)</span></p>';

        //TODO: Check if we can get all locations but show them as bookable or not
        let bookable = bookableLineArray.includes(id);

        let mearker = L.marker([lat, lng], {
            icon: bookable ? greenIcon : redIcon
        })
            .addTo(map)
            .bindPopup(bookable ? textBookable : textNotBookable)
            .on('click', function () {
                if (bookable) {
                    $('.locationId').val(id).change();
                } else {
                    ShowErrorMessage("Hinweis", "Dieser Strandabschnitt ist nicht buchbar. Bitte wählen Sie einen anderen!");
                }
            })
            .on('mouseover', function () {
                mearker.openPopup();
            })
            .on('mouseout', function () {
                mearker.closePopup();
            });

        markers.push([lat,lng]);

    }

    let greenIcon = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    let redIcon = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    /******************
     * HELPER METHODS *
     ******************/

    let ShowLoadingOverlay = function (message = '') {


        var customElement = $("<div>", {
            "css": {
                //"border": "4px dashed gold",
                "font-size": "40px",
                "text-align": "center",
                "padding": "0px"
            },
            "class": "your-custom-class",
            "text": message
        });

        $.LoadingOverlay("show", {
            direction: 'column',
            background: "rgba(200, 200, 200, 0.7)",
            custom: customElement,

        });
    }

    let HideLoadingOverlay = function (target = '') {

        $.LoadingOverlay("hide");

    }

    let EmptyShoppingCart = function () {
        shoppingCart = [];
        vabs__shoppingCartList.html('');
    }

    let SetModus = function (mode) {

        EmptyShoppingCart();

        if (currentMode != mode) {

            currentMode = mode;

            if (mode === 'normal') {
                $('.normal').show();
                $('.hopping').hide();
            } else {

                $('.normal').hide();
                $('.hopping').show();

                LoadBeachHoppingFilters();

            }

        }


    }

    let FormatToPrice = function (number) {
        return new Intl.NumberFormat('de-DE', {style: 'currency', currency: 'EUR'}).format(number);
    }

    let HideAlertMessage = function () {

        console.log('HideAlertMessage');
        errorMessage.html('');

    }

    let ShowAlertMessage = function (className, title, message) {

        let output = '' +
            '<div class="alert alert-' + className + '">' +
            '<strong>' + title + '</strong> ' + message +
            '</div>';

        errorMessage.html(output);

    }

    let HideErrorMessage = function () {
        console.log('HideErrorMessage');
        errorMessage.html('');
        errorMessage.hide();
    }

    let IsInShoppingCart = function (id) {

        if (shoppingCart.length === 0) {
            return -1;
        }

        for (let i = 0; i < shoppingCart.length; i++) {

            if (shoppingCart[i].id == id) {
                return i;
            }
        }

        return -1;
    }

    let BindSelects = function () {
        node.on('change', '#hoppingLocationId', TriggerBeachHopping);
        node.on('change', '#hoppingBeachChairTypeId', TriggerBeachHopping);
        $('#hoppingLocationId, #hoppingBeachChairTypeId').select2({width: '100%'});
    }

    let UnBindSelects = function () {
        node.off('change', '#hoppingLocationId', TriggerBeachHopping);
        node.off('change', '#hoppingBeachChairTypeId', TriggerBeachHopping);
    }

    let LogShoppingCart = function () {
        console.log(shoppingCart);
    }

    let IsBlocked = function (beachChairBlockedDates, globalStartDate, globalEndDate, log = false){

        let blocked = 0;

        if(beachChairBlockedDates.length !== 0){
            let blockedDateRanges = beachChairBlockedDates.split(',');
            //check if blocked date is in range
            for (let i = 0; i < blockedDateRanges.length; i++) {

                let blockedDateRange = blockedDateRanges[i].split('#');
                //console.log(blockedDateRange);
                let blockedStartDate = blockedDateRange[0];
                let blockedEndDate = blockedDateRange[1];
                LogToConsole({globalStartDate}, log);
                LogToConsole({globalEndDate}, log);
                LogToConsole({blockedStartDate}, log);
                LogToConsole({blockedEndDate}, log);

                //Create Date objects to be able to compare them
                let blockedStartDateObject = new Date(blockedStartDate);
                let blockedEndDateObject = new Date(blockedEndDate);
                let globalStartDateObject = new Date(globalStartDate);
                let globalEndDateObject = new Date(globalEndDate);

                LogToConsole({blockedStartDateObject}, log);
                LogToConsole({blockedEndDateObject}, log);
                LogToConsole({globalStartDateObject}, log);
                LogToConsole({globalEndDateObject}, log);
                LogToConsole('-----------------', log);

                if (
                    //check if global start date is in the range of the blocked start date and blocked end date
                    (blockedStartDateObject <= globalStartDateObject && globalStartDateObject <= blockedEndDateObject)
                    ||
                    //check if global end date is in the range of the blocked start date and blocked end date
                    (blockedStartDateObject <= globalEndDateObject && globalEndDateObject <= blockedEndDateObject)
                    ||
                    //check if blocked start date in the range of the global start date and global end date
                    (globalStartDateObject <= blockedStartDateObject && blockedStartDateObject <= globalEndDateObject)
                    ||
                    //check if blocked end date in the range of the global start date and global end date
                    (globalStartDateObject <= blockedEndDateObject && blockedEndDateObject <= globalEndDateObject)
                ) {
                    LogToConsole('blocked', log);
                    LogToConsole('-----------------', log);
                    blocked = 1;
                }else{
                    LogToConsole('not blocked', log);
                    LogToConsole('-----------------', log);
                }

            }


            LogToConsole('not blocked', log);
            LogToConsole('-----------------', log);
        }

        return blocked;

    }

    let LogToConsole = function (message, log) {
        if(log){
            console.log({message});
        }
    }

    function ShowErrorMessage(title, message, delay = 5) {

        $('#vabs__backendErrorMessage').removeClass('alert-danger').removeClass('alert-warning').removeClass('alert-success').removeClass('alert-info').html(message);

        if (title == "Fehler") {
            $('#vabs__backendErrorMessage').addClass('alert-danger');
        } else if (title == "Warnung") {
            $('#vabs__backendErrorMessage').addClass('alert-warning');
        } else if (title == "Hinweis") {
            $('#vabs__backendErrorMessage').addClass('alert-info');
        } else if (title == "Erfolg") {
            $('#vabs__backendErrorMessage').addClass('alert-success');
        }

        $('#vabs__backendErrorMessage').show();

    }

    Init();

});
