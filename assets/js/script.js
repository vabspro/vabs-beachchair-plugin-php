jQuery(document).ready(function ($) {

    //let debug = true;

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

    let map = null;
    let mapOutput = '';

    let shoppingCart = [];

    let currentMode = '';

    let additionalStartDays = 0;

    //declare nodes

    let dateFrom = $(".dateFrom");
    let vabs__flexHeadline = $('.vabs__flexHeadline');
    let vabs__flexRows = $('.vabs__flexRows');
    let flexRow = $('.flexRow');
    let locationId = $('.locationId');
    let chairCard = $('#vabs__chair-card');

    let successMessage = $('#successMessage');
    let vabs__bookingContainer = $('#vabs__bookingContainer');
    let vabs__chairCardBtnAddToShoppingCart = $('#vabs__chairCardBtnAddToShoppingCart');
    let vabs__chairCardBtnRemoveFromShoppingCart = $('#vabs__chairCardBtnRemoveFromShoppingCart');
    let chairCardHeader = $('.vabs__chair-header');
    let vabs__chairCardType = $('#vabs__chairCardType');
    let vabs__chairCardName = $('#vabs__chairCardName');

    let vabs__shoppingCartList = $('#vabs__shoppingCartList');
    let shoppingCartDateTimeRange = $('#shoppingCartDateTimeRange');

    let node = $('#vabs__bookingContainer');
    let btnRefresh = $('#btnRefresh');

    //Object-Templates
    let directions = {
        1: "L2R",
        2: "R2L",
        3: "Center",
    };
    let orders = {
        1: "left",
        2: "right"
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
        beachChairTypeId: '',
        beachChairTypeName: '',
        locationId: '',
        beachChairLocationName: '',
        beachRowName: '',
        rowDirection: '',
        rowDirectionName: '',
        dateFrom: '',
        dateFromFormatted: '',
        dateTo: '',
        dateToFormatted: '',
        unitPrice: '',
        bookable: '',
    };
    let currentBeachChair = {
        id: 0,
        name: '',
        beachChairTypeId: '',
        beachChairTypeName: '',
        locationId: '',
        beachChairLocationName: '',
        beachRowName: '',
        rowDirection: '',
        rowDirectionName: '',
        dateFrom: '',
        dateFromFormatted: '',
        dateTo: '',
        dateToFormatted: '',
        unitPrice: '',
        bookable: '',
    };

    //Methods

    /********
     * INIT *
     ********/

    let addDays = 0;

    let Init = function () {

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

            let fp = flatpickr('.dateFrom', {
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
                        $('.dateFrom').val(dateStr + " bis " + dateStr);
                        dates = [dates[0], dates[1]];
                    }

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
        //LoadMapSettings();


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

            let error = response.error;
            beachChairTypes = response.data;
            console.log(beachChairTypes);

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
            }else{
                throw 'Das Datum scheint nicht im richtigen Format ??bergeben wurden zu sein';
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

                console.log('Calling GetLocations with success');
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
                    ShowAlertMessage('warning', 'Schade!', 'Leider sind unsere Strandabschnitte im gew??hlten Zeitraum noch nicht buchbar');
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

                        let error = response.error;
                        let locationsSeasonBookable = response.data.data;

                        if (error !== "" || error.length !== 0) {
                            throw error;
                        }

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

                        } else {

                            GetBeachHopping(0, 0);

                        }

                    });

                }

            })

        } catch (error) {
            ShowErrorMessage("Fehler", error);
        }

    }

    let HandleLocationChange = function () {

        locationId = $(this).val();
        flexRow.html('');

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

                                    vabs__flexHeadline.html(allBeachChairs[i]['beachChairLocationName']);

                                    for (let j = 0; j < freeBeachChairs.length; j++) {

                                        if (Number(allBeachChairs[i].id) === Number(freeBeachChairs[j].id)) {
                                            bookableChairs.push(allBeachChairs[i]);
                                            break;
                                        }

                                    }

                                }

                                let selectHtml = '';

                                if (bookableChairs.length > 0) {

                                    selectHtml += '<select id="beachChairSelectDropDown" class="p-3 border bg-light">';

                                    for (let j = 0; j < bookableChairs.length; j++) {

                                        selectHtml += '<option value="' + bookableChairs[j]["id"] + '"> Strandkorb ' + bookableChairs[j]["name"] + ' am ' + bookableChairs[j]["beachChairLocationName"] + ' in Reihe: ' + bookableChairs[j]["beachRowName"] + '</option>)';

                                    }

                                    selectHtml +=
                                        '</select>';

                                } else {
                                    ShowErrorMessage('Fehler', 'Leider gibt es im gew??hlten Strandabschnitt und -Zeitraum keine freien K??rbe mehr');
                                }

                                $('#chairIdSelect').html(selectHtml);

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

                                        let error = response3.error;
                                        rows = response3.data;

                                        let consolidatedChairs = allBeachChairs;

                                        let currentBeachChair = Object.create(chairTemplate);
                                        for (let k = 0; k < bookableChairs.length; k++) {

                                            currentBeachChair = bookableChairs[k];
                                            let id = Number(currentBeachChair.id);
                                            let index = consolidatedChairs.findIndex(x => x.id === id);
                                            consolidatedChairs[index]['bookable'] = 1;

                                        }

                                        let directionClass;
                                        let bookableClass;
                                        let isBookable;
                                        let chairId;
                                        let dataId;
                                        let indexShoppingCart = -1;
                                        let icon = '';
                                        let bookableValue = 0;
                                        let orderId = 0; //1 => left 2 = right
                                        mapOutput = '';

                                        for (let r = 0; r < rows.length; r++) {

                                            directionClass = directions[rows[r].direction];
                                            orderId = rows[r].orderId;
                                            console.log(rows[r]);
                                            mapOutput += '<div class="flexRow ' + directionClass + ' flexRowDrawed">';

                                            if (orderId == 1) {
                                                consolidatedChairs.sort((a, b) => parseFloat(a.name) - parseFloat(b.name));
                                            } else {
                                                consolidatedChairs.sort((a, b) => parseFloat(b.name) - parseFloat(a.name));
                                            }

                                            for (let c = 0; c < consolidatedChairs.length; c++) {

                                                if (Number(consolidatedChairs[c]['beachRow']) === Number(rows[r]['id'])) {

                                                    currentBeachChair = consolidatedChairs[c];

                                                    isBookable = currentBeachChair.bookable;
                                                    bookableClass = Number(isBookable) === 1 ? '' : ' booked';
                                                    bookableValue = isBookable ? 1 : 0; //as bookable value in the result could be undefined!

                                                    chairId = currentBeachChair.id;
                                                    dataId = Number(isBookable) === 1 ? currentBeachChair.id : '';
                                                    indexShoppingCart = IsInShoppingCart(chairId);
                                                    icon = indexShoppingCart !== -1 ? '<i></i>' : '';

                                                    mapOutput +=
                                                        '<div ' +
                                                        'id="beachChair_' + currentBeachChair.id + '" ' +
                                                        'class="flexChair' + bookableClass + '" ' +
                                                        'data-id="' + currentBeachChair.id + '" ' +
                                                        'data-name="' + currentBeachChair.name + '" ' +
                                                        'data-beachChairTypeId="' + currentBeachChair.beachChairTypeId + '" ' +
                                                        'data-beachChairTypeName="' + currentBeachChair.beachChairTypeName + '" ' +
                                                        'data-beachChairLocationName="' + currentBeachChair.beachChairLocationName + '" ' +
                                                        'data-beachRowName="' + currentBeachChair.beachRowName + '" ' +
                                                        'data-dateFrom="' + globalStartDate.yyyymmdd() + '" ' +
                                                        'data-dateTo="' + globalEndDate.yyyymmdd() + '" ' +
                                                        'data-dateFromFormatted="' + globalStartDateFormatted + '" ' +
                                                        'data-dateToFormatted="' + globalEndDateFormatted + '" ' +
                                                        'data-unitPrice="' + currentBeachChair.unitPrice + '" ' +
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

                                    },
                                    error: function (error) {
                                        ShowErrorMessage('Fehler', error);
                                    }

                                });


                            } else {
                                ShowErrorMessage('Fehler', error);
                            }

                        },
                        error: function (error) {
                            ShowErrorMessage('Fehler', error);
                        }

                    });

                } else {
                    ShowErrorMessage('Fehler', error);
                }

            },
            error: function (error) {
                ShowErrorMessage('Fehler', error);
            }

        });

    }

    let HandleMapChairClick = function () {

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


        if (id != null && Number(id) !== 0 && id != '' && typeof id !== "undefined" && bookable == 1) {

            ShowBeachChairPopupCard(id, name, beachChairTypeId, beachChairTypeName, locationId, beachChairLocationName, beachRowName, rowDirection, rowDirectionName, dateFrom, dateFromFormatted, dateTo, dateToFormatted, unitPrice);

        } else {
            ShowErrorMessage("Hinweis", "Dieser Korb kann nicht gebucht werden.");
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

    let ShowBeachChairPopupCard = function (id, name, beachChairTypeId, beachChairTypeName, locationId, beachChairLocationName, beachRowName, rowDirection, rowDirectionName, dateFrom, dateFromFormatted, dateTo, dateToFormatted, unitPrice) {

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

        vabs__chairCardName.text(name);
        vabs__chairCardType.text(beachChairTypeName);
        let imageUrl = '';
        if (beachChairTypes.length > 0) {
            console.log('Length > 0');
            for (let i = 0; i < beachChairTypes.length; i++) {
                console.log('Current beachChairTypes at i = ' + i);
                console.log(beachChairTypes[i]);
                console.log('Looking for id: ' + beachChairTypeId);
                if (beachChairTypes[i]['id'] == beachChairTypeId) {
                    console.log('MATCH!')
                    imageUrl = beachChairTypes[i]["picture"] != "" ? beachChairTypes[i]["pictureWebPath"] : '';
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

        LogShoppingCart();

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

            console.log('Calling GetVacancy with success');

        }).then(function (response) {

                let error = response.error;
                let chairs = response.data.data; //As we are passing data as dat node but the response contains a data property as well!

                if (error === "") {

                    SetModus('hopping');

                    if (chairs.length === 0) {

                        ShowAlertMessage('danger', 'Oje!', '<b>Es tut uns Leid!</b> Aber wir sind in diesem Zeitraum <b>restlos ausgebucht</b>. W??hlen Sie einen anderen Zeitraum oder Abschnitt (wenn noch m??glich), um eventuell einer unserer letzen K??rbe buchen zu k??nnen');

                    } else {

                        ShowAlertMessage('info', 'Noch mal Gl??ck gehabt!', 'Wir haben zwar leider <b>keinen einzelnen Korb</b> mehr f??r Ihren gew??hlten Buchungszeitraum. <br>Aber wir k??nnen Ihnen (noch) <b>mehrere K??rbe</b> f??r Ihren Zeitraum anbieten, wobei Sie dann allerdings an verschiedenen Tagen eventuell in verschieden K??rben sitzen werden. <br>Wir nennen das dann liebevoll: \'<b>Strandkorbhopping</b>\' :). <br>Aber immer noch besser als keinen Korb. <br>??brigens haben wir Ihnen eine Auswahl bereits in den Warenkorb gelegt.');
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

        //Unbind Events from Selects to prevent a call stack miximum issue
        UnBindSelects();

        //Load Hopping Selects

        //Locations
        $.ajax({

            url: directory + "/ajax.php",

            type: "POST",

            data: {
                method: 'GetLocations',
            },

            dataType: "json"

        }).done(function () {

            console.log('Calling GetLocations for Hopping with success');
            HideLoadingOverlay();

        }).then(function (response) {

            let error = response.error;
            let locationsSeasonBookable = response.data;

            console.log("Bookable Hopping Locations");
            console.log(locationsSeasonBookable);

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

        console.log('Handle Id' + id);

        let node = $('#beachChair_' + id);

        let index = IsInShoppingCart(id);
        let isInCart = index !== -1;

        let price = 0;

        if (isInCart) {

            console.log("Remove From Cart");

            //Remove Mark Icon
            node.find('i').remove();

            //Remove From Shopping Cart
            shoppingCart.splice(index, 1);

            RenderShoppingCart();


        } else {

            console.log("Adding to Cart");

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

                        currentBeachChair.unitPrice = price;
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

    }

    let RenderShoppingCart = function () {

        let totalAmount = 0;
        let lineHtml = '';
        let output = '';
        let chair = Object.create(chairTemplate);

        for (let i = 0; i < shoppingCart.length; i++) {
            chair = shoppingCart[i];
            lineHtml += '' +
                '<tr>' +
                /*'   <th scope="row">' + (i + 1) + '</th>' +*/
                '   <td><b>' + chair.name + '</b></td>' +
                '   <td>Typ: <b>' + chair.beachChairTypeName + '</b><br>Abschnitt: <b>' + chair.beachChairLocationName + '</b><br>Reihe: <b>' + chair.beachRowName + '</b></td>' +
                '   <td>' + chair.dateFromFormatted + ' - ' + chair.dateToFormatted + '</td>' +
                '   <td>' + FormatToPrice(chair.unitPrice) + '</td>' +
                '</tr>';

            totalAmount += parseFloat(chair.unitPrice);
        }

        output = '' +
            '<div class="table-responsive">' +
            '<table class="table table-striped table-sm table-responsive">' +
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

        $("#vabs__btnOrderNow").hide();

        let button = $("#vabs__btnOrderNow");
        button.hide();

        ShowLoadingOverlay('Bitte haben Sie einen Moment Geduld. Dies kann einen Moment dauern.');

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

                console.log(response);

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
                        //Show Success Message
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

        //console.log("using Lat: " + latCenter + " Lon:" + lonCenter);

        map = L.map(targetNodeId, {
            //center: [latCenter, lonCenter],
            //zoom: zoomLevel,
            //dragging: false,
            scrollWheelZoom: false,
            bounds: null,
        });

        L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw', {
            //maxZoom: maxZoomLevel,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery ?? <a href="https://www.mapbox.com/">Mapbox</a>',
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

        let textBookable = '<p><b>' + title + '</b><br>Saison: ' + seasonFromFormatted + '-' + seasonToFormatted + '</p>';
        let textNotBookable = '<p><b>' + title + '</b><br>Saison: ' + seasonFromFormatted + '-' + seasonToFormatted + ' <br><span style="color: red">(In Ihrem Zeitraum nicht buchbar)</span></p>';

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
                    ShowErrorMessage("Hinweis", "Dieser Strandabschnitt ist nicht buchbar. Bitte w??hlen Sie einen anderen!");
                }
            })
            .on('mouseover', function () {
                mearker.openPopup();
            });

        markers.push([lat,lng]);

        //console.log(markers);


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
                "padding": "10px",

            },
            //"class": "your-custom-class",
            "text": message
        });

        $.LoadingOverlay("show", {
            //image: "",
            fontawesome: "fa fa-cog fa-spin",
            //text: message,
            background: "rgba(200, 200, 200, 0.7)",
            custom: customElement,
        });
    }

    let HideLoadingOverlay = function (target = '') {

        $.LoadingOverlay("hide");

    }

    let EmptyShoppingCart = function () {
        shoppingCart = [];
        console.log('Clear Shopping Cart');
        vabs__shoppingCartList.html('');
    }

    let SetModus = function (mode) {

        EmptyShoppingCart();

        if (currentMode != mode) {

            currentMode = mode;

            if (mode === 'normal') {
                $('.normal').show();
                $('.hopping').hide();
                console.log("Entering NORMAL mode");
            } else {

                $('.normal').hide();
                $('.hopping').show();
                console.log("Entering HOPPING mode");

                LoadBeachHoppingFilters();

            }

        }


    }

    let FormatToPrice = function (number) {
        return new Intl.NumberFormat('de-DE', {style: 'currency', currency: 'EUR'}).format(number);
    }

    let HideAlertMessage = function () {

        $('#errorMessage').html('');

    }

    let ShowAlertMessage = function (className, title, message) {

        let output = '' +
            '<div class="alert alert-' + className + '">' +
            '<strong>' + title + '</strong> ' + message +
            '</div>';

        $('#errorMessage').html(output);

    }

    let HideErrorMessage = function () {
        $('#errorMessage').hide();
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
