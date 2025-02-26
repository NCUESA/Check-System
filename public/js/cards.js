$(document).ready(function () {
    $.ajax({
        type: 'POST',
        url: '/cards/all',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')  // CSRF Token
        },
        success: function (response) {
            console.log(response);
            if (response.success) {
                genTable(response.data);
            }

        },
        error: function (error) {
            console.log(error);
        }
    });
});

function genTable(data) {
    $('#cards_table').empty();
    $.each(data, function (index, item) {
        console.log(item);
        const radio_val = item.status == '0' ? 'DOWN' : item.status == '1' ? 'UP' : 'N/A';
        var row = '<tr>' +
            '<td>' + item.person_id + '</td>' +
            '<td>' + item.inner_code + '</td>' +
            '<td>' + radio_val + '</td>' +

            '<td>' + '<button type="button" class="data-move btn btn-outline-secondary">異動</button>' + '</td>' +  // Img Column
            '</tr>';
        // 將生成的行添加到表格的 tbody 中
        $('#cards_table').append(row);
    });
}