@extends('layouts.template')

@section('content')
<form id='edit_list'>
    <h6><strong>請注意，此輸入並不防呆，送出前請先再三確認!!!</strong></h6>
    <div class="row g-3 align-items-center">
        <div class="col-1">
            <label for="id" class="col-form-label">ID(系統自帶)</label>
        </div>
        <div class="col-1">
            <input type="input" id="id" class="form-control" placeholder="ID" required disabled>
        </div>
        <div class="col-1">
            <label for="add_person" class="col-form-label">人員名稱(系統自帶)</label>
        </div>
        <div class="col-3">
            <input type="input" id="add_person" class="form-control" placeholder="" required>
        </div>
        <div class="col-1">
            <label for="inner_code" class="col-form-label">學號(系統自帶)</label>
        </div>
        <div class="col-3">
            <input type="input" id="stu_id" class="form-control" placeholder="" required>
        </div>
        <div class="col-2 d-grid gap-2">
            <button type="submit" class="btn btn-success btn-block"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hand-index-thumb" viewBox="0 0 16 16">
                <path d="M6.75 1a.75.75 0 0 1 .75.75V8a.5.5 0 0 0 1 0V5.467l.086-.004c.317-.012.637-.008.816.027.134.027.294.096.448.182.077.042.15.147.15.314V8a.5.5 0 0 0 1 0V6.435l.106-.01c.316-.024.584-.01.708.04.118.046.3.207.486.43.081.096.15.19.2.259V8.5a.5.5 0 1 0 1 0v-1h.342a1 1 0 0 1 .995 1.1l-.271 2.715a2.5 2.5 0 0 1-.317.991l-1.395 2.442a.5.5 0 0 1-.434.252H6.118a.5.5 0 0 1-.447-.276l-1.232-2.465-2.512-4.185a.517.517 0 0 1 .809-.631l2.41 2.41A.5.5 0 0 0 6 9.5V1.75A.75.75 0 0 1 6.75 1M8.5 4.466V1.75a1.75 1.75 0 1 0-3.5 0v6.543L3.443 6.736A1.517 1.517 0 0 0 1.07 8.588l2.491 4.153 1.215 2.43A1.5 1.5 0 0 0 6.118 16h6.302a1.5 1.5 0 0 0 1.302-.756l1.395-2.441a3.5 3.5 0 0 0 .444-1.389l.271-2.715a2 2 0 0 0-1.99-2.199h-.581a5 5 0 0 0-.195-.248c-.191-.229-.51-.568-.88-.716-.364-.146-.846-.132-1.158-.108l-.132.012a1.26 1.26 0 0 0-.56-.642 2.6 2.6 0 0 0-.738-.288c-.31-.062-.739-.058-1.05-.046zm2.094 2.025"/>
              </svg>手動簽到退</button>
        </div>

        <div class="col-1">
            <label for="checkin_time" class="col-form-label">簽到時間</label>
        </div>
        <div class="col-4">
            <input type="datetime-local" id="checkin_time" class="form-control" placeholder="" required step="60">
        </div>
        <div class="col-1">
            <label for="checkout_time" class="col-form-label">簽退時間</label>
        </div>
        <div class="col-4">
            <input type="datetime-local" id="checkout_time" class="form-control" placeholder="" required step="60">
        </div>


        <div class="col-2 d-grid gap-2">
            <button type="reset" class="btn btn-danger btn-block"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
              </svg>取消重填</button>
        </div>
    </div>
</form>
<hr>
<form>
    <h6><strong>查詢列表 (留空表示不查詢)</strong></h6>
    <div class="row g-3 align-items-center">

        <div class="col-1">
            <label for="search_name" class="col-form-label">人名</label>
        </div>
        <div class="col-2">
            <input type="input" id="search_name" class="form-control" placeholder="">
        </div>

        <div class="col-1">
            <label for="search_stuid" class="col-form-label">學號</label>
        </div>
        <div class="col-2">
            <input type="input" id="search_stuid" class="form-control" placeholder="">
        </div>
        <div class="col-1">
            <label for="search_year" class="col-form-label">設定年分</label>
        </div>
        <div class="col-2">
            <select class="form-select" id="search_year">
                <option selected value="">請選擇...</option>
                <option value="2024">2024</option>
                <option value="2025">2025</option>
                <option value="2026">2026</option>
                <option value="2027">2027</option>
                <option value="2028">2028</option>
                <option value="2029">2029</option>
                <option value="2030">2030</option>
                <option value="2031">2031</option>
                <option value="2032">2032</option>
            </select>
        </div>
        <div class="col-1">
            <label for="search_month" class="col-form-label">查詢整月</label>
        </div>
        <div class="col-2">
            <select class="form-select" id="search_month">
                <option selected value="">請選擇...</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
            </select>
        </div>



        <div class="col-1">
            <label for="search_in_place" class="col-form-label">簽到地點</label>
        </div>
        <div class="col-2">
            <select class="form-select" id="search_in_place">
                <option selected value="">請選擇...</option>
                <option value="jinde">進德</option>
                <option value="baosan">寶山</option>
                <option value="other">其他</option>
            </select>
        </div>

        <div class="col-1">
            <label for="search_out_place" class="col-form-label">簽退地點</label>
        </div>
        <div class="col-2">
            <select class="form-select" id="search_out_place">
                <option selected value="">請選擇...</option>
                <option value="jinde">進德</option>
                <option value="baosan">寶山</option>
                <option value="other">其他</option>
            </select>
        </div>

        <div class="col-3 d-grid gap-2">
            <button type="button" id="gen_month_paper" class="btn btn-warning btn-block"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-date" viewBox="0 0 16 16">
                <path d="M6.445 11.688V6.354h-.633A13 13 0 0 0 4.5 7.16v.695c.375-.257.969-.62 1.258-.777h.012v4.61zm1.188-1.305c.047.64.594 1.406 1.703 1.406 1.258 0 2-1.066 2-2.871 0-1.934-.781-2.668-1.953-2.668-.926 0-1.797.672-1.797 1.809 0 1.16.824 1.77 1.676 1.77.746 0 1.23-.376 1.383-.79h.027c-.004 1.316-.461 2.164-1.305 2.164-.664 0-1.008-.45-1.05-.82zm2.953-2.317c0 .696-.559 1.18-1.184 1.18-.601 0-1.144-.383-1.144-1.2 0-.823.582-1.21 1.168-1.21.633 0 1.16.398 1.16 1.23"/>
                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
              </svg>產生月報表</button>
        </div>
        <div class="col-3 d-grid gap-2">
            <button type="button" id="search" class="btn btn-primary btn-block"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
              </svg>查詢</button>
        </div>

    </div>
</form>

<hr>
<table class="table">
    <thead>
        <tr>
            <th scope="col">學號</th>
            <th scope="col">姓名</th>
            <th scope="col">簽到時間</th>
            <th scope="col">簽到地點</th>
            <th scope="col">簽退時間</th>
            <th scope="col">簽退地點</th>
        </tr>
    </thead>
    <tbody id="all_list">

    </tbody>
</table>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

@endsection