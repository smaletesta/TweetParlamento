var page = 1;
var busy = 0;
var last = 0;

$(window).scroll(function () {

    if($(window).scrollTop() + $(window).height() >= $(document).height() - 50) {
        if(!busy && !last) {
            busy = 1;
            $('#loading').show();
            page++;
            var data = { page: page };

            $.ajax({
                type: "POST",
                url: url,
                data: data,
                success: function(res) {
                        $('#loading').hide();
                        $('#risultati').append($(res));
                        busy = 0;
                        if($("#end").length > 0)
                            last = 1;
                }
            });
        }
    }
});

$(document).ready(function() {
    $('#loading').hide();
    if($("#end").length > 0)
        last = 1;
});
