$(document).ready(function () {
    getTableData();
    getPeople();

    $('#submit').on('click', submitBtnClicked);
    $('#delete').on('click', submitBtnClicked);
});

function getTableData() {
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
}

function getPeople() {
    $.ajax({
        type: 'POST', 
        url: '/show-user', 
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log(response);
            if (response.success) {
                genPersonSelect(response.data);
            }
        }, 
        error: function(error) {
            console.log(error);
        }
    })
}

function genPersonSelect(data) {
    $.each(data, function(idx, item) {
        console.log(item);
        const row = `<option value=${item.id}>` + item.stu_id + " " + item.name + `</option>`;
        $('#person-select').append(row);
    });
}

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

function submitBtnClicked(event) {
    event.preventDefault();
    const innerCode = $('#inner-code').val();
    const personId = $('#person-select').val();
    const stat = $('input[name="status"]:checked').val();
    if (innerCode == "") {
        alert('請輸入卡片內碼');
        return;
    }
    if (personId == "") {
        alert('請選擇卡片擁有者');
        return;
    }
    $.ajax({
        type: 'POST',
        url: '/cards',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),  // CSRF Token
            inner_code: innerCode, 
            person_id: personId, 
            status: stat
        },
        success: function (response) {
            console.log(response);
            alert(response.data);
            getPeople();
            getTableData();
        },
        error: function (error) {
            console.log(error);
            alert(response.data);
        }
    });
}