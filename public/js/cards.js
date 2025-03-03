$(document).ready(function () {
    resetInput();
    getTableData();
    getPeople();

    $('#submit').on('click', submitBtnClicked);
    $('#delete').on('click', deleteBtnClicked);

    $(document).on('click', '.data-move', function () {
        fillIn($(this).attr('id'));
    });
});

function resetInput() {
    $('#id').val('');
    $('#person-select').val('');
    $('#inner-code').val('');
    $('#status').val('1');
}

function getTableData() {
    $.ajax({
        type: 'GET',
        url: '/show-cards',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')  // CSRF Token
        },
        success: function (response) {
            console.log(response);
            if (response.success) {
                resetInput();
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
                resetInput();
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
            '<td>' + item.id + '</td>' +
            '<td>' + item.person.name + '</td>' +
            '<td>' + item.inner_code + '</td>' +
            '<td>' + radio_val + '</td>' +

            '<td>' + `<button type="button" id=data-move-${item.id} class="data-move btn btn-outline-secondary">異動</button>` + '</td>' +  // Img Column
            '<td hidden>' + item.person_id + '</td>' +
            '</tr>';
        // 將生成的行添加到表格的 tbody 中
        $('#cards_table').append(row);
    });
}

function submitBtnClicked(event) {
    event.preventDefault();
    const id = $('#id').val();
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
    if (stat == "") {
        alert('請選擇卡片狀態');
        return;
    }

    if (id == "") {
        $.ajax({
            type: 'POST',
            url: '/add-card',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),  // CSRF Token
                inner_code: innerCode, 
                person_id: personId, 
                status: stat
            },
            success: function (response) {
                console.log(response);
                alert(response.data);
                resetInput();
                getPeople();
                getTableData();
            },
            error: function (error) {
                console.log(error);
                alert(response.data);
            }
        });
    }
    else {
        $.ajax({
            type: 'PUT',
            url: '/update-card',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),  // CSRF Token
                id: id, 
                inner_code: innerCode, 
                person_id: personId, 
                status: stat
            },
            success: function (response) {
                console.log(response);
                alert(response.data);
                resetInput();
                getPeople();
                getTableData();
            },
            error: function (error) {
                console.log(error);
                alert(response.data);
            }
        });
    }
}

function deleteBtnClicked(event) {
    event.preventDefault();
    const id = $('#id').val();
    if (id == "") {
        alert('請選擇要刪除的卡片');
        return;
    }
    $.ajax({
        type: 'DELETE',
        url: '/delete-card',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),  // CSRF Token
            id: id
        },
        success: function (response) {
            console.log(response);
            alert(response.data);
            resetInput();
            getPeople();
            getTableData();
        },
        error: function (error) {
            console.log(error);
            alert(response.data);
        }
    });
}

function fillIn (btnId) {
    // 取得當前按鈕所在的資料列
    console.log(btnId);
    const currentRow = $(`#${btnId}`).closest('tr');

    // 提取每個 td 的內容
    const id = currentRow.find('td').eq(0).text();
    const personId = currentRow.find('td').eq(5).text();
    const innerCode = currentRow.find('td').eq(2).text();
    const statusText = currentRow.find('td').eq(3).text();
    const stat = statusText == 'DOWN' ? '0' : statusText == 'UP' ? '1' : 'X';

    // 將第一個 <td> 的內容填入 input #inputA
    $('#id').val(id);
    $('#person-select').val(personId);
    $('#inner-code').val(innerCode);
    let radioButton = $(`input:radio[value=${stat}]`);
    if (radioButton) {
        radioButton.prop('checked', true);
    }
};