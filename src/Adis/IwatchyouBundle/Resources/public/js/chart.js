var hoverTip;

function drawFollower() {
    $.plot($("#placeholderFollower"), datasetFollower, options);
}

function drawFollowing() {
    $.plot($("#placeholderFollowing"), datasetFollowing, options);
}

function drawEngagement() {
    $.plot($("#placeholderEngagement"), datasetEngagement, optionsEngagement);
}

function getData() {   
    $.ajax({
        url: url,
        type: "POST",
        data: {id: id, dataInizio: dataInizio , dataFine: dataFine},
        dataType:"json",
        async: false,
        success: function(datasets) { 
            datasets[0][0].color = '#4682b4';
            datasets[1][0].color = '#228b22';
            datasetFollower = datasets[0];
            datasetFollowing = datasets[1];
            datasetEngagement = datasets[2];
            dettagliTweet = datasets[3];
            console.log(dettagliTweet);
        } 
    });
}

function tooltipEngagementHTML(contents, data, engagement) {
    return "<div class='tooltip'><div class='tooltip-container'><span><div class='tooltip-img'><img src='" + pic + "' class='img-polaroid'></img></div><strong>" + screen_name + " </strong>" + contents + "</span><span>" + data + "</span><span><strong>Engagement: </strong>"+ engagement +"</span></div></div>";
}

function tooltipHTML(contents) {
    return "<div class='tooltip'><div class='tooltip-container'><span>"+ contents + "</span></div></div>";
}

$("#placeholderFollower").bind("plothover", function (event, pos, item) {
    var ofs = { height: 0, width: 0 }
    clearTooltips();
    if (item) {

        y = item.datapoint[1];

        hoverTip = $(tooltipHTML("<strong>" + item.series.label + ": </strong>"  + y));
        
        $('body').append(hoverTip);

        ofs.height = hoverTip.outerHeight();
        ofs.width = hoverTip.outerWidth();

        if((item.pageX + hoverTip.outerWidth() / 2) > $(document).width())
            hoverTip.offset({ left: $(document).width() - (hoverTip.outerWidth() + 5), top: item.pageY - ofs.height - 15 });
        else if((item.pageX - hoverTip.outerWidth() / 2) < 0)
            hoverTip.offset({ left: 5, top: item.pageY - ofs.height - 15 });
        else
            hoverTip.offset({ left: item.pageX - ofs.width / 2, top: item.pageY - ofs.height - 15 });

    }
});

$("#placeholderFollowing").bind("plothover", function (event, pos, item) {
    var ofs = { height: 0, width: 0 }
    clearTooltips();
    if (item) {

        y = item.datapoint[1];

        hoverTip = $(tooltipHTML("<strong>" + item.series.label + ": </strong>"  + y));
        
        $('body').append(hoverTip);

        ofs.height = hoverTip.outerHeight();
        ofs.width = hoverTip.outerWidth();

        if((item.pageX + hoverTip.outerWidth() / 2) > $(document).width())
            hoverTip.offset({ left: $(document).width() - (hoverTip.outerWidth() + 5), top: item.pageY - ofs.height - 15 });
        else if((item.pageX - hoverTip.outerWidth() / 2) < 0)
            hoverTip.offset({ left: 5, top: item.pageY - ofs.height - 15 });
        else
            hoverTip.offset({ left: item.pageX - ofs.width / 2, top: item.pageY - ofs.height - 15 });

    }
});

function clearTooltips() {
    if(hoverTip)
        hoverTip.remove();
    hoverTip = null;
}

$("#placeholderEngagement").bind("plothover", function (event, pos, item) {
    var ofs = { height: 0, width: 0 }
    clearTooltips();
    if (item) {


        x = item.datapoint[0];
        y = item.datapoint[1].toFixed(4);

        hoverTip = $(tooltipEngagementHTML(dettagliTweet[x]['testo'], dettagliTweet[x]['data'], y));

        $('body').append(hoverTip);

        ofs.width = hoverTip.outerWidth();
        ofs.height = hoverTip.outerHeight();
        
        if((item.pageX + hoverTip.outerWidth() / 2) > $(document).width())
            hoverTip.offset({ left: $(document).width() - (hoverTip.outerWidth() + 5), top: item.pageY - ofs.height - 15 });
        else if((item.pageX - hoverTip.outerWidth() / 2) < 0)
            hoverTip.offset({ left: 5, top: item.pageY - ofs.height - 15 });
        else
            hoverTip.offset({ left: item.pageX - ofs.width / 2, top: item.pageY - ofs.height - 15 });

    }
});

$('#graphTab a[href="#engagement"]').click(function (e) {
  e.preventDefault();
  $(this).tab('show');
});

$('#graphTab a[href="#follower"]').click(function (e) {
  e.preventDefault();
  $(this).tab('show');
});

$('#graphTab a[href="#following"]').click(function (e) {
  e.preventDefault();
  $(this).tab('show');
});

$('#graphTab a[href="#engagement"]').on('shown', function (e) {
    drawEngagement();
});

$('#graphTab a[href="#follower"]').on('shown', function (e) {
    drawFollower();
});

$('#graphTab a[href="#following"]').on('shown', function (e) {
    drawFollowing();
});

$(document).ready(function() {
    $('#daterange').daterangepicker({
        showDropdowns: true,
        separator: ' - ',
        startDate: Date.today().add({ days: -15 }),
        endDate: Date.today(),
        minDate: '07/04/2013',
        maxDate: Date.today(),
        format: 'dd/MM/yyyy',
        locale: {
            applyLabel: 'Invia',
            clearLabel: 'Reset',
            fromLabel: 'Da',
            toLabel: 'A',
            daysOfWeek: ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
            monthNames: ['Gennaio', 'Febraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
            firstDay: 1
        }
    },
    function(start, end) {
        if(start && end) {
            $('#daterange').val(start.toString('dd/MM/yyyy') + ' - ' + end.toString('dd/MM/yyyy'));
            dataInizio = start.toString('MM/dd/yyyy');
            dataFine = end.toString('MM/dd/yyyy');
            getData();
            drawEngagement();
            drawFollower();
            drawFollowing();
        }
    });
});
