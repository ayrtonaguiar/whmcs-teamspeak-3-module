function test(host, hash) {
    // Fork it
    var request;
    // fire off the request to /form.php
    request = $.ajax({
        url: '../modules/addons/teamspeak/lib/functions.php',
        type: 'get',
        data: {
            host: host
        },
        beforeSend: function () {
            $('.verify-status').show()
        }
    });
    // callback handler that will be called on success
    request.done(function (response, textStatus, jqXHR) {
        var status = response.status;
        var statusClass;
        if (status) {
            statusClass = 'success';
            $('#' + hash + ' span.manager').removeClass('invisible');
        } else {
            statusClass = 'danger';
        }

        $('#' + hash).removeClass('success danger').addClass(statusClass);
    });
    // callback handler that will be called on failure
    request.fail(function (jqXHR, textStatus, errorThrown) {
        // log the error to the console
        console.error(
            "The following error occured: " +
            textStatus, errorThrown
        );
    });
    request.always(function () {
        setTimeout(function () {
            $('.verify-status').hide();
        }, 2000);
    })
}

