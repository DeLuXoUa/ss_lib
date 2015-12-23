function labels_locale(lang) {
    if (!lang || !langs[lang]) {
        lang = 'en';
    }
    for(var key in langs[lang]){
        $(key).text(langs[lang][key]);
    }
}

function events(){
    $('#review_button').on('click', function(){
        var review = {
            points: ($('input[name="points"]:checked').val()),
            comment: ($('textarea').val()),
            case_id: case_id
        };

        $.ajax({
            url: '127.0.0.1:8080/openapi/support/review/rate',
            data: review,
            method: 'PUT',
            error: function(jso){
                $.cookie('case_'+case_id, review.points, { expires: 765 });
                $('#review').hide();
                $('#recived').show();
            },
            success: function(jso){
                $.cookie('case_'+case_id, review.points, { expires: 765 });
                $('#review').hide();
                $('#recived').show();
            }
        });

    });
}

$(document).on('ready', function(){
    case_id = 1;
    labels_locale('ru');

    if($.cookie('case_'+case_id)){
        $('#review').hide();
        $('#recived').show();
    } else {
        $('#review').show();
        $('#recived').hide();
        events();
    }
});
