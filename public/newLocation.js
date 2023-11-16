var channel=pusher.subscribe(`current-location`)
channel.bind('App\\Events\\PickLocation', function (data) {
    $(`table.pickers tr#user${data.user_id} td:eq(1)`).text(data.latitude)
    $(`table.pickers tr#user${data.user_id} td:eq(2)`).text(data.longitude)

});
