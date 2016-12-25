$(document).ready(function () {
    $inputSearch = $('#share_code');

    $inputSearch.autoComplete({
        source: function (request, response) {
            var callback = function (res) {
                var suggestions = [];
                $.each(res, function (i, val) {
                    // set property for autocomplete widget
                    val.label = val.name + ' | ' + val.symbol + ' | ' + val.typeDisp + ' - ' + val.exchDisp;
                    val.value = val.symbol;
                    suggestions.push(val);
                });

                response(suggestions);
            };

            $.ajax({
                type: 'GET',
                url: '/share/search-code/' + request,
                dataType: 'json',
                success: function (res) {
                    callback(res);
                }
            });
        },

        renderItem: function (item, search) {
            console.log(item, search);
            return '<div class="autocomplete-suggestion" data-val="' + item.value + '">' + item.label.replace(search, "<b>$1</b>") + '</div>';
        },
        onSelect: function (e, term, item) {
            console.log(e, term, item);
        },

        select: function (event, ui) {
            console.log(event, ui);
        },

        minLength: 2
    });
});