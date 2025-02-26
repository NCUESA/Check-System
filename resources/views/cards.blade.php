@extends('layouts.template')

@section('content')
<h2>卡片管理</h2>

<form id='add-card'>
    <h6><strong>請注意，此輸入並不防呆，送出前請先再三確認!!!</strong></h6>
    <div class="row g-3 align-items-center">
        <div class="col-2" hidden>
            <input type="input" id="add_id" class="form-control" placeholder="id">
        </div>
        <div class="col-1">
            <label for="add-ip" class="col-form-label">卡號內碼</label>
        </div>
        <div class="col-2">
            <input type="input" id="add-ip" class="form-control" placeholder="輸入卡號內碼" required>
        </div>
        <div class="col-1">
            <label for="card-owner" class="col-form-label">卡片擁有者學號</label>
        </div>
        <div class="col-2">
            <input type="input" id="card-owner" class="form-control" maxlength="8" placeholder="輸入卡片擁有者學號" required>
        </div>
        <div class="col-2">
            <label for="status" class="col-form-label">狀態</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" value="u" id="up" required>
                <label class="form-check-label" for="up">
                    UP
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" value="d" id="down" required>
                <label class="form-check-label" for="down">
                    DOWN
                </label>
            </div>
        </div>
        <div class="col-2 d-grid gap-2">
            <button type="submit" class="btn btn-success btn-block" id="add-ip">送出新增(調整)</button>
        </div>
        <div class="col-1 d-grid gap-2">
            <button type="button" class="btn btn-warning btn-block" id="delete-ip">刪除此筆</button>
        </div>
        <div class="col-1 d-grid gap-2">
            <button type="reset" class="btn btn-danger btn-block">取消重填</button>
        </div>
    </div>
</form>
<table class="table">
    <thead>
        <tr>
            <th scope="col">擁有者</th>
            <th scope="col">卡號內碼</th>
            <th scope="col">狀態</th>
            <th scope="col">異動</th>
        </tr>
    </thead>
    <tbody id='cards_table'>
        <tr>

        </tr>
    </tbody>
</table>
@endsection