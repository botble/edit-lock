$(() => {
    let pingTimeout = 0;
    let checkTimeout = 0;
    let $modal = $('#edit-lock-static-modal');
    let $notification = $('.edit-lock-notification');
    let elInterval = ($notification.data('el-interval') || 30) * 1000;

    if ($('.el-is-currently-editing').length) {
        $modal.modal('show');
        check();
    } else {
        ping();
    }

    function ping() {
        if (pingTimeout) {
            clearTimeout(pingTimeout);
        }
        pingTimeout = setTimeout(() => {
            $.ajax({
                type: 'GET',
                data: {
                    _el_ping: +new Date(),
                },
                dataType: 'json',
                success: (res) => {
                    if (res.error == false) {
                        ping();
                    } else {
                        $notification.html($(res.data.notification).html()).show();
                        $modal.find('.modal-body').html(res.data.modal_body);
                        $modal.modal('show');
                        check();
                    }
                },
                error: (res) => {
                    Botble.handleError(res);
                }
            });
        }, elInterval);
        
    }

    function check() {
        if (checkTimeout) {
            clearTimeout(checkTimeout);
        }
        checkTimeout = setTimeout(() => {
            $.ajax({
                type: 'GET',
                data: {
                    _el_check: +new Date(),
                },
                dataType: 'json',
                success: (res) => {
                    if (res.error == false) {
                        $modal.modal('hide');
                        ping();
                        $notification.hide();
                    } else {
                        $modal.find('.modal-body').html(res.data.modal_body);
                        $notification.html($(res.data.notification).html()).show();
                        check();
                    }
                },
                error: (res) => {
                    Botble.handleError(res);
                },
            });
        }, elInterval);
    }

    $(document).on('click', '#edit-lock-static-modal .btn-take-over', function () {
        takeOver();
    });

    function takeOver() {
        $.ajax({
            type: 'GET',
            data: {
                _el_take_over: +new Date(),
            },
            dataType: 'json',
            success: (res) => {
                if (res.error == false) {
                    $notification.hide();
                    $modal.modal('hide');
                    ping();
                    Botble.showSuccess(res.message);
                } else {
                    Botble.showError(res.message);
                }
            },
            error: (res) => {
                Botble.handleError(res);
            },
        });
    }
});
